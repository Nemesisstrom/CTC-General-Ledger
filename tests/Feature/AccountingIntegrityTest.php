<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $expenseAccount;
    private ChartOfAccount $retainedEarningsAccount;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::create(['name' => 'admin']);
        $permissions = [
            'manage-journals',
            'reverse-journals',
            'manage-cash-transactions',
            'manage-adjustments',
        ];
        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
        }
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole($adminRole);

        $parentAsset = ChartOfAccount::create([
            'code'           => '1-1100',
            'name'           => 'Kas & Bank',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $this->cashAccount = ChartOfAccount::create([
            'code'           => '1-1110',
            'name'           => 'Kas Utama',
            'type'           => 'asset',
            'normal_balance' => 'debit',
            'parent_id'      => $parentAsset->id,
            'is_active'      => true,
        ]);

        $parentEquity = ChartOfAccount::create([
            'code'           => '3-1000',
            'name'           => 'Ekuitas',
            'type'           => 'equity',
            'normal_balance' => 'credit',
            'is_active'      => true,
        ]);

        $this->retainedEarningsAccount = ChartOfAccount::create([
            'code'           => '3-1200',
            'name'           => 'Laba Ditahan',
            'type'           => 'equity',
            'normal_balance' => 'credit',
            'parent_id'      => $parentEquity->id,
            'is_active'      => true,
        ]);

        $parentRevenue = ChartOfAccount::create([
            'code'           => '4-1000',
            'name'           => 'Pendapatan Usaha',
            'type'           => 'revenue',
            'normal_balance' => 'credit',
            'is_active'      => true,
        ]);

        $this->revenueAccount = ChartOfAccount::create([
            'code'           => '4-1100',
            'name'           => 'Pendapatan Jasa',
            'type'           => 'revenue',
            'normal_balance' => 'credit',
            'parent_id'      => $parentRevenue->id,
            'is_active'      => true,
        ]);

        $parentExpense = ChartOfAccount::create([
            'code'           => '5-1000',
            'name'           => 'Beban Operasional',
            'type'           => 'expense',
            'normal_balance' => 'debit',
            'is_active'      => true,
        ]);

        $this->expenseAccount = ChartOfAccount::create([
            'code'           => '5-1100',
            'name'           => 'Beban Perlengkapan',
            'type'           => 'expense',
            'normal_balance' => 'debit',
            'parent_id'      => $parentExpense->id,
            'is_active'      => true,
        ]);
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/journals', [
            'journal_date' => '2026-11-10',
            'description'  => 'Jurnal Tidak Seimbang',
            'items'        => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 450000],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_balanced_journal_is_posted_and_can_be_reversed(): void
    {
        $createResponse = $this->actingAs($this->admin)->postJson('/api/v1/journals', [
            'journal_date' => '2026-11-10',
            'description'  => 'Penerimaan Pendapatan Jasa',
            'items'        => [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        $createResponse->assertStatus(201);
        $journal = Journal::first();
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);

        $reverseResponse = $this->actingAs($this->admin)->postJson("/api/v1/journals/{$journal->id}/reverse");
        $reverseResponse->assertStatus(200);

        $this->assertEquals('reversed', $journal->fresh()->status);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_closed_year_blocks_new_transactions(): void
    {
        $closingJournal = Journal::create([
            'journal_number'   => 'JRN-CLS-2025-1231',
            'journal_date'     => '2025-12-31',
            'reference_number' => 'CLOSING-2025',
            'description'      => 'Jurnal Penutup Tahun Buku 2025',
            'status'           => 'posted',
            'created_by'       => $this->admin->id,
        ]);

        JournalDetail::create([
            'journal_id' => $closingJournal->id,
            'account_id' => $this->retainedEarningsAccount->id,
            'debit'      => 0,
            'credit'     => 1000000,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/journals', [
            'journal_date' => '2025-08-15',
            'description'  => 'Transaksi Mundur ke Tahun Tutup Buku',
            'items'        => [
                ['account_id' => $this->cashAccount->id, 'debit' => 250000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 250000],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('journals', 1);
    }

    public function test_adjustment_journal_creates_balanced_entries(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/adjustments', [
            'adjustment_date'        => '2026-11-13',
            'expense_account_id'     => $this->expenseAccount->id,
            'accumulated_account_id' => $this->cashAccount->id,
            'amount'                 => 350000,
            'description'            => 'Penyesuaian Pemakaian Perlengkapan',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('journals', 1);

        $totalDebit = JournalDetail::sum('debit');
        $totalCredit = JournalDetail::sum('credit');
        $this->assertEquals(350000, (float) $totalDebit);
        $this->assertEquals((float) $totalDebit, (float) $totalCredit);
    }
}
