<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'state',
        'postal_code',
        'city',
        'tax_type',
        'tax_rate',
        'tax_name',
        'is_compound',
        'applies_to_shipping',
        'valid_from',
        'valid_until',
        'min_amount',
        'max_amount',
        'product_categories',
        'customer_groups',
        'is_active',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'applies_to_shipping' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'product_categories' => 'array',
        'customer_groups' => 'array',
        'is_active' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now());
        });
    }

    public function scopeForLocation($query, string $countryCode, ?string $state = null, ?string $postalCode = null, ?string $city = null)
    {
        $query->whereHas('country', function ($q) use ($countryCode) {
            $q->where('code', $countryCode);
        });

        if ($state) {
            $query->where(function ($q) use ($state) {
                $q->where('state', $state)
                  ->orWhereNull('state');
            });
        }

        if ($postalCode) {
            $query->where(function ($q) use ($postalCode) {
                $q->where('postal_code', $postalCode)
                  ->where('postal_code', 'LIKE', $postalCode . '%')
                  ->orWhereNull('postal_code');
            });
        }

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                  ->orWhereNull('city');
            });
        }
    }

    public function scopeForAmount($query, float $amount)
    {
        return $query->where(function ($q) use ($amount) {
            $q->whereNull('min_amount')
              ->orWhere('min_amount', '<=', $amount);
        })->where(function ($q) use ($amount) {
            $q->whereNull('max_amount')
              ->orWhere('max_amount', '>=', $amount);
        });
    }

    public function scopeForCategories($query, array $categoryIds)
    {
        return $query->where(function ($q) use ($categoryIds) {
            $q->whereNull('product_categories')
              ->orWhereJsonContains('product_categories', $categoryIds);
        });
    }

    public function scopeForCustomerGroup($query, string $customerGroup)
    {
        return $query->where(function ($q) use ($customerGroup) {
            $q->whereNull('customer_groups')
              ->orWhereJsonContains('customer_groups', $customerGroup);
        });
    }

    public function appliesTo(array $conditions): bool
    {
        // Check amount conditions
        if (isset($conditions['amount'])) {
            if ($this->min_amount && $conditions['amount'] < $this->min_amount) {
                return false;
            }
            if ($this->max_amount && $conditions['amount'] > $this->max_amount) {
                return false;
            }
        }

        // Check category conditions
        if (isset($conditions['category_ids']) && $this->product_categories) {
            if (!array_intersect($conditions['category_ids'], $this->product_categories)) {
                return false;
            }
        }

        // Check customer group conditions
        if (isset($conditions['customer_group']) && $this->customer_groups) {
            if (!in_array($conditions['customer_group'], $this->customer_groups)) {
                return false;
            }
        }

        return true;
    }

    public function calculateTax(float $amount, float $previousTaxAmount = 0): float
    {
        if ($this->is_compound) {
            // Compound tax is calculated on the amount plus previous taxes
            return ($amount + $previousTaxAmount) * ($this->tax_rate / 100);
        } else {
            // Regular tax is calculated only on the amount
            return $amount * ($this->tax_rate / 100);
        }
    }

    public static function getApplicableTaxes(array $conditions): array
    {
        $query = static::active()->valid();

        // Apply location filters
        if (isset($conditions['country_code'])) {
            $query->forLocation(
                $conditions['country_code'],
                $conditions['state'] ?? null,
                $conditions['postal_code'] ?? null,
                $conditions['city'] ?? null
            );
        }

        // Apply amount filter
        if (isset($conditions['amount'])) {
            $query->forAmount($conditions['amount']);
        }

        // Apply category filter
        if (isset($conditions['category_ids'])) {
            $query->forCategories($conditions['category_ids']);
        }

        // Apply customer group filter
        if (isset($conditions['customer_group'])) {
            $query->forCustomerGroup($conditions['customer_group']);
        }

        $taxRules = $query->orderBy('tax_rate')->get();

        $applicableTaxes = [];
        $previousTaxAmount = 0;

        foreach ($taxRules as $rule) {
            if ($rule->appliesTo($conditions)) {
                $taxAmount = $rule->calculateTax($conditions['amount'], $previousTaxAmount);
                $previousTaxAmount += $taxAmount;

                $applicableTaxes[] = [
                    'rule_id' => $rule->id,
                    'tax_name' => $rule->tax_name,
                    'tax_rate' => $rule->tax_rate,
                    'tax_amount' => $taxAmount,
                    'is_compound' => $rule->is_compound,
                    'applies_to_shipping' => $rule->applies_to_shipping,
                ];
            }
        }

        return $applicableTaxes;
    }

    public static function calculateTotalTax(array $conditions): array
    {
        $applicableTaxes = static::getApplicableTaxes($conditions);

        $totalTax = 0;
        $taxBreakdown = [];

        foreach ($applicableTaxes as $tax) {
            $totalTax += $tax['tax_amount'];
            $taxBreakdown[] = [
                'name' => $tax['tax_name'],
                'rate' => $tax['tax_rate'],
                'amount' => $tax['tax_amount'],
                'is_compound' => $tax['is_compound'],
            ];
        }

        return [
            'total_tax' => round($totalTax, 2),
            'tax_breakdown' => $taxBreakdown,
            'applicable_rules' => count($applicableTaxes),
        ];
    }

    public static function getTaxRatesByCountry(): array
    {
        $taxRates = [];
        $countries = Country::with('taxRules')->active()->get();

        foreach ($countries as $country) {
            $taxRates[$country->code] = [
                'country_name' => $country->name,
                'default_rate' => $country->tax_rate,
                'tax_rules' => $country->taxRules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'tax_name' => $rule->tax_name,
                        'tax_rate' => $rule->tax_rate,
                        'tax_type' => $rule->tax_type,
                        'state' => $rule->state,
                        'is_compound' => $rule->is_compound,
                        'applies_to_shipping' => $rule->applies_to_shipping,
                    ];
                })->toArray(),
            ];
        }

        return $taxRates;
    }
}
