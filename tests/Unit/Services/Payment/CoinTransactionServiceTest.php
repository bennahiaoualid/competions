<?php

namespace Tests\Unit\Services\Payment;

use Mockery;
use Exception;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Event;
use App\Enums\CoinTransactionTypeEnum;
use App\Models\Payment\CoinTransaction;
use App\Contracts\TransactionManagerInterface;
use App\Services\Payment\CoinTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CashManagment\PaymentCacheManagement;

class CoinTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $coinTransactionService;
    protected $transactionManager;
    protected $flasher;
    protected $paymentCacheService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->paymentCacheService = Mockery::mock(PaymentCacheManagement::class);

        
        $this->coinTransactionService = new CoinTransactionService(
            $this->transactionManager,
            $this->flasher,
            $this->paymentCacheService
        );

        // Create a test user
        $this->user = User::factory()->create();
        
        // Fake events to avoid actual event dispatching during tests
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_earn_transaction()
    {
        $amount = 100;
        $detail = CoinTransactionTypeEnum::COMPETITION_GIFT;
        $processedAt = now();

        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createEarnTransaction(
            $this->user, 
            $detail, 
            $amount, 
            $processedAt
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('earn', $result->type);
        $this->assertEquals($detail->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);
        $this->assertEquals(User::class, $result->transactionable_type);
        $this->assertEquals($processedAt->toDateTimeString(), $result->processed_at->toDateTimeString());

    }

    public function test_create_spend_transaction()
    {
        $amount = 50;
        $detail = CoinTransactionTypeEnum::QUESTION_GENERATE;
        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createSpendTransaction(
            $this->user, 
            $detail, 
            $amount
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('spend', $result->type);
        $this->assertEquals($detail->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);
        $this->assertEquals(User::class, $result->transactionable_type);
        $this->assertNotNull($result->processed_at);

    }

    public function test_create_purchased_transaction()
    {
        $amount = 200;
        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createPurchasedTransaction(
            $this->user, 
            $amount
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('earn', $result->type);
        $this->assertEquals(CoinTransactionTypeEnum::PURCHASED->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);

    }

    public function test_create_competition_gift_transaction()
    {
        $amount = 150;
        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createCompetitionGiftTransaction(
            $this->user, 
            $amount
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('earn', $result->type);
        $this->assertEquals(CoinTransactionTypeEnum::COMPETITION_GIFT->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);

    }

    public function test_create_question_generate_transaction()
    {
        $amount = 25;
        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createQuestionGenerateTransaction(
            $this->user, 
            $amount
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('spend', $result->type);
        $this->assertEquals(CoinTransactionTypeEnum::QUESTION_GENERATE->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);

    }

    public function test_create_premium_question_purchase_transaction()
    {
        $amount = 75;
        $this->mockInvalidateTranscatrionCache();

        $result = $this->coinTransactionService->createPremiumQuestionPurchaseTransaction(
            $this->user, 
            $amount
        );

        $this->assertInstanceOf(CoinTransaction::class, $result);
        $this->assertEquals('spend', $result->type);
        $this->assertEquals(CoinTransactionTypeEnum::PREMIUM_QUESTION_PURCHASE->value, $result->detail);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals($this->user->id, $result->transactionable_id);

    }

    public function test_create_transaction_with_default_processed_at()
    {
        $amount = 100;
        $detail = CoinTransactionTypeEnum::COMPETITION_GIFT;
        $this->mockInvalidateTranscatrionCache();
        $result = $this->coinTransactionService->createEarnTransaction(
            $this->user, 
            $detail, 
            $amount
        );

        $this->assertNotNull($result->processed_at);
        $this->assertLessThanOrEqual(now()->addSecond(), $result->processed_at);
        $this->assertGreaterThanOrEqual(now()->subSecond(), $result->processed_at);
    }

/*
    public function test_get_transactions_for_entity_without_filters()
    {
        // Create some test transactions
        CoinTransaction::factory()->count(5)->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionsForEntity($this->user);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
        $this->assertEquals(10, $result->perPage());
    }

    public function test_get_transactions_for_entity_with_type_filter()
    {
        // Create earn and spend transactions
        CoinTransaction::factory()->count(3)->earn()->forEntity($this->user)->create();
        CoinTransaction::factory()->count(2)->spend()->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionsForEntity(
            $this->user, 
            ['type' => 'earn']
        );

        $this->assertEquals(3, $result->total());
        foreach ($result as $transaction) {
            $this->assertEquals('earn', $transaction->type);
        }
    }

    public function test_get_transactions_for_entity_with_detail_filter()
    {
        // Create transactions with different details
        CoinTransaction::factory()->count(3)->purchased()->forEntity($this->user)->create();
        CoinTransaction::factory()->count(2)->questionGenerate()->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionsForEntity(
            $this->user, 
            ['detail' => CoinTransactionTypeEnum::PURCHASED->value]
        );

        $this->assertEquals(3, $result->total());
        foreach ($result as $transaction) {
            $this->assertEquals(CoinTransactionTypeEnum::PURCHASED->value, $transaction->detail);
        }
    }

    public function test_get_transactions_for_entity_with_date_filters()
    {
        Carbon::setTestNow(now());
        $yesterday = now()->subDay();
        $tomorrow = now()->addDay();

        // Create transactions on different dates
        CoinTransaction::factory()->count(2)->forEntity($this->user)->create([
            'created_at' => $yesterday->addSecond(3600)
        ]);
        CoinTransaction::factory()->count(3)->forEntity($this->user)->create([
            'created_at' => $tomorrow->subSecond(3600)
        ]);

        $result = $this->coinTransactionService->getTransactionsForEntity(
            $this->user, 
            [
                'date_from' => $yesterday->toDateString(),
                'date_to' => now()->toDateString()
            ]
        );

        $this->assertEquals(2, $result->total());
    }

    public function test_get_transactions_for_entity_with_pagination()
    {
        // Create 15 transactions
        CoinTransaction::factory()->count(15)->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionsForEntity(
            $this->user, 
            [], 
            2, 
            5
        );

        $this->assertEquals(15, $result->total());
        $this->assertEquals(5, $result->perPage());
        $this->assertEquals(2, $result->currentPage());
    }

    public function test_get_transaction_summary()
    {
        // Create earn transactions
        CoinTransaction::factory()->count(3)->earn()->withAmount(100)->forEntity($this->user)->create();
        // Create spend transactions
        CoinTransaction::factory()->count(2)->spend()->withAmount(50)->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionSummary($this->user);

        $this->assertIsArray($result);
        $this->assertEquals(300, $result['total_earned']); // 3 * 100
        $this->assertEquals(100, $result['total_spent']);  // 2 * 50
        $this->assertEquals(5, $result['total_transactions']);
        $this->assertEquals(3, $result['earn_transactions']);
        $this->assertEquals(2, $result['spend_transactions']);
    }

    public function test_get_transaction_summary_with_no_transactions()
    {
        $result = $this->coinTransactionService->getTransactionSummary($this->user);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_earned']);
        $this->assertEquals(0, $result['total_spent']);
        $this->assertEquals(0, $result['total_transactions']);
        $this->assertEquals(0, $result['earn_transactions']);
        $this->assertEquals(0, $result['spend_transactions']);
    }

    public function test_get_transaction_summary_with_mixed_transactions()
    {
        // Create mixed transactions
        CoinTransaction::factory()->count(2)->earn()->withAmount(200)->forEntity($this->user)->create();
        CoinTransaction::factory()->count(1)->spend()->withAmount(75)->forEntity($this->user)->create();
        CoinTransaction::factory()->count(1)->earn()->withAmount(150)->forEntity($this->user)->create();

        $result = $this->coinTransactionService->getTransactionSummary($this->user);

        $this->assertEquals(550, $result['total_earned']); // (2 * 200) + 150
        $this->assertEquals(75, $result['total_spent']);   // 1 * 75
        $this->assertEquals(4, $result['total_transactions']);
        $this->assertEquals(3, $result['earn_transactions']);
        $this->assertEquals(1, $result['spend_transactions']);
    }

    public function test_transactions_are_ordered_by_created_at_desc()
    {
        // Create transactions with different timestamps
        $first = CoinTransaction::factory()->forEntity($this->user)->create([
            'created_at' => now()->subDays(2)
        ]);
        $second = CoinTransaction::factory()->forEntity($this->user)->create([
            'created_at' => now()->subDay()
        ]);
        $third = CoinTransaction::factory()->forEntity($this->user)->create([
            'created_at' => now()
        ]);

        $result = $this->coinTransactionService->getTransactionsForEntity($this->user);

        $this->assertEquals($third->id, $result->first()->id);
        $this->assertEquals($second->id, $result->get(1)->id);
        $this->assertEquals($first->id, $result->last()->id);
    }*/


    private function mockInvalidateTranscatrionCache()
    {
        $this->paymentCacheService->shouldReceive('invalidateGetUserCoinTransactions')
        ->once()
        ->with(Mockery::any(),Mockery::any());

        $this->paymentCacheService->shouldReceive('invalidateUserBalanace')
            ->once()
            ->with(Mockery::any(),Mockery::any());
    }
} 