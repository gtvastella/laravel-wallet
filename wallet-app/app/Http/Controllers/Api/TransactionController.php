<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $transactions = $this->transactionService->getUserTransactions($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transactions retrieved successfully',
            'data' => [
                'transactions' => $transactions->toArray()
            ]
        ]);
    }

    public function show(Request $request, $id)
    {
        $transaction = $this->transactionService->getTransactionById($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transaction retrieved successfully',
            'data' => [
                'transaction' => $transaction
            ]
        ]);
    }

    public function reverse(Request $request, $id)
    {
        $result = $this->transactionService->reverseTransactionById($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transaction reversed successfully',
            'data' => $result
        ]);
    }
}
