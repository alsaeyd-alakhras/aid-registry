<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->json('spouses')->nullable()->after('spouse_full_name');

            $table->string('wife_1_national_id_gen', 20)
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[0].national_id'))");
            $table->string('wife_2_national_id_gen', 20)
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[1].national_id'))");
            $table->string('wife_3_national_id_gen', 20)
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[2].national_id'))");
            $table->string('wife_4_national_id_gen', 20)
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[3].national_id'))");

            $table->string('wife_1_full_name_gen')
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[0].full_name'))");
            $table->string('wife_2_full_name_gen')
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[1].full_name'))");
            $table->string('wife_3_full_name_gen')
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[2].full_name'))");
            $table->string('wife_4_full_name_gen')
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(spouses, '$[3].full_name'))");

            $table->index('wife_1_national_id_gen');
            $table->index('wife_2_national_id_gen');
            $table->index('wife_3_national_id_gen');
            $table->index('wife_4_national_id_gen');
        });

        DB::table('families')
            ->whereNull('spouses')
            ->whereNotNull('spouse_national_id')
            ->update([
                'spouses' => DB::raw("JSON_ARRAY(JSON_OBJECT('full_name', spouse_full_name, 'national_id', spouse_national_id))"),
            ]);
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex(['wife_1_national_id_gen']);
            $table->dropIndex(['wife_2_national_id_gen']);
            $table->dropIndex(['wife_3_national_id_gen']);
            $table->dropIndex(['wife_4_national_id_gen']);

            $table->dropColumn([
                'wife_1_national_id_gen',
                'wife_2_national_id_gen',
                'wife_3_national_id_gen',
                'wife_4_national_id_gen',
                'wife_1_full_name_gen',
                'wife_2_full_name_gen',
                'wife_3_full_name_gen',
                'wife_4_full_name_gen',
                'spouses',
            ]);
        });
    }
};
