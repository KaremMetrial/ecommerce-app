<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'company' => $this->company,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'state_code' => $this->state_code,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_default' => $this->is_default,
            'address_name' => $this->address_name,
            'notes' => $this->notes,
            'coordinates' => $this->coordinates,
            'metadata' => $this->metadata,
            'formatted_address' => $this->getFormattedAddress(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get formatted address string.
     */
    private function getFormattedAddress(): string
    {
        $parts = [];

        if ($this->company) {
            $parts[] = $this->company;
        }

        $parts[] = trim($this->first_name . ' ' . $this->last_name);
        $parts[] = $this->address_line_1;

        if ($this->address_line_2) {
            $parts[] = $this->address_line_2;
        }

        $cityStateZip = trim($this->city . ', ' . $this->state . ' ' . $this->postal_code);
        $parts[] = $cityStateZip;
        $parts[] = $this->country;

        return implode("\n", array_filter($parts));
    }
}
