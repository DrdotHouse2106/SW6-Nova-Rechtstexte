<?php declare(strict_types=1);

namespace Nova\Rechtstexte\Service;

use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Baut aus den Versandarten des Verkaufskanals eine Versandkosten-Übersicht für den Rechtstext
 * „Zahlung und Versand“ – so stimmen Text und Checkout immer überein.
 */
class ShippingTableRenderer
{
    private const CONFIG = 'NovaRechtstexte.config.';

    private const UNITS = [
        1 => ['Anzahl Artikel', ''],
        2 => ['Warenwert', '€'],
        3 => ['Gewicht', 'kg'],
        4 => ['Volumen', 'm³'],
    ];

    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(
        private readonly SystemConfigService $config,
        private readonly EntityRepository $shippingMethodRepository,
    ) {
    }

    public function render(SalesChannelContext $context): string
    {
        $scId = $context->getSalesChannelId();
        if (isset($this->cache[$scId])) {
            return $this->cache[$scId];
        }

        $hidden = array_flip(array_map('strval', (array) $this->config->get(self::CONFIG . 'shippingHidden', $scId)));
        $showRules = (bool) ($this->config->get(self::CONFIG . 'shippingShowRules', $scId) ?? true);
        $showDeliveryTime = (bool) ($this->config->get(self::CONFIG . 'shippingShowDeliveryTime', $scId) ?? true);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $scId));
        $criteria->addAssociation('prices.rule');
        $criteria->addAssociation('prices.calculationRule');
        $criteria->addAssociation('availabilityRule');
        $criteria->addAssociation('deliveryTime');
        $criteria->addSorting(new FieldSorting('position'), new FieldSorting('name'));

        $html = '';
        /** @var ShippingMethodEntity $method */
        foreach ($this->shippingMethodRepository->search($criteria, $context->getContext())->getEntities() as $method) {
            if (isset($hidden[$method->getId()])) {
                continue;
            }
            $html .= $this->renderMethod($method, $context, $showRules, $showDeliveryTime);
        }

        return $this->cache[$scId] = $html !== ''
            ? '<div class="nova-legal-shipping">' . $html . '</div>'
            : '<p>Die Versandkosten werden Ihnen im Warenkorb und im Bestellprozess angezeigt.</p>';
    }

    private function renderMethod(ShippingMethodEntity $method, SalesChannelContext $context, bool $showRules, bool $showDeliveryTime): string
    {
        $name = self::e($method->getTranslation('name') ?? $method->getName() ?? '');
        $meta = [];
        if ($showDeliveryTime && $method->getDeliveryTime() !== null) {
            $meta[] = 'Lieferzeit: ' . self::e($method->getDeliveryTime()->getTranslation('name') ?? $method->getDeliveryTime()->getName() ?? '');
        }
        if ($showRules && $method->getAvailabilityRule() !== null) {
            $meta[] = 'Gilt für: ' . self::e($method->getAvailabilityRule()->getName());
        }

        $prices = $method->getPrices()->getElements();
        usort($prices, static fn (ShippingMethodPriceEntity $a, ShippingMethodPriceEntity $b): int => [self::ruleName($a), $a->getQuantityStart() ?? 0] <=> [self::ruleName($b), $b->getQuantityStart() ?? 0]);

        $rows = [];
        $hasRuleColumn = false;
        $unitLabel = null;
        foreach ($prices as $price) {
            $amount = $price->getCurrencyPrice()?->getCurrencyPrice($context->getCurrencyId());
            if ($amount === null) {
                continue;
            }
            $rule = $showRules ? self::ruleName($price) : '';
            $hasRuleColumn = $hasRuleColumn || $rule !== '';
            $calc = $price->getCalculation();
            if ($calc !== null && isset(self::UNITS[$calc])) {
                $unitLabel ??= self::UNITS[$calc][0];
            }
            $rows[] = [$rule, $this->range($price), $this->money($amount->getGross())];
        }

        $out = '<h4>' . $name . '</h4>';
        if ($meta !== []) {
            $out .= '<p><small>' . implode(' · ', $meta) . '</small></p>';
        }
        if ($rows === []) {
            return $out . '<p>Die Kosten werden im Warenkorb angezeigt.</p>';
        }
        if (\count($rows) === 1 && $rows[0][1] === '' && $rows[0][0] === '') {
            return $out . '<p>' . $rows[0][2] . '</p>';
        }

        $out .= '<table class="table table-sm"><thead><tr>';
        if ($hasRuleColumn) {
            $out .= '<th>Gilt für</th>';
        }
        $out .= '<th>' . self::e($unitLabel ?? 'Bereich') . '</th><th>Preis</th></tr></thead><tbody>';
        foreach ($rows as [$rule, $range, $money]) {
            $out .= '<tr>' . ($hasRuleColumn ? '<td>' . self::e($rule) . '</td>' : '') . '<td>' . ($range !== '' ? $range : 'pauschal') . '</td><td>' . $money . '</td></tr>';
        }

        return $out . '</tbody></table>';
    }

    private function range(ShippingMethodPriceEntity $price): string
    {
        $calc = $price->getCalculation();
        if ($calc === null || !isset(self::UNITS[$calc])) {
            return '';
        }
        $unit = self::UNITS[$calc][1];
        $start = $price->getQuantityStart();
        $end = $price->getQuantityEnd();
        $fmt = fn (float $v): string => $unit === '€' ? $this->money($v) : self::num($v) . ($unit !== '' ? ' ' . $unit : ($calc === 1 ? ' Artikel' : ''));

        if (($start === null || $start <= 0) && $end === null) {
            return '';
        }
        if ($start === null || $start <= 0) {
            return 'bis ' . $fmt($end);
        }
        if ($end === null) {
            return 'ab ' . $fmt($start);
        }

        return $fmt($start) . ' bis ' . $fmt($end);
    }

    private function money(float $value): string
    {
        return $value <= 0.0 ? 'versandkostenfrei' : number_format($value, 2, ',', '.') . ' €';
    }

    private static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    private static function ruleName(ShippingMethodPriceEntity $price): string
    {
        return $price->getRule()?->getName() ?? $price->getCalculationRule()?->getName() ?? '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
    }
}
