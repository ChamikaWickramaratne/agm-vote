<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('members', function (Blueprint $table) {
            // New profile fields
            $table->string('title', 20)->nullable()->after('id');
            $table->string('first_name', 255)->nullable()->after('title');
            $table->string('last_name', 255)->nullable()->after('first_name');
            $table->string('branch_name', 255)->nullable()->after('last_name');
            $table->string('member_type', 255)->nullable()->after('branch_name');
            $table->text('bio')->nullable()->after('email');
            $table->string('photo', 512)->nullable()->after('bio');

            // Keep old 'name' for compatibility (we'll keep setting it)
            // $table->string('name')->change();  // no change, just noting it stays
        });
    }

    public function down(): void {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['title','first_name','last_name','branch_name','member_type','bio','photo']);
        });
    }
};