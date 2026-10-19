<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Phase 1 seeds only the demo login accounts (one per role).
     * CRM sample data (companies, leads, deals, tasks) is seeded in later phases.
     * Demo-only passwords — documented in the README, never for production use.
     */
    public function run(): void
    {
        $demo = [
            ['name' => 'Ayesha Admin', 'email' => 'admin@webnetcrm.test', 'role' => User::ROLE_ADMIN],
            ['name' => 'Bilal Manager', 'email' => 'manager@webnetcrm.test', 'role' => User::ROLE_MANAGER],
            ['name' => 'Sara Sales', 'email' => 'sales@webnetcrm.test', 'role' => User::ROLE_SALES_EXECUTIVE],
        ];

        foreach ($demo as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->call([
            PipelineStageSeeder::class,
            CrmDemoSeeder::class,
        ]);
    }
}
