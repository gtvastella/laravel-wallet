<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected $userRepository;
    protected $walletService;

    public function __construct(
        UserRepository $userRepository = null,
        WalletService $walletService = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->walletService = $walletService ?? new WalletService();
    }

    public function register(array $data)
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        $user = $this->userRepository->create($userData);
        $this->walletService->getWallet($user);
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            throw new InvalidCredentialsException('Invalid login credentials');
        }

        $user = $this->userRepository->findByEmail($credentials['email']);
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function logout()
    {
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
            return true;
        }

        return false;
    }
}
