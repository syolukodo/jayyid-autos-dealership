<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Every migration in this project skips tables that already exist, so a
 * database created from the pre-Laravel SQL dump (database/jayyid_autos.sql)
 * can be adopted as-is by running `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('email')->unique();
                $table->string('phone_number', 50)->nullable();
                $table->string('address', 500)->nullable();
                $table->mediumText('avatar')->nullable();
                // Staff created from the admin panel have no password until they accept their invite.
                $table->string('password')->nullable();
                $table->enum('role', ['customer', 'sales_officer', 'accountant', 'admin'])->default('customer');
                $table->enum('status', ['Active', 'Suspended'])->default('Active');
                $table->timestamp('email_verified_at')->nullable();
                $table->char('invite_token_hash', 64)->nullable()->index();
                $table->dateTime('invite_expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
