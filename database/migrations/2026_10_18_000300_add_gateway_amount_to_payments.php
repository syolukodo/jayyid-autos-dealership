<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * PayPal charges in another currency (naira is not supported), so the
 * payment keeps exactly what was charged and at which rate. The capture is
 * verified against these, and the receipt shows both currencies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'gateway_currency')) {
                $table->char('gateway_currency', 3)->nullable();
                $table->unsignedBigInteger('gateway_amount_minor')->nullable();
                $table->decimal('exchange_rate', 12, 4)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_currency', 'gateway_amount_minor', 'exchange_rate']);
        });
    }
};
