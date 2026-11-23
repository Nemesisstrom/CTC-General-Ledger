<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBranchAndRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_register_with_valid_branch_and_gets_staff_role_once(): void
    {
        Event::fake([Registered::class]);

        $branch = Branch::firstOrCreate(
            ['code' => 'CBG-TST'],
            ['name' => 'Cabang Pengujian', 'is_active' => true]
        );

        $response = $this->post(route('register.post'), [
            'name'                  => 'Karyawan Uji',
            'email'                 => 'karyawan.uji@ctc.co.id',
            'branch_id'             => $branch->id,
            'password'              => 'PasswordAman123!',
            'password_confirmation' => 'PasswordAman123!',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'karyawan.uji@ctc.co.id')->first();
        $this->assertNotNull($user);
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertTrue($user->hasRole('staff'));
        $this->assertFalse($user->hasRole('admin'));

        Event::assertDispatchedTimes(Registered::class, 1);
    }

    public function test_registration_rejects_client_supplied_role_and_base_salary(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'CBG-TST2'],
            ['name' => 'Cabang Uji 2', 'is_active' => true]
        );

        $response = $this->from(route('register'))->post(route('register.post'), [
            'name'                  => 'Percobaan Eskalasi',
            'email'                 => 'eskalasi@ctc.co.id',
            'branch_id'             => $branch->id,
            'password'              => 'PasswordAman123!',
            'password_confirmation' => 'PasswordAman123!',
            'role'                  => 'admin',
            'base_salary'           => 25000000,
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['role', 'base_salary']);
        $this->assertDatabaseMissing('users', ['email' => 'eskalasi@ctc.co.id']);
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active'         => true,
        ]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_staff_cannot_access_restricted_accounting_and_user_pages(): void
    {
        $staff = User::factory()->create([
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_non_admin_user_only_sees_own_branch_records(): void
    {
        $branchA = Branch::firstOrCreate(
            ['code' => 'CBG-A'],
            ['name' => 'Cabang A', 'is_active' => true]
        );
        $branchB = Branch::firstOrCreate(
            ['code' => 'CBG-B'],
            ['name' => 'Cabang B', 'is_active' => true]
        );

        $staffA = User::factory()->create([
            'branch_id'         => $branchA->id,
            'email_verified_at' => now(),
            'is_active'         => true,
            'password'          => Hash::make('PasswordAman123!'),
        ]);
        $staffA->assignRole('staff');

        $coas = ChartOfAccount::query()->take(2)->pluck('id');
        $accountId = $coas->first() ?? 1;
        $oppositeAccountId = $coas->last() ?? $accountId;

        $buildPayload = fn (int $branchId, string $prefix, string $desc, int $amount): array => [
            'branch_id'           => $branchId,
            'transaction_number'  => $prefix . '-' . uniqid(),
            'transaction_date'    => now()->toDateString(),
            'type'                => 'in',
            'account_id'          => $accountId,
            'opposite_account_id' => $oppositeAccountId,
            'amount'              => $amount,
            'description'         => $desc,
            'created_by'          => $staffA->id,
        ];

        $txA = CashTransaction::withoutGlobalScopes()->forceCreate(
            $buildPayload($branchA->id, 'KAS-TST-A', 'Kas Cabang A', 100000)
        );

        $txB = CashTransaction::withoutGlobalScopes()->forceCreate(
            $buildPayload($branchB->id, 'KAS-TST-B', 'Kas Cabang B', 200000)
        );

        $this->actingAs($staffA);

        $visibleIds = CashTransaction::query()->pluck('id')->all();
        $this->assertContains($txA->id, $visibleIds);
        $this->assertNotContains($txB->id, $visibleIds);
    }
}
