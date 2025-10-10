<?php

// database/migrations/xxxx_xx_xx_make_vsid_nullable_on_voter_ids.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Normal path: alter column
            Schema::table('voter_ids', function (Blueprint $table) {
                try { $table->dropForeign(['voting_session_id']); } catch (\Throwable $e) {}
                $table->unsignedBigInteger('voting_session_id')->nullable()->change();
                // Optional FK that allows NULLs
                try {
                    $table->foreign('voting_session_id')
                          ->references('id')->on('voting_sessions')
                          ->nullOnDelete();
                } catch (\Throwable $e) {}
            });
            return;
        }

        // ---- SQLite path: rebuild the table with voting_session_id nullable ----
        Schema::disableForeignKeyConstraints();

        // Clean any previous partial run
        if (Schema::hasTable('voter_ids_tmp')) {
            Schema::drop('voter_ids_tmp');
        }

        // 1) Create new temp table with desired schema
        Schema::create('voter_ids_tmp', function (Blueprint $table) {
            $table->id();
            // SQLite won't enforce FK here, but keep the columns consistent
            $table->unsignedBigInteger('conference_id')->nullable();
            $table->unsignedBigInteger('voting_session_id')->nullable(); // <-- NOW NULLABLE
            $table->unsignedBigInteger('member_id');                     // keep NOT NULL (codes must belong to a member)
            $table->string('voter_code_hash');
            $table->text('voter_code_encrypted')->nullable();            // include only if your table has it
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            // Helpful index
            $table->index(['conference_id', 'member_id'], 'voter_ids_conf_member_idx');
        });

        // 2) Work out the overlapping columns between old and new
        $oldCols = Schema::getColumnListing('voter_ids');
        $newCols = Schema::getColumnListing('voter_ids_tmp');
        $colsArr = array_values(array_intersect($oldCols, $newCols));
        $cols    = implode(', ', $colsArr);

        // 3) Copy data over. Skip rows with NULL member_id (they violate the NOT NULL)
        DB::statement("
            INSERT INTO voter_ids_tmp ($cols)
            SELECT $cols FROM voter_ids
            WHERE member_id IS NOT NULL
        ");

        // 4) Swap tables
        Schema::drop('voter_ids');
        Schema::rename('voter_ids_tmp', 'voter_ids');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('voter_ids', function (Blueprint $table) {
                try { $table->dropForeign(['voting_session_id']); } catch (\Throwable $e) {}
                $table->unsignedBigInteger('voting_session_id')->nullable(false)->change();
                try {
                    $table->foreign('voting_session_id')
                          ->references('id')->on('voting_sessions')
                          ->cascadeOnDelete();
                } catch (\Throwable $e) {}
            });
            return;
        }

        // (Optional) reverse for SQLite — usually not needed.
    }
};
