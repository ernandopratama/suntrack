<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'name' => 'SunTrack Enterprise',
        ]);

        Brand::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Acme Corp (Brand A)',
        ]);

        User::firstOrCreate(
            ['email' => 'admin@suntrack.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        $this->call(RolePermissionSeeder::class);

        $this->command->info('Database seeded with Company, Brand, Admin, and RBAC Roles.');
    }
}
