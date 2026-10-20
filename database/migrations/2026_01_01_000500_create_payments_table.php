<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('payment_schedules')->nullOnDelete();
            $table->enum('purpose', ['deposit', 'instalment', 'balance', 'cart']);
            $table->enum('method', ['paystack', 'transfer', 'paypal']);
            $table->unsignedBigInteger('amount_kobo');
            $table->decimal('outstanding', 15, 2)->nullable();
            $table->string('vehicle_title');
            $table->decimal('vehicle_price', 15, 2);
            $table->string('customer_name');
            $table->string('customer_email');
            $table->enum('status', ['pending', 'paid', 'failed', 'mismatch'])->default('pending')->index();
            $table->string('gateway_status', 50)->nullable();
            $table->string('gateway_message')->nullable();
            $table->string('gateway_reference', 100)->nullable()->index();
            $table->string('promo_code', 50)->nullable();
            $table->decimal('promo_discount', 15, 2)->nullable();
            $table->string('channel', 50)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
