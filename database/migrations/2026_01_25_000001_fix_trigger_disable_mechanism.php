<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Fix Trigger Syntax and Add Disable Capability
 *
 * ✅ FIXED VERSION - 2026-01-25
 *
 * Purpose: Replace existing trigger with proper syntax for disable mechanism
 *
 * FIXES:
 * - Removed LEAVE statement (not allowed in triggers)
 * - Wrapped logic in IF statement instead
 * - Added helper stored procedures for trigger control
 *
 * USAGE:
 * 1. Disable trigger: DB::statement('SET @DISABLE_TRIGGER = 1');
 * 2. Perform bulk insert/update
 * 3. Manually recalculate AM revenues
 * 4. Re-enable trigger: DB::statement('SET @DISABLE_TRIGGER = NULL');
 *
 * Command to run:
 * php artisan migrate
 *
 * Command to rollback:
 * php artisan migrate:rollback --step=1
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================================
        // STEP 1: Drop existing trigger if exists
        // ============================================================
        DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");

        // ============================================================
        // STEP 2: Create NEW trigger with FIXED syntax
        // ============================================================
        DB::unprepared("
            CREATE TRIGGER after_cc_revenues_update
            AFTER UPDATE ON cc_revenues
            FOR EACH ROW
            BEGIN
                -- Deklarasi variabel
                DECLARE v_updated_count INT DEFAULT 0;
                DECLARE v_target_changed BOOLEAN DEFAULT FALSE;
                DECLARE v_real_changed BOOLEAN DEFAULT FALSE;
                DECLARE v_trigger_disabled BOOLEAN DEFAULT FALSE;

                -- ✅ CHECK IF TRIGGER IS DISABLED VIA SESSION VARIABLE
                SET v_trigger_disabled = COALESCE(@DISABLE_TRIGGER, 0);

                -- ✅ FIXED: Wrap entire logic in IF statement (no LEAVE needed)
                IF v_trigger_disabled = 0 THEN
                
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

                    END IF;
                    
                END IF;

            END
        ");

        // ============================================================
        // STEP 3: Create helper stored procedures for trigger management
        // ============================================================
        
        // Procedure to disable trigger
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_disable_am_trigger");
        DB::unprepared("
            CREATE PROCEDURE sp_disable_am_trigger()
            BEGIN
                SET @DISABLE_TRIGGER = 1;
                SELECT 'AM Revenue auto-update trigger DISABLED for this session' AS status;
            END
        ");

        // Procedure to enable trigger
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_enable_am_trigger");
        DB::unprepared("
            CREATE PROCEDURE sp_enable_am_trigger()
            BEGIN
                SET @DISABLE_TRIGGER = NULL;
                SELECT 'AM Revenue auto-update trigger ENABLED for this session' AS status;
            END
        ");

        // Procedure to check trigger status
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_check_trigger_status");
        DB::unprepared("
            CREATE PROCEDURE sp_check_trigger_status()
            BEGIN
                SELECT 
                    CASE 
                        WHEN COALESCE(@DISABLE_TRIGGER, 0) = 1 THEN 'DISABLED'
                        ELSE 'ENABLED'
                    END AS trigger_status,
                    @DISABLE_TRIGGER AS session_variable_value,
                    'Trigger will skip auto-update when @DISABLE_TRIGGER = 1' AS note;
            END
        ");

        // Log the migration
        DB::table('migrations')->insert([
            'migration' => '2026_01_25_000001_fix_trigger_disable_mechanism',
            'batch' => DB::table('migrations')->max('batch') + 1
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop helper procedures
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_check_trigger_status");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_enable_am_trigger");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_disable_am_trigger");

        // Restore original trigger (without disable capability)
        DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");
        
        DB::unprepared("
            CREATE TRIGGER after_cc_revenues_update
            AFTER UPDATE ON cc_revenues
            FOR EACH ROW
            BEGIN
                DECLARE v_updated_count INT DEFAULT 0;
                DECLARE v_target_changed BOOLEAN DEFAULT FALSE;
                DECLARE v_real_changed BOOLEAN DEFAULT FALSE;

                IF OLD.target_revenue != NEW.target_revenue THEN
                    SET v_target_changed = TRUE;
                END IF;

                IF OLD.real_revenue != NEW.real_revenue THEN
                    SET v_real_changed = TRUE;
                END IF;

                IF v_target_changed OR v_real_changed THEN
                    CALL sp_recalculate_am_revenues(
                        NEW.corporate_customer_id,
                        NEW.divisi_id,
                        NEW.bulan,
                        NEW.tahun,
                        NEW.target_revenue,
                        NEW.real_revenue,
                        v_updated_count
                    );
                END IF;
            END
        ");
    }
};