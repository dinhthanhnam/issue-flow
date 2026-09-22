<?php

namespace Database\Seeders;

use App\Models\Workspace;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Workspace::query()->eachById(function (Workspace $workspace): void {
            foreach ([
                ['name' => 'Bug', 'color' => '#DC2626'],
                ['name' => 'Feature', 'color' => '#2563EB'],
                ['name' => 'Improvement', 'color' => '#16A34A'],
            ] as $label) {
                $workspace->labels()->updateOrCreate(
                    ['name' => $label['name']],
                    ['color' => $label['color']],
                );
            }
        });
    }
}
