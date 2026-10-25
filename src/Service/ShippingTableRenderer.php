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
        $availability = $method->getAvailabilityRule();
        if ($showRules && $availability !== null && trim((string) $availability->getDescription()) !== '-') {
            $label = trim(strtok((string) $availability->getDescription(), "\n") ?: '');
            $meta[] = 'Gilt für: ' . self::e($label !== '' ? $label : $availability->getName());
        }

        $prices = $method->getPrices()->getElements();
        usort($prices, static fn (ShippingMethodPriceEntity $a, ShippingMethodPriceEntity $b): int => [self::ruleName($a), $a->getQuantityStart() ?? 0] <=> [self::ruleName($b), $b->getQuantityStart() ?? 0]);

        $rows = [];
        $hasRuleColumn = false;
        $units = [];
        foreach ($prices as $price) {
            if (self::isHiddenRule($price)) {
                continue;
            }
            $amount = $price->getCurrencyPrice()?->getCurrencyPrice($context->getCurrencyId());
            if ($amount === null) {
                continue;
            }
            $rule = $showRules ? self::ruleName($price) : '';
            $hasRuleColumn = $hasRuleColumn || $rule !== '';
            $calc = $price->getCalculation();
            if ($calc !== null && isset(self::UNITS[$calc]) && $this->range($price) !== '') {
                $units[self::UNITS[$calc][0]] = true;
            }
            $rows[] = [$rule, $this->range($price), $this->money($amount->getGross())];
        }

        $out = '<h4>' . $name . '</h4>';
        if ($meta !== []) {
            $out .= '<p><small>' . implode(' · ', $meta) . '</small></p>';
        }
        if ($rows === []) {
            // Alle Preise ausgeblendet oder keine hinterlegt: Versandart nur nennen, wenn sie nicht ganz leer ist
            return $method->getPrices()->count() > 0 ? '' : $out . '<p>Die Kosten werden im Warenkorb angezeigt.</p>';
        }
        if (\count($rows) === 1 && $rows[0][1] === '' && $rows[0][0] === '') {
            return $out . '<p>' . $rows[0][2] . '</p>';
        }

        $unitLabel = \count($units) === 1 ? (string) array_key_first($units) : 'Bereich';
        $out .= '<table class="table table-sm"><thead><tr><th>' . self::e($unitLabel) . '</th><th>Preis</th></tr></thead><tbody>';
        $currentRule = null;
        foreach ($rows as [$rule, $range, $money]) {
            if ($hasRuleColumn && $rule !== $currentRule) {
                $currentRule = $rule;
                $out .= '<tr class="nova-legal-shipping-group"><th colspan="2">' . self::e($rule !== '' ? $rule : 'Alle übrigen') . '</th></tr>';
            }
            $out .= '<tr><td>' . ($range !== '' ? $range : 'pauschal') . '</td><td>' . $money . '</td></tr>';
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

        // „10 bis 20 kg“ statt „10 kg bis 20 kg“ (bei Beträgen beide mit €)
        return ($unit === '€' ? $fmt($start) : self::num($start)) . ' bis ' . $fmt($end);
    }

    private function money(float $value): string
    {
        return $value <= 0.0 ? 'versandkostenfrei' : number_format($value, 2, ',', '.') . ' €';
    }

    private static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    /**
     * Kundentext einer Regel: erste Zeile der Regel-Beschreibung (Rule Builder), sonst der Regelname.
     * Beschreibung „-“ = Preise dieser Regel nicht im Rechtstext aufführen (z. B. Sonderfälle für einzelne Artikel).
     */
    private static function ruleName(ShippingMethodPriceEntity $price): string
    {
        $rule = $price->getRule() ?? $price->getCalculationRule();
        if ($rule === null) {
            return '';
        }
        $description = trim(strtok((string) $rule->getDescription(), "\n") ?: '');

        return $description !== '' ? $description : $rule->getName();
    }

    private static function isHiddenRule(ShippingMethodPriceEntity $price): bool
    {
        $rule = $price->getRule() ?? $price->getCalculationRule();

        return $rule !== null && trim((string) $rule->getDescription()) === '-';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
    }
}
