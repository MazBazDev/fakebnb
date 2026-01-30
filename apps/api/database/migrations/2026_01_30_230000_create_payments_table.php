<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('guest_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_total');
            $table->unsignedInteger('amount_base');
            $table->unsignedInteger('amount_vat');
            $table->unsignedInteger('amount_service');
            $table->unsignedInteger('commission_amount');
            $table->unsignedInteger('payout_amount');
            $table->string('status')->default('requires_authorization');
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
