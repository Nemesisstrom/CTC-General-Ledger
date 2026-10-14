<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat Daftar Permission per Modul (Diselaraskan dengan Web & API Routes)
        $permissions = [
            // User & Master Data
            'manage-users',
            'manage-chart-of-accounts',

            // Keuangan & Kas
            'manage-cash-transactions',
            'manage-journals',
            'reverse-journals',
            'manage-bank-reconciliations',
            'manage-adjustments',

            // Pajak & Payroll
            'process-taxes',
            'manage-payrolls',

            // Laporan
            'view-reports-general-ledger',
            'view-reports-profit-loss',
            'view-reports-balance-sheet',
            'view-reports-tax',
            'view-reports-payroll',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Buat Role Admin & Assign Semua Permission
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // 3. Buat Role Akuntan & Assign Permission Spesifik
        $akuntanRole = Role::findOrCreate('akuntan', 'web');
        $akuntanRole->syncPermissions([
            'manage-chart-of-accounts',
            'manage-cash-transactions',
            'manage-journals',
            'reverse-journals',
            'manage-bank-reconciliations',
            'manage-adjustments',
            'process-taxes',
            'manage-payrolls',
            'view-reports-general-ledger',
            'view-reports-profit-loss',
            'view-reports-balance-sheet',
            'view-reports-tax',
            'view-reports-payroll',
        ]);

        // 4. Buat Role Staff (Role Default Registrasi)
        $staffRole = Role::findOrCreate('staff', 'web');
        $staffRole->syncPermissions([
            'manage-cash-transactions',
            'manage-payrolls',
        ]);

        // 5. Buat User Default (Gunakan firstOrCreate agar Seeder Idempotent)
        
        // Account Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@keuangan.com'],
            [
                'name'              => 'Administrator',
                'password'          => Hash::make('admin123'),
                'role'              => 'admin',
                'base_salary'       => 10000000,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Account Akuntan
        $akuntan = User::firstOrCreate(
            ['email' => 'akuntan@keuangan.com'],
            [
                'name'              => 'Akuntan Utama',
                'password'          => Hash::make('akuntan123'),
                'role'              => 'akuntan',
                'base_salary'       => 8000000,
                'email_verified_at' => now(),
            ]
        );
        $akuntan->syncRoles([$akuntanRole]);

        // Account Staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@keuangan.com'],
            [
                'name'              => 'Staff Operasional',
                'password'          => Hash::make('staff123'),
                'role'              => 'staff',
                'base_salary'       => 5000000,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles([$staffRole]);
    }
}