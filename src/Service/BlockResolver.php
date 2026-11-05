<?php declare(strict_types=1);

namespace Nova\Rechtstexte\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Ermittelt die aktiven Bausteine eines Verkaufskanals: manuell angeklickte, eigene und – falls
 * eingeschaltet – automatisch erkannte (zugewiesene Zahlungsarten, reCAPTCHA, Tag Manager mit
 * erweiterten Conversions, Nova Suche mit Auswertung bzw. Claude, Nova Termine, Nova Versandkosten
 * mit aktiver Versandart „Versandkosten auf Anfrage“). Bausteine fremder Plugins gelten nur, wenn das
 * Plugin aktiv ist.
 */
class BlockResolver
{
    private const CONFIG = 'NovaRechtstexte.config.';

    /** Muster im technischen Namen/Handler der Zahlungsart → Baustein */
    private const PAYMENT_PATTERNS = [
        'klarna' => 'klarna',
        'paypal' => 'paypal',
        'amazon' => 'amazonpay',
        'googlepay' => 'googlepay',
        'applepay' => 'applepay',
        'ratepay' => 'ratepay',
        'mollie' => 'mollie',
        'stripe' => 'stripe',
        'prepayment' => 'vorkasse',
        'cashpayment' => 'barzahlung',
        'cash_payment' => 'barzahlung',
        'invoicepayment' => 'rechnung',
        'invoice_payment' => 'rechnung',
        'debitpayment' => 'lastschrift',
    ];

    /**
     * Bausteine der Auswahlliste mit Anzeigenamen und Aliasen – im Text darf jede Schreibweise stehen,
     * z. B. [[wenn Gebrauchte Waren]] oder [[wenn gebrauchtware]].
     */
    private const ALIASES = [
        'vorkasse' => ['Vorkasse / Überweisung', 'ueberweisung'],
        'barzahlung' => ['Barzahlung bei Abholung'],
        'rechnung' => ['Rechnung (eigene)'],
        'lastschrift' => ['Lastschrift (eigene)'],
        'paypal' => ['PayPal', 'paypalcheckout', 'paypalexpress'],
        'klarna' => ['Klarna'],
        'amazonpay' => ['Amazon Pay', 'amazonpayments', 'amazon'],
        'googlepay' => ['Google Pay'],
        'applepay' => ['Apple Pay'],
        'ratepay' => ['Ratepay'],
        'mollie' => ['Mollie'],
        'stripe' => ['Stripe'],
        'versand' => ['Versand'],
        'abholung' => ['Selbstabholung'],
        'auslandsversand' => ['Versand ins Ausland'],
        'spedition' => ['Speditionsversand'],
        'kundenkonto' => ['Kundenkonto'],
        'newsletter' => ['Newsletter'],
        'bewertungen' => ['Produktbewertungen'],
        'kontaktformular' => ['Kontaktformular'],
        'dateiupload' => ['Datei-Upload (Nova Upload)', 'upload'],
        'tagmanager' => ['Google Tag Manager', 'gtm'],
        'analytics' => ['Google Analytics'],
        'googleads' => ['Google Ads'],
        'erweitertekonversionen' => ['Erweiterte Conversions (Google Ads)', 'enhancedconversions'],
        'suchauswertung' => ['Suchauswertung (Nova Suche)', 'novasuche'],
        'kisuche' => ['KI-Suchkorrektur (Nova Suche mit Claude)', 'claude'],
        'treffenmelden' => ['Formular „Treffen melden“ (Nova Termine)', 'termine', 'novatermine'],
        'versandanfrage' => ['Versandkosten auf Anfrage (Nova Versandkosten)', 'versandkostenaufanfrage', 'anfrageversand'],
        'tourbenachrichtigung' => ['Tour-Benachrichtigung (Nova Versandkosten)', 'tourmerkliste', 'tourinfo'],
        'bestandskundenwerbung' => ['Nachfrage nach dem Versand (Nova Nachfrage)', 'nachfrage', 'novanachfrage', 'bewertungsanfrage'],
        'adsense' => ['Google AdSense'],
        'shopwareanalytics' => ['Shopware Analytics'],
        'recaptcha' => ['Google reCAPTCHA'],
        'youtube' => ['YouTube'],
        'whatsapp' => ['WhatsApp'],
        'warenwirtschaft' => ['Warenwirtschaft (z. B. weclapp/ERPNext)'],
        'widerrufsformular' => ['Online-Widerrufsformular (Widerrufsbutton)', 'widerrufsbutton'],
        'gebrauchtware' => ['Gebrauchte Waren', 'gebraucht', 'gebrauchtteile'],
        'reparaturen' => ['Reparaturleistungen', 'reparatur'],
        'dienstleistungen' => ['Dienstleistungen'],
        'altteilpfand' => ['Altteilpfand'],
        'batterien' => ['Batterien (BattG)', 'battg'],
        'starterbatterien' => ['Starterbatterien (Pfand)', 'starterbatterie', 'fahrzeugbatterien'],
        'buchpreisbindung' => ['Preisgebundene Bücher'],
        'individualanfertigung' => ['Individuelle Anfertigungen (z. B. 3D-Druck)', '3ddruck', 'individuellgestaltetewaren'],
    ];

