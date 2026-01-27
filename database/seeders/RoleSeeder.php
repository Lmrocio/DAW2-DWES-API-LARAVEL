<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    // Guía docente: ver docs/07_roles_permisos.md.
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creamos roles con el guard de Sanctum (tokens), no con web.
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);

        Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'sanctum',
        ]);
    }
}
