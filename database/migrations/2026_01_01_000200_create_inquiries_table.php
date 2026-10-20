<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries')) {
            return;
        }

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone', 50)->nullable();
            $table->enum('preferred_contact', ['Phone call', 'WhatsApp', 'Email'])->default('Email');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['New', 'Contacted', 'Resolved', 'Closed'])->default('New')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
