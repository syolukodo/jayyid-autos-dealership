<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach a real payment gateway or mail API.
        Http::preventStrayRequests();
        config([
            'services.paystack.secret_key' => null,
            'services.paypal.client_id' => null,
            'services.paypal.secret' => null,
        ]);
    }

    protected function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function staff(UserRole $role = UserRole::Admin, array $attributes = []): User
    {
        return User::factory()->staff($role)->create($attributes);
    }
}
