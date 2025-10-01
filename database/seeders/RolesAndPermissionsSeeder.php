<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define roles
        $roles = ['super-admin', 'admin', 'user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Define permissions
        $permissions = [
            'create receipt', 'update receipt', 'delete receipt',
            'create member', 'update member', 'delete member',
            'create group', 'update group', 'delete group',
            'create user', 'update user', 'delete user',
            'create loan application', 'update loan application', 'delete loan application',
            'download documents', 'view member loans', 'view group members', 'view group loans',
            'view member', 'view group', 'view user', 'view loan application',
            'view receipt', 'view documents', 'view activities', 'view reports',
            'view user data', 'view user activities', 'view user permissions', 'create new phase',
        ];

        // Example: assign super-admin role to a known user (optional)
        $email = 'mundia@gmail.com'; // change to your actual super admin email
        $user = User::where('email', $email)->first();

        if ($user && !$user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
            echo "Assigned 'super-admin' role to {$email}\n";
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super-admin and admin
        $allPermissions = Permission::all();

        Role::findByName('super-admin')->syncPermissions($allPermissions);


    }
}
