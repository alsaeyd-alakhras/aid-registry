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
        Schema::table('projects', function (Blueprint $table) {
            $table->date('project_date')->nullable()->after('notes');
            $table->date('execution_date')->nullable()->after('project_date');
            $table->date('receipt_date')->nullable()->after('execution_date');
            $table->string('department', 255)->nullable()->after('receipt_date');
            $table->string('supervisor_name', 255)->nullable()->after('department');
            $table->string('execution_location', 255)->nullable()->after('supervisor_name');
            $table->enum('status', ['active', 'closed'])->default('active')->after('execution_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'project_date',
                'execution_date',
                'receipt_date',
                'department',
                'supervisor_name',
                'execution_location',
                'status',
            ]);
        });
    }
};
