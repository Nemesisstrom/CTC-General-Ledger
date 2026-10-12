<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ChartOfAccount; // 👈 Pastikan di-import

class JournalDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'chart_of_account_id', // Sesuaikan dengan nama kolom foreign key di database Anda
        'debit',
        'credit',
        'description',
    ];

    /**
     * Relasi ke Chart of Account
     */
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * Relasi ke Journal Utama (jika diperlukan)
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
