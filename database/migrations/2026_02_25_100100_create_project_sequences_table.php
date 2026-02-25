<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sequences', function (Blueprint $table) {
            $table->id();

            $table->enum('dependency_type', ['admin', 'office']);

            $table->foreignId('office_id')
                ->nullable()
                ->constrained('offices')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedBigInteger('last_number')->default(0);

            $table->timestamps();

            $table->unique(['dependency_type', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sequences');
    }
};
