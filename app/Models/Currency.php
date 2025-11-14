<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_active',
        'is_default',
        'decimal_places',
        'last_updated_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'decimal_places' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($currency) {
            if ($currency->is_default) {
                static::where('id', '!=', $currency->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public static function getDefault(): ?self
    {
        return Cache::remember('default_currency', 3600, function () {
            return static::default()->active()->first();
        });
    }

    public static function getByCode(string $code): ?self
    {
        return Cache::remember("currency_{$code}", 3600, function () use ($code) {
            return static::active()->byCode($code)->first();
        });
    }

    public static function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $from = static::getByCode($fromCurrency);
        $to = static::getByCode($toCurrency);

        if (!$from || !$to) {
            throw new \Exception("Currency conversion failed: Invalid currency codes");
        }

        // Convert to base currency (USD) first, then to target currency
        $baseAmount = $amount / $from->exchange_rate;
        $convertedAmount = $baseAmount * $to->exchange_rate;

        return round($convertedAmount, $to->decimal_places);
    }

    public static function updateExchangeRates(): array
    {
        $results = [];
        $defaultCurrency = static::getDefault();

        if (!$defaultCurrency) {
            throw new \Exception('No default currency set');
        }

        // Get all active currencies
        $currencies = static::active()->get();

        foreach ($currencies as $currency) {
            try {
                if ($currency->code === $defaultCurrency->code) {
                    // Default currency has exchange rate of 1
                    $currency->update([
                        'exchange_rate' => 1.0,
                        'last_updated_at' => now(),
                    ]);
                    $results[$currency->code] = 'success';
                    continue;
                }

                // Get exchange rate from API (using exchangerate-api.com as example)
                $response = Http::get("https://v6.exchangerate-api.com/v6/latest/{$defaultCurrency->code}");

                if ($response->successful()) {
                    $data = $response->json();
                    $rate = $data['conversion_rates'][$currency->code] ?? null;

                    if ($rate) {
                        $currency->update([
                            'exchange_rate' => $rate,
                            'last_updated_at' => now(),
                        ]);
                        $results[$currency->code] = 'success';
                    } else {
                        $results[$currency->code] = 'rate_not_found';
                    }
                } else {
                    $results[$currency->code] = 'api_error';
                }
            } catch (\Exception $e) {
                $results[$currency->code] = 'error: ' . $e->getMessage();
            }
        }

        // Clear cache
        Cache::forget('default_currency');
        foreach ($currencies as $currency) {
            Cache::forget("currency_{$currency->code}");
        }

        return $results;
    }

    public function format(float $amount): string
    {
        $formattedAmount = number_format(
            $amount,
            $this->decimal_places,
            '.',
            ','
        );

        return $this->symbol . $formattedAmount;
    }

    public static function getSupportedCurrencies(): array
    {
        return Cache::remember('supported_currencies', 3600, function () {
            return static::active()->pluck('code')->toArray();
        });
    }

    public static function isValidCurrency(string $code): bool
    {
        return in_array($code, static::getSupportedCurrencies());
    }

    public function getExchangeRateAgainst(string $targetCurrencyCode): float
    {
        $targetCurrency = static::getByCode($targetCurrencyCode);

        if (!$targetCurrency) {
            throw new \Exception("Target currency {$targetCurrencyCode} not found");
        }

        return $this->exchange_rate / $targetCurrency->exchange_rate;
    }

    public static function getCurrencyList(): array
    {
        return [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2],
            'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽', 'decimal_places' => 2],
            'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'decimal_places' => 2],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimal_places' => 2],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimal_places' => 2],
            'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'decimal_places' => 2],
            'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'decimal_places' => 2],
            'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'decimal_places' => 2],
            'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'decimal_places' => 2],
            'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'decimal_places' => 2],
            'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'decimal_places' => 0],
            'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei', 'decimal_places' => 2],
            'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв', 'decimal_places' => 2],
            'HRK' => ['name' => 'Croatian Kuna', 'symbol' => 'kn', 'decimal_places' => 2],
            'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'дин', 'decimal_places' => 2],
            'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'decimal_places' => 2],
            'KZT' => ['name' => 'Kazakhstani Tenge', 'symbol' => '₸', 'decimal_places' => 2],
            'GEL' => ['name' => 'Georgian Lari', 'symbol' => '₾', 'decimal_places' => 2],
            'AMD' => ['name' => 'Armenian Dram', 'symbol' => '֏', 'decimal_places' => 0],
            'AZN' => ['name' => 'Azerbaijani Manat', 'symbol' => '₼', 'decimal_places' => 2],
            'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'decimal_places' => 2],
            'ILS' => ['name' => 'Israeli New Shekel', 'symbol' => '₪', 'decimal_places' => 2],
            'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼', 'decimal_places' => 2],
            'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2],
            'QAR' => ['name' => 'Qatari Riyal', 'symbol' => '﷼', 'decimal_places' => 2],
            'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'decimal_places' => 3],
            'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => 'د.ب', 'decimal_places' => 3],
            'OMR' => ['name' => 'Omani Rial', 'symbol' => 'ر.ع.', 'decimal_places' => 3],
            'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'د.ا', 'decimal_places' => 3],
            'LBP' => ['name' => 'Lebanese Pound', 'symbol' => 'ل.ل', 'decimal_places' => 0],
            'EGP' => ['name' => 'Egyptian Pound', 'symbol' => 'ج.م', 'decimal_places' => 2],
            'MAD' => ['name' => 'Moroccan Dirham', 'symbol' => 'د.م.', 'decimal_places' => 2],
            'TND' => ['name' => 'Tunisian Dinar', 'symbol' => 'د.ت', 'decimal_places' => 3],
            'DZD' => ['name' => 'Algerian Dinar', 'symbol' => 'د.ج', 'decimal_places' => 2],
            'LYD' => ['name' => 'Libyan Dinar', 'symbol' => 'ل.د', 'decimal_places' => 3],
            'SDG' => ['name' => 'Sudanese Pound', 'symbol' => 'ج.س.', 'decimal_places' => 2],
            'MUR' => ['name' => 'Mauritian Rupee', 'symbol' => '₨', 'decimal_places' => 2],
            'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'decimal_places' => 2],
            'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh', 'decimal_places' => 0],
            'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'decimal_places' => 0],
            'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'decimal_places' => 2],
            'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'decimal_places' => 2],
            'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '₵', 'decimal_places' => 2],
            'XOF' => ['name' => 'West African CFA Franc', 'symbol' => 'CFA', 'decimal_places' => 0],
            'XAF' => ['name' => 'Central African CFA Franc', 'symbol' => 'CFA', 'decimal_places' => 0],
            'XPF' => ['name' => 'CFP Franc', 'symbol' => '₣', 'decimal_places' => 0],
            'VUV' => ['name' => 'Vanuatu Vatu', 'symbol' => 'VT', 'decimal_places' => 0],
            'WST' => ['name' => 'Samoan Tala', 'symbol' => 'WS$', 'decimal_places' => 2],
            'TOP' => ['name' => 'Tongan Paʻanga', 'symbol' => 'T$', 'decimal_places' => 2],
            'SBD' => ['name' => 'Solomon Islands Dollar', 'symbol' => 'SI$', 'decimal_places' => 2],
            'FJD' => ['name' => 'Fijian Dollar', 'symbol' => 'FJ$', 'decimal_places' => 2],
            'VND' => ['name' => 'Vietnamese Dong', 'symbol' => '₫', 'decimal_places' => 0],
            'KHR' => ['name' => 'Cambodian Riel', 'symbol' => '៛', 'decimal_places' => 0],
            'LAK' => ['name' => 'Lao Kip', 'symbol' => '₭', 'decimal_places' => 0],
            'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'decimal_places' => 2],
            'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2],
            'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2],
            'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimal_places' => 2],
            'TWD' => ['name' => 'Taiwan Dollar', 'symbol' => 'NT$', 'decimal_places' => 2],
            'KRW' => ['name' => 'Korean Won', 'symbol' => '₩', 'decimal_places' => 0],
            'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'decimal_places' => 2],
            'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0],
            'MVR' => ['name' => 'Maldivian Rufiyaa', 'symbol' => 'Rf', 'decimal_places' => 2],
            'LKR' => ['name' => 'Sri Lankan Rupee', 'symbol' => 'රු', 'decimal_places' => 2],
            'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨', 'decimal_places' => 2],
            'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₨', 'decimal_places' => 2],
            'BTN' => ['name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.', 'decimal_places' => 2],
            'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳', 'decimal_places' => 2],
            'MMK' => ['name' => 'Myanmar Kyat', 'symbol' => 'Ks', 'decimal_places' => 0],
            'MNT' => ['name' => 'Mongolian Tugrik', 'symbol' => '₮', 'decimal_places' => 2],
            'MOP' => ['name' => 'Macanese Pataca', 'symbol' => 'MOP$', 'decimal_places' => 2],
            'CUP' => ['name' => 'Cuban Peso', 'symbol' => '₱', 'decimal_places' => 2],
            'HTG' => ['name' => 'Haitian Gourde', 'symbol' => 'G', 'decimal_places' => 2],
            'XCD' => ['name' => 'East Caribbean Dollar', 'symbol' => 'EC$', 'decimal_places' => 2],
            'BBD' => ['name' => 'Barbadian Dollar', 'symbol' => 'Bds$', 'decimal_places' => 2],
            'BZD' => ['name' => 'Belize Dollar', 'symbol' => 'BZ$', 'decimal_places' => 2],
            'GTQ' => ['name' => 'Guatemalan Quetzal', 'symbol' => 'Q', 'decimal_places' => 2],
            'HNL' => ['name' => 'Honduran Lempira', 'symbol' => 'L', 'decimal_places' => 2],
            'NIO' => ['name' => 'Nicaraguan Córdoba', 'symbol' => 'C$', 'decimal_places' => 2],
            'CRC' => ['name' => 'Costa Rican Colón', 'symbol' => '₡', 'decimal_places' => 2],
            'PAB' => ['name' => 'Panamanian Balboa', 'symbol' => 'B/.', 'decimal_places' => 2],
            'COP' => ['name' => 'Colombian Peso', 'symbol' => '$', 'decimal_places' => 2],
            'PEN' => ['name' => 'Peruvian Sol', 'symbol' => 'S/', 'decimal_places' => 2],
            'BOB' => ['name' => 'Bolivian Boliviano', 'symbol' => 'Bs.', 'decimal_places' => 2],
            'PYG' => ['name' => 'Paraguayan Guaraní', 'symbol' => '₲', 'decimal_places' => 0],
            'UYU' => ['name' => 'Uruguayan Peso', 'symbol' => '$U', 'decimal_places' => 2],
            'CLP' => ['name' => 'Chilean Peso', 'symbol' => '$', 'decimal_places' => 0],
            'ARS' => ['name' => 'Argentine Peso', 'symbol' => '$', 'decimal_places' => 2],
            'VES' => ['name' => 'Venezuelan Bolívar', 'symbol' => 'Bs.', 'decimal_places' => 2],
            'GYD' => ['name' => 'Guyana Dollar', 'symbol' => 'G$', 'decimal_places' => 2],
            'SRD' => ['name' => 'Surinamese Dollar', 'symbol' => '$', 'decimal_places' => 2],
            'AWG' => ['name' => 'Aruban Florin', 'symbol' => 'ƒ', 'decimal_places' => 2],
            'ANG' => ['name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ', 'decimal_places' => 2],
            'CUC' => ['name' => 'Cuban Convertible Peso', 'symbol' => 'CUC$', 'decimal_places' => 2],
            'KYD' => ['name' => 'Cayman Islands Dollar', 'symbol' => 'CI$', 'decimal_places' => 2],
            'JMD' => ['name' => 'Jamaican Dollar', 'symbol' => 'J$', 'decimal_places' => 2],
            'TTD' => ['name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$', 'decimal_places' => 2],
            'BMD' => ['name' => 'Bermudian Dollar', 'symbol' => 'BD$', 'decimal_places' => 2],
            'DM' => ['name' => 'Dominican Peso', 'symbol' => 'RD$', 'decimal_places' => 2],
        ];
    }
}
