<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends BaseRepository
{
    public function __construct(Transaction $model = null)
    {
        parent::__construct($model ?? new Transaction());
    }

    public function getUserTransactions(User $user, array $with = [])
    {
        return $this->model
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->with($with)
            ->orderByDesc('created_at')
            ->get();
    }

    public function createDeposit(User $recipient, float $amount)
    {
        return $this->create([
            'type' => 'deposit',
            'amount' => $amount,
            'sender_id' => null,
            'recipient_id' => $recipient->id,
            'status' => 'completed'
        ]);
    }

    public function createTransfer(User $sender, User $recipient, float $amount)
    {
        return $this->create([
            'type' => 'transfer',
            'amount' => $amount,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'completed'
        ]);
    }

    public function createReversal(Transaction $originalTransaction, array $data)
    {
        return $this->create([
            'type' => 'reversal',
            'amount' => $originalTransaction->amount,
            'sender_id' => $data['sender_id'],
            'recipient_id' => $data['recipient_id'],
            'status' => 'completed',
            'reference_id' => $originalTransaction->id
        ]);
    }

    public function markAsReversed(Transaction $transaction)
    {
        $transaction->status = 'reversed';
        $transaction->save();
        return $transaction;
    }
}
