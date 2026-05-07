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
        // updateOrCreate mencari berdasarkan parameter pertama (email).
        // Jika tidak ada, buat baru dengan parameter kedua. Jika ada, perbarui datanya.
        User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Orderly Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