    /** @var array<string, array<string, true>> */
    private array $cache = [];

    /** @var array<string, true>|null technische Namen aktiver Plugins, null = noch nicht geladen */
    private ?array $activePlugins = null;

    public function __construct(
        private readonly SystemConfigService $config,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly ?Connection $connection = null,
    ) {
    }

    /**
     * @return array<string, true> Schlüssel = normierter Bausteinname
     */
    public function resolve(SalesChannelContext $context): array
    {
        $scId = $context->getSalesChannelId();
        if (isset($this->cache[$scId])) {
            return $this->cache[$scId];
        }

        $tags = [];
        foreach ((array) $this->config->get(self::CONFIG . 'blocks', $scId) as $tag) {
            $tags[self::key((string) $tag)] = true;
        }
        foreach (preg_split('/[,;\n]+/', (string) $this->config->get(self::CONFIG . 'customBlocks', $scId)) ?: [] as $tag) {
            if (trim($tag) !== '') {
                $tags[self::key($tag)] = true;
            }
        }
        if ($this->config->get(self::CONFIG . 'autoDetect', $scId) ?? true) {
            foreach ($this->detect($context) as $tag) {
                $tags[$tag] = true;
            }
        }
        $off = [];
        foreach ((array) $this->config->get(self::CONFIG . 'blocksOff', $scId) as $tag) {
            $off[self::key((string) $tag)] = true;
        }
        $tags = array_diff_key($tags, $off);

        // Anzeigenamen und Aliase der aktiven Bausteine ebenfalls als aktiv führen
        foreach (self::ALIASES as $id => $names) {
            if (isset($tags[$id])) {
                foreach ($names as $name) {
                    $tags[self::key($name)] = true;
                }
            }
        }

        return $this->cache[$scId] = $tags;
    }

