<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// tenant_id diisi otomatis oleh BelongsToTenant, jadi tidak boleh fillable.
#[Fillable(['name', 'type'])]
class Category extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeOfType(Builder $query, TransactionType $type): Builder
    {
        return $query->where('type', $type);
    }
}
