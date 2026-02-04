<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Create Regionals Table
 * 
 * PURPOSE:
 * - Support regional/TREG grouping for Witel
 * - Handle cross-TREG scenarios (witel_bill from different TREG)
 * 
 * STRUCTURE:
 * - id (primary key)
 * - nama (varchar) - TREG 1, TREG 2, TREG 3, TREG 4, TREG 5
 * - timestamps
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
        // Create regionals table
        Schema::create('regionals', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->timestamps();
            
            // Index for faster queries
            $table->index('nama');
        });

        // Seed initial data
        $this->seedInitialData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regionals');
    }

    /**
     * Seed initial TREG data
     */
    private function seedInitialData(): void
    {
        $regionals = [
            ['nama' => 'TREG 1', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TREG 2', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TREG 3', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TREG 4', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TREG 5', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('regionals')->insert($regionals);
    }
};