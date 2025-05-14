<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $walletService;
    protected $walletRepository;
    protected $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;

        $this->walletRepository = new WalletRepository();
        $this->transactionRepository = new TransactionRepository();
        $this->walletService = new WalletService(
            $this->walletRepository,
            $this->transactionRepository
        );

        $this->walletService->getWallet($this->user);
    }

    public function test_user_can_view_wallet()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'wallet' => [
                        'id',
                        'user_id',
                        'balance',
                        'status',
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Wallet retrieved successfully'
            ]);
    }

    public function test_user_can_deposit_money()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/wallet/deposit', [
                'amount' => 100
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'success' => true,
                'message' => 'Deposit successful'
            ]);

        $transactionType = $response->json('data.transaction.type');
        $transactionAmount = (float)$response->json('data.transaction.amount');
        $walletBalance = (float)$response->json('data.wallet.balance');

        $this->assertEquals('deposit', $transactionType);
        $this->assertEquals(100.0, $transactionAmount);
        $this->assertEquals(100.0, $walletBalance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'deposit',
            'amount' => 100,
            'recipient_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'balance' => 100,
        ]);
    }

    public function test_user_cannot_deposit_negative_amount()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/wallet/deposit', [
                'amount' => -50
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_user_can_transfer_money()
    {
        $recipient = User::factory()->create();
        $this->walletService->getWallet($recipient);
        $this->walletService->deposit($this->user, 200);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/wallet/transfer', [
                'recipient_id' => $recipient->id,
                'amount' => 150
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'success' => true,
                'message' => 'Transfer successful'
            ]);

        $transactionType = $response->json('data.transaction.type');
        $transactionAmount = (float)$response->json('data.transaction.amount');
        $walletBalance = (float)$response->json('data.wallet.balance');

        $this->assertEquals('transfer', $transactionType);
        $this->assertEquals(150.0, $transactionAmount);
        $this->assertEquals(50.0, $walletBalance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'transfer',
            'amount' => 150,
            'sender_id' => $this->user->id,
            'recipient_id' => $recipient->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'balance' => 50,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $recipient->id,
            'balance' => 150,
        ]);
    }

    public function test_user_cannot_transfer_without_sufficient_funds()
    {
        $recipient = User::factory()->create();
        $this->walletService->getWallet($recipient);
        $this->walletService->deposit($this->user, 50);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/wallet/transfer', [
                'recipient_id' => $recipient->id,
                'amount' => 100
            ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient funds for transfer.'
            ])
            ->assertJsonPath('error', 'insufficient_funds');
    }

    public function test_user_cannot_transfer_to_self()
    {
        $this->walletService->deposit($this->user, 200);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/wallet/transfer', [
                'recipient_id' => $this->user->id,
                'amount' => 100
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
