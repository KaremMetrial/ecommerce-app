<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): JsonResource
    {
        $user = Auth::user();
        $cacheKey = "user_addresses_{$user->id}";

        $addresses = $this->cacheService->remember($cacheKey, now()->addHours(2), function () use ($user) {
            return $user->addresses()
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return AddressResource::collection($addresses);
    }

    /**
     * Store a newly created address.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = Auth::user();

        // If this is set as default, unset other default addresses
        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($request->validated());

        // Clear user addresses cache
        $this->cacheService->forget("user_addresses_{$user->id}");

        return $this->successResponse(
            new AddressResource($address),
            __('Address created successfully'),
            201
        );
    }

    /**
     * Display the specified address.
     */
    public function show(Address $address): JsonResponse
    {
        $this->authorize('view', $address);

        $cacheKey = "address_{$address->id}";

        $cachedAddress = $this->cacheService->remember($cacheKey, now()->addHour(), function () use ($address) {
            return $address->load(['user']);
        });

        return $this->successResponse(
            new AddressResource($cachedAddress)
        );
    }

    /**
     * Update the specified address.
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        $user = Auth::user();

        // If this is set as default, unset other default addresses
        if ($request->boolean('is_default')) {
            $user->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($request->validated());

        // Clear caches
        $this->cacheService->forget("user_addresses_{$user->id}");
        $this->cacheService->forget("address_{$address->id}");

        return $this->successResponse(
            new AddressResource($address),
            __('Address updated successfully')
        );
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);
        $user = Auth::user();

        // Don't allow deletion of default address if user has other addresses
        if ($address->is_default && $user->addresses()->count() > 1) {
            return $this->errorResponse(
                __('Cannot delete default address. Please set another address as default first.'),
                422
            );
        }

        $address->delete();

        // Clear caches
        $this->cacheService->forget("user_addresses_{$user->id}");
        $this->cacheService->forget("address_{$address->id}");

        return $this->successResponse(
            null,
            __('Address deleted successfully')
        );
    }

    /**
     * Set an address as the default address.
     */
    public function setDefault(Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        $user = Auth::user();

        // Unset all other default addresses
        $user->addresses()->update(['is_default' => false]);

        // Set this address as default
        $address->update(['is_default' => true]);

        // Clear caches
        $this->cacheService->forget("user_addresses_{$user->id}");
        $this->cacheService->forget("address_{$address->id}");

        return $this->successResponse(
            new AddressResource($address),
            __('Default address updated successfully')
        );
    }

    /**
     * Get addresses by type (shipping, billing, both).
     */
    public function getByType(Request $request, string $type): JsonResource
    {
        $user = Auth::user();

        if (!in_array($type, ['shipping', 'billing', 'both'])) {
            return $this->errorResponse(__('Invalid address type'), 422);
        }

        $cacheKey = "user_addresses_{$user->id}_{$type}";

        $addresses = $this->cacheService->remember($cacheKey, now()->addHours(2), function () use ($user, $type) {
            return $user->addresses()
                ->where('type', $type)
                ->orWhere('type', 'both')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return AddressResource::collection($addresses);
    }

    /**
     * Validate an address using external service.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
        ]);

        // This would integrate with an address validation service
        // For now, we'll simulate validation
        $isValid = true;
        $suggestions = [];

        // Simulate validation logic
        if (strlen($request->postal_code) < 3) {
            $isValid = false;
            $suggestions[] = [
                'field' => 'postal_code',
                'message' => __('Postal code appears to be too short'),
                'suggestion' => 'Please enter a valid postal code',
            ];
        }

        return $this->successResponse([
            'is_valid' => $isValid,
            'suggestions' => $suggestions,
            'normalized_address' => $isValid ? [
                'address_line_1' => strtoupper($request->address_line_1),
                'city' => ucwords(strtolower($request->city)),
                'state' => strtoupper($request->state),
                'postal_code' => strtoupper($request->postal_code),
                'country' => ucwords(strtolower($request->country)),
            ] : null,
        ]);
    }

    /**
     * Get countries list for address forms.
     */
    public function countries(): JsonResponse
    {
        $cacheKey = 'countries_list';

        $countries = $this->cacheService->remember($cacheKey, now()->addDays(7), function () {
            return [
                ['code' => 'US', 'name' => 'United States'],
                ['code' => 'CA', 'name' => 'Canada'],
                ['code' => 'GB', 'name' => 'United Kingdom'],
                ['code' => 'AU', 'name' => 'Australia'],
                ['code' => 'DE', 'name' => 'Germany'],
                ['code' => 'FR', 'name' => 'France'],
                ['code' => 'IT', 'name' => 'Italy'],
                ['code' => 'ES', 'name' => 'Spain'],
                ['code' => 'JP', 'name' => 'Japan'],
                ['code' => 'CN', 'name' => 'China'],
                ['code' => 'IN', 'name' => 'India'],
                ['code' => 'BR', 'name' => 'Brazil'],
                ['code' => 'MX', 'name' => 'Mexico'],
                ['code' => 'RU', 'name' => 'Russia'],
                ['code' => 'KR', 'name' => 'South Korea'],
                ['code' => 'NL', 'name' => 'Netherlands'],
                ['code' => 'SE', 'name' => 'Sweden'],
                ['code' => 'NO', 'name' => 'Norway'],
                ['code' => 'DK', 'name' => 'Denmark'],
                ['code' => 'FI', 'name' => 'Finland'],
            ];
        });

        return $this->successResponse($countries);
    }

    /**
     * Get states/provinces for a country.
     */
    public function states(Request $request, string $countryCode): JsonResponse
    {
        $cacheKey = "states_{$countryCode}";

        $states = $this->cacheService->remember($cacheKey, now()->addDays(7), function () use ($countryCode) {
            return match ($countryCode) {
                'US' => [
                    ['code' => 'AL', 'name' => 'Alabama'],
                    ['code' => 'CA', 'name' => 'California'],
                    ['code' => 'FL', 'name' => 'Florida'],
                    ['code' => 'NY', 'name' => 'New York'],
                    ['code' => 'TX', 'name' => 'Texas'],
                    ['code' => 'WA', 'name' => 'Washington'],
                    // Add more states as needed
                ],
                'CA' => [
                    ['code' => 'ON', 'name' => 'Ontario'],
                    ['code' => 'QC', 'name' => 'Quebec'],
                    ['code' => 'BC', 'name' => 'British Columbia'],
                    ['code' => 'AB', 'name' => 'Alberta'],
                    // Add more provinces as needed
                ],
                default => [],
            };
        });

        return $this->successResponse($states);
    }
}
