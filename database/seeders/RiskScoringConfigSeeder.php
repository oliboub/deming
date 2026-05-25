<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiskScoringConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('risk_scoring_configs')->count() > 0) {
            return;
        }

        DB::table('risk_scoring_configs')->insert([
            'name'    => 'ISO 27005',
            'formula' => 'probability_x_impact',
            'is_active' => true,
            'probability_levels' => json_encode([
                ['value' => 1, 'label' => 'Rare',        'description' => ''],
                ['value' => 2, 'label' => 'Unlikely',    'description' => ''],
                ['value' => 3, 'label' => 'Possible',    'description' => ''],
                ['value' => 4, 'label' => 'Likely',      'description' => ''],
                ['value' => 5, 'label' => 'Very Likely', 'description' => ''],
            ]),
            'impact_levels' => json_encode([
                ['value' => 1, 'label' => 'Negligible', 'description' => ''],
                ['value' => 2, 'label' => 'Low',        'description' => ''],
                ['value' => 3, 'label' => 'Moderate',   'description' => ''],
                ['value' => 4, 'label' => 'High',       'description' => ''],
                ['value' => 5, 'label' => 'Critical',   'description' => ''],
            ]),
            'exposure_levels' => json_encode([
                ['value' => 0, 'label' => 'Offline',   'description' => ''],
                ['value' => 1, 'label' => 'Internal',  'description' => ''],
                ['value' => 2, 'label' => 'Internet',  'description' => ''],
            ]),
            'vulnerability_levels' => json_encode([
                ['value' => 1, 'label' => 'None',              'description' => ''],
                ['value' => 2, 'label' => 'Known',             'description' => ''],
                ['value' => 3, 'label' => 'Exploitable (int)', 'description' => ''],
                ['value' => 4, 'label' => 'Exploitable (ext)', 'description' => ''],
            ]),
            'risk_thresholds' => json_encode([
                ['level' => 'low',      'label' => 'Low',      'max' => 4,    'color' => '#27ae60'],
                ['level' => 'medium',   'label' => 'Medium',   'max' => 9,    'color' => '#f39c12'],
                ['level' => 'high',     'label' => 'High',     'max' => 16,   'color' => '#e74c3c'],
                ['level' => 'critical', 'label' => 'Critical', 'max' => null, 'color' => '#c0392b'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
