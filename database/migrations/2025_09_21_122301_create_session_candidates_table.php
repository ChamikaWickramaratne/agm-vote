<?php

// database/migrations/2025_09_21_000000_create_session_candidates_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('session_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->unique(['voting_session_id', 'candidate_id']);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('session_candidates');
    }
};
