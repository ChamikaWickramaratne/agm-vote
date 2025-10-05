<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conference_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at')->nullable(); // optional
            $table->timestamps();

            $table->unique(['conference_id','member_id']); // one row per conf+member
        });
    }
    public function down(): void {
        Schema::dropIfExists('conference_members');
    }
};
