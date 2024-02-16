<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Category;
use App\Models\Product;
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
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

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

        Category::factory()->count(5)->create();

        Product::factory()->count(5)->create();
    }
}
