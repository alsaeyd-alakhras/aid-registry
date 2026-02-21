<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('aid_distributions', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->nullable()->after('aid_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('aid_distributions', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
