<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    private array $carriers = [
        'ups' => [
            'name' => 'UPS',
            'api_url' => 'https://onlinetools.ups.com/ship/v1/shipments',
            'tracking_url' => 'https://www.ups.com/track?tracknum={tracking_number}',
        ],
        'fedex' => [
            'name' => 'FedEx',
            'api_url' => 'https://ws.fedex.com:443/web-services',
            'tracking_url' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}',
        ],
        'dhl' => [
            'name' => 'DHL',
            'api_url' => 'https://xmlpi-ea.dhl.com/XMLShippingServlet',
            'tracking_url' => 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}',
        ],
        'usps' => [
            'name' => 'USPS',
            'api_url' => 'https://secure.shippingapis.com/ShippingAPI.dll',
            'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_number}',
        ],
    ];

    public function getAvailableShippingMethods(array $location, float $weight = null, float $subtotal = null): array
    {
        $zone = ShippingZone::findZoneForLocation($location);

        if (!$zone) {
            return [];
        }

        return $zone->getAvailableMethods($weight, $subtotal);
    }

    public function calculateShippingCost(string $methodId, array $location, float $weight = null, float $subtotal = null, int $itemCount = 0): ?float
    {
        $method = ShippingMethod::find($methodId);

        if (!$method || !$method->is_active) {
            return null;
        }

        // Check if method covers the location
        $zone = $method->shippingZone;
        if (!$zone->coversLocation($location)) {
            return null;
        }

        // Check for specific rates first
        $specificRate = ShippingRate::findRate([
            'country_code' => $location['country_code'],
            'state' => $location['state'] ?? null,
            'postal_code' => $location['postal_code'] ?? null,
            'weight' => $weight ?? 0,
            'subtotal' => $subtotal ?? 0,
        ]);

        if ($specificRate) {
            return $specificRate->cost;
        }

        // Use method's calculation
        return $method->calculateCost($weight, $subtotal, $itemCount);
    }

    public function createShipment(Order $order, string $methodId, array $packages = []): array
    {
        $method = ShippingMethod::find($methodId);

        if (!$method) {
            throw new \Exception('Invalid shipping method');
        }

        $shipmentData = [
            'order_id' => $order->id,
            'method_id' => $methodId,
            'carrier' => $method->carrier,
            'service' => $method->carrier_service,
            'packages' => $packages,
            'ship_from' => $this->getShipFromAddress(),
            'ship_to' => $order->shipping_address,
        ];

        if ($method->carrier && isset($this->carriers[$method->carrier])) {
            return $this->createCarrierShipment($method->carrier, $shipmentData);
        }

        return $this->createGenericShipment($shipmentData);
    }

    private function createCarrierShipment(string $carrier, array $data): array
    {
        switch ($carrier) {
            case 'ups':
                return $this->createUPSShipment($data);
            case 'fedex':
                return $this->createFedExShipment($data);
            case 'dhl':
                return $this->createDHLShipment($data);
            case 'usps':
                return $this->createUSPSShipment($data);
            default:
                return $this->createGenericShipment($data);
        }
    }

    private function createUPSShipment(array $data): array
    {
        try {
            $payload = [
                'ShipmentRequest' => [
                    'Request' => [
                        'RequestOption' => 'nonvalidate',
                        'TransactionReference' => [
                            'CustomerContext' => 'E-Commerce Order',
                        ],
                    ],
                    'Shipment' => [
                        'Description' => 'E-Commerce Order',
                        'Shipper' => [
                            'Name' => $data['ship_from']['company'] ?? $data['ship_from']['name'],
                            'AttentionName' => $data['ship_from']['name'],
                            'Phone' => [
                                'Number' => $data['ship_from']['phone'],
                            ],
                            'Address' => [
                                'AddressLine' => [
                                    $data['ship_from']['address_line_1'],
                                    $data['ship_from']['address_line_2'] ?? '',
                                ],
                                'City' => $data['ship_from']['city'],
                                'StateProvinceCode' => $data['ship_from']['state'],
                                'PostalCode' => $data['ship_from']['postal_code'],
                                'CountryCode' => $data['ship_from']['country'],
                            ],
                        ],
                        'ShipTo' => [
                            'Name' => $data['ship_to']['first_name'] . ' ' . $data['ship_to']['last_name'],
                            'AttentionName' => $data['ship_to']['first_name'] . ' ' . $data['ship_to']['last_name'],
                            'Phone' => [
                                'Number' => $data['ship_to']['phone'] ?? '',
                            ],
                            'Address' => [
                                'AddressLine' => [
                                    $data['ship_to']['address_line_1'],
                                    $data['ship_to']['address_line_2'] ?? '',
                                ],
                                'City' => $data['ship_to']['city'],
                                'StateProvinceCode' => $data['ship_to']['state'],
                                'PostalCode' => $data['ship_to']['postal_code'],
                                'CountryCode' => $data['ship_to']['country'],
                            ],
                        ],
                        'PaymentInformation' => [
                            'Prepaid' => [
                                'BillShipper' => [
                                    'AccountNumber' => config('shipping.ups.account_number'),
                                ],
                            ],
                        ],
                        'Service' => [
                            'Code' => $this->mapUPSServiceCode($data['service']),
                            'Description' => $data['service'],
                        ],
                        'Package' => $this->formatUPSPackages($data['packages']),
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'AccessLicenseNumber' => config('shipping.ups.access_key'),
                'Username' => config('shipping.ups.username'),
                'Password' => config('shipping.ups.password'),
            ])->post($this->carriers['ups']['api_url'], $payload);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'tracking_number' => $result['ShipmentResults']['ShipmentIdentificationNumber'] ?? null,
                    'label_url' => $result['ShipmentResults']['PackageResults']['ShippingLabel']['GraphicImage'] ?? null,
                    'cost' => $result['ShipmentResults']['ShipmentCharges']['TotalCharges']['MonetaryValue'] ?? 0,
                    'carrier_response' => $result,
                ];
            } else {
                Log::error('UPS shipment creation failed', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to create UPS shipment',
                ];
            }
        } catch (\Exception $e) {
            Log::error('UPS shipment service error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createFedExShipment(array $data): array
    {
        try {
            // FedEx API implementation would go here
            // For now, return a mock response
            return [
                'success' => true,
                'tracking_number' => 'FDX' . time(),
                'label_url' => null,
                'cost' => 0,
                'carrier_response' => ['mock' => true],
            ];
        } catch (\Exception $e) {
            Log::error('FedEx shipment service error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createDHLShipment(array $data): array
    {
        try {
            // DHL API implementation would go here
            // For now, return a mock response
            return [
                'success' => true,
                'tracking_number' => 'DHL' . time(),
                'label_url' => null,
                'cost' => 0,
                'carrier_response' => ['mock' => true],
            ];
        } catch (\Exception $e) {
            Log::error('DHL shipment service error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createUSPSShipment(array $data): array
    {
        try {
            // USPS API implementation would go here
            // For now, return a mock response
            return [
                'success' => true,
                'tracking_number' => 'USPS' . time(),
                'label_url' => null,
                'cost' => 0,
                'carrier_response' => ['mock' => true],
            ];
        } catch (\Exception $e) {
            Log::error('USPS shipment service error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createGenericShipment(array $data): array
    {
        return [
            'success' => true,
            'tracking_number' => 'GEN' . time(),
            'label_url' => null,
            'cost' => 0,
            'carrier_response' => ['generic' => true],
        ];
    }

    public function trackShipment(string $carrier, string $trackingNumber): array
    {
        if (!isset($this->carriers[$carrier])) {
            return [
                'success' => false,
                'error' => 'Unsupported carrier',
            ];
        }

        try {
            switch ($carrier) {
                case 'ups':
                    return $this->trackUPS($trackingNumber);
                case 'fedex':
                    return $this->trackFedEx($trackingNumber);
                case 'dhl':
                    return $this->trackDHL($trackingNumber);
                case 'usps':
                    return $this->trackUSPS($trackingNumber);
                default:
                    return [
                        'success' => false,
                        'error' => 'Tracking not available for this carrier',
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Shipment tracking error', [
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function trackUPS(string $trackingNumber): array
    {
        $response = Http::get("https://onlinetools.ups.com/track/v1/details/{$trackingNumber}", [
            'locale' => 'en_US',
            'returnSignature' => 'false',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status' => $data['trackResponse']['shipment'][0]['package'][0]['currentStatus']['description'] ?? 'Unknown',
                'events' => $this->formatUPSEvents($data['trackResponse']['shipment'][0]['package'][0]['activity'] ?? []),
                'estimated_delivery' => $data['trackResponse']['shipment'][0]['package'][0]['estimatedTimeOfArrival'] ?? null,
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to track UPS shipment',
            ];
        }
    }

    private function trackFedEx(string $trackingNumber): array
    {
        // FedEx tracking implementation would go here
        return [
            'success' => true,
            'status' => 'In Transit',
            'events' => [],
            'estimated_delivery' => null,
        ];
    }

    private function trackDHL(string $trackingNumber): array
    {
        // DHL tracking implementation would go here
        return [
            'success' => true,
            'status' => 'In Transit',
            'events' => [],
            'estimated_delivery' => null,
        ];
    }

    private function trackUSPS(string $trackingNumber): array
    {
        // USPS tracking implementation would go here
        return [
            'success' => true,
            'status' => 'In Transit',
            'events' => [],
            'estimated_delivery' => null,
        ];
    }

    private function getShipFromAddress(): array
    {
        return [
            'name' => config('shipping.from.name'),
            'company' => config('shipping.from.company'),
            'address_line_1' => config('shipping.from.address_line_1'),
            'address_line_2' => config('shipping.from.address_line_2'),
            'city' => config('shipping.from.city'),
            'state' => config('shipping.from.state'),
            'postal_code' => config('shipping.from.postal_code'),
            'country' => config('shipping.from.country'),
            'phone' => config('shipping.from.phone'),
            'email' => config('shipping.from.email'),
        ];
    }

    private function mapUPSServiceCode(string $service): string
    {
        $serviceMap = [
            'ground' => '03',
            '2nd_day_air' => '02',
            'next_day_air' => '01',
            'worldwide_expedited' => '08',
            'worldwide_express' => '07',
        ];

        return $serviceMap[$service] ?? '03';
    }

    private function formatUPSPackages(array $packages): array
    {
        return array_map(function ($package) {
            return [
                'Packaging' => [
                    'Code' => '02', // Customer supplied packaging
                ],
                'Description' => 'E-Commerce Package',
                'Dimensions' => [
                    'UnitOfMeasurement' => [
                        'Code' => 'IN',
                    ],
                    'Length' => $package['length'] ?? 10,
                    'Width' => $package['width'] ?? 10,
                    'Height' => $package['height'] ?? 10,
                ],
                'PackageWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => 'LBS',
                    ],
                    'Weight' => $package['weight'] ?? 1,
                ],
            ];
        }, $packages);
    }

    private function formatUPSEvents(array $activities): array
    {
        return array_map(function ($activity) {
            return [
                'date' => $activity['date'] ?? null,
                'time' => $activity['time'] ?? null,
                'location' => $activity['location']['address']['city'] . ', ' . $activity['location']['address']['stateProvinceCode'] ?? 'Unknown',
                'description' => $activity['status']['description'] ?? 'Unknown',
                'code' => $activity['status']['code'] ?? null,
            ];
        }, $activities);
    }

    public function getTrackingUrl(string $carrier, string $trackingNumber): string
    {
        if (!isset($this->carriers[$carrier])) {
            return '#';
        }

        return str_replace('{tracking_number}', $trackingNumber, $this->carriers[$carrier]['tracking_url']);
    }

    public function getSupportedCarriers(): array
    {
        return array_keys($this->carriers);
    }

    public function validateAddress(array $address): array
    {
        // Address validation implementation would go here
        // For now, return the address as valid
        return [
            'valid' => true,
            'address' => $address,
            'suggestions' => [],
        ];
    }

    public function estimateDeliveryTime(string $carrier, string $service, array $from, array $to): array
    {
        // Delivery time estimation implementation would go here
        return [
            'business_days' => 3,
            'calendar_days' => 5,
            'estimated_date' => now()->addDays(5)->format('Y-m-d'),
        ];
    }
}
