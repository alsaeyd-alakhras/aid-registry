<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_distribution_import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('aid_distribution_import_batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedInteger('row_number');

            $table->json('payload');

            $table->boolean('duplicate_in_file')->default(false);
            $table->boolean('duplicate_in_db')->default(false);
            $table->json('duplicate_details')->nullable();

            $table->boolean('has_error')->default(false);
            $table->json('error_messages')->nullable();

            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('created_distribution_id')
                ->nullable()
                ->constrained('aid_distributions')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index('batch_id');
            $table->index(['batch_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_distribution_import_rows');
    }
};
