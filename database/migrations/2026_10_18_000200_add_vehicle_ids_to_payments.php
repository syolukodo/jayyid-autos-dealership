<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A selection (cart) payment covers several vehicles. Keep their ids so the
 * confirmed payment can reserve exactly those vehicles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'vehicle_ids')) {
                $table->json('vehicle_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('vehicle_ids');
        });
    }
};
