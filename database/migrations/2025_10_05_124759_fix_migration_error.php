<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Non-SQLite: if you still need to support other drivers here,
            // you can alter with ->change(); otherwise no-op.
            return;
        }

        Schema::disableForeignKeyConstraints();

        // Clean up from partial runs
        if (Schema::hasTable('voter_ids_tmp')) {
            Schema::drop('voter_ids_tmp');
        }

        // 1) Create temp table with IDENTICAL columns except:
        //    - voting_session_id MUST be nullable
        Schema::create('voter_ids_tmp', function (Blueprint $table) {
            $table->id();

            // PRAGMA showed these columns; mirror types & nullability closely
            $table->unsignedBigInteger('voting_session_id')->nullable(); // <-- make NULLABLE
            $table->string('voter_code_hash');                           // varchar NOT NULL
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->boolean('used')->default(false);                     // tinyint(1) NOT NULL DEFAULT 0
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();         // keep as in your PRAGMA (nullable)
            $table->unsignedBigInteger('conference_id')->nullable();     // nullable in PRAGMA
            $table->text('voter_code_encrypted')->nullable();

            // helpful index you had/need:
            $table->index(['conference_id','member_id'], 'voter_ids_conf_member_idx');
        });

        // 2) Explicitly copy ALL columns
        DB::statement("
            INSERT INTO voter_ids_tmp
                (id, voting_session_id, voter_code_hash, issued_by, issued_at, used, used_at, created_at, updated_at, member_id, conference_id, voter_code_encrypted)
            SELECT
                id, voting_session_id, voter_code_hash, issued_by, issued_at, used, used_at, created_at, updated_at, member_id, conference_id, voter_code_encrypted
            FROM voter_ids
        ");

        // 3) Swap tables
        Schema::drop('voter_ids');
        Schema::rename('voter_ids_tmp', 'voter_ids');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // Optional: rebuild back to NOT NULL if you ever need to rollback.
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('voter_ids_tmp')) {
            Schema::drop('voter_ids_tmp');
        }

        Schema::create('voter_ids_tmp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('voting_session_id');             // NOT NULL again
            $table->string('voter_code_hash');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->boolean('used')->default(false);
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('conference_id')->nullable();
            $table->text('voter_code_encrypted')->nullable();

            $table->index(['conference_id','member_id'], 'voter_ids_conf_member_idx');
        });

        DB::statement("
            INSERT INTO voter_ids_tmp
                (id, voting_session_id, voter_code_hash, issued_by, issued_at, used, used_at, created_at, updated_at, member_id, conference_id, voter_code_encrypted)
            SELECT
                id, voting_session_id, voter_code_hash, issued_by, issued_at, used, used_at, created_at, updated_at, member_id, conference_id, voter_code_encrypted
            FROM voter_ids
        ");

        Schema::drop('voter_ids');
        Schema::rename('voter_ids_tmp', 'voter_ids');

        Schema::enableForeignKeyConstraints();
    }
};
