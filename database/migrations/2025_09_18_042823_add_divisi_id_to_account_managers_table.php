<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('account_managers', 'divisi_id')) {
                $table->unsignedBigInteger('divisi_id')->nullable()->after('role');
                $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_managers', function (Blueprint $table) {
            if (Schema::hasColumn('account_managers', 'divisi_id')) {
                $table->dropForeign(['divisi_id']);
                $table->dropColumn('divisi_id');
            }
        });
    }
};
