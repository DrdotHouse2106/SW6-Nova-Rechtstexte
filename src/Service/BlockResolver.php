<?php declare(strict_types=1);

namespace Nova\Rechtstexte\Service;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Ermittelt die aktiven Bausteine eines Verkaufskanals: manuell angeklickte, eigene und – falls
 * eingeschaltet – automatisch erkannte (zugewiesene Zahlungsarten, reCAPTCHA, Tag Manager mit
 * erweiterten Conversions, Nova Suche mit Auswertung bzw. Claude).
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
        'adsense' => ['Google AdSense'],
        'shopwareanalytics' => ['Shopware Analytics'],
        'recaptcha' => ['Google reCAPTCHA'],
        'youtube' => ['YouTube'],
        'doofinder' => ['Doofinder'],
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

    public function __construct(
        private readonly SystemConfigService $config,
        private readonly EntityRepository $paymentMethodRepository,
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

        $gtm = trim((string) ($this->config->get('NovaTagManager.config.containerId', $scId) ?? ''));
        if ($gtm !== '') {
            $found[] = 'tagmanager';
            if ($this->config->get('NovaTagManager.config.enhancedConversions', $scId)) {
                $found[] = 'erweitertekonversionen';
            }
        }

        if ($this->config->get('NovaSearch.config.active', $scId)) {
            if ($this->config->get('NovaSearch.config.learningActive', $scId) ?? true) {
                $found[] = 'suchauswertung';
            }
            if ($this->config->get('NovaSearch.config.claudeActive', $scId)) {
                $found[] = 'suchauswertung';
                $found[] = 'kisuche';
            }
        }

        // Nova Termine: Shopware lädt die Klassen nur aktiver Plugins
        if (class_exists('Nova\\Termine\\NovaTermine')) {
            $found[] = 'treffenmelden';
        }

        return array_values(array_unique($found));
    }
}
