<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;
}
