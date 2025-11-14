<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:' . $this->payment->amount],
            'reason' => ['required', 'string', 'max:500'],
            'refund_type' => ['sometimes', 'string', 'in:full,partial'],
            'notify_customer' => ['boolean'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'gateway_response' => ['nullable', 'array'],
            'gateway_response.refund_id' => ['nullable', 'string'],
            'gateway_response.status' => ['nullable', 'string'],
            'gateway_response.response_code' => ['nullable', 'string'],
            'gateway_response.response_message' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.refunded_by' => ['nullable', 'string'],
            'metadata.refund_method' => ['nullable', 'string', 'in:automatic,manual'],
            'metadata.processing_time' => ['nullable', 'integer', 'min:1'],
            'metadata.customer_notified' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => __('Refund amount is required'),
            'amount.numeric' => __('Refund amount must be a number'),
            'amount.min' => __('Refund amount must be at least 0.01'),
            'amount.max' => __('Refund amount cannot exceed the original payment amount'),
            'reason.required' => __('Refund reason is required'),
            'reason.max' => __('Refund reason cannot exceed 500 characters'),
            'refund_type.in' => __('Refund type must be either full or partial'),
            'internal_notes.max' => __('Internal notes cannot exceed 1000 characters'),
            'metadata.refund_method.in' => __('Refund method must be either automatic or manual'),
            'metadata.processing_time.min' => __('Processing time must be at least 1 minute'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'amount' => __('refund amount'),
            'reason' => __('refund reason'),
            'refund_type' => __('refund type'),
            'notify_customer' => __('notify customer'),
            'internal_notes' => __('internal notes'),
            'gateway_response.refund_id' => __('gateway refund ID'),
            'gateway_response.status' => __('gateway status'),
            'gateway_response.response_code' => __('gateway response code'),
            'gateway_response.response_message' => __('gateway response message'),
            'metadata.refunded_by' => __('refunded by'),
            'metadata.refund_method' => __('refund method'),
            'metadata.processing_time' => __('processing time'),
            'metadata.customer_notified' => __('customer notified'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notify_customer' => $this->boolean('notify_customer'),
            'gateway_response' => $this->input('gateway_response', []),
            'metadata' => $this->input('metadata', []),
        ]);

        // Set default refund type based on amount
        if ($this->filled('amount')) {
            $paymentAmount = $this->payment->amount;
            $refundAmount = $this->input('amount');

            if (abs($refundAmount - $paymentAmount) < 0.01) {
                $this->merge([
                    'refund_type' => 'full',
                    'amount' => $paymentAmount,
                ]);
            } else {
                $this->merge([
                    'refund_type' => 'partial',
                ]);
            }
        } else {
            // Default to full refund if no amount specified
            $this->merge([
                'amount' => $this->payment->amount,
                'refund_type' => 'full',
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if payment can be refunded
            if ($this->payment->status !== 'completed') {
                $validator->errors()->add('payment', __('Only completed payments can be refunded'));
            }

            // Check if payment has already been refunded
            if ($this->payment->refunded_at) {
                $validator->errors()->add('payment', __('Payment has already been refunded'));
            }

            // Check if refund amount exceeds available amount
            if ($this->payment->refund_amount) {
                $availableAmount = $this->payment->amount - $this->payment->refund_amount;
                if ($this->input('amount') > $availableAmount) {
                    $validator->errors()->add('amount', __('Refund amount cannot exceed available amount of :amount', [
                        'amount' => number_format($availableAmount, 2),
                    ]));
                }
            }

            // Validate refund reason for partial refunds
            if ($this->input('refund_type') === 'partial' && empty($this->input('reason'))) {
                $validator->errors()->add('reason', __('Refund reason is required for partial refunds'));
            }

            // Check business rules for refund timing
            if ($this->payment->paid_at) {
                $daysSincePayment = $this->payment->paid_at->diffInDays(now());
                if ($daysSincePayment > 180) { // 6 months
                    $validator->errors()->add('payment', __('Refunds are not available for payments older than 6 months'));
                }
            }
        });
    }
}