    public static function key(string $name): string
    {
        $name = html_entity_decode($name, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $name = strtr(mb_strtolower($name), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        return (string) preg_replace('/[^a-z0-9]/', '', $name);
    }

    /**
     * @return list<string>
     */
    private function detect(SalesChannelContext $context): array
    {
        $found = [];
        $scId = $context->getSalesChannelId();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $scId));
        /** @var PaymentMethodEntity $method */
        foreach ($this->paymentMethodRepository->search($criteria, $context->getContext())->getEntities() as $method) {
            $haystack = mb_strtolower($method->getTechnicalName() . ' ' . $method->getHandlerIdentifier());
            foreach (self::PAYMENT_PATTERNS as $needle => $tag) {
                if (str_contains($haystack, $needle)) {
                    $found[] = $tag;
                }
            }
        }

        $captchas = (array) $this->config->get('core.basicInformation.activeCaptchasV2', $scId);
        foreach (['googleReCaptchaV2', 'googleReCaptchaV3'] as $name) {
            if (!empty($captchas[$name]['isActive'])) {
                $found[] = 'recaptcha';
            }
        }

        // Fremde Plugin-Einstellungen bleiben nach dem Deaktivieren in system_config stehen – deshalb immer
        // zusätzlich prüfen, ob das Plugin aktiv ist, sonst beschreibt die Datenschutzerklärung Verarbeitungen,
        // die gar nicht stattfinden
        $gtm = trim((string) ($this->config->get('NovaTagManager.config.containerId', $scId) ?? ''));
        if ($gtm !== '' && $this->pluginActive('Nova\\TagManager\\NovaTagManager')) {
            $found[] = 'tagmanager';
            if ($this->config->get('NovaTagManager.config.enhancedConversions', $scId)) {
                $found[] = 'erweitertekonversionen';
            }
        }

        if ($this->config->get('NovaSearch.config.active', $scId) && $this->pluginActive('Nova\\Search\\NovaSearch')) {
            if ($this->config->get('NovaSearch.config.learningActive', $scId) ?? true) {
                $found[] = 'suchauswertung';
            }
            if ($this->config->get('NovaSearch.config.claudeActive', $scId)) {
                $found[] = 'suchauswertung';
                $found[] = 'kisuche';
            }
        }

        // Nova Termine: Plugin aktiv
        if ($this->pluginActive('Nova\\Termine\\NovaTermine')) {
            $found[] = 'treffenmelden';
        }

        // Nova Versandkosten: Plugin aktiv und Versandart „Versandkosten auf Anfrage“ aktiv im Kanal
        if ($this->pluginActive('Nova\\Versandkosten\\NovaVersandkosten') && $this->requestShippingActive($scId)) {
            $found[] = 'versandanfrage';
        }

        // Nova Versandkosten (ab 1.2.0): Tour-Benachrichtigung für diesen Kanal eingeschaltet
        if ($this->pluginActive('Nova\\Versandkosten\\NovaVersandkosten') && $this->config->get('NovaVersandkosten.config.tourNotifyEnabled', $scId)) {
            $found[] = 'tourbenachrichtigung';
        }

        // Nova Nachfrage: Plugin aktiv und im Kanal eingeschaltet (Bewertungsanfrage/Bestandskundenwerbung nach dem Versand)
        if ($this->pluginActive('Nova\\Nachfrage\\NovaNachfrage') && $this->config->get('NovaNachfrage.config.active', $scId)) {
            $found[] = 'bestandskundenwerbung';
        }

        return array_values(array_unique($found));
    }

    /**
     * Plugin-Klasse ladbar UND Plugin laut Tabelle `plugin` installiert und aktiv. class_exists() allein reicht
     * nicht: Per Composer installierte Plugins sind auch deaktiviert ladbar. Ist die Tabelle nicht lesbar,
     * gilt nur class_exists() (Shopware lädt Klassen aus custom/plugins nur für aktive Plugins).
     */
    private function pluginActive(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        if ($this->activePlugins === null) {
            $this->activePlugins = [];
            try {
                if ($this->connection !== null) {
                    foreach ($this->connection->fetchFirstColumn('SELECT name FROM plugin WHERE active = 1 AND installed_at IS NOT NULL') as $name) {
                        $this->activePlugins[(string) $name] = true;
                    }
                }
            } catch (\Throwable) {
                $this->activePlugins = [];
            }
            if ($this->activePlugins === []) {
                // Keine Auskunft möglich (kein Zugriff oder leere Antwort): nur die Klassenprüfung zählt
                $this->activePlugins['*'] = true;
            }
        }

        if (isset($this->activePlugins['*'])) {
            return true;
        }

        $pos = strrpos($class, '\\');

        return isset($this->activePlugins[$pos === false ? $class : substr($class, $pos + 1)]);
    }

    private function requestShippingActive(string $salesChannelId): bool
    {
        if ($this->connection === null) {
            return false;
        }
        try {
            return $this->connection->fetchOne(
                'SELECT 1 FROM shipping_method sm
                  INNER JOIN sales_channel_shipping_method scsm ON scsm.shipping_method_id = sm.id
                  WHERE sm.technical_name = :name AND sm.active = 1 AND scsm.sales_channel_id = UNHEX(:sc) LIMIT 1',
                ['name' => 'nova_versandkosten_anfrage', 'sc' => $salesChannelId]
            ) !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
