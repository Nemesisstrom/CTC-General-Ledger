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

        // 1. Buat Daftar Permission per Modul
        $permissions = [
            // User & Master Data
            'manage-users',
            'manage-coa',

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
        $adminRole->givePermissionTo(Permission::all());

        // 3. Buat Role Akuntan & Assign Permission Spesifik
        $akuntanRole = Role::findOrCreate('akuntan', 'web');
        $akuntanRole->givePermissionTo([
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

        // 4. Buat User Default dan Assign Role Spatie
        // Buat Admin
        $admin = User::create([
            'name'        => 'Administrator',
            'email'       => 'admin@perusahaan.com',
            'password'    => Hash::make('admin123'),
            'role'        => 'admin', // Isi kolom Enum
            'base_salary' => 10000000,
        ]);
        $admin->assignRole('admin'); // Assign Role Spatie

        // Buat Akuntan
        $akuntan = User::create([
            'name'        => 'Akuntan Utama',
            'email'       => 'akuntan@perusahaan.com',
            'password'    => Hash::make('akuntan123'),
            'role'        => 'akuntan', // Isi kolom Enum
            'base_salary' => 8000000,
        ]);
        $akuntan->assignRole('akuntan'); // Assign Role Spatie
    }
}
