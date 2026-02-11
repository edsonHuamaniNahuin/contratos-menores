<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'ADMIN',
                'description' => 'Administrador del sistema',
            ]
        );

        $email = 'edson_4555@hotmail.com';

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Edson Admin',
                'email' => $email,
                'password' => Hash::make('Admin123!'),
            ]);
        }

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
