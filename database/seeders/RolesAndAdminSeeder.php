<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $admin  = Role::firstOrCreate(['name' => 'admin']);
        $asesor = Role::firstOrCreate(['name' => 'asesor']);

        // Crear usuario administrador si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'       => 'Cesar Mata',
                'nickname'   => 'admin',
                'password'   => Hash::make('123123'),
                'activo'     => true,
                'Cfolio'     => 0,
                'Dfolio'     => 0,
                'lastfolio'  => 0,
            ]
        );

        // Si ya existía, asegurarse de que nickname y activo estén bien
        $adminUser->fill([
            'nickname' => 'admin',
            'activo'   => true,
        ])->save();

        $adminUser->syncRoles([$admin]);

        // Crear permisos de módulos para el admin (todos en true)
        foreach (\App\Models\User::DEFAULT_MODULES as $modulo) {
            \App\Models\ModuloPermiso::firstOrCreate(
                ['user_id' => $adminUser->id, 'modulo' => $modulo],
                ['habilitado' => true]
            );
        }

        $this->command->info('✓ Roles creados: admin, asesor');
        $this->command->info('✓ Admin: admin@example.com / 123123 (nickname: admin)');
    }
}
