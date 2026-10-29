<?php declare(strict_types=1);

namespace Nova\Rechtstexte\Subscriber;

use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Cms\SalesChannel\Struct\TextStruct;
use Nova\Rechtstexte\Service\BlockResolver;
use Nova\Rechtstexte\Service\ShippingTableRenderer;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Platzhalter in Erlebniswelt-Texten je Verkaufskanal ersetzen, z. B. [[Shopname]] oder [[E-Mail]].
 *
 * Damit lassen sich Rechtstexte (Impressum, Datenschutz, AGB …) EINMAL als gemeinsames Layout pflegen
 * und in allen Shops zuweisen. Läuft serverseitig nach dem Laden der Seite – unabhängig vom Theme des
 * Verkaufskanals, also auch in Modalen (AGB im Checkout) und auf Shops mit anderem Theme.
 */
class LegalPlaceholderSubscriber implements EventSubscriberInterface
{
    private const CONFIG = 'NovaRechtstexte.config.';

    /** @var list<string> PCRE-Fehler der aktuellen Verarbeitung (Schritt + Meldung) */
    private static array $regexErrors = [];

    private ?LoggerInterface $logger = null;

    public function __construct(
        private readonly SystemConfigService $config,
        private readonly BlockResolver $blocks,
        private readonly ShippingTableRenderer $shipping,
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [CmsPageLoadedEvent::class => 'onCmsPageLoaded'];
    }

