<?php declare(strict_types=1);

namespace Nova\Rechtstexte\Service;

use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Baut aus den Versandarten des Verkaufskanals eine Versandkosten-Übersicht für den Rechtstext
 * „Zahlung und Versand“ – so stimmen Text und Checkout immer überein.
 *
 * Versandarten, deren Preis ein anderes Plugin im Checkout rechnet, haben in Shopware oft eine 0-€-Matrix.
 * Für Nova Versandkosten (technischer Name „nova_versandkosten_…“) fragt der Renderer deshalb – nur wenn
 * das Plugin aktiv ist – dessen Rechner nach den echten Preisen. Ohne das Plugin bleibt alles wie bisher.
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

    private const NOVA_PREFIX = 'nova_versandkosten_';

    /** Gewichte (kg), an denen die Paketpreise abgefragt werden; gleiche Preise werden zusammengefasst */
    private const NOVA_WEIGHTS = [1, 2, 3, 5, 10, 15, 20, 25, 31.5];

    /** @var array<string, string> */
    private array $countryCache = [];

    /**
     * @param object|null $novaQuote    Nova\Versandkosten\Service\QuoteService, falls installiert und aktiv
     * @param object|null $novaSettings Nova\Versandkosten\Service\Settings, falls installiert und aktiv
     */
    public function __construct(
        private readonly SystemConfigService $config,
        private readonly EntityRepository $shippingMethodRepository,
        private readonly EntityRepository $countryRepository,
        private readonly ?object $novaQuote = null,
        private readonly ?object $novaSettings = null,
    ) {
    }

    /**
     * Lieferländer des Verkaufskanals (bei Nova Versandkosten eingeschränkt auf dessen freigeschaltete Länder),
     * mit Komma getrennt in der Sprache des Shops.
     */
    public function countries(SalesChannelContext $context): string
    {
        $scId = $context->getSalesChannelId();
        if (isset($this->countryCache[$scId])) {
            return $this->countryCache[$scId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $scId));
        $allowed = $this->novaSettings !== null ? $this->novaSettings->rules($scId)->allowedCountries : [];

        $names = [];
        /** @var CountryEntity $country */
        foreach ($this->countryRepository->search($criteria, $context->getContext())->getEntities() as $country) {
            if ($allowed === [] || \in_array(strtoupper((string) $country->getIso()), $allowed, true)) {
                $names[] = (string) ($country->getTranslation('name') ?? $country->getName());
            }
        }
        sort($names, \SORT_LOCALE_STRING);

        return $this->countryCache[$scId] = self::e(implode(', ', $names));
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
        $technical = (string) $method->getTechnicalName();
        if ($this->novaQuote !== null && $this->novaSettings !== null && str_starts_with($technical, self::NOVA_PREFIX)) {
            return '<h4>' . $name . '</h4>' . $this->renderNova(substr($technical, \strlen(self::NOVA_PREFIX)), $context, $showDeliveryTime);
        }
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

    /**
     * Nova Versandkosten: Preise im Heimatland über den Rechner des Plugins (inkl. aktueller Zuschläge),
     * Ausland, Sperrgut und Laufzeit als Text.
     */
    private function renderNova(string $option, SalesChannelContext $context, bool $showDeliveryTime): string
    {
        $scId = $context->getSalesChannelId();
        $rules = $this->novaSettings->rules($scId);
        $home = $rules->homeCountry;
        $homeName = $this->countryName($home, $context);

        switch ($option) {
            case 'abholung':
                return '<p>kostenlos</p>';
            case 'anfrage':
                return '<p>Für Artikel, deren Versandkosten sich vorab nicht berechnen lassen: Wir teilen Ihnen die Versandkosten nach der Bestellung mit (siehe AGB).</p>';
            case 'spedition':
                return '<p>Für Speditionsware: Preis nach Gewicht, Maßen und Lieferort, wird im Warenkorb vor der Bestellung angezeigt. Ist das nicht möglich, nennen wir Ihnen die Versandkosten nach der Bestellung.</p>';
            case 'warenpost':
            case 'kleinpaket':
            case 'dpd':
            case 'dhl':
                break;
            default:
                return '<p>Die Kosten werden im Warenkorb angezeigt.</p>';
        }

        $small = \in_array($option, ['warenpost', 'kleinpaket'], true);
        $rows = [];
        $last = null;
        $weights = $small ? [max(0.01, min(0.25, $rules->smallNoDimsMaxKg))] : array_filter(
            array_unique([...self::NOVA_WEIGHTS, $rules->maxPackageKg, $rules->fixedPriceMaxKg($option) ?? 1.0]),
            static fn ($kg) => $kg <= $rules->maxPackageKg
        );
        sort($weights);
        foreach ($weights as $kg) {
            $price = $this->novaPrice($option, (float) $kg, $small, $home, $scId) ?? ($small ? $rules->fixedPrice($option) : null);
            if ($price === null) {
                break;
            }
            if ($last !== null && abs($rows[$last][1] - $price) < 0.005) {
                $rows[$last][0] = (float) $kg;
            } else {
                $rows[] = [(float) $kg, $price];
                $last = array_key_last($rows);
            }
        }

        $out = '';
        if ($rows === []) {
            $out .= '<p>Die Kosten werden im Warenkorb angezeigt.</p>';
        } elseif ($small) {
            $out .= '<p>' . self::e($homeName) . ': ' . $this->money($rows[0][1]) . '</p>';
        } else {
            $out .= '<table class="table table-sm"><thead><tr><th>Gewicht je Paket (' . self::e($homeName) . ')</th><th>Preis</th></tr></thead><tbody>';
            foreach ($rows as [$kg, $price]) {
                $out .= '<tr><td>bis ' . self::num($kg) . ' kg</td><td>' . $this->money($price) . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }

        $notes = [];
        if ($small) {
            $limit = $rules->goodsValueLimit($option);
            $notes[] = 'Nur für kleine, leichte Artikel' . ($limit !== null ? ' und einen Warenwert bis ' . number_format($limit, 2, ',', '.') . ' €' : '') . '; ob eine Bestellung passt, zeigt der Warenkorb.';
        } else {
            $notes[] = 'Schwerere Sendungen gehen in mehreren Paketen, der Preis gilt je Paket.';
            if ($rules->passOversize) {
                $notes[] = 'Für Sperrgut (übergroße Pakete) kommt der Zuschlag des Versanddienstleisters hinzu.';
            }
        }
        $notes[] = 'Ins Ausland richtet sich der Preis nach Land, Gewicht und Größe; er wird im Warenkorb vor der Bestellung angezeigt.';
        if ($showDeliveryTime && ($days = $rules->transitDays($option, $home)) !== null) {
            $notes[] = 'Laufzeit ' . self::e($homeName) . ': ' . ($days[0] === $days[1] ? $days[0] : $days[0] . '–' . $days[1]) . ' Werktage ab Versand.';
        }

        return $out . '<p><small>' . implode(' ', $notes) . '</small></p>';
    }

    /** Preis brutto in € für ein Paket mit Standardmaßen, null = nicht möglich. */
    private function novaPrice(string $option, float $kg, bool $small, string $country, string $scId): ?float
    {
        try {
            $item = new \Nova\Versandkosten\Calculator\Item('nova-rechtstexte', '', $kg, null, null, null, 'paket', 'einzelpaket', 1, $small ? 10.0 : 100.0, false, null, $small);
            $result = $this->novaQuote->calculateItems([$item], $country, '', '', $scId)?->option($option);
        } catch (\Throwable) {
            return null;
        }

        return $result !== null && $result->available ? $result->saleCents / 100 : null;
    }

    private function countryName(string $iso, SalesChannelContext $context): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $iso))->setLimit(1);
        $country = $this->countryRepository->search($criteria, $context->getContext())->first();

        return $country instanceof CountryEntity ? (string) ($country->getTranslation('name') ?? $country->getName()) : $iso;
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
