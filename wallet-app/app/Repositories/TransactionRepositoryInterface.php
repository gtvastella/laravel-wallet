<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;

interface TransactionRepositoryInterface extends RepositoryInterface
{
    public function getUserTransactions(User $user, array $with = []);
    public function createDeposit(User $recipient, float $amount);
    public function createTransfer(User $sender, User $recipient, float $amount);
    public function createReversal(Transaction $originalTransaction, array $data);
    public function markAsReversed(Transaction $transaction);
}
