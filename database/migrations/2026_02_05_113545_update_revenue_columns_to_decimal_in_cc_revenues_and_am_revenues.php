<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update cc_revenues table
        Schema::table('cc_revenues', function (Blueprint $table) {
            $table->decimal('real_revenue_sold', 20, 8)->change();
            $table->decimal('target_revenue_sold', 20, 8)->change();
            $table->decimal('real_revenue_bill', 20, 8)->change();
        });

        // Update am_revenues table
        Schema::table('am_revenues', function (Blueprint $table) {
            $table->decimal('real_revenue', 20, 8)->change();
            $table->decimal('target_revenue', 20, 8)->change();
            $table->decimal('proporsi', 9, 8)->change(); // FIX: 9 >= 8 (max value 1.00000000)
        });

        // Update witel_target_revenues table
        Schema::table('witel_target_revenues', function (Blueprint $table) {
            $table->decimal('target_revenue_bill', 20, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback cc_revenues
        Schema::table('cc_revenues', function (Blueprint $table) {
            $table->bigInteger('real_revenue_sold')->change();
            $table->bigInteger('target_revenue_sold')->change();
            $table->bigInteger('real_revenue_bill')->change();
        });

        // Rollback am_revenues
        Schema::table('am_revenues', function (Blueprint $table) {
            $table->bigInteger('real_revenue')->change();
            $table->bigInteger('target_revenue')->change();
            $table->decimal('proporsi', 5, 2)->change();
        });

        // Rollback witel_target_revenues
        Schema::table('witel_target_revenues', function (Blueprint $table) {
            $table->bigInteger('target_revenue_bill')->change();
        });
    }
};