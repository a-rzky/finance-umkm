<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// tenant_id diisi otomatis oleh BelongsToTenant dan user_id diisi dari user yang
// login, jadi keduanya tidak boleh fillable.
#[Fillable(['category_id', 'type', 'amount', 'occurred_on', 'note'])]
class Transaction extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBetweenDates(Builder $query, string $from, string $until): Builder
    {
        return $query->whereBetween('occurred_on', [$from, $until]);
    }
}
