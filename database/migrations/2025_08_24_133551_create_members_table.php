<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');                // minimal; add more fields later as needed
            $table->string('email')->nullable()->unique(); // optional unique contact
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('members');
    }
};
