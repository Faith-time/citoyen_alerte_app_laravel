<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_images', function (Blueprint $table) {
            $table->enum('media_type', ['image', 'video'])
                ->default('image')
                ->after('type');
            $table->unsignedInteger('duration_seconds')
                ->nullable()
                ->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('report_images', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'duration_seconds']);
        });
    }
};
