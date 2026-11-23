<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Branch::firstOrCreate(
            ['code' => 'CBG-001'],
            [
                'name' => 'Kantor Pusat (Head Office)',
                'phone' => '021-88990011',
                'address' => 'Depok, Jawa Barat, Indonesia',
                'is_active' => true,
            ]
        );

        Branch::firstOrCreate(
            ['code' => 'CBG-001'],
            [
                'name' => 'Cabang Jakarta',
                'phone' => '021-55667788',
                'address' => 'Gedung Wisma Bisnis Indonesia 2, Jl Ir H Juanda III No 32, Jakarta Pusat',
                'is_active' => true,
            ]
        );

        // Kaitkan user yang belum memiliki cabang ke Kantor Pusat
        User::whereNull('branch_id')->update(['branch_id' => $pusat->id]);
    }
}
