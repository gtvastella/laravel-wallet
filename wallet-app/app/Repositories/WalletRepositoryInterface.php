<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;

interface WalletRepositoryInterface extends RepositoryInterface
{
    public function findByUser(User $user);
    public function createForUser(User $user, array $data = []);
    public function getFirstOrCreateForUser(User $user);
    public function updateBalance(Wallet $wallet, float $amount);
}
