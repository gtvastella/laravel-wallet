<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTransactionTypeException;
use App\Exceptions\RecipientNotFoundException;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Exceptions\TransactionReversalException;
use App\Exceptions\WalletBlockedException;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    protected $walletRepository;
    protected $transactionRepository;
    protected $userRepository;

    public function __construct(
        WalletRepository $walletRepository = null,
        TransactionRepository $transactionRepository = null,
        UserRepository $userRepository = null
    ) {
        $this->walletRepository = $walletRepository ?? new WalletRepository();
        $this->transactionRepository = $transactionRepository ?? new TransactionRepository();
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    public function getWallet(User $user)
    {
        return $this->walletRepository->getFirstOrCreateForUser($user);
    }

    public function deposit(User $user, float $amount)
    {
        return DB::transaction(function () use ($user, $amount) {
            $wallet = $this->getWallet($user);

            if (!$wallet->isActive()) {
                throw new WalletBlockedException('Wallet is blocked. Cannot make deposit.');
            }

            $transaction = $this->transactionRepository->createDeposit($user, $amount);

            $wallet->balance += $amount;
            $this->walletRepository->updateBalance($wallet, $wallet->balance);

            Log::info("Deposit successful", [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function transfer(User $sender, int $recipientId, float $amount)
    {
        return DB::transaction(function () use ($sender, $recipientId, $amount) {
            $recipient = $this->userRepository->find($recipientId);
            if (!$recipient) {
                throw new RecipientNotFoundException('Recipient not found.');
            }

            $senderWallet = $this->getWallet($sender);
            $recipientWallet = $this->getWallet($recipient);

            if (!$senderWallet->isActive()) {
                throw new WalletBlockedException('Sender wallet is blocked. Cannot make transfer.');
            }

            if (!$recipientWallet->isActive()) {
                throw new WalletBlockedException('Recipient wallet is blocked. Cannot receive transfer.');
            }

            if ($senderWallet->balance < $amount) {
                throw new InsufficientFundsException('Insufficient funds for transfer.');
            }

            $transaction = $this->transactionRepository->createTransfer($sender, $recipient, $amount);

            $senderWallet->balance -= $amount;
            $recipientWallet->balance += $amount;

            $this->walletRepository->updateBalance($senderWallet, $senderWallet->balance);
            $this->walletRepository->updateBalance($recipientWallet, $recipientWallet->balance);

            Log::info("Transfer successful", [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function reverseTransaction(Transaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->isReversed()) {
                throw new TransactionAlreadyReversedException('Transaction already reversed.');
            }

            if (!$transaction->isCompleted()) {
                throw new TransactionReversalException('Only completed transactions can be reversed.');
            }

            if ($transaction->isReversal()) {
                throw new TransactionReversalException('Reversal transactions cannot be reversed.');
            }

            if ($transaction->type === 'deposit') {
                $reversal = $this->reverseDeposit($transaction);
            } elseif ($transaction->type === 'transfer') {
                $reversal = $this->reverseTransfer($transaction);
            } else {
                throw new InvalidTransactionTypeException('Cannot reverse this transaction type.');
            }

            $this->transactionRepository->markAsReversed($transaction);

            Log::info("Transaction reversed", [
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    private function reverseDeposit(Transaction $transaction)
    {
        $recipient = $transaction->recipient;
        $recipientWallet = $this->getWallet($recipient);

        if ($recipientWallet->balance < $transaction->amount) {
            throw new InsufficientFundsException('Recipient does not have enough balance to reverse the deposit.');
        }

        $reversal = $this->transactionRepository->createReversal($transaction, [
            'sender_id' => $recipient->id,
            'recipient_id' => null
        ]);

        $recipientWallet->balance -= $transaction->amount;
        $this->walletRepository->updateBalance($recipientWallet, $recipientWallet->balance);

        return $reversal;
    }

    private function reverseTransfer(Transaction $transaction)
    {
        $sender = $transaction->sender;
        $recipient = $transaction->recipient;

        $senderWallet = $this->getWallet($sender);
        $recipientWallet = $this->getWallet($recipient);

        if ($recipientWallet->balance < $transaction->amount) {
            throw new InsufficientFundsException('Recipient does not have enough balance to reverse the transfer.');
        }

        $reversal = $this->transactionRepository->createReversal($transaction, [
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id
        ]);

        $recipientWallet->balance -= $transaction->amount;
        $senderWallet->balance += $transaction->amount;

        $this->walletRepository->updateBalance($recipientWallet, $recipientWallet->balance);
        $this->walletRepository->updateBalance($senderWallet, $senderWallet->balance);

        return $reversal;
    }
}
