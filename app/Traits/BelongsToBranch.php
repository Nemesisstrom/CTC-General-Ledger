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
            if (auth()->check() && !$model->branch_id) {
                $model->branch_id = session('active_branch_id') ?? auth()->user()->branch_id;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                $table = $builder->getQuery()->from;

                if ($user->hasAnyRole(['admin', 'akuntan'])) {
                    if (session()->has('active_branch_id') && session('active_branch_id')) {
                        $builder->where("{$table}.branch_id", session('active_branch_id'));
                    }
                } elseif ($user->branch_id) {
                    $builder->where("{$table}.branch_id", $user->branch_id);
                }
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
