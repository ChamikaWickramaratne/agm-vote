<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ballots', function (Blueprint $table) {
            // If you previously added a unique constraint, drop it.
            // Adjust the name to whatever you used earlier.
            try {
                $table->dropUnique('ballots_session_code_unique');
            } catch (\Throwable $e) {
                // ignore if it didn't exist
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty — we don't want to restore uniqueness.
    }
};
