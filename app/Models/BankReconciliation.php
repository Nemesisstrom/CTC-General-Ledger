<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'difference',
        'status',
        'notes',
        'reconciled_by',
    ];

    protected $casts = [
        'statement_date'    => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance'      => 'decimal:2',
        'difference'        => 'decimal:2',
    ];

    // Relasi ke Rekening Kas/Bank
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    // Relasi ke Pembuat / Akuntan yang Merekon
    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by')->withDefault([
            'name' => 'Sistem / Unknown'
        ]);
    }
}
