<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'currency_code',
        'currency_symbol',
        'tax_rate',
        'shipping_zone',
        'is_active',
        'phone_code',
        'locale',
        'date_format',
        'time_format',
        'decimal_separator',
        'thousands_separator',
        'tax_inclusive',
        'requires_vat_number',
        'min_order_amount',
        'max_order_amount',
        'supported_payment_methods',
        'shipping_carriers',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'tax_inclusive' => 'boolean',
        'requires_vat_number' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'supported_payment_methods' => 'array',
        'shipping_carriers' => 'array',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    public function shippingZones(): HasMany
    {
        return $this->hasMany(ShippingZone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByCurrency($query, $currencyCode)
    {
        return $query->where('currency_code', $currencyCode);
    }

    public function getCurrencySymbolAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'RUB' => '₽',
            'BRL' => 'R$',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'RON' => 'lei',
            'BGN' => 'лв',
            'HRK' => 'kn',
            'RSD' => 'дин',
            'UAH' => '₴',
            'KZT' => '₸',
            'GEL' => '₾',
            'AMD' => '֏',
            'AZN' => '₼',
            'TRY' => '₺',
            'ILS' => '₪',
            'SAR' => '﷼',
            'AED' => 'د.إ',
            'QAR' => '﷼',
            'KWD' => 'د.ك',
            'BHD' => 'د.ب',
            'OMR' => 'ر.ع.',
            'JOD' => 'د.ا',
            'LBP' => 'ل.ل',
            'EGP' => 'ج.م',
            'MAD' => 'د.م.',
            'TND' => 'د.ت',
            'DZD' => 'د.ج',
            'LYD' => 'ل.د',
            'SDG' => 'ج.س.',
            'MUR' => '₨',
            'KES' => 'KSh',
            'UGX' => 'USh',
            'TZS' => 'TSh',
            'ZAR' => 'R',
            'NGN' => '₦',
            'GHS' => '₵',
            'XOF' => 'CFA',
            'XAF' => 'CFA',
            'XPF' => '₣',
            'VUV' => 'VT',
            'WST' => 'WS$',
            'TOP' => 'T$',
            'SBD' => 'SI$',
            'FJD' => 'FJ$',
            'VND' => '₫',
            'KHR' => '៛',
            'LAK' => '₭',
            'THB' => '฿',
            'MYR' => 'RM',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'TWD' => 'NT$',
            'KRW' => '₩',
            'PHP' => '₱',
            'IDR' => 'Rp',
            'MVR' => 'Rf',
            'LKR' => 'රු',
            'PKR' => '₨',
            'NPR' => '₨',
            'BTN' => 'Nu.',
            'BDT' => '৳',
            'MMK' => 'Ks',
            'MNT' => '₮',
            'MOP' => 'MOP$',
            'CUP' => '₱',
            'HTG' => 'G',
            'XCD' => 'EC$',
            'BBD' => 'Bds$',
            'BZD' => 'BZ$',
            'GTQ' => 'Q',
            'HNL' => 'L',
            'NIO' => 'C$',
            'CRC' => '₡',
            'PAB' => 'B/.',
            'COP' => '$',
            'PEN' => 'S/',
            'BOB' => 'Bs.',
            'PYG' => '₲',
            'UYU' => '$U',
            'CLP' => '$',
            'ARS' => '$',
            'VES' => 'Bs.',
            'GYD' => 'G$',
            'SRD' => '$',
            'AWG' => 'ƒ',
            'ANG' => 'ƒ',
            'CUC' => 'CUC$',
            'KYD' => 'CI$',
            'JMD' => 'J$',
            'TTD' => 'TT$',
            'XCD' => 'EC$',
            'BMD' => 'BD$',
            'DM' => 'EC$',
            'GD' => 'EC$',
            'VC' => 'EC$',
            'LC' => 'EC$',
            'AG' => 'EC$',
            'KN' => 'EC$',
            'DM' => 'EC$',
        ];

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }

    public function formatCurrency(float $amount): string
    {
        $decimalPlaces = in_array($this->currency_code, ['JPY', 'KRW', 'VND']) ? 0 : 2;
        $formattedAmount = number_format(
            $amount,
            $decimalPlaces,
            $this->decimal_separator ?? '.',
            $this->thousands_separator ?? ','
        );

        // Position currency symbol based on locale
        $symbolPosition = in_array($this->locale, ['en', 'es', 'fr', 'de', 'it', 'pt']) ? 'before' : 'after';

        if ($symbolPosition === 'before') {
            return $this->currency_symbol . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $this->currency_symbol;
        }
    }

    public function calculateTax(float $amount, bool $isTaxInclusive = null): array
    {
        $isTaxInclusive = $isTaxInclusive ?? $this->tax_inclusive;
        $taxRate = $this->tax_rate / 100;

        if ($isTaxInclusive) {
            $taxAmount = ($amount * $taxRate) / (1 + $taxRate);
            $netAmount = $amount - $taxAmount;
        } else {
            $taxAmount = $amount * $taxRate;
            $netAmount = $amount;
        }

        $totalAmount = $netAmount + $taxAmount;

        return [
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'tax_rate' => $this->tax_rate,
            'tax_inclusive' => $isTaxInclusive,
        ];
    }

    public function supportsPaymentMethod(string $method): bool
    {
        return in_array($method, $this->supported_payment_methods ?? []);
    }

    public function supportsShippingCarrier(string $carrier): bool
    {
        return in_array($carrier, $this->shipping_carriers ?? []);
    }

    public function getValidationRules(): array
    {
        return [
            'min_order_amount' => $this->min_order_amount ? "min:{$this->min_order_amount}" : null,
            'max_order_amount' => $this->max_order_amount ? "max:{$this->max_order_amount}" : null,
            'vat_number' => $this->requires_vat_number ? 'required' : 'nullable',
        ];
    }
}
