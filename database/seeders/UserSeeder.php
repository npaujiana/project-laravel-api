<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Penjual',
            'email' => 'penjual@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'seller',
        ]);

        User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
        ]);
        User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'User 3',
            'email' => 'user3@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
        ]);
    }
}
