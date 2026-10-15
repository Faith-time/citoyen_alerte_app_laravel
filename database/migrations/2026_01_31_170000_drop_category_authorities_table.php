<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('category_authorities');
    }

    public function down(): void
    {
        // Impossible de restaurer facilement
        // Backup nécessaire
        throw new Exception('Cannot rollback - restore from backup if needed');
    }
};
