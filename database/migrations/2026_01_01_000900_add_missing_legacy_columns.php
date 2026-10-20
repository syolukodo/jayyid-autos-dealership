<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Databases created from an older pre-Laravel dump can predate columns that
 * the old database/migrate.php used to add. The create migrations skip
 * existing tables, so this fills in whatever is still missing.
 */
return new class extends Migration
{
    /**
     * table => [column => definition]
     *
     * @var array<string, array<string, Closure(Blueprint): mixed>>
     */
    private function columns(): array
    {
        return [
            'payments' => [
                'gateway_reference' => fn (Blueprint $t) => $t->string('gateway_reference', 100)->nullable()->index()->after('gateway_message'),
                'promo_code' => fn (Blueprint $t) => $t->string('promo_code', 50)->nullable()->after('gateway_reference'),
                'promo_discount' => fn (Blueprint $t) => $t->decimal('promo_discount', 15, 2)->nullable()->after('promo_code'),
                'channel' => fn (Blueprint $t) => $t->string('channel', 50)->nullable(),
            ],
            'payment_schedules' => [
                'deposit' => fn (Blueprint $t) => $t->decimal('deposit', 15, 2)->default(0)->after('total_price'),
                'deposit_amount' => fn (Blueprint $t) => $t->decimal('deposit_amount', 12, 2)->default(0)->after('deposit'),
            ],
            'users' => [
                'address' => fn (Blueprint $t) => $t->string('address', 500)->nullable()->after('phone_number'),
                'avatar' => fn (Blueprint $t) => $t->mediumText('avatar')->nullable()->after('address'),
                'invite_token_hash' => fn (Blueprint $t) => $t->char('invite_token_hash', 64)->nullable()->index(),
                'invite_expires_at' => fn (Blueprint $t) => $t->dateTime('invite_expires_at')->nullable(),
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $table => $columns) {
            $missing = array_filter($columns, fn (string $column) => ! Schema::hasColumn($table, $column), ARRAY_FILTER_USE_KEY);
            if ($missing === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($missing) {
                foreach ($missing as $define) {
                    $define($blueprint);
                }
            });
        }
    }

    public function down(): void
    {
        // These columns belong to the base schema; never drop them.
    }
};
