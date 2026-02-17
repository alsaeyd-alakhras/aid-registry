<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            ['name' => 'مكتب غزة', 'location' => 'غزة', 'is_active' => true],
            ['name' => 'مكتب خانيونس', 'location' => 'خانيونس', 'is_active' => true],
            ['name' => 'مكتب رفح', 'location' => 'رفح', 'is_active' => true],
        ];

        foreach ($offices as $office) {
            Office::updateOrCreate(
                ['name' => $office['name']],
                $office
            );
        }
    }
}
