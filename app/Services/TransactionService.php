<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

class TransactionService
{
    /**
     * @param  array{category_id: ?int, type: string, amount: int, occurred_on: string, note: ?string}  $data
     */
    public function record(array $data, User $user): Transaction
    {
        $transaction = new Transaction($data);
        // tenant_id diisi otomatis oleh BelongsToTenant dari user yang login.
        $transaction->user_id = $user->id;
        $transaction->save();

        return $transaction;
    }

    /**
     * @param  array{category_id: ?int, type: string, amount: int, occurred_on: string, note: ?string}  $data
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);

        return $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
