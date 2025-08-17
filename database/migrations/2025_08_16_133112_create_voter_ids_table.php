<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('voter_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();

            // Store a HASH of the code (recommended). If you must keep plaintext, rename to voter_code_plain.
            $table->string('voter_code_hash');

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->boolean('used')->default(false)->index();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->index(['voting_session_id', 'used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voter_ids');
    }
};
