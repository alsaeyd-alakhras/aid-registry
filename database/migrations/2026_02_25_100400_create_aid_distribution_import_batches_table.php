<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_distribution_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('filename');

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->enum('status', ['pending_review', 'in_progress', 'completed', 'failed', 'cancelled'])
                ->default('pending_review');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);

            $table->json('errors')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('uploaded_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_distribution_import_batches');
    }
};
