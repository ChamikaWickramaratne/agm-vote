<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_close_after_minutes_to_voting_sessions.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->unsignedInteger('close_after_minutes')->nullable()->after('close_condition'); // only used when close_condition='Timer'
        });
    }
    public function down(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->dropColumn('close_after_minutes');
        });
    }
};

