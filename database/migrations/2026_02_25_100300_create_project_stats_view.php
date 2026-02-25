<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE VIEW project_stats_view AS
            SELECT
                p.id,
                p.project_number,
                p.name,
                p.institution_id,
                p.project_type,
                p.aid_item_id,
                p.total_quantity,
                p.consumed_quantity,
                (p.total_quantity - p.consumed_quantity) AS remaining_quantity,
                p.total_amount_ils,
                p.consumed_amount,
                (p.total_amount_ils - p.consumed_amount) AS remaining_amount,
                p.estimated_amount,
                p.beneficiaries_total,
                p.beneficiaries_consumed,
                (p.beneficiaries_total - p.beneficiaries_consumed) AS remaining_beneficiaries,
                p.created_by,
                p.dependency_type,
                p.dependency_office_id,
                p.notes,
                p.created_at,
                p.updated_at,
                COUNT(ad.id) AS aid_distributions_count
            FROM projects p
            LEFT JOIN aid_distributions ad ON ad.project_id = p.id AND ad.status = 'active'
            GROUP BY
                p.id,
                p.project_number,
                p.name,
                p.institution_id,
                p.project_type,
                p.aid_item_id,
                p.total_quantity,
                p.consumed_quantity,
                p.total_amount_ils,
                p.consumed_amount,
                p.estimated_amount,
                p.beneficiaries_total,
                p.beneficiaries_consumed,
                p.created_by,
                p.dependency_type,
                p.dependency_office_id,
                p.notes,
                p.created_at,
                p.updated_at
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS project_stats_view');
    }
};
