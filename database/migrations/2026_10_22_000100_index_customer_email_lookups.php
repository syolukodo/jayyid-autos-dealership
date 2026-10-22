<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Customers' payments and inquiries are also matched on the email used at
 * checkout or on the contact form (every portal page, and once per row on
 * Admin > Users). Without these indexes each lookup scans the whole table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasIndex('payments', ['customer_email'])) {
                $table->index('customer_email');
            }
        });

        Schema::table('inquiries', function (Blueprint $table) {
            if (! Schema::hasIndex('inquiries', ['client_email'])) {
                $table->index('client_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex(['customer_email']));
        Schema::table('inquiries', fn (Blueprint $table) => $table->dropIndex(['client_email']));
    }
};
