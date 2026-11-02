<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    public function test_registration_rejects_a_client_supplied_role(): void
    {
        $rules = (new RegisterRequest())->rules();
        $validator = Validator::make(['role' => 'admin'], ['role' => $rules['role']]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('role'));
    }

    public function test_registration_allows_the_server_to_assign_the_role(): void
    {
        $rules = (new RegisterRequest())->rules();
        $validator = Validator::make([], ['role' => $rules['role']]);

        $this->assertTrue($validator->passes());
    }

    public function test_registration_rejects_a_client_supplied_base_salary(): void
    {
        $rules = (new RegisterRequest())->rules();

        foreach ([10000000, 0, null, ''] as $baseSalary) {
            $validator = Validator::make(
                ['base_salary' => $baseSalary],
                ['base_salary' => $rules['base_salary']]
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('base_salary'));
        }
    }

    public function test_registration_allows_the_server_to_assign_the_base_salary(): void
    {
        $rules = (new RegisterRequest())->rules();
        $validator = Validator::make([], ['base_salary' => $rules['base_salary']]);

        $this->assertTrue($validator->passes());
    }
}
