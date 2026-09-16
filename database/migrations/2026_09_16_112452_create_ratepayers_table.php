<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratepayers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('council_id')->nullable();
            $table->string('phone')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('constituency')->nullable();
            $table->string('ward')->nullable();
            $table->string('market_name')->nullable();
            $table->string('shop_no')->nullable();
            $table->string('location')->nullable();
            $table->string('pin')->nullable();
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index('council_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratepayers');
    }
};
