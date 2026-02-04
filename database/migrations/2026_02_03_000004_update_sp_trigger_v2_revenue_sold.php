<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Update SP & Trigger v2 - Use real_revenue_sold (ULTRA-SAFE)
 * 
 * ⚠️ ULTRA-SAFE VERSION - Handles existing SP/Triggers gracefully
 * 
 * PURPOSE:
 * - Drop OLD SP & Trigger (v1 & v2 if exists)
 * - Create NEW SP & Trigger (v2) that use real_revenue_sold
 * - Idempotent (can run multiple times safely)
 * 
 * CHANGES:
 * v1 (OLD - DEPRECATED):
 * - Used: real_revenue (ambiguous column)
 * - Logic: Calculate AM revenue from CC.real_revenue × proporsi
 * 
 * v2 (NEW - CORRECT):
 * - Uses: real_revenue_sold (explicit column)
 * - Logic: Calculate AM revenue from CC.real_revenue_sold × proporsi
 * - NOTE: AM revenue ALWAYS uses sold, regardless of CC divisi
 * 
 * CRITICAL BUSINESS RULE:
 * - AM Revenue = CC.real_revenue_sold × AM.proporsi
 * - This applies to ALL divisions (DGS, DPS, DSS, DES)
 * - Even if CC is BILL type (DPS/DSS), AM uses SOLD value
 * 
 * DATE: 2026-02-03 (ULTRA-SAFE)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 ULTRA-SAFE MIGRATION: Updating SP & Trigger to v2 (revenue_sold)');

            // ============================================================
            // STEP 1: Drop ALL existing versions (v1, v2, any variants)
            // ============================================================
            Log::info('📋 Step 1: Dropping ALL existing triggers...');
            
            // Drop all possible trigger names
            $triggers = [
                'after_cc_revenues_update',
                'after_cc_revenues_update_v2',
                'trg_cc_revenues_update',
            ];
            
            foreach ($triggers as $trigger) {
                try {
                    DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
                    Log::info("✅ Dropped trigger (if exists): {$trigger}");
                } catch (\Exception $e) {
                    Log::warning("⚠️ Could not drop trigger {$trigger}: " . $e->getMessage());
                }
            }
            
            Log::info('📋 Step 2: Dropping ALL existing stored procedures...');
            
            // Drop all possible SP names
            $procedures = [
                'sp_recalculate_am_revenues',
                'sp_recalculate_am_revenues_v2',
                'sp_update_am_revenues',
            ];
            
            foreach ($procedures as $procedure) {
                try {
                    DB::unprepared("DROP PROCEDURE IF EXISTS {$procedure}");
                    Log::info("✅ Dropped procedure (if exists): {$procedure}");
                } catch (\Exception $e) {
                    Log::warning("⚠️ Could not drop procedure {$procedure}: " . $e->getMessage());
                }
            }

            // ============================================================
            // STEP 2: Create NEW Stored Procedure v2 (revenue_sold)
            // ============================================================
            Log::info('📋 Step 3: Creating NEW stored procedure (v2)...');
            
            DB::unprepared("
                CREATE PROCEDURE sp_recalculate_am_revenues_v2(
                    IN p_cc_id BIGINT UNSIGNED,
                    IN p_divisi_id BIGINT UNSIGNED,
                    IN p_bulan TINYINT UNSIGNED,
                    IN p_tahun SMALLINT UNSIGNED,
                    IN p_new_target_revenue_sold DECIMAL(30,2),
                    IN p_new_real_revenue_sold DECIMAL(30,2),
                    OUT p_updated_count INT
                )
                BEGIN
                    -- Deklarasi variabel
                    DECLARE v_am_id BIGINT UNSIGNED;
                    DECLARE v_am_revenue_id BIGINT UNSIGNED;
                    DECLARE v_proporsi DECIMAL(5,4);
                    DECLARE v_new_target_am DECIMAL(30,2);
                    DECLARE v_new_real_am DECIMAL(30,2);
                    DECLARE v_achievement_rate DECIMAL(8,2);
                    DECLARE v_done INT DEFAULT 0;

                    -- Cursor untuk loop semua AM yang terkait
                    DECLARE cur_am_revenues CURSOR FOR
                        SELECT
                            id,
                            account_manager_id,
                            proporsi
                        FROM am_revenues
                        WHERE corporate_customer_id = p_cc_id
                          AND divisi_id = p_divisi_id
                          AND bulan = p_bulan
                          AND tahun = p_tahun;

                    -- Handler untuk end of cursor
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

                    -- Initialize counter
                    SET p_updated_count = 0;

                    -- Open cursor
                    OPEN cur_am_revenues;

                    -- Loop through all AM revenues
                    read_loop: LOOP
                        -- Fetch next row
                        FETCH cur_am_revenues INTO v_am_revenue_id, v_am_id, v_proporsi;

                        -- Exit if no more rows
                        IF v_done THEN
                            LEAVE read_loop;
                        END IF;

                        -- Normalize proporsi to 0.0-1.0 range
                        IF v_proporsi > 1 THEN
                            SET v_proporsi = v_proporsi / 100;
                        END IF;

                        -- Calculate proportional target revenue (from target_revenue_sold)
                        SET v_new_target_am = p_new_target_revenue_sold * v_proporsi;

                        -- CRITICAL: Use real_revenue_sold (not real_revenue)
                        -- AM Revenue ALWAYS uses SOLD value, regardless of CC divisi
                        SET v_new_real_am = p_new_real_revenue_sold * v_proporsi;

                        -- Calculate achievement rate (avoid division by zero)
                        IF v_new_target_am > 0 THEN
                            SET v_achievement_rate = (v_new_real_am / v_new_target_am) * 100;
                        ELSE
                            SET v_achievement_rate = 0;
                        END IF;

                        -- Round achievement rate to 2 decimal places
                        SET v_achievement_rate = ROUND(v_achievement_rate, 2);

                        -- Update AM revenue
                        UPDATE am_revenues
                        SET
                            target_revenue = v_new_target_am,
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
                            cc_id,
                            divisi_id,
                            bulan,
                            tahun,
                            updated_count,
                            message,
                            created_at
                        ) VALUES (
                            'sp_recalculate_am_revenues_v2',
                            p_cc_id,
                            p_divisi_id,
                            p_bulan,
                            p_tahun,
                            1,
                            CONCAT(
                                'Updated AM Revenue ID: ', v_am_revenue_id, 
                                ', Proporsi: ', v_proporsi,
                                ', Real Sold: ', p_new_real_revenue_sold,
                                ', Real AM: ', v_new_real_am
                            ),
                            NOW()
                        );

                    END LOOP read_loop;

                    -- Close cursor
                    CLOSE cur_am_revenues;

                END
            ");
            
            Log::info('✅ NEW stored procedure (v2) created');

            // ============================================================
            // STEP 3: Create NEW Trigger v2 (revenue_sold)
            // ============================================================
            Log::info('📋 Step 4: Creating NEW trigger (v2)...');
            
            DB::unprepared("
                CREATE TRIGGER after_cc_revenues_update_v2
                AFTER UPDATE ON cc_revenues
                FOR EACH ROW
                BEGIN
                    -- Deklarasi variabel
                    DECLARE v_updated_count INT DEFAULT 0;
                    DECLARE v_target_sold_changed BOOLEAN DEFAULT FALSE;
                    DECLARE v_real_sold_changed BOOLEAN DEFAULT FALSE;

                    -- Check if target_revenue_sold changed
                    IF OLD.target_revenue_sold != NEW.target_revenue_sold THEN
                        SET v_target_sold_changed = TRUE;
                    END IF;

                    -- CRITICAL: Check if real_revenue_sold changed
                    IF OLD.real_revenue_sold != NEW.real_revenue_sold THEN
                        SET v_real_sold_changed = TRUE;
                    END IF;

                    -- Proceed only if at least one sold revenue field changed
                    IF v_target_sold_changed OR v_real_sold_changed THEN

                        -- Call NEW stored procedure v2
                        CALL sp_recalculate_am_revenues_v2(
                            NEW.corporate_customer_id,
                            NEW.divisi_id,
                            NEW.bulan,
                            NEW.tahun,
                            NEW.target_revenue_sold,
                            NEW.real_revenue_sold,
                            v_updated_count
                        );

                        -- Log to trigger_logs (if table exists)
                        INSERT IGNORE INTO trigger_logs (
                            table_name,
                            action,
                            record_id,
                            old_target_revenue,
                            old_real_revenue,
                            new_target_revenue,
                            new_real_revenue,
                            am_updated_count,
                            details,
                            created_at
                        ) VALUES (
                            'cc_revenues',
                            'UPDATE',
                            NEW.id,
                            OLD.target_revenue_sold,
                            OLD.real_revenue_sold,
                            NEW.target_revenue_sold,
                            NEW.real_revenue_sold,
                            v_updated_count,
                            CONCAT(
                                'CC ID: ', NEW.corporate_customer_id, 
                                ', Divisi: ', NEW.divisi_id, 
                                ', Period: ', NEW.tahun, '-', NEW.bulan,
                                ', Tipe: ', NEW.tipe_revenue,
                                ', Source: ', NEW.revenue_source
                            ),
                            NOW()
                        );

                    END IF;

                END
            ");
            
            Log::info('✅ NEW trigger (v2) created');

            // ============================================================
            // STEP 4: Verify creation
            // ============================================================
            Log::info('📋 Step 5: Verifying SP & Trigger creation...');
            
            // Check SP exists
            $spExists = DB::select("
                SELECT ROUTINE_NAME 
                FROM INFORMATION_SCHEMA.ROUTINES 
                WHERE ROUTINE_SCHEMA = DATABASE() 
                AND ROUTINE_NAME = 'sp_recalculate_am_revenues_v2'
                AND ROUTINE_TYPE = 'PROCEDURE'
            ");
            
            if (!empty($spExists)) {
                Log::info('✅ Stored Procedure verified: sp_recalculate_am_revenues_v2');
            } else {
                throw new \Exception('Failed to create stored procedure');
            }
            
            // Check Trigger exists
            $triggerExists = DB::select("
                SELECT TRIGGER_NAME 
                FROM INFORMATION_SCHEMA.TRIGGERS 
                WHERE TRIGGER_SCHEMA = DATABASE() 
                AND TRIGGER_NAME = 'after_cc_revenues_update_v2'
            ");
            
            if (!empty($triggerExists)) {
                Log::info('✅ Trigger verified: after_cc_revenues_update_v2');
            } else {
                throw new \Exception('Failed to create trigger');
            }

            // ============================================================
            // FINAL SUMMARY
            // ============================================================
            Log::info('🎉🎉🎉 SP & TRIGGER v2 MIGRATION COMPLETED!');
            Log::info('📊 Summary:', [
                'sp_version' => 'v2 (sp_recalculate_am_revenues_v2)',
                'trigger_version' => 'v2 (after_cc_revenues_update_v2)',
                'watched_column' => 'real_revenue_sold',
                'business_rule' => 'AM Revenue = CC.real_revenue_sold × AM.proporsi',
                'applies_to' => 'ALL divisions (DGS, DPS, DSS, DES)',
                'idempotent' => 'YES - safe to run multiple times'
            ]);

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
            Log::info('🔄 Rolling back SP & Trigger v2...');

            // Drop v2 versions (SAFE)
            try {
                DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update_v2");
                Log::info('✅ Dropped trigger v2');
            } catch (\Exception $e) {
                Log::warning('⚠️ Could not drop trigger: ' . $e->getMessage());
            }
            
            try {
                DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues_v2");
                Log::info('✅ Dropped SP v2');
            } catch (\Exception $e) {
                Log::warning('⚠️ Could not drop procedure: ' . $e->getMessage());
            }

            Log::info('✅ Rollback completed');
            Log::warning('⚠️ Note: Old v1 versions NOT restored (they are deprecated)');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }
};