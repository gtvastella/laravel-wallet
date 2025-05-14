<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function show(Request $request)
    {
        $wallet = $this->walletService->getWallet($request->user());
        $wallet->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Wallet retrieved successfully',
            'data' => [
                'wallet' => $wallet
            ]
        ]);
    }

    public function deposit(DepositRequest $request)
    {
        $transaction = $this->walletService->deposit(
            $request->user(),
            $request->validated()['amount']
        );

        $wallet = $this->walletService->getWallet($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Deposit successful',
            'data' => [
                'transaction' => $transaction,
                'wallet' => $wallet
            ]
        ], Response::HTTP_CREATED);
    }

    public function transfer(TransferRequest $request)
    {
        $transaction = $this->walletService->transfer(
            $request->user(),
            $request->validated()['recipient_id'],
            $request->validated()['amount']
        );

        $wallet = $this->walletService->getWallet($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transfer successful',
            'data' => [
                'transaction' => $transaction,
                'wallet' => $wallet
            ]
        ], Response::HTTP_CREATED);
    }
}
