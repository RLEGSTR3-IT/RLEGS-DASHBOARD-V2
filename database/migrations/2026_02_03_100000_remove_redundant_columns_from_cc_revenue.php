<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ✅ PURPOSE: Remove redundant columns from cc_revenues table
     * 
     * REMOVED COLUMNS (3):
     * 1. real_revenue        → Computed from (tipe_revenue === 'HO' ? real_revenue_sold : real_revenue_bill)
     * 2. target_revenue      → Alias to target_revenue_sold (all divisions use sold for target)
     * 3. target_revenue_bill → CC doesn't have target bill (only witel has target bill)
     * 
     * REMAINING COLUMNS (5):
     * - real_revenue_sold    ✅ All CC have this
     * - real_revenue_bill    ✅ All CC have this
     * - target_revenue_sold  ✅ All CC use this for target
     * - tipe_revenue         ✅ Determines which revenue is "active" (HO/BILL)
     * - revenue_source       ✅ Data source (REGULER/NGTMA)
     */
    public function up(): void
    {
        try {
            Log::info('========================================');
            Log::info('MIGRATION: Remove Redundant Columns');
            Log::info('========================================');

            // Step 1: Check current structure
            Log::info('Step 1: Checking current table structure...');
            $columns = DB::select("SHOW COLUMNS FROM cc_revenues");
            $columnNames = array_column($columns, 'Field');
            
            Log::info('Current columns:', $columnNames);

            // Step 2: Verify columns exist before dropping
            $columnsToRemove = ['real_revenue', 'target_revenue', 'target_revenue_bill'];
            $existingColumnsToRemove = array_intersect($columnsToRemove, $columnNames);

            if (empty($existingColumnsToRemove)) {
                Log::warning('No redundant columns found to remove. Migration skipped.');
                return;
            }

            Log::info('Columns to remove:', $existingColumnsToRemove);

            // Step 3: Drop redundant columns
            Schema::table('cc_revenues', function (Blueprint $table) use ($existingColumnsToRemove) {
                if (in_array('real_revenue', $existingColumnsToRemove)) {
                    Log::info('Dropping column: real_revenue');
                    $table->dropColumn('real_revenue');
                }

                if (in_array('target_revenue', $existingColumnsToRemove)) {
                    Log::info('Dropping column: target_revenue');
                    $table->dropColumn('target_revenue');
                }

                if (in_array('target_revenue_bill', $existingColumnsToRemove)) {
                    Log::info('Dropping column: target_revenue_bill');
                    $table->dropColumn('target_revenue_bill');
                }
            });

            Log::info('Step 3: ✅ Redundant columns dropped successfully');

            // Step 4: Verify final structure
            Log::info('Step 4: Verifying final table structure...');
            $finalColumns = DB::select("SHOW COLUMNS FROM cc_revenues");
            $finalColumnNames = array_column($finalColumns, 'Field');
            
            Log::info('Final columns:', $finalColumnNames);

            // Verify expected columns remain
            $expectedColumns = [
                'real_revenue_sold',
                'real_revenue_bill',
                'target_revenue_sold',
                'tipe_revenue',
                'revenue_source'
            ];

            $missingColumns = array_diff($expectedColumns, $finalColumnNames);
            if (!empty($missingColumns)) {
                throw new \Exception('ERROR: Expected columns missing after migration: ' . implode(', ', $missingColumns));
            }

            Log::info('Step 4: ✅ Table structure verified');
            Log::info('========================================');
            Log::info('✅✅✅ MIGRATION COMPLETED SUCCESSFULLY');
            Log::info('========================================');

        } catch (\Exception $e) {
            Log::error('❌❌❌ MIGRATION FAILED');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * ROLLBACK: Add back the redundant columns with default values
     */
    public function down(): void
    {
        try {
            Log::info('========================================');
            Log::info('ROLLBACK: Add Redundant Columns Back');
            Log::info('========================================');

            Schema::table('cc_revenues', function (Blueprint $table) {
                // Add back real_revenue (computed column)
                if (!Schema::hasColumn('cc_revenues', 'real_revenue')) {
                    Log::info('Adding column: real_revenue');
                    $table->decimal('real_revenue', 25, 2)->default(0)->after('revenue_source');
                }

                // Add back target_revenue (alias column)
                if (!Schema::hasColumn('cc_revenues', 'target_revenue')) {
                    Log::info('Adding column: target_revenue');
                    $table->decimal('target_revenue', 25, 2)->default(0)->after('real_revenue');
                }

                // Add back target_revenue_bill (unused column)
                if (!Schema::hasColumn('cc_revenues', 'target_revenue_bill')) {
                    Log::info('Adding column: target_revenue_bill');
                    $table->decimal('target_revenue_bill', 25, 2)->default(0)->after('target_revenue_sold');
                }
            });

            // Sync values after adding columns back
            Log::info('Syncing values for restored columns...');
            
            DB::statement("
                UPDATE cc_revenues 
                SET 
                    real_revenue = CASE 
                        WHEN tipe_revenue = 'HO' THEN real_revenue_sold 
                        ELSE real_revenue_bill 
                    END,
                    target_revenue = target_revenue_sold,
                    target_revenue_bill = 0
            ");

            Log::info('✅ Columns restored and values synced');
            Log::info('========================================');
            Log::info('✅✅✅ ROLLBACK COMPLETED SUCCESSFULLY');
            Log::info('========================================');

        } catch (\Exception $e) {
            Log::error('❌❌❌ ROLLBACK FAILED');
            Log::error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
};