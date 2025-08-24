<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('voter_ids', function (Blueprint $table) {
            // Optional link to a member for issuance/tracking.
            // Keep it NULL so you can "break" the link after the conference.
            $table->foreignId('member_id')
                  ->nullable()
                  ->after('voting_session_id')
                  ->constrained('members')
                  ->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('voter_ids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
