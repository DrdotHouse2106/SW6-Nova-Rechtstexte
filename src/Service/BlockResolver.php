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
 * eingeschaltet – automatisch erkannte (zugewiesene Zahlungsarten, reCAPTCHA, Tag Manager).
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
        foreach ((array) $this->config->get(self::CONFIG . 'blocksOff', $scId) as $tag) {
            unset($tags[self::key((string) $tag)]);
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
        }

        return array_values(array_unique($found));
    }
}
