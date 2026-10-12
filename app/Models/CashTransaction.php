<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'type',
        'account_id',
        'opposite_account_id',
        'amount',
        'description',
        'created_by',
    ];

    // Akun Kas/Bank Utama
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    // Akun Lawan
    public function oppositeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'opposite_account_id');
    }

    // Pembuat Transaksi
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
