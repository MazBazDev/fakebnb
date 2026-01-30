<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohosts', function (Blueprint $table) {
            $table->foreignId('listing_id')->nullable()->after('cohost_user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('cohosts', function (Blueprint $table) {
            $table->dropUnique('cohosts_host_user_id_cohost_user_id_unique');
            $table->unique(['listing_id', 'cohost_user_id']);
            $table->index(['host_user_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cohosts', function (Blueprint $table) {
            $table->dropIndex(['host_user_id', 'listing_id']);
            $table->dropUnique(['listing_id', 'cohost_user_id']);
            $table->unique(['host_user_id', 'cohost_user_id']);
        });

        Schema::table('cohosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_id');
        });
    }
};
