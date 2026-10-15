<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_add_authority_role_to_users.php
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('authority_id')
                ->nullable()
                ->after('role')
                ->constrained('authorities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['authority_id']);
            $table->dropColumn('authority_id');
        });

    }
};
