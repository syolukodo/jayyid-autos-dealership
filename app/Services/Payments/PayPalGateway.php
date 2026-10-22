<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the PayPal Orders API. The buyer approves on PayPal, then
 * the order is captured on the return trip (or by the webhook).
 */
class PayPalGateway
{
    private ?string $accessToken = null;

    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function currency(): string
    {
        return config('services.paypal.currency');
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function createOrder(array $order): GatewayResponse
    {
        return $this->request('post', '/v2/checkout/orders', $order);
    }

    /**
     * Capturing is idempotent: replaying it on a COMPLETED order returns the same order.
     */
    public function captureOrder(string $orderId): GatewayResponse
    {
        return $this->request('post', '/v2/checkout/orders/'.rawurlencode($orderId).'/capture', []);
    }

    /**
     * Confirm PayPal really signed a webhook delivery.
     *
     * @param  array<string, string>  $headers  auth_algo, cert_url, transmission_id, transmission_sig, transmission_time
     */
    public function hasValidSignature(string $payload, array $headers): bool
    {
        $webhookId = (string) config('services.paypal.webhook_id');
        if ($webhookId === '' || ! $this->isConfigured()) {
            Log::warning('PayPal webhook rejected: PAYPAL_WEBHOOK_ID is not configured.');

            return false;
        }

        // Turn away unsigned deliveries before calling PayPal, so junk POSTs cannot tie up workers on outbound requests.
        if (in_array('', $headers, true)) {
            return false;
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || $event === []) {
            return false;
        }

        $verify = $this->request('post', '/v1/notifications/verify-webhook-signature', $headers + [
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        return $verify->ok && strtoupper((string) ($verify->data['verification_status'] ?? '')) === 'SUCCESS';
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $path, array $body): GatewayResponse
    {
        $token = $this->token();
        if ($token === '') {
            return GatewayResponse::failed('We could not authenticate with PayPal.');
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->{$method}($path, $body === [] ? (object) [] : $body);
        } catch (ConnectionException $exception) {
            return GatewayResponse::failed($exception->getMessage());
        }

        $data = $response->json() ?? [];
        if ($response->successful()) {
            return new GatewayResponse(true, '', is_array($data) ? $data : []);
        }

        $detail = $data['details'][0] ?? [];
        $message = (string) ($detail['description'] ?? $detail['issue'] ?? $data['message'] ?? $data['error_description'] ?? '');

        return new GatewayResponse(false, $message ?: 'PayPal did not accept the request (HTTP '.$response->status().').', is_array($data) ? $data : []);
    }

    private function token(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }
        if (! $this->isConfigured()) {
            return '';
        }

        // Tokens last hours; reuse one across requests instead of an extra round trip per PayPal call.
        $cacheKey = 'paypal.token.'.hash('sha256', config('services.paypal.mode').'|'.config('services.paypal.client_id'));
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $this->accessToken = $cached;
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
                ->acceptJson()
                ->asForm()
                ->connectTimeout(10)
                ->timeout(30)
                ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $exception) {
            Log::error('PayPal token request failed', ['exception' => $exception]);

            return '';
        }

        $token = (string) $response->json('access_token', '');
        if ($token === '') {
            Log::error('PayPal token request failed', ['error' => $response->json('error_description') ?? $response->json('error')]);
        } else {
            Cache::put($cacheKey, $token, max(60, (int) $response->json('expires_in', 0) - 300));
        }

        return $this->accessToken = $token;
    }

    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
