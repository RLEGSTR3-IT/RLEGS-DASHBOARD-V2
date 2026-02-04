<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Add Regional ID to Witel
 * 
 * PURPOSE:
 * - Link Witel to Regional (TREG grouping)
 * - Enable cross-TREG scenario tracking
 * 
 * CHANGES:
 * - Add regional_id BIGINT UNSIGNED NULLABLE
 * - Add foreign key constraint to regionals.id
 * - Add index for performance
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
            Log::info('🚀 Adding regional_id to witel table');

            Schema::table('witel', function (Blueprint $table) {
                // Add regional_id column
                $table->unsignedBigInteger('regional_id')
                      ->nullable()
                      ->after('nama')
                      ->comment('Foreign key to regionals table');
                
                // Add index for performance
                $table->index('regional_id', 'witel_regional_id_index');
                
                // Add foreign key constraint
                $table->foreign('regional_id', 'witel_regional_id_foreign')
                      ->references('id')
                      ->on('regionals')
                      ->onDelete('set null')
                      ->onUpdate('cascade');
            });

            Log::info('✅ Successfully added regional_id to witel table');

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
            Log::info('🔄 Removing regional_id from witel table');

            Schema::table('witel', function (Blueprint $table) {
                // Drop foreign key first
                $table->dropForeign('witel_regional_id_foreign');
                
                // Drop index
                $table->dropIndex('witel_regional_id_index');
                
                // Drop column
                $table->dropColumn('regional_id');
            });

            Log::info('✅ Successfully removed regional_id from witel table');

        } catch (\Exception $e) {
            Log::error('❌ Migration rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};