<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
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
            'payment_method' => ['required', 'string', 'in:credit_card,debit_card,paypal,stripe,bank_transfer'],
            'gateway_response' => ['nullable', 'array'],
            'gateway_response.status' => ['required_with:gateway_response', 'string'],
            'gateway_response.transaction_id' => ['required_with:gateway_response', 'string'],
            'gateway_response.auth_code' => ['nullable', 'string'],
            'gateway_response.response_code' => ['nullable', 'string'],
            'gateway_response.response_message' => ['nullable', 'string'],
            'gateway_response.avs_result' => ['nullable', 'string'],
            'gateway_response.cvv_result' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.ip_address' => ['nullable', 'ip'],
            'metadata.user_agent' => ['nullable', 'string'],
            'metadata.browser' => ['nullable', 'string'],
            'metadata.device_type' => ['nullable', 'string', 'in:desktop,mobile,tablet'],
            'metadata.payment_gateway' => ['nullable', 'string'],
            'card_number' => ['required_if:payment_method,credit_card,debit_card', 'string', 'digits_between:13,19'],
            'card_expiry_month' => ['required_if:payment_method,credit_card,debit_card', 'integer', 'between:1,12'],
            'card_expiry_year' => ['required_if:payment_method,credit_card,debit_card', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 10)],
            'card_cvv' => ['required_if:payment_method,credit_card,debit_card', 'string', 'digits_between:3,4'],
            'card_holder_name' => ['required_if:payment_method,credit_card,debit_card', 'string', 'max:100'],
            'paypal_email' => ['required_if:payment_method,paypal', 'email'],
            'paypal_payer_id' => ['nullable', 'string'],
            'stripe_payment_method_id' => ['required_if:payment_method,stripe', 'string'],
            'stripe_customer_id' => ['nullable', 'string'],
            'bank_account_number' => ['required_if:payment_method,bank_transfer', 'string', 'min:8', 'max:17'],
            'bank_routing_number' => ['required_if:payment_method,bank_transfer', 'string', 'digits:9'],
            'bank_account_holder_name' => ['required_if:payment_method,bank_transfer', 'string', 'max:100'],
            'bank_account_type' => ['required_if:payment_method,bank_transfer', 'string', 'in:checking,savings'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.address_line_1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'max:100'],
            'save_payment_method' => ['boolean'],
            'set_as_default' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => __('Payment method is required'),
            'payment_method.in' => __('Invalid payment method selected'),
            'card_number.required_if' => __('Card number is required for credit/debit card payments'),
            'card_number.digits_between' => __('Card number must be between 13 and 19 digits'),
            'card_expiry_month.required_if' => __('Card expiry month is required'),
            'card_expiry_month.between' => __('Card expiry month must be between 1 and 12'),
            'card_expiry_year.required_if' => __('Card expiry year is required'),
            'card_expiry_year.min' => __('Card expiry year cannot be in the past'),
            'card_expiry_year.max' => __('Card expiry year is too far in the future'),
            'card_cvv.required_if' => __('CVV is required for credit/debit card payments'),
            'card_cvv.digits_between' => __('CVV must be 3 or 4 digits'),
            'card_holder_name.required_if' => __('Card holder name is required'),
            'paypal_email.required_if' => __('PayPal email is required for PayPal payments'),
            'paypal_email.email' => __('Please provide a valid PayPal email address'),
            'stripe_payment_method_id.required_if' => __('Stripe payment method ID is required for Stripe payments'),
            'bank_account_number.required_if' => __('Bank account number is required for bank transfers'),
            'bank_routing_number.required_if' => __('Bank routing number is required for bank transfers'),
            'bank_routing_number.digits' => __('Bank routing number must be 9 digits'),
            'bank_account_holder_name.required_if' => __('Bank account holder name is required'),
            'bank_account_type.required_if' => __('Bank account type is required'),
            'bank_account_type.in' => __('Bank account type must be checking or savings'),
            'metadata.ip_address.ip' => __('IP address must be valid'),
            'metadata.device_type.in' => __('Device type must be desktop, mobile, or tablet'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'payment_method' => __('payment method'),
            'card_number' => __('card number'),
            'card_expiry_month' => __('expiry month'),
            'card_expiry_year' => __('expiry year'),
            'card_cvv' => __('CVV'),
            'card_holder_name' => __('card holder name'),
            'paypal_email' => __('PayPal email'),
            'paypal_payer_id' => __('PayPal payer ID'),
            'stripe_payment_method_id' => __('Stripe payment method ID'),
            'stripe_customer_id' => __('Stripe customer ID'),
            'bank_account_number' => __('bank account number'),
            'bank_routing_number' => __('bank routing number'),
            'bank_account_holder_name' => __('account holder name'),
            'bank_account_type' => __('account type'),
            'save_payment_method' => __('save payment method'),
            'set_as_default' => __('set as default'),
            'metadata.ip_address' => __('IP address'),
            'metadata.user_agent' => __('user agent'),
            'metadata.browser' => __('browser'),
            'metadata.device_type' => __('device type'),
            'metadata.payment_gateway' => __('payment gateway'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'save_payment_method' => $this->boolean('save_payment_method'),
            'set_as_default' => $this->boolean('set_as_default'),
            'gateway_response' => $this->input('gateway_response', []),
            'metadata' => $this->input('metadata', []),
            'billing_address' => $this->input('billing_address', []),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate card expiry date
            if ($this->filled(['card_expiry_month', 'card_expiry_year'])) {
                $expiryDate = \Carbon\Carbon::createFromDate(
                    $this->input('card_expiry_year'),
                    $this->input('card_expiry_month'),
                    1
                )->endOfMonth();

                if ($expiryDate->isPast()) {
                    $validator->errors()->add('card_expiry_year', __('Card has expired'));
                }
            }

            // Validate PayPal email format
            if ($this->payment_method === 'paypal' && $this->filled('paypal_email')) {
                $paypalEmail = $this->input('paypal_email');
                if (!str_ends_with($paypalEmail, '@paypal.com') && !filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('paypal_email', __('Please provide a valid email address for PayPal'));
                }
            }
        });
    }
}
