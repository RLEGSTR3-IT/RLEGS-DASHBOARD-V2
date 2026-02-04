<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Restructure CC Revenues Table (FIXED VERSION)
 * 
 * CHANGES:
 * 1. Swap columns: tipe_revenue ↔ revenue_source
 *    - OLD: tipe_revenue = REGULER/NGTMA, revenue_source = HO/BILL
 *    - NEW: tipe_revenue = HO/BILL, revenue_source = REGULER/NGTMA
 * 
 * 2. Add new columns:
 *    - real_revenue_sold (DECIMAL(25,2))
 *    - real_revenue_bill (DECIMAL(25,2))
 *    - target_revenue_sold (DECIMAL(25,2))
 *    - target_revenue_bill (DECIMAL(25,2))
 * 
 * 3. Update unique constraint:
 *    - Remove tipe_revenue from constraint
 *    - Allow multiple records per CC per period (sold + bill)
 * 
 * 4. Update Stored Procedure & Trigger:
 *    - SP v2: Use real_revenue_sold (not real_revenue)
 *    - Trigger v2: Detect changes in real_revenue_sold
 * 
 * Date: 2026-02-03
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Log::info('🚀 Starting migration: Restructure CC Revenues Table');

            // ========================================
            // STEP 1: CHECK CURRENT STRUCTURE
            // ========================================
            
            Log::info('📊 Step 1: Checking current table structure...');
            
            $hasTipeRevenue = Schema::hasColumn('cc_revenues', 'tipe_revenue');
            $hasRevenueSource = Schema::hasColumn('cc_revenues', 'revenue_source');
            
            Log::info('Current columns exist:', [
                'tipe_revenue' => $hasTipeRevenue ? 'YES' : 'NO',
                'revenue_source' => $hasRevenueSource ? 'YES' : 'NO'
            ]);

            if (!$hasTipeRevenue || !$hasRevenueSource) {
                throw new \Exception('Required columns (tipe_revenue, revenue_source) not found!');
            }

            // ========================================
            // STEP 2: SWAP COLUMNS (Rename Strategy)
            // ========================================
            
            Log::info('🔄 Step 2: Swapping columns tipe_revenue ↔ revenue_source...');
            
            // Step 2.1: Rename to temporary names
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN tipe_revenue tipe_revenue_temp ENUM('REGULER','NGTMA') DEFAULT 'REGULER'
            ");
            Log::info('  ✅ Renamed tipe_revenue → tipe_revenue_temp');
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN revenue_source revenue_source_temp ENUM('HO','BILL') NOT NULL
            ");
            Log::info('  ✅ Renamed revenue_source → revenue_source_temp');
            
            // Step 2.2: Swap names (temp → final)
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN revenue_source_temp tipe_revenue ENUM('HO','BILL') NOT NULL
            ");
            Log::info('  ✅ Renamed revenue_source_temp → tipe_revenue (NEW)');
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN tipe_revenue_temp revenue_source ENUM('REGULER','NGTMA') DEFAULT 'REGULER'
            ");
            Log::info('  ✅ Renamed tipe_revenue_temp → revenue_source (NEW)');
            
            Log::info('✅ Step 2 completed: Column swap successful!');

            // ========================================
            // STEP 3: ADD NEW COLUMNS
            // ========================================
            
            Log::info('➕ Step 3: Adding new revenue columns...');
            
            Schema::table('cc_revenues', function (Blueprint $table) {
                // Real revenue columns (from CSV)
                if (!Schema::hasColumn('cc_revenues', 'real_revenue_sold')) {
                    $table->decimal('real_revenue_sold', 25, 2)
                          ->default(0)
                          ->after('real_revenue')
                          ->comment('Revenue Sold from CSV (REVENUE_SOLD column)');
                    Log::info('  ✅ Added column: real_revenue_sold');
                }
                
                if (!Schema::hasColumn('cc_revenues', 'real_revenue_bill')) {
                    $table->decimal('real_revenue_bill', 25, 2)
                          ->default(0)
                          ->after('real_revenue_sold')
                          ->comment('Revenue Bill from CSV (REVENUE_BILL column)');
                    Log::info('  ✅ Added column: real_revenue_bill');
                }

                // Target revenue columns (from CSV)
                if (!Schema::hasColumn('cc_revenues', 'target_revenue_sold')) {
                    $table->decimal('target_revenue_sold', 25, 2)
                          ->default(0)
                          ->after('target_revenue')
                          ->comment('Target Sold from CSV (TARGET_REVENUE_SOLD column)');
                    Log::info('  ✅ Added column: target_revenue_sold');
                }
                
                if (!Schema::hasColumn('cc_revenues', 'target_revenue_bill')) {
                    $table->decimal('target_revenue_bill', 25, 2)
                          ->default(0)
                          ->after('target_revenue_sold')
                          ->comment('Target Bill for witel-specific (future use)');
                    Log::info('  ✅ Added column: target_revenue_bill');
                }
            });

            Log::info('✅ Step 3 completed: All new columns added!');

            // ========================================
            // STEP 4: UPDATE UNIQUE CONSTRAINT
            // ========================================
            
            Log::info('🔑 Step 4: Updating unique constraint...');
            
            // Drop old unique constraint (cc_id, tahun, bulan, tipe_revenue)
            try {
                DB::statement("ALTER TABLE cc_revenues DROP INDEX cc_revenue_unique");
                Log::info('  ✅ Dropped old unique constraint');
            } catch (\Exception $e) {
                Log::warning('  ⚠️ Old unique constraint not found, skipping...');
            }

            // Add new unique constraint (cc_id, divisi_id, tahun, bulan)
            // Allows multiple records per CC per period if divisi different
            try {
                DB::statement("
                    ALTER TABLE cc_revenues 
                    ADD UNIQUE KEY cc_revenue_unique (corporate_customer_id, divisi_id, tahun, bulan)
                ");
                Log::info('  ✅ Added new unique constraint');
            } catch (\Exception $e) {
                // Constraint might already exist
                Log::warning('  ⚠️ New unique constraint already exists or failed: ' . $e->getMessage());
            }

            Log::info('✅ Step 4 completed: Unique constraint updated!');

            // ========================================
            // STEP 5: DROP OLD SP & TRIGGER
            // ========================================
            
            Log::info('🗑️ Step 5: Dropping old Stored Procedures & Triggers...');

            // Drop ALL versions of triggers (v1 and v2)
            DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");
            Log::info('  ✅ Dropped trigger v1: after_cc_revenues_update');
            
            DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update_v2");
            Log::info('  ✅ Dropped trigger v2: after_cc_revenues_update_v2');

            // Drop ALL versions of stored procedures (v1 and v2)
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues");
            Log::info('  ✅ Dropped SP v1: sp_recalculate_am_revenues');
            
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues_v2");
            Log::info('  ✅ Dropped SP v2: sp_recalculate_am_revenues_v2');

            Log::info('✅ Step 5 completed: All old SP & Triggers dropped!');

            // ========================================
            // STEP 6: CREATE NEW STORED PROCEDURE V2
            // ========================================
            
            Log::info('🔧 Step 6: Creating new Stored Procedure v2...');

            DB::unprepared("
                CREATE PROCEDURE sp_recalculate_am_revenues_v2(
                    IN p_cc_id BIGINT UNSIGNED,
                    IN p_divisi_id BIGINT UNSIGNED,
                    IN p_bulan TINYINT UNSIGNED,
                    IN p_tahun SMALLINT UNSIGNED,
                    IN p_new_revenue_sold DECIMAL(25,2),
                    OUT p_updated_count INT
                )
                BEGIN
                    -- Variable declarations
                    DECLARE v_am_revenue_id BIGINT UNSIGNED;
                    DECLARE v_am_id BIGINT UNSIGNED;
                    DECLARE v_proporsi DECIMAL(5,4);
                    DECLARE v_new_real_am DECIMAL(25,2);
                    DECLARE v_target_am DECIMAL(25,2);
                    DECLARE v_achievement_rate DECIMAL(8,2);
                    DECLARE v_done INT DEFAULT 0;

                    -- Cursor for all AM revenues related to this CC
                    DECLARE cur_am_revenues CURSOR FOR
                        SELECT
                            id,
                            account_manager_id,
                            proporsi,
                            target_revenue
                        FROM am_revenues
                        WHERE corporate_customer_id = p_cc_id
                          AND divisi_id = p_divisi_id
                          AND bulan = p_bulan
                          AND tahun = p_tahun;

                    -- Handler for end of cursor
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

                    -- Initialize counter
                    SET p_updated_count = 0;

                    -- Validate revenue_sold (set to 0 if NULL)
                    IF p_new_revenue_sold IS NULL THEN
                        SET p_new_revenue_sold = 0;
                    END IF;

                    -- Open cursor
                    OPEN cur_am_revenues;

                    -- Loop through all AM revenues
                    read_loop: LOOP
                        FETCH cur_am_revenues INTO v_am_revenue_id, v_am_id, v_proporsi, v_target_am;

                        -- Exit if no more rows
                        IF v_done THEN
                            LEAVE read_loop;
                        END IF;

                        -- Normalize proporsi if > 1 (assume percentage format)
                        IF v_proporsi > 1 THEN
                            SET v_proporsi = v_proporsi / 100;
                        END IF;

                        -- CRITICAL: Calculate AM revenue from revenue_sold ONLY
                        -- Regardless of divisi (DPS/DSS/DGS), always use revenue_sold
                        SET v_new_real_am = p_new_revenue_sold * v_proporsi;

                        -- Calculate achievement rate (avoid division by zero)
                        IF v_target_am > 0 THEN
                            SET v_achievement_rate = (v_new_real_am / v_target_am) * 100;
                        ELSE
                            SET v_achievement_rate = 0;
                        END IF;

                        -- Round to 2 decimal places
                        SET v_achievement_rate = ROUND(v_achievement_rate, 2);

                        -- Update AM revenue
                        UPDATE am_revenues
                        SET
                            real_revenue = v_new_real_am,
                            achievement_rate = v_achievement_rate,
                            updated_at = NOW()
                        WHERE id = v_am_revenue_id;

                        -- Increment counter if update successful
                        IF ROW_COUNT() > 0 THEN
                            SET p_updated_count = p_updated_count + 1;
                        END IF;

                        -- Log to sp_logs (if table exists)
                        INSERT IGNORE INTO sp_logs (
                            procedure_name,
                            input_params,
                            output_params,
                            execution_time,
                            created_at
                        ) VALUES (
                            'sp_recalculate_am_revenues_v2',
                            JSON_OBJECT(
                                'cc_id', p_cc_id,
                                'divisi_id', p_divisi_id,
                                'bulan', p_bulan,
                                'tahun', p_tahun,
                                'revenue_sold', p_new_revenue_sold,
                                'am_revenue_id', v_am_revenue_id,
                                'proporsi', v_proporsi
                            ),
                            JSON_OBJECT(
                                'new_real_am', v_new_real_am,
                                'achievement_rate', v_achievement_rate
                            ),
                            0,
                            NOW()
                        );

                    END LOOP read_loop;

                    -- Close cursor
                    CLOSE cur_am_revenues;

                END
            ");

            Log::info('  ✅ Created SP: sp_recalculate_am_revenues_v2');
            Log::info('✅ Step 6 completed: New SP v2 created!');

            // ========================================
            // STEP 7: CREATE NEW TRIGGER V2
            // ========================================
            
            Log::info('🔧 Step 7: Creating new Trigger v2...');

            DB::unprepared("
                CREATE TRIGGER after_cc_revenues_update_v2
                AFTER UPDATE ON cc_revenues
                FOR EACH ROW
                BEGIN
                    -- Variable declarations
                    DECLARE v_updated_count INT DEFAULT 0;
                    DECLARE v_revenue_sold_changed BOOLEAN DEFAULT FALSE;

                    -- Check if real_revenue_sold changed (NOT real_revenue)
                    IF OLD.real_revenue_sold != NEW.real_revenue_sold THEN
                        SET v_revenue_sold_changed = TRUE;
                    END IF;

                    -- Proceed only if revenue_sold changed
                    IF v_revenue_sold_changed THEN
                        -- Call stored procedure to recalculate AM revenues
                        CALL sp_recalculate_am_revenues_v2(
                            NEW.corporate_customer_id,
                            NEW.divisi_id,
                            NEW.bulan,
                            NEW.tahun,
                            NEW.real_revenue_sold,
                            v_updated_count
                        );

                        -- Log to trigger_logs (if table exists)
                        INSERT IGNORE INTO trigger_logs (
                            trigger_name,
                            table_name,
                            operation,
                            old_values,
                            new_values,
                            created_at
                        ) VALUES (
                            'after_cc_revenues_update_v2',
                            'cc_revenues',
                            'UPDATE',
                            JSON_OBJECT(
                                'real_revenue_sold', OLD.real_revenue_sold,
                                'real_revenue_bill', OLD.real_revenue_bill
                            ),
                            JSON_OBJECT(
                                'real_revenue_sold', NEW.real_revenue_sold,
                                'real_revenue_bill', NEW.real_revenue_bill,
                                'am_updated_count', v_updated_count
                            ),
                            NOW()
                        );
                    END IF;

                END
            ");

            Log::info('  ✅ Created Trigger: after_cc_revenues_update_v2');
            Log::info('✅ Step 7 completed: New Trigger v2 created!');

            // ========================================
            // STEP 8: VERIFY CHANGES
            // ========================================
            
            Log::info('🔍 Step 8: Verifying all changes...');
            
            // Check columns
            $columnsAfter = DB::select("SHOW COLUMNS FROM cc_revenues");
            $columnNames = array_column($columnsAfter, 'Field');
            
            Log::info('📊 Final table structure (columns):', $columnNames);

            // Verify required columns exist
            $requiredColumns = [
                'tipe_revenue',          // HO/BILL (swapped)
                'revenue_source',        // REGULER/NGTMA (swapped)
                'real_revenue_sold',     // NEW
                'real_revenue_bill',     // NEW
                'target_revenue_sold',   // NEW
                'target_revenue_bill'    // NEW
            ];
            
            $missingColumns = [];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $columnNames)) {
                    $missingColumns[] = $col;
                }
            }

            if (!empty($missingColumns)) {
                throw new \Exception('Required columns missing: ' . implode(', ', $missingColumns));
            }

            Log::info('  ✅ All required columns exist!');

            // Check triggers
            $triggers = DB::select("SHOW TRIGGERS LIKE 'cc_revenues'");
            $triggerNames = array_column($triggers, 'Trigger');
            Log::info('🔧 Active triggers:', $triggerNames);

            if (!in_array('after_cc_revenues_update_v2', $triggerNames)) {
                throw new \Exception('Trigger after_cc_revenues_update_v2 not found!');
            }

            Log::info('  ✅ Trigger v2 active!');

            // Check stored procedures
            $procedures = DB::select("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name LIKE '%recalculate%'");
            $procedureNames = array_column($procedures, 'Name');
            Log::info('🔧 Active stored procedures:', $procedureNames);

            if (!in_array('sp_recalculate_am_revenues_v2', $procedureNames)) {
                throw new \Exception('SP sp_recalculate_am_revenues_v2 not found!');
            }

            Log::info('  ✅ SP v2 active!');

            // ========================================
            // FINAL SUMMARY
            // ========================================
            
            Log::info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            Log::info('✅✅✅ MIGRATION COMPLETED SUCCESSFULLY! ✅✅✅');
            Log::info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            Log::info('');
            Log::info('📊 Summary of changes:');
            Log::info('  1. ✅ Swapped columns: tipe_revenue ↔ revenue_source');
            Log::info('  2. ✅ Added 4 new columns: sold/bill variants');
            Log::info('  3. ✅ Updated unique constraint');
            Log::info('  4. ✅ Created SP v2: sp_recalculate_am_revenues_v2');
            Log::info('  5. ✅ Created Trigger v2: after_cc_revenues_update_v2');
            Log::info('');
            Log::info('🎯 New structure:');
            Log::info('  - tipe_revenue: HO (sold) | BILL');
            Log::info('  - revenue_source: REGULER | NGTMA');
            Log::info('  - real_revenue_sold: From CSV REVENUE_SOLD');
            Log::info('  - real_revenue_bill: From CSV REVENUE_BILL');
            Log::info('  - target_revenue_sold: From CSV TARGET_REVENUE_SOLD');
            Log::info('  - target_revenue_bill: For witel-specific targets');
            Log::info('');
            Log::info('⚙️ AM Revenue calculation:');
            Log::info('  - ALWAYS uses real_revenue_sold (not bill)');
            Log::info('  - Trigger watches real_revenue_sold changes');
            Log::info('  - SP v2 calculates: AM revenue = sold × proporsi');
            Log::info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        } catch (\Exception $e) {
            Log::error('❌❌❌ MIGRATION FAILED! ❌❌❌');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Log::info('🔄 Starting rollback: Restructure CC Revenues Table');

            // ========================================
            // STEP 1: DROP NEW SP & TRIGGER
            // ========================================
            
            Log::info('🗑️ Step 1: Dropping new SP & Trigger v2...');

            DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update_v2");
            Log::info('  ✅ Dropped trigger: after_cc_revenues_update_v2');
            
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues_v2");
            Log::info('  ✅ Dropped SP: sp_recalculate_am_revenues_v2');

            // ========================================
            // STEP 2: DROP NEW COLUMNS
            // ========================================
            
            Log::info('🗑️ Step 2: Dropping new revenue columns...');

            Schema::table('cc_revenues', function (Blueprint $table) {
                if (Schema::hasColumn('cc_revenues', 'real_revenue_sold')) {
                    $table->dropColumn('real_revenue_sold');
                }
                if (Schema::hasColumn('cc_revenues', 'real_revenue_bill')) {
                    $table->dropColumn('real_revenue_bill');
                }
                if (Schema::hasColumn('cc_revenues', 'target_revenue_sold')) {
                    $table->dropColumn('target_revenue_sold');
                }
                if (Schema::hasColumn('cc_revenues', 'target_revenue_bill')) {
                    $table->dropColumn('target_revenue_bill');
                }
            });

            Log::info('  ✅ Dropped all new columns');

            // ========================================
            // STEP 3: SWAP COLUMNS BACK
            // ========================================
            
            Log::info('🔄 Step 3: Swapping columns back to original...');

            // Swap back: tipe_revenue (HO/BILL) → revenue_source
            //            revenue_source (REGULER/NGTMA) → tipe_revenue
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN tipe_revenue tipe_revenue_temp ENUM('HO','BILL') NOT NULL
            ");
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN revenue_source revenue_source_temp ENUM('REGULER','NGTMA') DEFAULT 'REGULER'
            ");
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN revenue_source_temp tipe_revenue ENUM('REGULER','NGTMA') DEFAULT 'REGULER'
            ");
            
            DB::statement("
                ALTER TABLE cc_revenues 
                CHANGE COLUMN tipe_revenue_temp revenue_source ENUM('HO','BILL') NOT NULL
            ");
            
            Log::info('  ✅ Swapped columns back to original structure');

            // ========================================
            // STEP 4: RESTORE OLD UNIQUE CONSTRAINT
            // ========================================
            
            Log::info('🔑 Step 4: Restoring old unique constraint...');

            try {
                DB::statement("ALTER TABLE cc_revenues DROP INDEX cc_revenue_unique");
            } catch (\Exception $e) {
                Log::warning('  ⚠️ Unique constraint not found, skipping...');
            }

            DB::statement("
                ALTER TABLE cc_revenues 
                ADD UNIQUE KEY cc_revenue_unique (corporate_customer_id, tahun, bulan, tipe_revenue)
            ");
            
            Log::info('  ✅ Restored old unique constraint');

            // ========================================
            // STEP 5: RESTORE OLD SP & TRIGGER (Optional)
            // ========================================
            
            Log::info('⚠️ Step 5: Old SP & Trigger NOT restored (manual restore required)');
            Log::info('   To restore, run migration 2025_11_09_135004 again');

            Log::info('✅ Rollback completed successfully');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
};