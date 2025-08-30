<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            // nullable so existing rows don’t break; one conference -> many sessions
            $table->foreignId('conference_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('conferences')
                  ->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conference_id');
        });
    }
};
