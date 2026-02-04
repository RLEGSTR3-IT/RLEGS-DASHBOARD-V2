<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Create Witel Target Revenues Table
 * 
 * PURPOSE:
 * - Menyimpan target_revenue_bill per witel-divisi
 * - Khusus untuk divisi DPS & DSS
 * - Data diinput via import terpisah (bukan dari CC)
 * 
 * STRUCTURE:
 * - witel_id: Foreign key ke tabel witel
 * - divisi_id: Foreign key ke tabel divisi (DPS/DSS only)
 * - target_revenue_bill: Target revenue bill untuk witel tersebut
 * - bulan, tahun: Periode target
 * 
 * Date: 2026-02-03
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Log::info('🚀 Starting migration: Create Witel Target Revenues Table');

            Schema::create('witel_target_revenues', function (Blueprint $table) {
                // Primary key
                $table->id();

                // Foreign keys
                $table->unsignedBigInteger('witel_id')->index();
                $table->unsignedBigInteger('divisi_id')->index();

                // Target revenue bill
                $table->decimal('target_revenue_bill', 25, 2)->default(0);

                // Periode
                $table->tinyInteger('bulan')->unsigned()->comment('1-12');
                $table->smallInteger('tahun')->unsigned()->comment('Year, e.g. 2026');

                // Timestamps
                $table->timestamps();

                // Foreign key constraints
                $table->foreign('witel_id')
                      ->references('id')
                      ->on('witel')
                      ->onDelete('cascade')
                      ->onUpdate('cascade');

                $table->foreign('divisi_id')
                      ->references('id')
                      ->on('divisi')
                      ->onDelete('restrict')
                      ->onUpdate('cascade');

                // Unique constraint: 1 record per witel-divisi-periode
                $table->unique(['witel_id', 'divisi_id', 'tahun', 'bulan'], 'witel_target_unique');

                // Performance indexes
                $table->index(['tahun', 'bulan'], 'idx_periode');
                $table->index(['witel_id', 'tahun', 'bulan'], 'idx_witel_periode');
                $table->index(['divisi_id', 'tahun', 'bulan'], 'idx_divisi_periode');
            });

            Log::info('✅ Table witel_target_revenues created successfully');

            // Verify table structure
            $columns = \DB::select("SHOW COLUMNS FROM witel_target_revenues");
            Log::info('📊 Table structure:', array_column($columns, 'Field'));

            $indexes = \DB::select("SHOW INDEX FROM witel_target_revenues");
            Log::info('🔑 Indexes created:', array_unique(array_column($indexes, 'Key_name')));

            Log::info('✅ Migration completed successfully!');

        } catch (\Exception $e) {
            Log::error('❌ Migration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            Log::info('🔄 Rolling back migration: Drop Witel Target Revenues Table');

            Schema::dropIfExists('witel_target_revenues');

            Log::info('✅ Table witel_target_revenues dropped successfully');
            Log::info('✅ Rollback completed!');

        } catch (\Exception $e) {
            Log::error('❌ Rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};