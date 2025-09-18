<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voter_ids', function (Blueprint $table) {
            // Add conference scope and an optional encrypted view of the code for managers
            $table->foreignId('conference_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('voter_code_encrypted')->nullable()->after('voter_code_hash');

            // Useful index when resolving a member's code at conference scope
            $table->index(['conference_id', 'member_id']);
        });

        // Backfill from sessions -> conferences (MySQL / MariaDB syntax)
        // If you use SQLite in dev, wrap in try/catch or gate by driver.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE voter_ids vi
                JOIN voting_sessions vs ON vs.id = vi.voting_session_id
                SET vi.conference_id = vs.conference_id
                WHERE vi.conference_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('voter_ids', function (Blueprint $table) {
            $table->dropIndex(['conference_id', 'member_id']);
            $table->dropColumn('voter_code_encrypted');
            $table->dropConstrainedForeignId('conference_id');
        });
    }
};
