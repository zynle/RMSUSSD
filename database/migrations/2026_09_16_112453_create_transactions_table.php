<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('session_id')->nullable()->index();
            $table->string('phone')->index();
            $table->string('service_category');
            $table->json('breakdown')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('mobile_money');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('gateway_reference')->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
