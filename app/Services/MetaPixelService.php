<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetaPixelService
{
    private string $pixelId;
    private string $accessToken;
    private string $testEventCode;
    private bool $enabled;

    public function __construct()
    {
        $this->pixelId = config('services.meta.pixel_id');
        $this->accessToken = config('services.meta.access_token');
        $this->testEventCode = config('services.meta.test_event_code');
        $this->enabled = config('services.meta.enabled', false);
    }

    public function trackEvent(string $eventName, array $data = [], ?string $userId = null): bool
    {
        if (!$this->enabled || !$this->pixelId || !$this->accessToken) {
            return false;
        }

        try {
            $payload = [
                'event_name' => $eventName,
                'event_time' => time(),
                'action_source' => 'website',
                'event_source_url' => request()->fullUrl(),
                'user_data' => $this->prepareUserData($data, $userId),
                'custom_data' => $this->prepareCustomData($data),
            ];

            if ($this->testEventCode) {
                $payload['test_event_code'] = $this->testEventCode;
            }

            $response = Http::post("https://graph.facebook.com/v18.0/{$this->pixelId}/events", [
                'data' => [$payload],
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) {
                Log::info('Meta Pixel event tracked', [
                    'event' => $eventName,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('Meta Pixel event tracking failed', [
                    'event' => $eventName,
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Meta Pixel service error', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function trackPageView(?string $userId = null): bool
    {
        return $this->trackEvent('PageView', [], $userId);
    }

    public function trackViewContent(array $product, ?string $userId = null): bool
    {
        $data = [
            'content_ids' => [$product['id']],
            'content_name' => $product['name'],
            'content_type' => 'product',
            'value' => $product['price'],
            'currency' => $product['currency'] ?? 'USD',
            'contents' => [[
                'id' => $product['id'],
                'quantity' => 1,
                'item_price' => $product['price'],
            ]],
        ];

        return $this->trackEvent('ViewContent', $data, $userId);
    }

    public function trackSearch(string $searchString, array $results = [], ?string $userId = null): bool
    {
        $data = [
            'search_string' => $searchString,
            'content_ids' => array_column($results, 'id'),
            'content_type' => 'product_group',
        ];

        return $this->trackEvent('Search', $data, $userId);
    }

    public function trackAddToCart(array $cartItem, float $cartTotal, ?string $userId = null): bool
    {
        $data = [
            'content_ids' => [$cartItem['product_id']],
            'content_name' => $cartItem['product_name'],
            'content_type' => 'product',
            'value' => $cartItem['price'] * $cartItem['quantity'],
            'currency' => $cartItem['currency'] ?? 'USD',
            'contents' => [[
                'id' => $cartItem['product_id'],
                'quantity' => $cartItem['quantity'],
                'item_price' => $cartItem['price'],
            ]],
        ];

        return $this->trackEvent('AddToCart', $data, $userId);
    }

    public function trackPurchase(array $order, ?string $userId = null): bool
    {
        $contents = [];
        foreach ($order['items'] as $item) {
            $contents[] = [
                'id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'item_price' => $item['price'],
            ];
        }

        $data = [
            'content_ids' => array_column($order['items'], 'product_id'),
            'content_type' => 'product',
            'value' => $order['total'],
            'currency' => $order['currency'],
            'transaction_id' => $order['order_number'],
            'contents' => $contents,
            'num_items' => count($order['items']),
        ];

        return $this->trackEvent('Purchase', $data, $userId);
    }

    public function trackInitiateCheckout(array $cart, ?string $userId = null): bool
    {
        $contents = [];
        foreach ($cart['items'] as $item) {
            $contents[] = [
                'id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'item_price' => $item['price'],
            ];
        }

        $data = [
            'content_ids' => array_column($cart['items'], 'product_id'),
            'content_type' => 'product',
            'value' => $cart['total'],
            'currency' => $cart['currency'],
            'contents' => $contents,
            'num_items' => count($cart['items']),
        ];

        return $this->trackEvent('InitiateCheckout', $data, $userId);
    }

    public function trackAddPaymentInfo(?string $userId = null): bool
    {
        return $this->trackEvent('AddPaymentInfo', [], $userId);
    }

    public function trackAddWishlist(array $product, ?string $userId = null): bool
    {
        $data = [
            'content_ids' => [$product['id']],
            'content_name' => $product['name'],
            'content_type' => 'product',
            'value' => $product['price'],
            'currency' => $product['currency'] ?? 'USD',
        ];

        return $this->trackEvent('AddToWishlist', $data, $userId);
    }

    public function trackLead(?string $userId = null): bool
    {
        return $this->trackEvent('Lead', [], $userId);
    }

    public function trackCompleteRegistration(?string $userId = null): bool
    {
        return $this->trackEvent('CompleteRegistration', [], $userId);
    }

    public function trackCustomEvent(string $eventName, array $data = [], ?string $userId = null): bool
    {
        return $this->trackEvent($eventName, $data, $userId);
    }

    private function prepareUserData(array $data, ?string $userId): array
    {
        $userData = [];

        if ($userId) {
            $userData['external_id'] = hash('sha256', $userId);
        }

        // Add user data if available
        if (isset($data['email'])) {
            $userData['em'] = hash('sha256', strtolower(trim($data['email'])));
        }

        if (isset($data['phone'])) {
            $userData['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $data['phone']));
        }

        if (isset($data['first_name'])) {
            $userData['fn'] = hash('sha256', strtolower(trim($data['first_name'])));
        }

        if (isset($data['last_name'])) {
            $userData['ln'] = hash('sha256', strtolower(trim($data['last_name'])));
        }

        if (isset($data['city'])) {
            $userData['ct'] = hash('sha256', strtolower(trim($data['city'])));
        }

        if (isset($data['country'])) {
            $userData['country'] = hash('sha256', strtolower(trim($data['country'])));
        }

        if (isset($data['postal_code'])) {
            $userData['zp'] = hash('sha256', strtolower(trim($data['postal_code'])));
        }

        return $userData;
    }

    private function prepareCustomData(array $data): array
    {
        $customData = [];

        $allowedFields = [
            'content_ids',
            'content_name',
            'content_type',
            'contents',
            'currency',
            'value',
            'search_string',
            'transaction_id',
            'num_items',
            'item_price',
            'quantity',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $customData[$field] = $data[$field];
            }
        }

        return $customData;
    }

    public function getPixelScript(): string
    {
        if (!$this->enabled || !$this->pixelId) {
            return '';
        }

        return "
        <!-- Meta Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{$this->pixelId}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height='1' width='1' style='display:none'
        src='https://www.facebook.com/tr?id={$this->pixelId}&ev=PageView&noscript=1'
        /></noscript>
        <!-- End Meta Pixel Code -->";
    }

    public function getEventScript(string $eventName, array $data = []): string
    {
        if (!$this->enabled || !$this->pixelId) {
            return '';
        }

        $jsonData = json_encode($data);
        return "fbq('track', '{$eventName}', {$jsonData});";
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getPixelId(): string
    {
        return $this->pixelId;
    }

    public function setTestMode(bool $enabled, ?string $testEventCode = null): void
    {
        $this->testEventCode = $enabled ? ($testEventCode ?? 'TEST12345') : null;
    }

    public function getTestEventCode(): ?string
    {
        return $this->testEventCode;
    }
}
