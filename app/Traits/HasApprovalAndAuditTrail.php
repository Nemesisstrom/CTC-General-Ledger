<?php

namespace App\Traits;

use App\Models\ApprovalSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasApprovalAndAuditTrail
{
    public static function bootHasApprovalAndAuditTrail(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (empty($model->approval_status)) {
                if ($model->requiresApprovalByAmount()) {
                    $model->approval_status = 'draft';
                } else {
                    $model->approval_status = 'approved';

                    if (Auth::check()) {
                        $model->approved_by = Auth::id();
                        $model->approved_at = now();
                    }
                }
            }
        });

        static::deleting(function (Model $model): void {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'deleted_by')) {
                $model->newQuery()
                    ->whereKey($model->getKey())
                    ->update(['deleted_by' => Auth::id()]);
            }
        });
    }

    public function getApprovalModuleKey(): string
    {
        if (property_exists($this, 'approvalModuleKey') && is_string($this->approvalModuleKey)) {
            return $this->approvalModuleKey;
        }

        return $this->getTable();
    }

    public function getApprovalAmount(): float
    {
        foreach (['amount', 'total_amount', 'net_salary', 'gross_salary', 'total_debit'] as $field) {
            if (isset($this->{$field}) && is_numeric($this->{$field})) {
                return (float) $this->{$field};
            }
        }

        return 0.0;
    }

    public function getApprovalThreshold(): float
    {
        return ApprovalSetting::getMinAmountForModule($this->getApprovalModuleKey());
    }

    public function requiresApprovalByAmount(): bool
    {
        $threshold = $this->getApprovalThreshold();

        if ($threshold <= 0) {
            return false;
        }

        return $this->getApprovalAmount() >= $threshold;
    }

    public function submitForApproval(): bool
    {
        if ($this->approval_status !== 'draft') {
            return false;
        }

        return $this->update([
            'approval_status' => 'submitted',
        ]);
    }

    public function approve(?int $approverId = null): bool
    {
        $actorId = $approverId ?? Auth::id();

        if ($this->created_by !== null && (int) $this->created_by === (int) $actorId) {
            // Cegah self-approval bila diperlukan sesuai kebijakan kontrol internal
            return false;
        }

        return $this->update([
            'approval_status' => 'approved',
            'approved_by'     => $actorId,
            'approved_at'     => now(),
        ]);
    }
}
