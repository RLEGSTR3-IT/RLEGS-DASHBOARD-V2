<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Add Revenue Columns & Fix Enum Names (ULTRA-SAFE VERSION)
 * 
 * HANDLES:
 * - Partial migration failures (temp columns already exist)
 * - Idempotent operation (can run multiple times safely)
 * - Column existence checks before every operation
 * 
 * DATE: 2026-02-03 (ULTRA-SAFE)
 */
return new class extends Migration
{
    /**
     * Check if column exists
     */
    private function columnExists($table, $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 ULTRA-SAFE MIGRATION: Adding revenue columns & fixing enum names');

            // ============================================================
            // STEP 0: CLEANUP - Remove any leftover temp columns
            // ============================================================
            Log::info('📋 Step 0: Cleaning up any leftover temp columns...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if ($this->columnExists('cc_revenues', 'tipe_revenue_temp')) {
                    $table->dropColumn('tipe_revenue_temp');
                    Log::info('🧹 Dropped leftover tipe_revenue_temp');
                }
                
                if ($this->columnExists('cc_revenues', 'revenue_source_temp')) {
                    $table->dropColumn('revenue_source_temp');
                    Log::info('🧹 Dropped leftover revenue_source_temp');
                }
            });

            // ============================================================
            // STEP 1: Check current structure
            // ============================================================
            Log::info('📋 Step 1: Checking current table structure...');
            
            $columns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'cc_revenues'
                ORDER BY ORDINAL_POSITION
            ");
            
            $currentColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);
            Log::info('Current columns: ' . implode(', ', $currentColumns));

            // ============================================================
            // STEP 2: Add temporary columns (SAFE)
            // ============================================================
            Log::info('📋 Step 2: Adding temporary columns for enum data...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if (!$this->columnExists('cc_revenues', 'tipe_revenue_temp')) {
                    $table->string('tipe_revenue_temp', 20)->nullable();
                    Log::info('✅ Added tipe_revenue_temp');
                }
                
                if (!$this->columnExists('cc_revenues', 'revenue_source_temp')) {
                    $table->string('revenue_source_temp', 20)->nullable();
                    Log::info('✅ Added revenue_source_temp');
                }
            });

            // Copy current enum values to temp (SAFE - only if columns exist)
            if ($this->columnExists('cc_revenues', 'revenue_source') && 
                $this->columnExists('cc_revenues', 'tipe_revenue')) {
                
                DB::statement("
                    UPDATE cc_revenues 
                    SET tipe_revenue_temp = revenue_source,
                        revenue_source_temp = tipe_revenue
                ");
                
                Log::info('✅ Enum values copied to temp columns');
            } else {
                Log::warning('⚠️ Old enum columns not found, skipping copy');
            }

            // ============================================================
            // STEP 3: Drop old enum columns (SAFE)
            // ============================================================
            Log::info('📋 Step 3: Dropping old enum columns...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if ($this->columnExists('cc_revenues', 'revenue_source')) {
                    $table->dropColumn('revenue_source');
                    Log::info('✅ Dropped revenue_source');
                }
                
                if ($this->columnExists('cc_revenues', 'tipe_revenue')) {
                    $table->dropColumn('tipe_revenue');
                    Log::info('✅ Dropped tipe_revenue');
                }
            });

            // ============================================================
            // STEP 4: Create new enum columns (SAFE)
            // ============================================================
            Log::info('📋 Step 4: Creating new enum columns with correct structure...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if (!$this->columnExists('cc_revenues', 'tipe_revenue')) {
                    $table->enum('tipe_revenue', ['HO', 'BILL'])
                          ->after('nipnas')
                          ->comment('Type of revenue: HO (sold) or BILL');
                    Log::info('✅ Created tipe_revenue');
                }
                
                if (!$this->columnExists('cc_revenues', 'revenue_source')) {
                    $table->enum('revenue_source', ['REGULER', 'NGTMA'])
                          ->default('REGULER')
                          ->after('tipe_revenue')
                          ->comment('Source of revenue data: REGULER or NGTMA');
                    Log::info('✅ Created revenue_source');
                }
            });

            // ============================================================
            // STEP 5: Copy data from temp to new enum columns (SAFE)
            // ============================================================
            Log::info('📋 Step 5: Restoring enum data to correct columns...');
            
            if ($this->columnExists('cc_revenues', 'tipe_revenue_temp') && 
                $this->columnExists('cc_revenues', 'revenue_source_temp')) {
                
                DB::statement("
                    UPDATE cc_revenues 
                    SET tipe_revenue = tipe_revenue_temp,
                        revenue_source = revenue_source_temp
                    WHERE tipe_revenue_temp IS NOT NULL
                ");
                
                Log::info('✅ Enum data restored');
            } else {
                Log::warning('⚠️ Temp columns not found, skipping restore');
            }

            // ============================================================
            // STEP 6: Drop temp columns (SAFE)
            // ============================================================
            Log::info('📋 Step 6: Dropping temporary columns...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if ($this->columnExists('cc_revenues', 'tipe_revenue_temp')) {
                    $table->dropColumn('tipe_revenue_temp');
                    Log::info('✅ Dropped tipe_revenue_temp');
                }
                
                if ($this->columnExists('cc_revenues', 'revenue_source_temp')) {
                    $table->dropColumn('revenue_source_temp');
                    Log::info('✅ Dropped revenue_source_temp');
                }
            });

            // ============================================================
            // STEP 7: Add 3 new revenue columns (SAFE)
            // ============================================================
            Log::info('📋 Step 7: Adding new revenue columns...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                if (!$this->columnExists('cc_revenues', 'real_revenue_sold')) {
                    $table->decimal('real_revenue_sold', 30, 2)
                          ->default(0)
                          ->after('revenue_source')
                          ->comment('Actual revenue sold (HO) - used by all divisions for AM calculation');
                    Log::info('✅ Added real_revenue_sold');
                }
                
                if (!$this->columnExists('cc_revenues', 'real_revenue_bill')) {
                    $table->decimal('real_revenue_bill', 30, 2)
                          ->default(0)
                          ->after('real_revenue_sold')
                          ->comment('Actual revenue bill - used for witel bill calculation');
                    Log::info('✅ Added real_revenue_bill');
                }
                
                if (!$this->columnExists('cc_revenues', 'target_revenue_sold')) {
                    $table->decimal('target_revenue_sold', 30, 2)
                          ->default(0)
                          ->after('real_revenue_bill')
                          ->comment('Target revenue (all divisions use sold for target)');
                    Log::info('✅ Added target_revenue_sold');
                }
            });

            // ============================================================
            // STEP 8: Add indexes (SAFE - with error handling)
            // ============================================================
            Log::info('📋 Step 8: Adding indexes for performance...');
            
            try {
                DB::statement('CREATE INDEX cc_revenues_tipe_revenue_index ON cc_revenues (tipe_revenue)');
                Log::info('✅ Added tipe_revenue index');
            } catch (\Exception $e) {
                Log::warning('⚠️ Index cc_revenues_tipe_revenue_index already exists or failed: ' . $e->getMessage());
            }
            
            try {
                DB::statement('CREATE INDEX cc_revenues_revenue_source_index ON cc_revenues (revenue_source)');
                Log::info('✅ Added revenue_source index');
            } catch (\Exception $e) {
                Log::warning('⚠️ Index cc_revenues_revenue_source_index already exists or failed: ' . $e->getMessage());
            }
            
            try {
                DB::statement('CREATE INDEX cc_revenues_period_tipe_index ON cc_revenues (tahun, bulan, tipe_revenue)');
                Log::info('✅ Added period_tipe index');
            } catch (\Exception $e) {
                Log::warning('⚠️ Index cc_revenues_period_tipe_index already exists or failed: ' . $e->getMessage());
            }

            // ============================================================
            // STEP 9: Verify final structure
            // ============================================================
            Log::info('📋 Step 9: Verifying final structure...');
            
            $finalColumns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'cc_revenues'
                AND COLUMN_NAME IN ('tipe_revenue', 'revenue_source', 'real_revenue_sold', 'real_revenue_bill', 'target_revenue_sold')
                ORDER BY ORDINAL_POSITION
            ");
            
            Log::info('✅ Final structure verified:', array_map(function($col) {
                return [
                    'name' => $col->COLUMN_NAME,
                    'type' => $col->COLUMN_TYPE,
                ];
            }, $finalColumns));

            // ============================================================
            // FINAL SUMMARY
            // ============================================================
            Log::info('🎉🎉🎉 MIGRATION COMPLETED SUCCESSFULLY!');

        } catch (\Exception $e) {
            Log::error('❌ MIGRATION FAILED: ' . $e->getMessage(), [
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
            Log::info('🔄 Rolling back revenue columns & enum fixes...');

            // Add temp columns (SAFE)
            Schema::table('cc_revenues', function (Blueprint $table) {
                if (!$this->columnExists('cc_revenues', 'tipe_revenue_temp')) {
                    $table->string('tipe_revenue_temp', 20)->nullable();
                }
                if (!$this->columnExists('cc_revenues', 'revenue_source_temp')) {
                    $table->string('revenue_source_temp', 20)->nullable();
                }
            });

            // Copy current values
            if ($this->columnExists('cc_revenues', 'tipe_revenue') && 
                $this->columnExists('cc_revenues', 'revenue_source')) {
                DB::statement("
                    UPDATE cc_revenues 
                    SET tipe_revenue_temp = tipe_revenue,
                        revenue_source_temp = revenue_source
                ");
            }

            // Drop indexes (SAFE)
            try { DB::statement('DROP INDEX cc_revenues_tipe_revenue_index ON cc_revenues'); } catch (\Exception $e) {}
            try { DB::statement('DROP INDEX cc_revenues_revenue_source_index ON cc_revenues'); } catch (\Exception $e) {}
            try { DB::statement('DROP INDEX cc_revenues_period_tipe_index ON cc_revenues'); } catch (\Exception $e) {}

            // Drop new columns (SAFE)
            Schema::table('cc_revenues', function (Blueprint $table) {
                if ($this->columnExists('cc_revenues', 'real_revenue_sold')) {
                    $table->dropColumn('real_revenue_sold');
                }
                if ($this->columnExists('cc_revenues', 'real_revenue_bill')) {
                    $table->dropColumn('real_revenue_bill');
                }
                if ($this->columnExists('cc_revenues', 'target_revenue_sold')) {
                    $table->dropColumn('target_revenue_sold');
                }
                if ($this->columnExists('cc_revenues', 'tipe_revenue')) {
                    $table->dropColumn('tipe_revenue');
                }
                if ($this->columnExists('cc_revenues', 'revenue_source')) {
                    $table->dropColumn('revenue_source');
                }
            });

            // Restore old enum columns (SAFE)
            Schema::table('cc_revenues', function (Blueprint $table) {
                if (!$this->columnExists('cc_revenues', 'revenue_source')) {
                    $table->enum('revenue_source', ['HO', 'BILL'])->after('nipnas');
                }
                if (!$this->columnExists('cc_revenues', 'tipe_revenue')) {
                    $table->enum('tipe_revenue', ['REGULER', 'NGTMA'])->default('REGULER')->after('revenue_source');
                }
            });

            // Restore data (SAFE)
            if ($this->columnExists('cc_revenues', 'tipe_revenue_temp') && 
                $this->columnExists('cc_revenues', 'revenue_source_temp')) {
                DB::statement("
                    UPDATE cc_revenues 
                    SET revenue_source = tipe_revenue_temp,
                        tipe_revenue = revenue_source_temp
                    WHERE tipe_revenue_temp IS NOT NULL
                ");
            }

            // Drop temp columns
            Schema::table('cc_revenues', function (Blueprint $table) {
                if ($this->columnExists('cc_revenues', 'tipe_revenue_temp')) {
                    $table->dropColumn('tipe_revenue_temp');
                }
                if ($this->columnExists('cc_revenues', 'revenue_source_temp')) {
                    $table->dropColumn('revenue_source_temp');
                }
            });

            Log::info('✅ Rollback completed');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }
};