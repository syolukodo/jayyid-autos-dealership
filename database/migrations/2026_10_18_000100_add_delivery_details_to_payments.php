<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Doorstep delivery is priced by destination state, so the payment keeps
 * where the vehicles go and what delivery cost. A null fee means pickup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'delivery_fee')) {
                $table->decimal('delivery_fee', 15, 2)->nullable();
                $table->string('delivery_state', 50)->nullable();
                $table->string('delivery_city', 100)->nullable();
                $table->string('delivery_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'delivery_state', 'delivery_city', 'delivery_address']);
        });
    }
};
