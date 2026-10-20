<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Paystack API. Card payments open Paystack's secure
 * window from an access code; bank transfers use the Charge API so the
 * virtual account renders in our own layout.
 */
class PaystackGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    public function currency(): string
    {
        return config('services.paystack.currency');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function initializeTransaction(array $payload): GatewayResponse
    {
        return $this->request('post', '/transaction/initialize', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function chargeBankTransfer(array $payload): GatewayResponse
    {
        return $this->request('post', '/charge', $payload);
    }

    public function verify(string $reference): GatewayResponse
    {
        return $this->request('get', '/transaction/verify/'.rawurlencode($reference));
    }

    /**
     * Validate the x-paystack-signature header of a webhook delivery.
     */
    public function hasValidSignature(string $payload, string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');

        return $secret !== '' && $signature !== ''
            && hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $path, array $body = []): GatewayResponse
    {
        try {
            $response = $this->client()->{$method}($path, $body);
        } catch (ConnectionException $exception) {
            return GatewayResponse::failed($exception->getMessage());
        }

        $data = $response->json('data');

        return new GatewayResponse(
            ok: $response->json('status') === true,
            message: (string) ($response->json('message') ?? 'Unexpected response from Paystack.'),
            data: is_array($data) ? $data : [],
        );
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30);
    }
}
