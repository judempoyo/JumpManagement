<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view-dashboard',

            // Products
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',

            // Categories
            'view-categories',
            'create-categories',
            'edit-categories',
            'delete-categories',

            // Customers
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',

            // Suppliers
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',

            // Purchases
            'view-purchases',
            'create-purchases',
            'edit-purchases',
            'delete-purchases',

            // Sales
            'view-sales',
            'create-sales',
            'edit-sales',
            'delete-sales',

            // Inventory
            'view-inventory',
            'manage-inventory',

            // Reports
            'view-reports',

            // Users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Roles
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view-dashboard',
            'view-products',
            'create-products',
            'edit-products',
            'view-categories',
            'create-categories',
            'edit-categories',
            'view-customers',
            'create-customers',
            'edit-customers',
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'view-purchases',
            'create-purchases',
            'edit-purchases',
            'view-sales',
            'create-sales',
            'edit-sales',
            'view-inventory',
            'manage-inventory',
            'view-reports',
        ]);

        $cashier = Role::create(['name' => 'cashier']);
        $cashier->givePermissionTo([
            'view-dashboard',
            'view-products',
            'view-customers',
            'create-customers',
            'view-sales',
            'create-sales',
        ]);

        $stockManager = Role::create(['name' => 'stock-manager']);
        $stockManager->givePermissionTo([
            'view-dashboard',
            'view-products',
            'view-categories',
            'view-suppliers',
            'view-purchases',
            'view-inventory',
            'manage-inventory',
        ]);
    }
}