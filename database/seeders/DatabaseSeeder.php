<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['staff_id' => 'HR001'],
            [
                'name' => 'Admin HR',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
    }
}
