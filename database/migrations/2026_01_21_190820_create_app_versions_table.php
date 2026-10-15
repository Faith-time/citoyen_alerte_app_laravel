<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['android', 'ios']);
            $table->string('version', 20);
            $table->integer('build_number');

            $table->boolean('is_mandatory')->default(false);
            $table->string('min_supported_version', 20)->nullable();

            $table->text('release_notes')->nullable();
            $table->string('download_url')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('released_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('platform');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
