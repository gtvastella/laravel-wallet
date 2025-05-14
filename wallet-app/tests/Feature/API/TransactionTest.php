<?php

namespace Tests\Feature\API;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $walletService;
    protected $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;

        $walletRepository = new WalletRepository();
        $transactionRepository = new TransactionRepository();
        $userRepository = new UserRepository();

        $this->walletService = new WalletService(
            $walletRepository,
            $transactionRepository,
            $userRepository
        );

        $this->transactionService = new TransactionService(
            $transactionRepository,
            $walletRepository,
            $this->walletService
        );

        $this->walletService->getWallet($this->user);
    }

    public function test_user_can_view_transaction_history()
    {
        $deposit = $this->walletService->deposit($this->user, 100);
        $this->assertNotNull($deposit);

        $recipient = User::factory()->create();
        $this->walletService->getWallet($recipient);
        $transfer = $this->walletService->transfer($this->user, $recipient->id, 50);
        $this->assertNotNull($transfer);

        $this->assertDatabaseHas('transactions', [
            'id' => $deposit->id,
            'amount' => 100,
            'recipient_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transfer->id,
            'amount' => 50,
            'sender_id' => $this->user->id,
            'recipient_id' => $recipient->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'transactions'
                ]
            ]);
    }

    public function test_user_can_view_transaction_details()
    {
        $transaction = $this->walletService->deposit($this->user, 100);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/transactions/' . $transaction->id);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'transaction' => [
                        'id',
                        'type',
                        'amount',
                        'sender_id',
                        'recipient_id',
                        'status',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Transaction retrieved successfully'
            ]);
    }

    public function test_user_cannot_view_transaction_of_another_user()
    {
        $otherUser = User::factory()->create();
        $this->walletService->getWallet($otherUser);
        $transaction = $this->walletService->deposit($otherUser, 100);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/transactions/' . $transaction->id);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'success' => false
            ]);
    }

    public function test_user_can_reverse_their_deposit()
    {
        $transaction = $this->walletService->deposit($this->user, 100);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/transactions/' . $transaction->id . '/reverse');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Transaction reversed successfully'
            ])
            ->assertJsonPath('data.transaction.status', 'reversed');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'balance' => 0,
        ]);
    }

    public function test_user_can_reverse_their_transfer()
    {
        $recipient = User::factory()->create();
        $this->walletService->getWallet($recipient);

        $this->walletService->deposit($this->user, 200);
        $transfer = $this->walletService->transfer($this->user, $recipient->id, 150);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/transactions/' . $transfer->id . '/reverse');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Transaction reversed successfully'
            ])
            ->assertJsonPath('data.transaction.status', 'reversed');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'balance' => 200,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $recipient->id,
            'balance' => 0,
        ]);
    }

    public function test_user_cannot_reverse_already_reversed_transaction()
    {
        $transaction = $this->walletService->deposit($this->user, 100);
        $this->walletService->reverseTransaction($transaction);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/transactions/' . $transaction->id . '/reverse');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'success' => false,
                'message' => 'Only completed transactions can be reversed.'
            ]);
    }
}
