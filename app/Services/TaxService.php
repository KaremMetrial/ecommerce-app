<?php

namespace App\Services;

use App\Models\TaxRule;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class TaxService
{
    public function calculateTax(array $conditions): array
    {
        $cacheKey = $this->generateCacheKey($conditions);

        return Cache::remember($cacheKey, 3600, function () use ($conditions) {
            return TaxRule::calculateTotalTax($conditions);
        });
    }

    public function getTaxRate(string $countryCode, ?string $state = null, ?string $postalCode = null): array
    {
        $conditions = [
            'country_code' => $countryCode,
            'state' => $state,
            'postal_code' => $postalCode,
            'amount' => 100, // Use 100 for percentage calculation
        ];

        $taxCalculation = $this->calculateTax($conditions);
        $totalRate = ($taxCalculation['total_tax'] / 100) * 100;

        return [
            'country_code' => $countryCode,
            'state' => $state,
            'postal_code' => $postalCode,
            'total_rate' => round($totalRate, 4),
            'tax_breakdown' => $taxCalculation['tax_breakdown'],
            'is_tax_inclusive' => $this->isTaxInclusive($countryCode),
        ];
    }

    public function validateVATNumber(string $vatNumber, string $countryCode): array
    {
        try {
            // EU VAT validation service
            if ($this->isEUCountry($countryCode)) {
                return $this->validateEUVAT($vatNumber, $countryCode);
            }

            // For non-EU countries, perform basic format validation
            return $this->validateVATFormat($vatNumber, $countryCode);
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function validateEUVAT(string $vatNumber, string $countryCode): array
    {
        $wsdl = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

        try {
            $client = new \SoapClient($wsdl);
            $result = $client->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);

            return [
                'valid' => $result->valid,
                'company_name' => $result->name ?? null,
                'company_address' => $result->address ?? null,
                'request_date' => $result->requestDate ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'VAT validation service unavailable',
            ];
        }
    }

    private function validateVATFormat(string $vatNumber, string $countryCode): array
    {
        $patterns = [
            'US' => '/^\d{2}-\d{7}$/', // EIN format
            'CA' => '/^[A-Z]{2}\d{9}$/', // Canadian Business Number
            'GB' => '/^GB\d{9}$|^GB\d{12}$/', // UK VAT
            'AU' => '/^\d{2}\d{7}\d{2}$/', // ABN format
            'NZ' => '/^\d{2}-\d{3}-\d{4}$/', // NZBN format
        ];

        $pattern = $patterns[$countryCode] ?? '/^[A-Z0-9]{8,15}$/';

        if (preg_match($pattern, strtoupper($vatNumber))) {
            return [
                'valid' => true,
                'message' => 'Format is valid',
            ];
        }

        return [
            'valid' => false,
            'error' => 'Invalid VAT number format',
        ];
    }

    private function isEUCountry(string $countryCode): bool
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];

        return in_array(strtoupper($countryCode), $euCountries);
    }

    private function isTaxInclusive(string $countryCode): bool
    {
        $taxInclusiveCountries = [
            'AU', 'NZ', 'GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT',
            'DK', 'SE', 'NO', 'FI', 'IE', 'PT', 'GR', 'CZ', 'HU', 'PL',
            'SK', 'SI', 'EE', 'LV', 'LT', 'LU', 'MT', 'CY', 'HR', 'RO',
            'BG',
        ];

        return in_array(strtoupper($countryCode), $taxInclusiveCountries);
    }

    private function generateCacheKey(array $conditions): string
    {
        $keyData = [
            'country' => $conditions['country_code'] ?? '',
            'state' => $conditions['state'] ?? '',
            'postal_code' => $conditions['postal_code'] ?? '',
            'amount' => $conditions['amount'] ?? 0,
            'category_ids' => $conditions['category_ids'] ?? [],
            'customer_group' => $conditions['customer_group'] ?? 'default',
        ];

        return 'tax_calculation_' . md5(serialize($keyData));
    }

    public function getTaxSummary(array $conditions): array
    {
        $taxCalculation = $this->calculateTax($conditions);
        $country = Country::where('code', $conditions['country_code'])->first();

        return [
            'subtotal' => $conditions['amount'],
            'tax_amount' => $taxCalculation['total_tax'],
            'total' => $conditions['amount'] + $taxCalculation['total_tax'],
            'tax_breakdown' => $taxCalculation['tax_breakdown'],
            'country' => $country ? [
                'name' => $country->name,
                'code' => $country->code,
                'currency' => $country->currency_code,
                'tax_inclusive' => $country->tax_inclusive,
            ] : null,
            'is_tax_inclusive' => $this->isTaxInclusive($conditions['country_code']),
        ];
    }

    public function reverseTaxCalculation(float $totalAmount, string $countryCode, ?string $state = null): array
    {
        $conditions = [
            'country_code' => $countryCode,
            'state' => $state,
            'amount' => 100, // Use 100 to get tax rate
        ];

        $taxCalculation = $this->calculateTax($conditions);
        $taxRate = $taxCalculation['total_tax'] / 100;

        if ($this->isTaxInclusive($countryCode)) {
            // Tax is included in total
            $netAmount = $totalAmount / (1 + $taxRate);
            $taxAmount = $totalAmount - $netAmount;
        } else {
            // Tax is added to total
            $netAmount = $totalAmount;
            $taxAmount = $totalAmount * $taxRate;
        }

        return [
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($netAmount + $taxAmount, 2),
            'tax_rate' => round($taxRate * 100, 2),
            'is_tax_inclusive' => $this->isTaxInclusive($countryCode),
        ];
    }

    public function getTaxReport($startDate, $endDate): array
    {
        $taxRules = TaxRule::with('country')
            ->active()
            ->valid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'countries' => [],
            'total_tax_collected' => 0,
            'tax_rules_count' => $taxRules->count(),
        ];

        foreach ($taxRules->groupBy('country.code') as $countryCode => $rules) {
            $countryData = [
                'country_code' => $countryCode,
                'country_name' => $rules->first()->country->name,
                'tax_rules' => $rules->count(),
                'total_rate' => $rules->sum('tax_rate'),
                'rules' => $rules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'tax_name' => $rule->tax_name,
                        'tax_rate' => $rule->tax_rate,
                        'tax_type' => $rule->tax_type,
                        'state' => $rule->state,
                        'is_compound' => $rule->is_compound,
                    ];
                })->toArray(),
            ];

            $report['countries'][] = $countryData;
        }

        return $report;
    }

    public function syncTaxRates(): array
    {
        $results = [];

        // Sync EU VAT rates
        $results['eu_vat'] = $this->syncEUVATRates();

        // Sync US state tax rates
        $results['us_sales_tax'] = $this->syncUSSalesTaxRates();

        // Sync other country tax rates
        $results['other_countries'] = $this->syncOtherCountryTaxRates();

        return $results;
    }

    private function syncEUVATRates(): array
    {
        try {
            // This would integrate with a tax rate API
            // For now, return mock data
            $euRates = [
                'DE' => ['rate' => 19.0, 'reduced_rate' => 7.0],
                'FR' => ['rate' => 20.0, 'reduced_rate' => 5.5],
                'IT' => ['rate' => 22.0, 'reduced_rate' => 10.0],
                'ES' => ['rate' => 21.0, 'reduced_rate' => 10.0],
                'NL' => ['rate' => 21.0, 'reduced_rate' => 9.0],
                'BE' => ['rate' => 21.0, 'reduced_rate' => 12.0],
                'AT' => ['rate' => 20.0, 'reduced_rate' => 10.0],
                'PT' => ['rate' => 23.0, 'reduced_rate' => 13.0],
                'SE' => ['rate' => 25.0, 'reduced_rate' => 12.0],
                'DK' => ['rate' => 25.0, 'reduced_rate' => 12.0],
                'FI' => ['rate' => 24.0, 'reduced_rate' => 14.0],
                'IE' => ['rate' => 23.0, 'reduced_rate' => 13.5],
                'GR' => ['rate' => 24.0, 'reduced_rate' => 13.0],
                'CZ' => ['rate' => 21.0, 'reduced_rate' => 15.0],
                'HU' => ['rate' => 27.0, 'reduced_rate' => 18.0],
                'PL' => ['rate' => 23.0, 'reduced_rate' => 8.0],
                'SK' => ['rate' => 20.0, 'reduced_rate' => 10.0],
                'SI' => ['rate' => 22.0, 'reduced_rate' => 9.5],
                'EE' => ['rate' => 20.0, 'reduced_rate' => 9.0],
                'LV' => ['rate' => 21.0, 'reduced_rate' => 12.0],
                'LT' => ['rate' => 21.0, 'reduced_rate' => 9.0],
                'LU' => ['rate' => 17.0, 'reduced_rate' => 8.0],
                'MT' => ['rate' => 18.0, 'reduced_rate' => 5.0],
                'CY' => ['rate' => 19.0, 'reduced_rate' => 9.0],
                'HR' => ['rate' => 25.0, 'reduced_rate' => 13.0],
                'RO' => ['rate' => 19.0, 'reduced_rate' => 9.0],
                'BG' => ['rate' => 20.0, 'reduced_rate' => 9.0],
            ];

            foreach ($euRates as $countryCode => $rates) {
                Country::where('code', $countryCode)->update([
                    'tax_rate' => $rates['rate'],
                ]);
            }

            return ['success' => true, 'updated' => count($euRates)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function syncUSSalesTaxRates(): array
    {
        try {
            // This would integrate with a US sales tax API like TaxJar or Avalara
            // For now, return mock data
            $usRates = [
                'CA' => ['rate' => 8.75, 'state_rate' => 7.25],
                'NY' => ['rate' => 8.0, 'state_rate' => 4.0],
                'TX' => ['rate' => 8.19, 'state_rate' => 6.25],
                'FL' => ['rate' => 7.0, 'state_rate' => 6.0],
                'IL' => ['rate' => 8.75, 'state_rate' => 6.25],
                'PA' => ['rate' => 6.0, 'state_rate' => 6.0],
                'OH' => ['rate' => 7.25, 'state_rate' => 5.75],
                'GA' => ['rate' => 7.31, 'state_rate' => 4.0],
                'NC' => ['rate' => 7.25, 'state_rate' => 4.75],
                'MI' => ['rate' => 6.0, 'state_rate' => 6.0],
            ];

            foreach ($usRates as $stateCode => $rates) {
                TaxRule::updateOrCreate([
                    'country_id' => Country::where('code', 'US')->first()->id,
                    'state' => $stateCode,
                    'tax_type' => 'sales_tax',
                    'tax_name' => 'Sales Tax',
                ], [
                    'tax_rate' => $rates['rate'],
                    'is_active' => true,
                ]);
            }

            return ['success' => true, 'updated' => count($usRates)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function syncOtherCountryTaxRates(): array
    {
        try {
            // This would integrate with international tax rate APIs
            // For now, return mock data
            $otherRates = [
                'AU' => ['rate' => 10.0, 'type' => 'gst'],
                'NZ' => ['rate' => 15.0, 'type' => 'gst'],
                'CA' => ['rate' => 5.0, 'type' => 'gst'],
                'GB' => ['rate' => 20.0, 'type' => 'vat'],
                'CH' => ['rate' => 7.7, 'type' => 'vat'],
                'NO' => ['rate' => 25.0, 'type' => 'vat'],
                'JP' => ['rate' => 10.0, 'type' => 'consumption'],
                'KR' => ['rate' => 10.0, 'type' => 'vat'],
                'SG' => ['rate' => 8.0, 'type' => 'gst'],
                'IN' => ['rate' => 18.0, 'type' => 'gst'],
                'MX' => ['rate' => 16.0, 'type' => 'iva'],
                'BR' => ['rate' => 17.0, 'type' => 'icms'],
                'AR' => ['rate' => 21.0, 'type' => 'iva'],
            ];

            foreach ($otherRates as $countryCode => $rates) {
                Country::where('code', $countryCode)->update([
                    'tax_rate' => $rates['rate'],
                ]);
            }

            return ['success' => true, 'updated' => count($otherRates)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function clearTaxCache(): void
    {
        Cache::forget('tax_calculation_*');
    }
}
