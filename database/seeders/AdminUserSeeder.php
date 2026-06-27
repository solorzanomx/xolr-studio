<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles (idempotente)
        $roles = ['owner', 'admin', 'editor', 'viewer', 'client'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Crear usuario administrador principal
        $user = User::firstOrCreate(
            ['email' => 'lechugadesign@gmail.com'],
            [
                'name'     => 'Alejandro Solórzano',
                'password' => Hash::make('xolrstudio2026'),
                'timezone' => 'America/Mexico_City',
            ]
        );

        $user->assignRole('owner');
    }
}
