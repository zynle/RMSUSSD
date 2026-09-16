<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_levies', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('business_name');
            $table->enum('status', ['new', 'existing'])->default('new');
            $table->unsignedInteger('employees_count')->default(0);
            $table->string('trading_centre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_levies');
    }
};
