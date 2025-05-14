<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;

class WalletRepository extends BaseRepository
{
    public function __construct(Wallet $model = null)
    {
        parent::__construct($model ?? new Wallet());
    }

    public function findByUser(User $user)
    {
        return $this->model->where('user_id', $user->id)->first();
    }

    public function createForUser(User $user, array $data = [])
    {
        $walletData = array_merge([
            'user_id' => $user->id,
            'balance' => 0,
            'status' => 'active'
        ], $data);

        return $this->create($walletData);
    }

    public function getFirstOrCreateForUser(User $user)
    {
        $wallet = $this->findByUser($user);

        if (!$wallet) {
            $wallet = $this->createForUser($user);
        }

        return $wallet;
    }

    public function updateBalance(Wallet $wallet, float $amount)
    {
        $wallet->balance = $amount;
        $wallet->save();
        return $wallet;
    }
}
