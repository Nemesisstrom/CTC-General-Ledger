<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = [
            'view-dashboard',
            'manage-coa',
            'manage-journals',
            'reverse-journals',
            'manage-cash-transactions',
            'manage-adjustments',
            'manage-bank-reconciliations',
            'manage-taxes',
            'process-taxes',
            'manage-payroll',
            'manage-payrolls',
            'manage-closing',
            'manage-users',
            'view-reports',
            'view-reports-general-ledger',
            'view-reports-profit-loss',
            'view-reports-balance-sheet',
            'view-reports-tax',
            'view-reports-payroll',
        ];

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($allPermissions);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->syncPermissions(['view-dashboard']);

        $this->admin = User::factory()->create([
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole($adminRole);

        $this->staff = User::factory()->create([
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $this->staff->assignRole($staffRole);

        $this->seedMinimalChartOfAccounts();
    }

    private function seedMinimalChartOfAccounts(): void
    {
        $assets = ChartOfAccount::create([
            'code'           => '1-1000',
            'name'           => 'Aset Lancar',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        ChartOfAccount::create([
            'code'           => '1-1110',
            'name'           => 'Kas Utama',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $assets->id,
            'is_active'      => true,
        ]);

        $liabilities = ChartOfAccount::create([
            'code'           => '2-1000',
            'name'           => 'Liabilitas Jangka Pendek',
            'type'           => 'liability',
            'normal_balance' => 'credit',
            'is_active'      => true,
        ]);

        ChartOfAccount::create([
            'code'           => '2-1100',
            'name'           => 'Utang Usaha',
            'type'           => 'liability',
            'normal_balance' => 'credit',
            'parent_id'      => $liabilities->id,
            'is_active'      => true,
        ]);

        $equity = ChartOfAccount::create([
            'code'           => '3-1000',
            'name'           => 'Ekuitas',
            'type'           => 'equity',
            'normal_balance' => 'credit',
            'is_active'      => true,
        ]);

        ChartOfAccount::create([
            'code'           => '3-1200',
            'name'           => 'Laba Ditahan',
            'type'           => 'equity',
            'normal_balance' => 'credit',
            'parent_id'      => $equity->id,
            'is_active'      => true,
        ]);

        $revenue = ChartOfAccount::create([
            'code'           => '4-1000',
            'name'           => 'Pendapatan',
            'type'           => 'revenue',
            'normal_balance' => 'credit',
            'is_active'      => true,
        ]);

        ChartOfAccount::create([
            'code'           => '4-1100',
            'name'           => 'Pendapatan Jasa',
            'type'           => 'revenue',
            'normal_balance' => 'credit',
            'parent_id'      => $revenue->id,
            'is_active'      => true,
        ]);

        $expense = ChartOfAccount::create([
            'code'           => '5-1000',
            'name'           => 'Beban Operasional',
            'type'           => 'expense',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        ChartOfAccount::create([
            'code'           => '5-1100',
            'name'           => 'Beban Gaji',
            'type'           => 'expense',
            'normal_balance' => 'debit',
            'parent_id'      => $expense->id,
            'is_active'      => true,
        ]);
    }

    public static function adminPagesProvider(): array
    {
        return [
            'dashboard'             => ['/dashboard'],
            'chart of accounts'     => ['/admin/chart-of-accounts'],
            'journals'              => ['/admin/journals'],
            'journal reversals'     => ['/admin/journals/reversals'],
            'cash transactions'     => ['/admin/cash-transactions'],
            'adjustments'           => ['/admin/adjustments'],
            'bank reconciliations'  => ['/admin/bank-reconciliations'],
            'taxes'                 => ['/admin/taxes'],
            'payrolls'              => ['/admin/payrolls'],
            'users'                 => ['/admin/users'],
            'report general ledger' => ['/admin/reports/general-ledger'],
            'report profit loss'    => ['/admin/reports/profit-loss'],
            'report balance sheet'  => ['/admin/reports/balance-sheet'],
            'report tax'            => ['/admin/reports/tax'],
            'report payroll'        => ['/admin/reports/payroll'],
        ];
    }

    #[DataProvider('adminPagesProvider')]
    public function test_admin_can_render_all_blade_pages_without_errors(string $url): void
    {
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_from_admin_pages(): void
    {
        $response = $this->get('/admin/adjustments');

        $response->assertRedirect('/login');
    }

    public function test_staff_without_permission_gets_forbidden_on_restricted_pages(): void
    {
        $response = $this->actingAs($this->staff)->get('/admin/adjustments');

        $response->assertStatus(403);
    }
}
