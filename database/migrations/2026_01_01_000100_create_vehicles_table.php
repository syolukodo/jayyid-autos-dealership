<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vin_number', 100)->nullable()->unique();
            $table->string('title');
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->enum('condition', ['Brand New', 'Tokunbo']);
            $table->enum('category', ['Sedan', 'SUV', 'Pickup', 'Commercial']);
            $table->decimal('price', 15, 2);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->string('mileage', 100)->nullable();
            $table->enum('transmission', ['Automatic', 'Manual'])->default('Automatic');
            $table->enum('fuel_type', ['Petrol', 'Diesel', 'Hybrid', 'Electric'])->default('Petrol');
            $table->string('engine', 100)->nullable();
            $table->string('drive_type', 50)->nullable();
            $table->string('colour', 50)->nullable();
            $table->integer('seats')->nullable();
            $table->string('location', 120)->nullable();
            $table->text('description')->nullable();
            $table->text('features')->nullable()->comment('JSON array of feature strings');
            $table->enum('status', ['Available', 'Reserved', 'Sold'])->default('Available')->index();
            $table->boolean('is_featured')->default(false);
            $table->string('primary_image')->nullable();
            $table->timestamps();

            $table->index(['make', 'model', 'year']);
            $table->index(['category', 'condition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
