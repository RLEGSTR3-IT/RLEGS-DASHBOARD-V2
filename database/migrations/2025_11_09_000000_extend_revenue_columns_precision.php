<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Extend Revenue Columns Precision (UPDATED)
 *
 * PURPOSE: Support unlimited digits for revenue values
 * - FROM: DECIMAL(15,2) = max 999 billion
 * - TO: DECIMAL(25,2) = max 999 quadrillion (update dari 20 ke 25 untuk future-proof)
 *
 * AFFECTED TABLES:
 * - cc_revenues: target_revenue, real_revenue (OLD)
 *                + real_revenue_sold, real_revenue_bill (NEW - akan ditambah migration lain)
 *                + target_revenue_sold, target_revenue_bill (NEW - akan ditambah migration lain)
 * - am_revenues: target_revenue, real_revenue
 *
 * NOTE: Kolom baru (sold/bill) akan ditambahkan oleh migration restructure,
 *       migration ini hanya extend precision kolom yang sudah ada
 *
 * DATE: 2025-11-09 (Updated 2026-02-03)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 Starting migration: Extend revenue columns precision');

            // ========================================
            // STEP 1: Check current column types
            // ========================================
            
            $ccRevenueColumns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'cc_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue')
            ");

            $amRevenueColumns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'am_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue')
            ");

            Log::info('📋 Current column types (BEFORE)', [
                'cc_revenues' => $ccRevenueColumns,
                'am_revenues' => $amRevenueColumns
            ]);

            // ========================================
            // STEP 2: Modify CC Revenues table (OLD COLUMNS)
            // ========================================
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                // Extend old columns (tetap dipertahankan untuk backward compatibility)
                $table->decimal('target_revenue', 25, 2)->default(0)->change();
                $table->decimal('real_revenue', 25, 2)->default(0)->change();
            });

            Log::info('✅ Modified cc_revenues OLD columns to DECIMAL(25,2)');

            // ========================================
            // STEP 3: Check if NEW columns exist (sold/bill)
            // ========================================
            // NOTE: Kolom ini akan ada setelah migration restructure
            
            $hasNewColumns = Schema::hasColumns('cc_revenues', [
                'real_revenue_sold', 
                'real_revenue_bill', 
                'target_revenue_sold', 
                'target_revenue_bill'
            ]);

            if ($hasNewColumns) {
                Log::info('🔍 Detected NEW columns (sold/bill), extending precision...');
                
                // Extend NEW columns jika sudah ada
                Schema::table('cc_revenues', function (Blueprint $table) {
                    $table->decimal('real_revenue_sold', 25, 2)->default(0)->change();
                    $table->decimal('real_revenue_bill', 25, 2)->default(0)->change();
                    $table->decimal('target_revenue_sold', 25, 2)->default(0)->change();
                    $table->decimal('target_revenue_bill', 25, 2)->default(0)->change();
                });

                Log::info('✅ Modified cc_revenues NEW columns to DECIMAL(25,2)');
            } else {
                Log::info('⚠️ NEW columns not found yet (will be added by restructure migration)');
            }

            // ========================================
            // STEP 4: Modify AM Revenues table
            // ========================================
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 25, 2)->default(0)->change();
                $table->decimal('real_revenue', 25, 2)->default(0)->change();
                $table->decimal('achievement_rate', 8, 2)->default(0)->change();
            });

            Log::info('✅ Modified am_revenues columns to DECIMAL(25,2)');

            // ========================================
            // STEP 5: Verify changes
            // ========================================
            
            $ccRevenueColumnsAfter = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'cc_revenues'
                AND COLUMN_NAME LIKE '%revenue%'
                ORDER BY ORDINAL_POSITION
            ");

            $amRevenueColumnsAfter = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'am_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue', 'achievement_rate')
            ");

            Log::info('📋 Column types AFTER migration', [
                'cc_revenues' => $ccRevenueColumnsAfter,
                'am_revenues' => $amRevenueColumnsAfter
            ]);

            // ========================================
            // STEP 6: Summary
            // ========================================
            
            $summary = [
                'cc_revenues_old_columns' => 'DECIMAL(25,2)',
                'cc_revenues_new_columns' => $hasNewColumns ? 'DECIMAL(25,2)' : 'Not exists yet',
                'am_revenues_columns' => 'DECIMAL(25,2)',
                'max_value_supported' => '999,999,999,999,999,999,999,999.99 (999 quadrillion)'
            ];

            Log::info('📊 Migration Summary:', $summary);
            Log::info('✅✅✅ Migration completed successfully!');

        } catch (\Exception $e) {
            Log::error('❌ Migration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Log::info('🔄 Reverting migration: Restore revenue columns to DECIMAL(15,2)');

            // ========================================
            // STEP 1: Revert CC Revenues table (OLD COLUMNS)
            // ========================================
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 15, 2)->default(0)->change();
                $table->decimal('real_revenue', 15, 2)->default(0)->change();
            });

            Log::info('✅ Reverted cc_revenues OLD columns to DECIMAL(15,2)');

            // ========================================
            // STEP 2: Revert NEW COLUMNS (if exist)
            // ========================================
            
            $hasNewColumns = Schema::hasColumns('cc_revenues', [
                'real_revenue_sold', 
                'real_revenue_bill', 
                'target_revenue_sold', 
                'target_revenue_bill'
            ]);

            if ($hasNewColumns) {
                Schema::table('cc_revenues', function (Blueprint $table) {
                    $table->decimal('real_revenue_sold', 15, 2)->default(0)->change();
                    $table->decimal('real_revenue_bill', 15, 2)->default(0)->change();
                    $table->decimal('target_revenue_sold', 15, 2)->default(0)->change();
                    $table->decimal('target_revenue_bill', 15, 2)->default(0)->change();
                });

                Log::info('✅ Reverted cc_revenues NEW columns to DECIMAL(15,2)');
            }

            // ========================================
            // STEP 3: Revert AM Revenues table
            // ========================================
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 15, 2)->default(0)->change();
                $table->decimal('real_revenue', 15, 2)->default(0)->change();
                $table->decimal('achievement_rate', 5, 2)->default(0)->change();
            });

            Log::info('✅ Reverted am_revenues columns to DECIMAL(15,2)');
            Log::info('✅ Migration rollback completed');

        } catch (\Exception $e) {
            Log::error('❌ Migration rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};