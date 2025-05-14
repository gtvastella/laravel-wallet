<?php

namespace App\Services;

use App\Exceptions\TransactionReversalException;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class TransactionService
{
    protected $transactionRepository;
    protected $walletRepository;
    protected $walletService;

    public function __construct(
        TransactionRepository $transactionRepository = null,
        WalletRepository $walletRepository = null,
        WalletService $walletService = null
    ) {
        $this->transactionRepository = $transactionRepository ?? new TransactionRepository();
        $this->walletRepository = $walletRepository ?? new WalletRepository();
        $this->walletService = $walletService ?? new WalletService();
    }

    public function getUserTransactions(User $user)
    {
        return $this->transactionRepository->getUserTransactions($user);
    }

    public function getTransactionById(int $id, User $user)
    {
        $transaction = $this->transactionRepository->find($id);

        if (!$transaction) {
            throw new ModelNotFoundException('Transaction not found');
        }

        if ($transaction->sender_id !== $user->id && $transaction->recipient_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to view this transaction');
        }

        return $transaction;
    }

    public function reverseTransactionById(int $id, User $user)
    {
        $transaction = $this->getTransactionById($id, $user);

        if (!$transaction->isCompleted()) {
            throw new TransactionReversalException('Only completed transactions can be reversed.');
        }

        if ($transaction->isReversed()) {
            throw new TransactionReversalException('This transaction has already been reversed.');
        }

        if ($transaction->isReversal()) {
            throw new TransactionReversalException('Reversal transactions cannot be reversed.');
        }

        $reversal = $this->walletService->reverseTransaction($transaction);

        return [
            'transaction' => $transaction->fresh(),
            'reversal' => $reversal
        ];
    }
}
