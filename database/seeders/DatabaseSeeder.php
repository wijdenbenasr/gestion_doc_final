<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Compte administrateur par défaut
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'prenom' => 'QMS',
                'cin' => 'ADMIN-001',
                'matricule' => 'MAT-0001',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_admin_approved' => true,
                'admin_approved_at' => now(),
            ]
        );
    }
}
