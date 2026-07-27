<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Spatie jika durung tersedia
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole  = Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);

        // 2. Pastikan akun Admin default ada
        $adminUser = User::where('email', 'admin@chainguard.com')->first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name'     => 'Administrator',
                'email'    => 'admin@chainguard.com',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]);
        } else {
            $adminUser->update(['role' => 'admin']);
        }
        $adminUser->assignRole($adminRole);

        // 3. Update user pertama yang belum punya role menjadi admin jika belum ada admin lain
        $firstUser = User::first();
        if ($firstUser && $firstUser->role !== 'admin') {
            $firstUser->update(['role' => 'admin']);
            $firstUser->assignRole($adminRole);
        }

        // 4. Update semua user sisa agar memiliki role 'user' jika role masih kosong
        User::whereNull('role')->orWhere('role', '')->update(['role' => 'user']);
        
        foreach (User::where('role', 'user')->get() as $u) {
            $u->assignRole($userRole);
        }
    }
}
