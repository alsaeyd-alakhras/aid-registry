<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_office_allocations', function (Blueprint $table) {
            $table->boolean('received')->default(false)->after('max_quantity');
            $table->string('receipt_file_path')->nullable()->after('received');
        });
    }

    public function down(): void
    {
        Schema::table('project_office_allocations', function (Blueprint $table) {
            $table->dropColumn(['received', 'receipt_file_path']);
        });
    }
};
