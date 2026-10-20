<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('title', 100);
                $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
                $table->decimal('discount_value', 12, 2);
                $table->decimal('min_order_amount', 12, 2)->default(0);
                $table->enum('target_type', ['all', 'brand', 'category', 'specific_vehicles'])->default('all')->index();
                $table->string('target_brand', 50)->nullable();
                $table->string('target_category', 50)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('times_used')->default(0);
                $table->integer('usage_limit_per_user')->default(1);
                $table->dateTime('start_date')->nullable();
                $table->dateTime('expiry_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('promo_code_vehicles')) {
            Schema::create('promo_code_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

                $table->unique(['promo_code_id', 'vehicle_id'], 'promo_code_vehicles_promo_vehicle_unique');
            });
        }

        if (! Schema::hasTable('promo_code_redemptions')) {
            Schema::create('promo_code_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->decimal('discount_amount', 12, 2);
                $table->timestamp('redeemed_at')->nullable()->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_redemptions');
        Schema::dropIfExists('promo_code_vehicles');
        Schema::dropIfExists('promo_codes');
    }
};
