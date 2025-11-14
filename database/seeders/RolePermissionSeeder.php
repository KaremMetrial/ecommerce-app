<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage users',
            'manage products',
            'manage categories',
            'manage orders',
            'manage coupons',
            'manage payments',
            'view admin dashboard',
            'view own orders',
            'manage own cart',
            'manage own wishlist',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // Assign permissions to admin role
        $adminRole->givePermissionTo($permissions);

        // Assign basic permissions to customer role
        $customerRole->givePermissionTo([
            'view own orders',
            'manage own cart',
            'manage own wishlist',
        ]);
    }
}
