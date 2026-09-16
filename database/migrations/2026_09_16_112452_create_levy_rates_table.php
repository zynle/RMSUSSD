<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levy_rates', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('code')->unique();
            $table->string('label');
            $table->string('unit_label')->default('unit');
            $table->decimal('rate', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levy_rates');
    }
};
