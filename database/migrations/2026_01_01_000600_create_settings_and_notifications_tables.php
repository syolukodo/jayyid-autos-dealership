<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('setting_key', 100)->primary();
                $table->text('setting_value')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // In-app alerts for both portals; the row's user_id decides whose bell counts it.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['inquiry', 'payment', 'account', 'system'])->default('system');
                $table->string('title');
                $table->string('body', 500)->nullable();
                $table->string('link')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('read_at')->nullable();

                $table->index(['user_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
    }
};
