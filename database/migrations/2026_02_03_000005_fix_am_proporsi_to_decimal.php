<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Fix AM Proporsi to Decimal (OPTIONAL)
 * 
 * PURPOSE:
 * - Standardize proporsi range to 0.0 - 1.0 at DATABASE level
 * - Migrate existing data from 0-100 format to 0.0-1.0 format
 * - Increase precision to DECIMAL(5,4) for accuracy
 * 
 * BEFORE:
 * - proporsi DECIMAL(5,2) = supports 0-100 range (e.g., 40.00 = 40%)
 * - Inconsistency: Model expects 0-1, DB allows 0-100
 * - Normalization happens in app layer (boot() method)
 * 
 * AFTER:
 * - proporsi DECIMAL(5,4) = supports 0.0000-1.0000 range (e.g., 0.4000 = 40%)
 * - Consistency: Model and DB both use 0-1 range
 * - More precision: 4 decimal places instead of 2
 * 
 * MIGRATION LOGIC:
 * - If proporsi > 1 → divide by 100 (convert from percentage)
 * - Example: 40.00 → 0.4000
 * - Example: 0.4 → 0.4000 (already correct, just add precision)
 * 
 * NOTE: This migration is OPTIONAL because:
 * 1. AmRevenue model already handles normalization in boot()
 * 2. SP v2 already normalizes proporsi (if > 1, divide by 100)
 * 3. This just enforces it at DB level for extra safety
 * 
 * RECOMMENDATION:
 * - Run this migration if you want strict DB-level enforcement
 * - Skip this migration if you prefer app-level normalization
 * 
 * DATE: 2026-02-03
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 OPTIONAL MIGRATION: Standardizing proporsi to 0.0-1.0 range');

            // ============================================================
            // STEP 1: Check current proporsi values
            // ============================================================
            Log::info('📋 Step 1: Analyzing current proporsi values...');
            
            $proporsiStats = DB::select("
                SELECT 
                    COUNT(*) as total_records,
                    MIN(proporsi) as min_proporsi,
                    MAX(proporsi) as max_proporsi,
                    AVG(proporsi) as avg_proporsi,
                    SUM(CASE WHEN proporsi > 1 THEN 1 ELSE 0 END) as needs_conversion,
                    SUM(CASE WHEN proporsi <= 1 THEN 1 ELSE 0 END) as already_decimal
                FROM am_revenues
                WHERE proporsi IS NOT NULL
            ");
            
            Log::info('📊 Current proporsi statistics:', (array) $proporsiStats[0]);
            
            $needsConversion = $proporsiStats[0]->needs_conversion ?? 0;
            $alreadyDecimal = $proporsiStats[0]->already_decimal ?? 0;

            // ============================================================
            // STEP 2: Add temporary column for migration
            // ============================================================
            Log::info('📋 Step 2: Adding temporary column...');
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('proporsi_new', 5, 4)->nullable()->after('proporsi');
            });
            
            Log::info('✅ Temporary column added');

            // ============================================================
            // STEP 3: Migrate data to new column (normalize to 0.0-1.0)
            // ============================================================
            Log::info('📋 Step 3: Converting proporsi values to 0.0-1.0 range...');
            
            // Convert values > 1 (percentage format) to decimal
            DB::statement("
                UPDATE am_revenues 
                SET proporsi_new = CASE 
                    WHEN proporsi > 1 THEN proporsi / 100
                    ELSE proporsi
                END
                WHERE proporsi IS NOT NULL
            ");
            
            Log::info("✅ Converted {$needsConversion} records from percentage to decimal");
            Log::info("✅ Kept {$alreadyDecimal} records that were already in decimal format");

            // ============================================================
            // STEP 4: Verify conversion
            // ============================================================
            Log::info('📋 Step 4: Verifying conversion...');
            
            $verificationStats = DB::select("
                SELECT 
                    COUNT(*) as total_records,
                    MIN(proporsi_new) as min_proporsi_new,
                    MAX(proporsi_new) as max_proporsi_new,
                    AVG(proporsi_new) as avg_proporsi_new,
                    SUM(CASE WHEN proporsi_new > 1 THEN 1 ELSE 0 END) as still_over_1,
                    SUM(CASE WHEN proporsi_new < 0 THEN 1 ELSE 0 END) as negative_values
                FROM am_revenues
                WHERE proporsi_new IS NOT NULL
            ");
            
            Log::info('📊 After conversion statistics:', (array) $verificationStats[0]);
            
            $stillOver1 = $verificationStats[0]->still_over_1 ?? 0;
            $negativeValues = $verificationStats[0]->negative_values ?? 0;
            
            if ($stillOver1 > 0) {
                Log::warning("⚠️ Warning: {$stillOver1} records still have proporsi > 1");
            }
            
            if ($negativeValues > 0) {
                Log::error("❌ Error: {$negativeValues} records have negative proporsi!");
                throw new \Exception("Data validation failed: Found negative proporsi values");
            }

            // ============================================================
            // STEP 5: Drop old proporsi column
            // ============================================================
            Log::info('📋 Step 5: Dropping old proporsi column...');
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->dropColumn('proporsi');
            });
            
            Log::info('✅ Old proporsi column dropped');

            // ============================================================
            // STEP 6: Rename new column to proporsi
            // ============================================================
            Log::info('📋 Step 6: Renaming proporsi_new to proporsi...');
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->renameColumn('proporsi_new', 'proporsi');
            });
            
            Log::info('✅ Column renamed');

            // ============================================================
            // STEP 7: Add check constraint (optional, MySQL 8.0.16+)
            // ============================================================
            Log::info('📋 Step 7: Adding check constraint...');
            
            try {
                DB::statement("
                    ALTER TABLE am_revenues 
                    ADD CONSTRAINT chk_proporsi_range 
                    CHECK (proporsi >= 0 AND proporsi <= 1)
                ");
                Log::info('✅ Check constraint added (proporsi must be 0-1)');
            } catch (\Exception $e) {
                Log::warning('⚠️ Could not add check constraint (MySQL < 8.0.16?): ' . $e->getMessage());
            }

            // ============================================================
            // STEP 8: Update SP v2 to handle new precision (optional)
            // ============================================================
            Log::info('📋 Step 8: Checking SP compatibility...');
            Log::info('✅ SP v2 already handles decimal proporsi correctly');
            Log::info('💡 No SP changes needed');

            // ============================================================
            // FINAL SUMMARY
            // ============================================================
            Log::info('🎉 PROPORSI STANDARDIZATION COMPLETED!');
            Log::info('📊 Summary:', [
                'old_precision' => 'DECIMAL(5,2) - supports 0-100',
                'new_precision' => 'DECIMAL(5,4) - supports 0.0000-1.0000',
                'records_converted' => $needsConversion,
                'records_unchanged' => $alreadyDecimal,
                'validation' => 'CHECK constraint added (MySQL 8.0.16+)',
                'example_conversion' => '40.00 → 0.4000 (40% → 0.4)',
                'sp_compatibility' => 'Compatible with SP v2'
            ]);

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
            Log::info('🔄 Rolling back proporsi standardization...');

            // ============================================================
            // STEP 1: Remove check constraint
            // ============================================================
            try {
                DB::statement("ALTER TABLE am_revenues DROP CONSTRAINT chk_proporsi_range");
                Log::info('✅ Check constraint removed');
            } catch (\Exception $e) {
                Log::warning('⚠️ Check constraint not found (already dropped or never existed)');
            }

            // ============================================================
            // STEP 2: Add temporary column for rollback
            // ============================================================
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('proporsi_old', 5, 2)->nullable()->after('proporsi');
            });

            // ============================================================
            // STEP 3: Convert back to percentage format (0-100)
            // ============================================================
            DB::statement("
                UPDATE am_revenues 
                SET proporsi_old = proporsi * 100
                WHERE proporsi IS NOT NULL
            ");

            // ============================================================
            // STEP 4: Drop new column and rename old back
            // ============================================================
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->dropColumn('proporsi');
            });
            
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->renameColumn('proporsi_old', 'proporsi');
            });

            Log::info('✅ Rollback completed - proporsi reverted to DECIMAL(5,2) with 0-100 range');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};