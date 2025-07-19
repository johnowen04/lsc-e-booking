<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // ✅ secure hash
            ]
        );

        // Assign super_admin role using Filament Shield
        Artisan::call('shield:super-admin', [
            '--user' => $user->id,
            '--panel' => 'admin', // Change if your panel ID is different
        ]);
    }
}
