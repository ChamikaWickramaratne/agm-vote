<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
       Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('branch')->nullable();
            $table->string('member_type')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

    }
    public function down(): void {
        Schema::dropIfExists('members');
    }
};
