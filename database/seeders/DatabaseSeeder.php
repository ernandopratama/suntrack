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
        $company = Company::create([
            'name' => 'SunTrack Enterprise',
        ]);

        $brand = Brand::create([
            'company_id' => $company->id,
            'name' => 'Acme Corp (Brand A)',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'admin@suntrack.com',
            'password' => Hash::make('password'),
        ]);

        $this->call(RolePermissionSeeder::class);

        $this->command->info('Database seeded with Company, Brand, Admin, and RBAC Roles.');
    }
}
