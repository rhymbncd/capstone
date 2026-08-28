<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD');

        if (blank($password)) {
            $this->command?->warn('AdminSeeder skipped — set ADMIN_SEED_PASSWORD to seed admin accounts.');

            return;
        }

        foreach (config('admin.emails') as $email) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin',
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'approval_status' => 'approved',
                ]
            );
        }
    }
}
