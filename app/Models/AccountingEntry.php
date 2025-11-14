<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountingEntry extends Model
{
    use HasFactory;

    const TYPE_DEBIT = 'debit';
    const TYPE_CREDIT = 'credit';

    const CATEGORY_SALES = 'sales';
    const CATEGORY_PURCHASES = 'purchases';
    const CATEGORY_EXPENSES = 'expenses';
    const CATEGORY_TAX = 'tax';
    const CATEGORY_SHIPPING = 'shipping';
    const CATEGORY_DISCOUNTS = 'discounts';
    const CATEGORY_REFUNDS = 'refunds';
    const CATEGORY_FEES = 'fees';

    protected $fillable = [
        'date',
        'description',
        'type',
        'category',
        'amount',
        'currency',
        'exchange_rate',
        'reference_type',
        'reference_id',
        'account_code',
        'tax_rate',
        'tax_amount',
        'reconciled',
        'reconciled_at',
        'reconciled_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function reference(): MorphMany
    {
        return $this->morphTo();
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('reconciled', false);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForReference($query, string $referenceType, int $referenceId)
    {
        return $query->where('reference_type', $referenceType)
                   ->where('reference_id', $referenceId);
    }

    public function reconcile(int $userId, ?string $notes = null): void
    {
        $this->update([
            'reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => $userId,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    public function unreconcile(): void
    {
        $this->update([
            'reconciled' => false,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    public function getAmountInBaseCurrency(): float
    {
        return $this->amount * $this->exchange_rate;
    }

    public function getTaxAmountInBaseCurrency(): float
    {
        return ($this->tax_amount ?? 0) * $this->exchange_rate;
    }

    public static function createFromOrder(Order $order): array
    {
        $entries = [];
        $baseCurrency = Currency::getDefault();

        // Sales entry (credit)
        $entries[] = static::create([
            'date' => $order->created_at,
            'description' => "Sales - Order #{$order->order_number}",
            'type' => self::TYPE_CREDIT,
            'category' => self::CATEGORY_SALES,
            'amount' => $order->subtotal,
            'currency' => $order->currency,
            'exchange_rate' => $order->currency !== $baseCurrency->code
                ? Currency::getByCode($order->currency)->exchange_rate
                : 1.0,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'account_code' => '4000', // Sales revenue
        ]);

        // Tax entry (credit)
        if ($order->tax_amount > 0) {
            $entries[] = static::create([
                'date' => $order->created_at,
                'description' => "Tax - Order #{$order->order_number}",
                'type' => self::TYPE_CREDIT,
                'category' => self::CATEGORY_TAX,
                'amount' => $order->tax_amount,
                'currency' => $order->currency,
                'exchange_rate' => $order->currency !== $baseCurrency->code
                    ? Currency::getByCode($order->currency)->exchange_rate
                    : 1.0,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'account_code' => '2200', // Tax payable
            ]);
        }

        // Shipping entry (credit)
        if ($order->shipping_amount > 0) {
            $entries[] = static::create([
                'date' => $order->created_at,
                'description' => "Shipping - Order #{$order->order_number}",
                'type' => self::TYPE_CREDIT,
                'category' => self::CATEGORY_SHIPPING,
                'amount' => $order->shipping_amount,
                'currency' => $order->currency,
                'exchange_rate' => $order->currency !== $baseCurrency->code
                    ? Currency::getByCode($order->currency)->exchange_rate
                    : 1.0,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'account_code' => '4100', // Shipping revenue
            ]);
        }

        // Discount entry (debit)
        if ($order->discount_amount > 0) {
            $entries[] = static::create([
                'date' => $order->created_at,
                'description' => "Discount - Order #{$order->order_number}",
                'type' => self::TYPE_DEBIT,
                'category' => self::CATEGORY_DISCOUNTS,
                'amount' => $order->discount_amount,
                'currency' => $order->currency,
                'exchange_rate' => $order->currency !== $baseCurrency->code
                    ? Currency::getByCode($order->currency)->exchange_rate
                    : 1.0,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'account_code' => '4200', // Sales discounts
            ]);
        }

        return $entries;
    }

    public static function createFromPayment(Payment $payment): array
    {
        $entries = [];
        $baseCurrency = Currency::getDefault();

        if ($payment->status === 'completed') {
            // Cash/Bank entry (debit)
            $entries[] = static::create([
                'date' => $payment->paid_at ?? $payment->created_at,
                'description' => "Payment Received - {$payment->payment_method}",
                'type' => self::TYPE_DEBIT,
                'category' => self::CATEGORY_SALES,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'exchange_rate' => $payment->currency !== $baseCurrency->code
                    ? Currency::getByCode($payment->currency)->exchange_rate
                    : 1.0,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'account_code' => '1000', // Cash/Bank
            ]);

            // Payment processing fees (debit)
            $fees = (new \App\Services\PaymentService())->calculateFees(
                $payment->amount,
                $payment->payment_method,
                $payment->currency
            );

            if ($fees['total'] > 0) {
                $entries[] = static::create([
                    'date' => $payment->paid_at ?? $payment->created_at,
                    'description' => "Payment Processing Fees - {$payment->payment_method}",
                    'type' => self::TYPE_DEBIT,
                    'category' => self::CATEGORY_FEES,
                    'amount' => $fees['total'],
                    'currency' => $payment->currency,
                    'exchange_rate' => $payment->currency !== $baseCurrency->code
                        ? Currency::getByCode($payment->currency)->exchange_rate
                        : 1.0,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'account_code' => '5000', // Bank charges
                ]);
            }
        }

        return $entries;
    }

    public static function createFromRefund(Payment $payment, float $refundAmount): array
    {
        $entries = [];
        $baseCurrency = Currency::getDefault();

        // Refund entry (debit)
        $entries[] = static::create([
            'date' => now(),
            'description' => "Refund - Order #{$payment->order->order_number}",
            'type' => self::TYPE_DEBIT,
            'category' => self::CATEGORY_REFUNDS,
            'amount' => $refundAmount,
            'currency' => $payment->currency,
            'exchange_rate' => $payment->currency !== $baseCurrency->code
                ? Currency::getByCode($payment->currency)->exchange_rate
                : 1.0,
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
            'account_code' => '4000', // Sales returns
        ]);

        return $entries;
    }

    public static function getFinancialReport($startDate, $endDate): array
    {
        $entries = static::forPeriod($startDate, $endDate)->get();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'revenue' => [
                'sales' => 0,
                'shipping' => 0,
                'tax' => 0,
                'total' => 0,
            ],
            'expenses' => [
                'discounts' => 0,
                'refunds' => 0,
                'fees' => 0,
                'total' => 0,
            ],
            'net_income' => 0,
            'entries_count' => $entries->count(),
            'unreconciled_count' => $entries->unreconciled()->count(),
        ];

        foreach ($entries as $entry) {
            $amount = $entry->getAmountInBaseCurrency();

            if ($entry->type === self::TYPE_CREDIT) {
                switch ($entry->category) {
                    case self::CATEGORY_SALES:
                        $report['revenue']['sales'] += $amount;
                        break;
                    case self::CATEGORY_SHIPPING:
                        $report['revenue']['shipping'] += $amount;
                        break;
                    case self::CATEGORY_TAX:
                        $report['revenue']['tax'] += $amount;
                        break;
                }
            } else {
                switch ($entry->category) {
                    case self::CATEGORY_DISCOUNTS:
                        $report['expenses']['discounts'] += $amount;
                        break;
                    case self::CATEGORY_REFUNDS:
                        $report['expenses']['refunds'] += $amount;
                        break;
                    case self::CATEGORY_FEES:
                        $report['expenses']['fees'] += $amount;
                        break;
                }
            }
        }

        $report['revenue']['total'] = $report['revenue']['sales'] +
                                   $report['revenue']['shipping'] +
                                   $report['revenue']['tax'];
        $report['expenses']['total'] = $report['expenses']['discounts'] +
                                    $report['expenses']['refunds'] +
                                    $report['expenses']['fees'];
        $report['net_income'] = $report['revenue']['total'] - $report['expenses']['total'];

        return $report;
    }
}
