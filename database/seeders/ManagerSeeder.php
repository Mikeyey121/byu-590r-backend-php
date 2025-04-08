<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProjectManager;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managers = [
            [
                'managerName' => 'Bobthe Builder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'managerName' => 'Canhe Fixit',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'managerName' => 'Yeshe Can',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];

        ProjectManager::insert($managers);
        
    }
}
