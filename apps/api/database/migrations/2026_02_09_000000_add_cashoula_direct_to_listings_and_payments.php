<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('cashoula_direct_enabled')->default(false)->after('price_per_night');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('cashoula_direct_applied')->default(false)->after('payout_amount');
            $table->boolean('cashoula_direct_won')->default(false)->after('cashoula_direct_applied');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cashoula_direct_applied', 'cashoula_direct_won']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('cashoula_direct_enabled');
        });
    }
};
