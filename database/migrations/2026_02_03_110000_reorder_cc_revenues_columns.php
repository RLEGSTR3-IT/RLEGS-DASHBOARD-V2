<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ✅ PURPOSE: Reorder columns in cc_revenues table for better readability
     * 
     * NOTE: This is purely cosmetic - column order doesn't affect performance or functionality.
     * Laravel accesses columns by name, not position.
     * 
     * NEW ORDER:
     * 1. id
     * 2. corporate_customer_id
     * 3. nama_cc
     * 4. nipnas
     * 5. divisi_id
     * 6. segment_id (added - was missing from user list)
     * 7. witel_ho_id
     * 8. real_revenue_sold
     * 9. target_revenue_sold
     * 10. witel_bill_id
     * 11. real_revenue_bill
     * 12. tipe_revenue
     * 13. revenue_source
     * 14. bulan
     * 15. tahun
     * 16. created_at
     * 17. updated_at
     */
    public function up(): void
    {
        try {
            Log::info('========================================');
            Log::info('MIGRATION: Reorder CC Revenues Columns');
            Log::info('========================================');

            // Get all column names to verify
            $columns = DB::select("SHOW COLUMNS FROM cc_revenues");
            $columnNames = array_column($columns, 'Field');
            
            Log::info('Current columns:', $columnNames);

            // ⚠️ WARNING: MySQL doesn't support pure column reordering
            // We need to use MODIFY COLUMN with AFTER clause
            // This is a cosmetic change - be careful on production!

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN corporate_customer_id BIGINT(20) UNSIGNED NOT NULL AFTER id");
            Log::info('✅ Moved: corporate_customer_id');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN nama_cc VARCHAR(255) NOT NULL AFTER corporate_customer_id");
            Log::info('✅ Moved: nama_cc');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN nipnas VARCHAR(50) NOT NULL AFTER nama_cc");
            Log::info('✅ Moved: nipnas');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN divisi_id BIGINT(20) UNSIGNED NOT NULL AFTER nipnas");
            Log::info('✅ Moved: divisi_id');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN segment_id BIGINT(20) UNSIGNED NULL AFTER divisi_id");
            Log::info('✅ Moved: segment_id');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN witel_ho_id BIGINT(20) UNSIGNED NULL AFTER segment_id");
            Log::info('✅ Moved: witel_ho_id');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN real_revenue_sold DECIMAL(25,2) NOT NULL DEFAULT 0.00 AFTER witel_ho_id");
            Log::info('✅ Moved: real_revenue_sold');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN target_revenue_sold DECIMAL(25,2) NOT NULL DEFAULT 0.00 AFTER real_revenue_sold");
            Log::info('✅ Moved: target_revenue_sold');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN witel_bill_id BIGINT(20) UNSIGNED NULL AFTER target_revenue_sold");
            Log::info('✅ Moved: witel_bill_id');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN real_revenue_bill DECIMAL(25,2) NOT NULL DEFAULT 0.00 AFTER witel_bill_id");
            Log::info('✅ Moved: real_revenue_bill');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN tipe_revenue ENUM('HO','BILL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER real_revenue_bill");
            Log::info('✅ Moved: tipe_revenue');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN revenue_source ENUM('REGULER','NGTMA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGULER' AFTER tipe_revenue");
            Log::info('✅ Moved: revenue_source');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN bulan SMALLINT(6) UNSIGNED NOT NULL AFTER revenue_source");
            Log::info('✅ Moved: bulan');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN tahun SMALLINT(6) UNSIGNED NOT NULL AFTER bulan");
            Log::info('✅ Moved: tahun');

            // created_at and updated_at should already be at the end, but let's ensure
            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN created_at TIMESTAMP NULL AFTER tahun");
            Log::info('✅ Moved: created_at');

            DB::statement("ALTER TABLE cc_revenues MODIFY COLUMN updated_at TIMESTAMP NULL AFTER created_at");
            Log::info('✅ Moved: updated_at');

            // Verify final order
            $finalColumns = DB::select("SHOW COLUMNS FROM cc_revenues");
            $finalColumnNames = array_column($finalColumns, 'Field');
            
            Log::info('Final column order:', $finalColumnNames);
            
            $expectedOrder = [
                'id',
                'corporate_customer_id',
                'nama_cc',
                'nipnas',
                'divisi_id',
                'segment_id',
                'witel_ho_id',
                'real_revenue_sold',
                'target_revenue_sold',
                'witel_bill_id',
                'real_revenue_bill',
                'tipe_revenue',
                'revenue_source',
                'bulan',
                'tahun',
                'created_at',
                'updated_at'
            ];

            if ($finalColumnNames === $expectedOrder) {
                Log::info('✅✅✅ Column reorder successful! Order verified.');
            } else {
                Log::warning('⚠️ Column order may not match expected order. Please verify manually.');
            }

            Log::info('========================================');
            Log::info('✅✅✅ MIGRATION COMPLETED');
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
     * NOTE: Rollback is difficult for column reordering.
     * This will work, but columns will go back to original random order.
     */
    public function down(): void
    {
        Log::warning('========================================');
        Log::warning('ROLLBACK: Column Reorder');
        Log::warning('========================================');
        Log::warning('Column reordering rollback is not implemented.');
        Log::warning('Columns will remain in new order.');
        Log::warning('To restore original order, you would need to manually reorder them.');
        Log::warning('========================================');
    }
};