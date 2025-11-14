<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'countries',
        'states',
        'postal_codes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'countries' => 'array',
        'states' => 'array',
        'postal_codes' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function coversLocation(array $location): bool
    {
        $countryCode = $location['country_code'] ?? null;
        $state = $location['state'] ?? null;
        $postalCode = $location['postal_code'] ?? null;

        if (!$countryCode) {
            return false;
        }

        // Check if country is covered
        if (!in_array($countryCode, $this->countries)) {
            return false;
        }

        // Check if state is restricted and matches
        if (!empty($this->states) && $state) {
            if (!in_array($state, $this->states)) {
                return false;
            }
        }

        // Check if postal code is restricted and matches
        if (!empty($this->postal_codes) && $postalCode) {
            $matches = false;
            foreach ($this->postal_codes as $pattern) {
                if ($this->matchesPostalCode($postalCode, $pattern)) {
                    $matches = true;
                    break;
                }
            }
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    private function matchesPostalCode(string $postalCode, string $pattern): bool
    {
        // Handle wildcards
        if (strpos($pattern, '*') !== false) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
            return preg_match($regex, $postalCode);
        }

        // Handle ranges (e.g., "1000-2000")
        if (strpos($pattern, '-') !== false) {
            [$start, $end] = explode('-', $pattern, 2);
            return $postalCode >= $start && $postalCode <= $end;
        }

        // Exact match
        return $postalCode === $pattern;
    }

    public static function findZoneForLocation(array $location): ?self
    {
        return static::active()
            ->ordered()
            ->get()
            ->first(function ($zone) use ($location) {
                return $zone->coversLocation($location);
            });
    }

    public function getAvailableMethods(float $weight = null, float $subtotal = null): array
    {
        return $this->shippingMethods()
            ->active()
            ->applicableForWeight($weight)
            ->applicableForSubtotal($subtotal)
            ->ordered()
            ->get()
            ->map(function ($method) use ($weight, $subtotal) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'description' => $method->description,
                    'code' => $method->code,
                    'cost' => $method->calculateCost($weight, $subtotal),
                    'delivery_time' => $method->delivery_time,
                    'is_free' => $method->isFree($subtotal),
                ];
            })
            ->toArray();
    }
}

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id',
        'name',
        'description',
        'code',
        'calculation_type',
        'cost',
        'cost_per_item',
        'cost_per_weight',
        'free_shipping_threshold',
        'delivery_time',
        'max_weight',
        'max_dimensions',
        'is_active',
        'sort_order',
        'carrier',
        'carrier_service',
        'tracking_available',
        'insurance_available',
        'signature_required',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'cost_per_item' => 'decimal:2',
        'cost_per_weight' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'max_dimensions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'tracking_available' => 'boolean',
        'insurance_available' => 'boolean',
        'signature_required' => 'boolean',
    ];

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeApplicableForWeight($query, ?float $weight)
    {
        if ($weight !== null) {
            $query->where(function ($q) use ($weight) {
                $q->whereNull('max_weight')
                  ->orWhere('max_weight', '>=', $weight);
            });
        }
    }

    public function scopeApplicableForSubtotal($query, ?float $subtotal)
    {
        if ($subtotal !== null) {
            $query->where(function ($q) use ($subtotal) {
                $q->whereNull('free_shipping_threshold')
                  ->orWhere('free_shipping_threshold', '>', $subtotal);
            });
        }
    }

    public function calculateCost(?float $weight = null, ?float $subtotal = null, int $itemCount = 0): float
    {
        $cost = $this->cost;

        // Add per-item cost
        if ($this->cost_per_item && $itemCount > 0) {
            $cost += $this->cost_per_item * $itemCount;
        }

        // Add per-weight cost
        if ($this->cost_per_weight && $weight !== null) {
            $cost += $this->cost_per_weight * $weight;
        }

        // Check for free shipping
        if ($this->isFree($subtotal)) {
            $cost = 0;
        }

        return round($cost, 2);
    }

    public function isFree(?float $subtotal): bool
    {
        return $this->free_shipping_threshold && $subtotal && $subtotal >= $this->free_shipping_threshold;
    }

    public function canShipWeight(float $weight): bool
    {
        return !$this->max_weight || $weight <= $this->max_weight;
    }

    public function canShipDimensions(array $dimensions): bool
    {
        if (!$this->max_dimensions) {
            return true;
        }

        return (
            $dimensions['length'] <= ($this->max_dimensions['length'] ?? PHP_INT_MAX) &&
            $dimensions['width'] <= ($this->max_dimensions['width'] ?? PHP_INT_MAX) &&
            $dimensions['height'] <= ($this->max_dimensions['height'] ?? PHP_INT_MAX)
        );
    }

    public function getEstimatedDelivery(): string
    {
        return $this->delivery_time ?? 'Standard delivery';
    }

    public function getCarrierInfo(): array
    {
        return [
            'carrier' => $this->carrier,
            'service' => $this->carrier_service,
            'tracking_available' => $this->tracking_available,
            'insurance_available' => $this->insurance_available,
            'signature_required' => $this->signature_required,
        ];
    }
}

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'country_code',
        'state',
        'postal_code_from',
        'postal_code_to',
        'weight_from',
        'weight_to',
        'subtotal_from',
        'subtotal_to',
        'cost',
        'is_active',
    ];

    protected $casts = [
        'weight_from' => 'decimal:2',
        'weight_to' => 'decimal:2',
        'subtotal_from' => 'decimal:2',
        'subtotal_to' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLocation($query, string $countryCode, ?string $state = null, ?string $postalCode = null)
    {
        $query->where('country_code', $countryCode);

        if ($state) {
            $query->where(function ($q) use ($state) {
                $q->where('state', $state)
                  ->orWhereNull('state');
            });
        }

        if ($postalCode) {
            $query->where(function ($q) use ($postalCode) {
                $q->where('postal_code_from', '<=', $postalCode)
                  ->where('postal_code_to', '>=', $postalCode);
            });
        }
    }

    public function scopeForWeight($query, float $weight)
    {
        $query->where(function ($q) use ($weight) {
            $q->whereNull('weight_from')
              ->orWhere('weight_from', '<=', $weight);
        })->where(function ($q) use ($weight) {
            $q->whereNull('weight_to')
              ->orWhere('weight_to', '>=', $weight);
        });
    }

    public function scopeForSubtotal($query, float $subtotal)
    {
        $query->where(function ($q) use ($subtotal) {
            $q->whereNull('subtotal_from')
              ->orWhere('subtotal_from', '<=', $subtotal);
        })->where(function ($q) use ($subtotal) {
            $q->whereNull('subtotal_to')
              ->orWhere('subtotal_to', '>=', $subtotal);
        });
    }

    public static function findRate(array $conditions): ?self
    {
        return static::active()
            ->forLocation(
                $conditions['country_code'],
                $conditions['state'] ?? null,
                $conditions['postal_code'] ?? null
            )
            ->forWeight($conditions['weight'] ?? 0)
            ->forSubtotal($conditions['subtotal'] ?? 0)
            ->first();
    }
}
