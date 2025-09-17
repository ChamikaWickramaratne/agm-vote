<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            // percent as DECIMAL allows things like 50.00, 66.67, etc.
            $table->decimal('majority_percent', 5, 2)->nullable()->after('close_condition');
        });
    }

    public function down(): void {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->dropColumn('majority_percent');
        });
    }
};
