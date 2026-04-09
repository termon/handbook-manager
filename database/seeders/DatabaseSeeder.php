<?php

namespace Database\Seeders;

use App\Enums\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
            'role' => Role::ADMIN
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@mail.com',
            'password' => Hash::make('password'),
            'role' => Role::USER
        ]);

        User::create([
            'name' => 'Guest',
            'email' => 'guest@mail.com',
            'password' => Hash::make('password'),
            'role' => Role::GUEST
        ]);
        
        User::factory()->author()->create([
            'name' => 'SCEIS Author',
            'email' => 'author1@mail.com',
        ]);
        User::factory()->author()->create([
            'name' => 'Engineering Author',
            'email' => 'author2@mail.com',
        ]);

        $this->call([
           // other seeders
        ]);
    }
}
