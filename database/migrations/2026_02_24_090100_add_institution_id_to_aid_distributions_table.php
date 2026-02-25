<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aid_distributions', function (Blueprint $table) {
            $table->foreignId('institution_id')
                ->nullable()
                ->after('office_id')
                ->constrained('institutions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        // $now = now();
        // $seedNames = [
        //     'مؤسسة الرحمة',
        //     'مؤسسة التكافل',
        //     'لجنة الزكاة',
        //     'الهلال الخيري',
        // ];

        // foreach ($seedNames as $name) {
        //     DB::table('institutions')->updateOrInsert(
        //         ['name' => $name],
        //         ['notes' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        //     );
        // }

        // $defaultInstitutionId = DB::table('institutions')->where('is_active', true)->orderBy('id')->value('id');
        // if ($defaultInstitutionId) {
        //     DB::table('aid_distributions')
        //         ->whereNull('institution_id')
        //         ->update(['institution_id' => $defaultInstitutionId]);
        // }

        // $driver = Schema::getConnection()->getDriverName();
        // if ($driver === 'mysql') {
        //     DB::statement('ALTER TABLE aid_distributions MODIFY institution_id BIGINT UNSIGNED NOT NULL');
        // } elseif ($driver === 'pgsql') {
        //     DB::statement('ALTER TABLE aid_distributions ALTER COLUMN institution_id SET NOT NULL');
        // }
    }

    public function down(): void
    {
        Schema::table('aid_distributions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};
