<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The pre-Laravel schema left 'PayPal' out of payment_transactions.payment_method,
 * so applying a settled PayPal instalment failed on MySQL. Fresh databases get
 * the full list from the create migration; this brings adopted ones in line.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE `payment_transactions` MODIFY `payment_method`
             ENUM('Paystack Card', 'Bank Transfer', 'Showroom Cash', 'PayPal') NOT NULL DEFAULT 'Paystack Card'"
        );
    }

    public function down(): void
    {
        // Narrowing the enum could fail on rows that already use 'PayPal'; leave it widened.
    }
};
