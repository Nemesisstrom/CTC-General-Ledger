<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::creating(function (Model $model) {
            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();

            if ($user->hasRole('admin')) {
                if (!$model->branch_id) {
                    $model->branch_id = session('active_branch_id') ?: $user->branch_id;
                }

                return;
            }

            if ($user->branch_id) {
                $model->branch_id = $user->branch_id;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();
            $table = $builder->getModel()->getTable();

            if ($user->hasRole('admin')) {
                $activeBranchId = session('active_branch_id');
                if (!empty($activeBranchId)) {
                    $builder->where("{$table}.branch_id", $activeBranchId);
                }

                return;
            }

            if ($user->branch_id) {
                $builder->where("{$table}.branch_id", $user->branch_id);
            } else {
                $builder->whereRaw('1 = 0');
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
