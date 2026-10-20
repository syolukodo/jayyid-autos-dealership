<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_schedules')) {
            Schema::create('payment_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('schedule_code', 50)->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->decimal('total_price', 15, 2);
                $table->decimal('deposit', 15, 2)->default(0);
                $table->decimal('deposit_amount', 12, 2)->default(0);
                $table->decimal('total_paid', 15, 2)->default(0);
                $table->decimal('remaining_balance', 15, 2);
                $table->enum('status', ['Active Schedule', 'Completed', 'Overdue'])->default('Active Schedule');
                $table->string('paystack_plan_code', 100)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_instalments')) {
            Schema::create('payment_instalments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schedule_id')->constrained('payment_schedules')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence');
                $table->date('due_date');
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['Paid', 'Due', 'Upcoming', 'Overdue'])->default('Upcoming');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->unique(['schedule_id', 'sequence'], 'payment_instalments_schedule_sequence_unique');
            });
        }

        if (! Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schedule_id')->constrained('payment_schedules')->cascadeOnDelete();
                $table->string('paystack_reference', 100)->unique();
                $table->decimal('amount_paid', 15, 2);
                $table->enum('payment_method', ['Paystack Card', 'Bank Transfer', 'Showroom Cash', 'PayPal'])->default('Paystack Card');
                $table->timestamp('payment_date')->useCurrent();
                $table->string('receipt_url')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_instalments');
        Schema::dropIfExists('payment_schedules');
    }
};
