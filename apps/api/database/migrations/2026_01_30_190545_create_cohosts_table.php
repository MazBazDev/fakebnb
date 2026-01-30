<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cohost_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_read_conversations')->default(false);
            $table->boolean('can_reply_messages')->default(false);
            $table->boolean('can_edit_listings')->default(false);
            $table->timestamps();

            $table->unique(['host_user_id', 'cohost_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohosts');
    }
};
