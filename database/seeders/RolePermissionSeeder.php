<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
    }
}
