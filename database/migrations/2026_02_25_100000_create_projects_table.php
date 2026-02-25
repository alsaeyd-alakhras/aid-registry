<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('project_number', 50)->unique();
            $table->string('name');

            $table->foreignId('institution_id')
                ->constrained('institutions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->enum('project_type', ['cash', 'in_kind']);

            $table->foreignId('aid_item_id')
                ->nullable()
                ->constrained('aid_items')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('total_quantity', 12, 2)->nullable()->default(0);
            $table->decimal('consumed_quantity', 12, 2)->nullable()->default(0);

            $table->decimal('total_amount_ils', 12, 2)->nullable()->default(0);
            $table->decimal('consumed_amount', 12, 2)->nullable()->default(0);

            $table->decimal('estimated_amount', 12, 2)->nullable();

            $table->unsignedInteger('beneficiaries_total')->default(0);
            $table->unsignedInteger('beneficiaries_consumed')->default(0);

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->enum('dependency_type', ['admin', 'office'])->default('admin');

            $table->foreignId('dependency_office_id')
                ->nullable()
                ->constrained('offices')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['institution_id', 'project_type']);
            $table->index('dependency_office_id');
            $table->index('created_by');
            $table->index('project_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
