<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AidItem;

class AidItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'طرد غذائي', 'description' => 'سلة غذائية كاملة', 'is_active' => true],
            ['name' => 'بطانية', 'description' => 'بطانية شتوية', 'is_active' => true],
            ['name' => 'مواد تنظيف', 'description' => 'طقم مواد تنظيف أساسي', 'is_active' => true],
        ];

        foreach ($items as $item) {
            AidItem::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
