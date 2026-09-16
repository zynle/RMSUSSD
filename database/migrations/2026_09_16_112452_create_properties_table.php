<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('plot_no')->unique();
            $table->string('owner_name');
            $table->enum('rate_type', ['land_rates', 'property_rates'])->default('property_rates');
            $table->decimal('balance_bf', 12, 2)->default(0);
            $table->decimal('charge', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