    public function onCmsPageLoaded(CmsPageLoadedEvent $event): void
    {
        if ($this->config->getBool(self::CONFIG . 'disabled', $event->getSalesChannelContext()->getSalesChannelId())) {
            return;
        }

        $map = null;
        $tags = null;

        foreach ($event->getResult() as $page) {
            if (!$page instanceof CmsPageEntity || $page->getSections() === null) {
                continue;
            }
            foreach ($page->getSections() as $section) {
                foreach ($section->getBlocks() ?? [] as $block) {
                    foreach ($block->getSlots() ?? [] as $slot) {
                        $data = $slot->getData();
                        if (!$data instanceof TextStruct) {
                            continue;
                        }
                        $content = $data->getContent();
                        if ($content === null || !str_contains($content, '[[')) {
                            continue;
                        }
                        self::takeRegexErrors();
                        $map ??= $this->buildMap($event);
                        if ($tags === null) {
                            // Bausteine plus alle gefüllten Platzhalter: [[wenn Telefax]] … greift, sobald ein Fax eingetragen ist
                            $tags = $this->blocks->resolve($event->getSalesChannelContext());
                            foreach ($map as $key => $value) {
                                if (trim(strip_tags((string) $value)) !== '') {
                                    $tags[BlockResolver::key($key)] = true;
                                }
                            }
                        }
                        $content = self::applyConditions(self::expandTemplates($content), $tags);
                        $data->setContent(self::replace(self::number($content), $map));
                        $errors = self::takeRegexErrors();
                        if ($errors !== []) {
                            $this->logger?->error('Nova Rechtstexte: Textbaustein nur teilweise verarbeitet (PCRE-Fehler), betroffener Schritt unverändert gelassen', [
                                'cmsPageId' => $page->getId(),
                                'slotId' => $slot->getId(),
                                'errors' => array_values(array_unique($errors)),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * [[Vorlage AGB]], [[Vorlage Datenschutz]] … durch die mitgelieferte Textvorlage ersetzen
     * (src/Resources/legal/de-DE/<name>.html). Unbekannte Vorlagen bleiben sichtbar stehen.
     */
    public static function expandTemplates(string $content): string
    {
        return self::checked(preg_replace_callback('/\[\[\s*vorlage\s+([^\[\]]{1,40}?)\s*\]\]/iu', static function (array $m): string {
            // Dateiname und Vorlagenname werden normiert verglichen: „Widerruf Waren“ = widerruf-waren.html
            $file = null;
            foreach (glob(\dirname(__DIR__) . '/Resources/legal/de-DE/*.html') ?: [] as $candidate) {
                if (BlockResolver::key(basename($candidate, '.html')) === BlockResolver::key($m[1])) {
                    $file = $candidate;
                    break;
                }
            }
            if ($file === null) {
                return $m[0];
            }

            // Kopfkommentar (Hinweise für Shopbetreiber) nicht ausliefern
            $template = (string) file_get_contents($file);

            return self::checked(preg_replace('/^\s*<!--.*?-->\s*/s', '', $template, 1), $template, 'Vorlage');
        }, $content), $content, 'Vorlagen');
    }

    /**
     * Fortlaufende Nummern nach dem Ausblenden von Abschnitten: [[§]] für Paragraphen, [[Nr]] für Abschnitte –
     * fällt ein Abschnitt weg, entsteht keine Lücke in der Zählung.
     */
    public static function number(string $content): string
    {
        foreach (['§' => '/\[\[\s*§\s*\]\]/u', 'nr' => '/\[\[\s*nr\.?\s*\]\]/iu'] as $pattern) {
            $i = 0;
            $content = self::checked(preg_replace_callback($pattern, static function () use (&$i): string {
                return (string) ++$i;
            }, $content), $content, 'Nummerierung');
        }

        return $content;
    }

    /**
     * Bedingte Abschnitte: [[wenn Klarna]] … [[sonst]] … [[ende]], auch [[wenn nicht Klarna]] und
     * mehrere Bausteine mit „oder“: [[wenn PayPal oder Klarna]]. Verschachtelung ist erlaubt.
     * Zeitgesteuert: [[bis 02.05.2026]] … [[ende]] (bis einschließlich dieses Tages) und [[ab 01.03.2027]] … [[ende]],
     * jeweils auch mit [[sonst]] – z. B. für Termine, die nach dem Datum von selbst verschwinden.
     * Steht eine Markierung allein in einem Absatz, verschwindet der ganze Absatz mit.
     *
     * @param array<string, true> $tags aktive Bausteine (normierte Namen)
     */
    public static function applyConditions(string $content, array $tags, ?\DateTimeInterface $today = null): string
    {
        $hasConditions = preg_match('/\[\[\s*(?:wenn|bis|ab)\b/iu', $content);
        if ($hasConditions !== 1) {
            if ($hasConditions === false) {
                self::$regexErrors[] = 'Bedingungen: ' . preg_last_error_msg();
            }

            return $content;
        }

        $today = ($today ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Berlin')))->format('Y-m-d');

        // Markierungen, die allein in einem Absatz stehen, aus dem Absatz lösen
        $marker = '\[\[\s*(?:wenn\b[^\[\]]*|(?:bis|ab)\s+[0-9.\-]+|sonst|ende)\s*\]\]';
        $content = self::checked(preg_replace(
            '#<(p|div|h[1-6]|li)\b[^>]*>(?:\s|&nbsp;|<br\s*/?>|</?(?:span|strong|b|em|i)\b[^>]*>)*(' . $marker . ')(?:\s|&nbsp;|<br\s*/?>|</?(?:span|strong|b|em|i)\b[^>]*>)*</\1>#iu',
            '$2',
            $content
        ), $content, 'Absatzmarkierungen');

        // Innerste Bedingung zuerst auflösen (wenn / bis / ab), bis keine mehr übrig ist
        $inner = '((?:(?!\[\[\s*(?:wenn|bis|ab)\b).)*?)\[\[\s*ende\s*\]\]';
        $ifPattern = '/\[\[\s*wenn\s+(nicht\s+)?([^\[\]]+?)\s*\]\]' . $inner . '/isu';
        $datePattern = '/\[\[\s*(bis|ab)\s+(\d{1,2}\.\d{1,2}\.\d{4}|\d{4}-\d{2}-\d{2})\s*\]\]' . $inner . '/isu';

        for ($i = 0; $i < 100 && (preg_match($ifPattern, $content) === 1 || preg_match($datePattern, $content) === 1); ++$i) {
            $before = $content;
            $content = self::checked(preg_replace_callback($ifPattern, static function (array $m) use ($tags): string {
                $match = false;
                foreach (preg_split('/\s+oder\s+/iu', $m[2]) ?: [] as $name) {
                    if (isset($tags[BlockResolver::key($name)])) {
                        $match = true;
                        break;
                    }
                }
                if ($m[1] !== '') {
                    $match = !$match;
                }
                $parts = preg_split('/\[\[\s*sonst\s*\]\]/iu', $m[3], 2) ?: [$m[3]];

                return $match ? $parts[0] : ($parts[1] ?? '');
            }, $content), $content, 'Bedingungen');

            // [[bis 02.05.2026]] … [[ende]]: bis einschließlich dieses Tages sichtbar; [[ab …]]: ab diesem Tag
            $content = self::checked(preg_replace_callback($datePattern, static function (array $m) use ($today): string {
                $date = self::isoDate($m[2]);
                $parts = preg_split('/\[\[\s*sonst\s*\]\]/iu', $m[3], 2) ?: [$m[3]];
                if ($date === null) {
                    // Ungültiges Datum: Inhalt zeigen, damit nichts unbemerkt verschwindet
                    return $parts[0];
                }
                $match = strtolower($m[1]) === 'bis' ? $today <= $date : $today >= $date;

                return $match ? $parts[0] : ($parts[1] ?? '');
            }, $content), $content, 'Zeitsteuerung');

            if ($content === $before) {
                break;
            }
        }
        // preg_match in der Schleifenbedingung scheitert ebenfalls lautlos (false) – Bedingungen bleiben dann sichtbar stehen
        if (preg_last_error() !== \PREG_NO_ERROR) {
            self::$regexErrors[] = 'Bedingungen: ' . preg_last_error_msg();
        }

        return $content;
    }

    /** „2.5.2026“ oder „2026-05-02“ → „2026-05-02“, ungültig → null */
    private static function isoDate(string $value): ?string
    {
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($month, $day, $year) ? \sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    /**
     * Ersetzt bekannte Platzhalter; Groß-/Kleinschreibung und Leerzeichen innen sind egal.
     * Unbekannte Platzhalter bleiben stehen, damit Tippfehler auffallen.
     *
     * @param array<string, string> $map Schlüssel klein geschrieben, ohne Leerzeichen
     */
    public static function replace(string $content, array $map): string
    {
        return self::checked(preg_replace_callback(
            '/\[\[\s*([^\[\]<>]{1,40}?)\s*\]\]/u',
            static function (array $m) use ($map): string {
                $key = self::key($m[1]);

                return \array_key_exists($key, $map) ? $map[$key] : $m[0];
            },
            $content
        ), $content, 'Platzhalter');
    }

    /**
     * preg_* liefert bei PCRE-Fehlern (Backtrack-Limit, ungültiges UTF-8) null – dann den Text vor diesem
     * Schritt behalten statt einen leeren Rechtstext auszuliefern.
     */
    /**
     * @return list<string>
     */
    private static function takeRegexErrors(): array
    {
        $errors = self::$regexErrors;
        self::$regexErrors = [];

        return $errors;
    }

    private static function checked(?string $result, string $original, string $step): string
    {
        if ($result !== null) {
            return $result;
        }

        self::$regexErrors[] = $step . ': ' . preg_last_error_msg();

        return $original;
    }

    public static function key(string $name): string
    {
        return mb_strtolower((string) preg_replace('/[\s_]+/u', '', html_entity_decode($name, \ENT_QUOTES | \ENT_HTML5, 'UTF-8')));
    }

    /**
     * @return array<string, string>
     */
    private function buildMap(CmsPageLoadedEvent $event): array
    {
        $scId = $event->getSalesChannelContext()->getSalesChannelId();
        $request = $event->getRequest();

        $get = fn (string $key): string => trim((string) $this->config->get($key, $scId));
        // „-“ = in diesem Verkaufskanal bewusst leer (ein leeres Feld würde den Wert von „Alle Verkaufskanäle“ erben)
        $cfg = static function (string $field) use ($get): string {
            $value = $get(self::CONFIG . $field);

            return $value === '-' ? '' : $value;
        };

        $baseUrl = (string) $request->attributes->get('sw-sales-channel-absolute-base-url', '');
        if ($baseUrl === '') {
            $baseUrl = $request->getSchemeAndHttpHost();
        }
        $host = (string) (parse_url($baseUrl, \PHP_URL_HOST) ?: $request->getHost());
        $domain = (string) preg_replace('/^www\./i', '', $host);

        $values = [
            'shopname' => $get('core.basicInformation.shopName'),
            'e-mail' => $cfg('email') ?: $get('core.basicInformation.email'),
            'telefon' => $cfg('phone') ?: $get('NovaTheme.config.topbarPhone'),
            'telefax' => $cfg('fax'),
            'whatsapp' => $cfg('whatsapp'),
            'domain' => $domain,
            'shop-url' => rtrim($baseUrl, '/'),
            'jahr' => date('Y'),
            'firma' => $cfg('company') ?: $get('core.basicInformation.shopName'),
            'inhaber' => $cfg('owner'),
            'ust-idnr' => $cfg('vatId'),
            'lieferzeit' => $cfg('deliveryTime'),
            'warenwirtschaft' => $cfg('erpProvider'),
            'altteil-frist' => $cfg('oldPartsDays') ?: '14',
            'altteil-erstattungsfrist' => $cfg('oldPartsRefundDays') ?: '30',
        ];
        // Mehrzeilige Felder dürfen HTML enthalten (nur von Admins pflegbar) und werden nicht maskiert
        // Versandkosten: eigener Text, sonst automatisch aus den Versandarten des Verkaufskanals
        $auto = fn (): string => $this->shipping->render($event->getSalesChannelContext());
        $manual = $cfg('shipping');
        $html = [
            'versandkosten' => $manual !== '' ? $manual : $auto(),
            'versandkostenautomatisch' => $auto(),
            'anschrift' => nl2br(htmlspecialchars($cfg('address'), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'), false),
            'aufsichtsbehoerde' => nl2br($cfg('supervisoryAuthority'), false),
            'altteil-details' => $cfg('oldPartsDetails'),
            'altteil-anforderungen' => $cfg('oldPartsRequirements'),
            'ruecksendekosten' => $cfg('returnCosts'),
            'zusatz1' => $cfg('extra1'),
            'zusatz2' => $cfg('extra2'),
            'zusatz3' => $cfg('extra3'),
        ];

        $map = [];
        foreach ($values as $k => $v) {
            $map[$k] = htmlspecialchars($v, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        }
        foreach ($html as $k => $v) {
            $map[$k] = $v;
        }
        $map['ustidnr'] = $map['ust-id'] = $map['ust-idnr'];
        $map['aufsichtsbehörde'] = $map['aufsichtsbehoerde'];
        $map['rücksendekosten'] = $map['ruecksendekosten'];
        $map['altteildetails'] = $map['altteil-details'];
        $map['altteilanforderungen'] = $map['altteil-anforderungen'];
        $map['altteilfrist'] = $map['altteil-frist'];
        $map['altteilerstattungsfrist'] = $map['altteil-erstattungsfrist'];
        // Englische/alternative Schreibweisen
        $map['email'] = $map['mail'] = $map['e-mail'];
        $map['phone'] = $map['tel'] = $map['telefon'];
        $map['fax'] = $map['telefax'];
        $map['shop'] = $map['shopname'];
        $map['url'] = $map['shopurl'] = $map['shop-url'];

        return $map;
    }
}
