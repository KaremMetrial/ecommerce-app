<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
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
            'type' => ['sometimes', 'required', 'string', 'in:shipping,billing,both'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'is_default' => ['boolean'],
            'address_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'coordinates.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'coordinates.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata.verified' => ['boolean'],
            'metadata.delivery_instructions' => ['nullable', 'string', 'max:500'],
            'metadata.gate_code' => ['nullable', 'string', 'max:20'],
            'metadata.building_type' => ['nullable', 'string', 'in:house,apartment,office,condo,townhouse'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => __('Address type is required'),
            'type.in' => __('Address type must be one of: shipping, billing, both'),
            'first_name.required' => __('First name is required'),
            'last_name.required' => __('Last name is required'),
            'address_line_1.required' => __('Address line 1 is required'),
            'city.required' => __('City is required'),
            'state.required' => __('State is required'),
            'postal_code.required' => __('Postal code is required'),
            'country.required' => __('Country is required'),
            'phone.required' => __('Phone number is required'),
            'email.required' => __('Email is required'),
            'email.email' => __('Please provide a valid email address'),
            'coordinates.latitude.between' => __('Latitude must be between -90 and 90'),
            'coordinates.longitude.between' => __('Longitude must be between -180 and 180'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'address_line_1' => __('address line 1'),
            'address_line_2' => __('address line 2'),
            'postal_code' => __('postal code'),
            'country_code' => __('country code'),
            'state_code' => __('state code'),
            'is_default' => __('default address'),
            'address_name' => __('address name'),
            'coordinates.latitude' => __('latitude'),
            'coordinates.longitude' => __('longitude'),
            'metadata.verified' => __('verified status'),
            'metadata.delivery_instructions' => __('delivery instructions'),
            'metadata.gate_code' => __('gate code'),
            'metadata.building_type' => __('building type'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'coordinates' => $this->input('coordinates', []),
            'metadata' => $this->input('metadata', []),
        ]);
    }
}
