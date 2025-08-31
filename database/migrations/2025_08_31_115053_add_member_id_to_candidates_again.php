<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('candidates','member_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                // For SQLite, adding FKs on ALTER may be skipped; that's OK in dev.
                $table->unsignedBigInteger('member_id')->nullable()->after('position_id');
            });

            // Add the FK separately (works on MySQL/Postgres; may be skipped on SQLite)
            Schema::table('candidates', function (Blueprint $table) {
                if (config('database.default') !== 'sqlite') {
                    $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void {
        if (Schema::hasColumn('candidates','member_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (config('database.default') !== 'sqlite') {
                    $table->dropForeign(['member_id']);
                }
                $table->dropColumn('member_id');
            });
        }
    }
};
