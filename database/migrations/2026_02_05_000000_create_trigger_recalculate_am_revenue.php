<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * Migration: Create Trigger for Auto-Recalculate AM Revenue
 * ============================================================================
 * 
 * PURPOSE:
 * Auto-recalculate AM Revenue when CC Revenue updated (works with phpMyAdmin too)
 * 
 * TRIGGER NAME: recalculate_am_revenue_after_cc_update
 * TRIGGER ON: cc_revenues table (AFTER UPDATE)
 * ACTION: Update am_revenues.target_revenue & real_revenue
 * 
 * USAGE:
 * php artisan migrate
 * php artisan migrate:rollback (to remove trigger)
 * 
 * @author RLEGS Team
 * @version 1.0
 * ============================================================================
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop trigger if exists (for re-running migration)
        DB::unprepared('DROP TRIGGER IF EXISTS `recalculate_am_revenue_after_cc_update`');
        
        // Create trigger
        DB::unprepared('
            CREATE TRIGGER `recalculate_am_revenue_after_cc_update`
            AFTER UPDATE ON `cc_revenues`
            FOR EACH ROW
            BEGIN
                -- Only recalculate if revenue fields changed
                IF OLD.real_revenue_sold != NEW.real_revenue_sold 
                   OR OLD.target_revenue_sold != NEW.target_revenue_sold THEN
                    
                    -- Update all AM revenues for this CC in this period
                    UPDATE am_revenues
                    SET 
                        target_revenue = NEW.target_revenue_sold * proporsi,
                        real_revenue = NEW.real_revenue_sold * proporsi,
                        updated_at = NOW()
                    WHERE 
                        corporate_customer_id = NEW.corporate_customer_id
                        AND bulan = NEW.bulan
                        AND tahun = NEW.tahun;
                        
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop trigger when rolling back
        DB::unprepared('DROP TRIGGER IF EXISTS `recalculate_am_revenue_after_cc_update`');
    }
};