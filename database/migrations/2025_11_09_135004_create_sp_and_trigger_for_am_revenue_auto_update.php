<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Create Stored Procedure and Trigger for Auto-Update AM Revenues
 * 
 * UPDATED VERSION - Support both old and new structure:
 * - OLD: Uses real_revenue (backward compatibility)
 * - NEW: Uses real_revenue_sold (new structure post-restructure)
 * 
 * NOTE: Migration baru (2026_02_02_230341) akan DROP SP & Trigger lama,
 *       lalu CREATE v2 yang pakai real_revenue_sold
 *
 * Purpose: When cc_revenues updated, automatically recalculate related am_revenues
 *
 * Command to create this file:
 * php artisan make:migration create_sp_and_trigger_for_am_revenue_auto_update
 *
 * Command to run:
 * php artisan migrate
 *
 * Command to rollback:
 * php artisan migrate:rollback
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 Creating Stored Procedure and Trigger for AM Revenue Auto-Update');

            // ============================================================
            // STEP 1: Create Stored Procedure (v1 - OLD STRUCTURE)
            // ============================================================
            // NOTE: Ini akan di-DROP oleh migration restructure nanti
            
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues");

            DB::unprepared("
                CREATE PROCEDURE sp_recalculate_am_revenues(
                    IN p_cc_id BIGINT UNSIGNED,
                    IN p_divisi_id BIGINT UNSIGNED,
                    IN p_bulan TINYINT UNSIGNED,
                    IN p_tahun SMALLINT UNSIGNED,
                    IN p_new_target_revenue DECIMAL(25,2),
                    IN p_new_real_revenue DECIMAL(25,2),
                    OUT p_updated_count INT
                )
                BEGIN
                    -- Deklarasi variabel
                    DECLARE v_am_id BIGINT UNSIGNED;
                    DECLARE v_am_revenue_id BIGINT UNSIGNED;
                    DECLARE v_proporsi DECIMAL(5,2);
                    DECLARE v_new_target_am DECIMAL(25,2);
                    DECLARE v_new_real_am DECIMAL(25,2);
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

                        -- Normalize proporsi (jika > 1, anggap dalam persen, convert ke decimal)
                        IF v_proporsi > 1 THEN
                            SET v_proporsi = v_proporsi / 100;
                        END IF;

                        -- Calculate proportional target revenue
                        SET v_new_target_am = p_new_target_revenue * v_proporsi;

                        -- Calculate proportional real revenue
                        SET v_new_real_am = p_new_real_revenue * v_proporsi;

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

                        -- Log to sp_logs (if exists)
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
                            'sp_recalculate_am_revenues',
                            p_cc_id,
                            p_divisi_id,
                            p_bulan,
                            p_tahun,
                            1,
                            CONCAT('Updated AM Revenue ID: ', v_am_revenue_id, ', Proporsi: ', v_proporsi),
                            NOW()
                        );

                    END LOOP read_loop;

                    -- Close cursor
                    CLOSE cur_am_revenues;

                END
            ");

            Log::info('✅ Created Stored Procedure: sp_recalculate_am_revenues (v1)');

            // ============================================================
            // STEP 2: Create Trigger (v1 - OLD STRUCTURE)
            // ============================================================
            // NOTE: Ini akan di-DROP oleh migration restructure nanti
            
            DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");

            DB::unprepared("
                CREATE TRIGGER after_cc_revenues_update
                AFTER UPDATE ON cc_revenues
                FOR EACH ROW
                BEGIN
                    -- Deklarasi variabel
                    DECLARE v_updated_count INT DEFAULT 0;
                    DECLARE v_target_changed BOOLEAN DEFAULT FALSE;
                    DECLARE v_real_changed BOOLEAN DEFAULT FALSE;

                    -- Check if target_revenue changed
                    IF OLD.target_revenue != NEW.target_revenue THEN
                        SET v_target_changed = TRUE;
                    END IF;

                    -- Check if real_revenue changed
                    IF OLD.real_revenue != NEW.real_revenue THEN
                        SET v_real_changed = TRUE;
                    END IF;

                    -- Proceed only if at least one revenue field changed
                    IF v_target_changed OR v_real_changed THEN

                        -- Call stored procedure to recalculate AM revenues
                        CALL sp_recalculate_am_revenues(
                            NEW.corporate_customer_id,
                            NEW.divisi_id,
                            NEW.bulan,
                            NEW.tahun,
                            NEW.target_revenue,
                            NEW.real_revenue,
                            v_updated_count
                        );

                        -- Log to trigger_logs (if exists)
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
                            OLD.target_revenue,
                            OLD.real_revenue,
                            NEW.target_revenue,
                            NEW.real_revenue,
                            v_updated_count,
                            CONCAT('CC ID: ', NEW.corporate_customer_id, ', Divisi: ', NEW.divisi_id, ', Period: ', NEW.tahun, '-', NEW.bulan),
                            NOW()
                        );

                    END IF;

                END
            ");

            Log::info('✅ Created Trigger: after_cc_revenues_update (v1)');
            Log::info('✅ Migration completed successfully');

        } catch (\Exception $e) {
            Log::error('❌ Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Log::info('🔄 Rolling back SP and Trigger');

            // Drop trigger first (depends on stored procedure)
            DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");
            Log::info('✅ Dropped trigger: after_cc_revenues_update');

            // Drop stored procedure
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues");
            Log::info('✅ Dropped stored procedure: sp_recalculate_am_revenues');

            Log::info('✅ Rollback completed');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }
};