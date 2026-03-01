<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_office_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->integer('max_beneficiaries')->unsigned()->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->decimal('max_quantity', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'office_id']);
            $table->index('project_id');
            $table->index('office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_office_allocations');
    }
};
