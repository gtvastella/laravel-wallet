<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\WalletBlockedException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\UserRepository;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $walletService;
    protected $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository();
        $this->walletService = new WalletService();
    }

    public function test_get_wallet_creates_wallet_if_not_exists()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->getWallet($user);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($user->id, $wallet->user_id);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals('active', $wallet->status);
    }

    public function test_get_wallet_returns_existing_wallet()
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'status' => 'active'
        ]);

        $wallet = $this->walletService->getWallet($user);

        $this->assertEquals(100, $wallet->balance);
    }

    public function test_deposit_increases_wallet_balance()
    {
        $user = User::factory()->create();
        $this->walletService->getWallet($user);

        $transaction = $this->walletService->deposit($user, 100);

        $this->assertEquals('deposit', $transaction->type);
        $this->assertEquals(100, $transaction->amount);
        $this->assertEquals($user->id, $transaction->recipient_id);
        $this->assertEquals('completed', $transaction->status);

        $wallet = $this->walletService->getWallet($user);
        $this->assertEquals(100, $wallet->balance);
    }

    public function test_deposit_fails_when_wallet_is_blocked()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->getWallet($user);
        $wallet->status = 'blocked';
        $wallet->save();

        $this->expectException(WalletBlockedException::class);
        $this->walletService->deposit($user, 100);
    }

    public function test_transfer_moves_funds_between_wallets()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $senderWallet = $this->walletService->getWallet($sender);
        $recipientWallet = $this->walletService->getWallet($recipient);

        $this->walletService->deposit($sender, 200);

        $transaction = $this->walletService->transfer($sender, $recipient->id, 150);

        $this->assertEquals('transfer', $transaction->type);
        $this->assertEquals(150, $transaction->amount);
        $this->assertEquals($sender->id, $transaction->sender_id);
        $this->assertEquals($recipient->id, $transaction->recipient_id);
        $this->assertEquals('completed', $transaction->status);

        $senderWallet->refresh();
        $recipientWallet->refresh();
        $this->assertEquals(50, $senderWallet->balance);
        $this->assertEquals(150, $recipientWallet->balance);
    }

    public function test_transfer_fails_with_insufficient_funds()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->walletService->getWallet($sender);
        $this->walletService->getWallet($recipient);

        $this->walletService->deposit($sender, 50);

        $this->expectException(InsufficientFundsException::class);
        $this->walletService->transfer($sender, $recipient->id, 100);
    }

    public function test_reverse_deposit_transaction()
    {
        $user = User::factory()->create();
        $this->walletService->getWallet($user);

        $deposit = $this->walletService->deposit($user, 100);
        $reversal = $this->walletService->reverseTransaction($deposit);

        $deposit->refresh();
        $this->assertEquals('reversed', $deposit->status);

        $actualReversal = Transaction::where('reference_id', $deposit->id)
            ->where('type', 'reversal')
            ->first();

        $this->assertNotNull($actualReversal);
        $this->assertEquals('reversal', $actualReversal->type);
        $this->assertEquals(100, $actualReversal->amount);
        $this->assertEquals($user->id, $actualReversal->sender_id);
        $this->assertNull($actualReversal->recipient_id);
        $this->assertEquals($deposit->id, $actualReversal->reference_id);

        $wallet = $this->walletService->getWallet($user);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_reverse_transfer_transaction()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->walletService->getWallet($sender);
        $this->walletService->getWallet($recipient);

        $this->walletService->deposit($sender, 200);
        $transfer = $this->walletService->transfer($sender, $recipient->id, 150);
        $reversal = $this->walletService->reverseTransaction($transfer);

        $transfer->refresh();
        $this->assertEquals('reversed', $transfer->status);

        $actualReversal = Transaction::where('reference_id', $transfer->id)
            ->where('type', 'reversal')
            ->first();

        $this->assertNotNull($actualReversal);
        $this->assertEquals('reversal', $actualReversal->type);
        $this->assertEquals(150, $actualReversal->amount);
        $this->assertEquals($recipient->id, $actualReversal->sender_id);
        $this->assertEquals($sender->id, $actualReversal->recipient_id);
        $this->assertEquals($transfer->id, $actualReversal->reference_id);

        $senderWallet = $this->walletService->getWallet($sender);
        $recipientWallet = $this->walletService->getWallet($recipient);
        $this->assertEquals(200, $senderWallet->balance);
        $this->assertEquals(0, $recipientWallet->balance);
    }
}
