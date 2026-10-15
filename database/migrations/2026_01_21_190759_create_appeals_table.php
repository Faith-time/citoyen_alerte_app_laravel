<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('citizen_id')->constrained('users')->onDelete('cascade');

            $table->text('reason');
            $table->json('evidence')->nullable();

            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();

            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();

            $table->index('report_id');
            $table->index('citizen_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeals');
    }
};
