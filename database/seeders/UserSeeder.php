<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Créer un admin
        $admin = User::create([
            'name' => 'Jude',
            'email' => 'mpoyojude0@gmail.com',
            'password' => Hash::make('12345678'),
            'profile_photo' => 'users/admin.jpg',
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        // Créer un manager
        $manager = User::create([
            'name' => 'test Image',
            'email' => 'testimage@gmail.com',
            'password' => Hash::make('12345678'),
            'profile_photo' => 'users/manager.jpg',
            'is_active' => true,
        ]);
        $manager->assignRole('manager');

        // Créer un vendeur
        $customer = User::create([
            'name' => 'Tesad',
            'email' => 'tesadtim@gmail.com',
            'password' => Hash::make('12345678'),
            'profile_photo' => 'users/customer.jpg',
            'is_active' => true,
        ]);
        $customer->assignRole('cashier');

        
    }
}