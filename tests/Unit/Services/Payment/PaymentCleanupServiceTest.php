<?php

namespace Tests\Unit\Services\Payment;

use Mockery;
use Tests\TestCase;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\PaymentReviewRequest;
use App\Services\Payment\PaymentCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class PaymentCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentCleanupService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentCleanupService = new PaymentCleanupService();
        
        // Mock Storage facade
        Storage::fake('local');
        
        // Set up test configuration
        Config::set('payment.cleanup.rules', [
            'approved' => [
                'keep_days' => 365,
                'cleanup_images' => 1095
            ],
            'rejected' => [
                'keep_days' => 30,
                'cleanup_images' => 90
            ],
            'cancelled' => [
                'keep_days' => 7,
                'cleanup_images' => 60
            ],
            'pending' => [
                'keep_days' => null,
                'cleanup_images' => false
            ]
        ]);
        
        Config::set('payment.proofs.storage_disk', 'local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_detect_expired_transactions_with_approved_status()
    {
        // Create approved transaction that should be detected
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100), // Older than cleanup_images (1095 days)
            'proof_image_path' => 'proofs/old_approved.jpg'
        ]);

        // Create approved transaction that should NOT be detected (too recent)
        $recentTransaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1000), // Newer than cleanup_images
            'proof_image_path' => 'proofs/recent_approved.jpg'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(1, $result);
        $this->assertEquals($expiredTransaction->id, $result[0]['id']);
        $this->assertEquals('proofs/old_approved.jpg', $result[0]['proof_image_path']);
    }

    public function test_detect_expired_transactions_with_rejected_status()
    {
        // Create rejected transaction that should be detected
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'rejected',
            'created_at' => now()->subDays(95), // Older than cleanup_images (90 days)
            'proof_image_path' => 'proofs/old_rejected.jpg'
        ]);

        // Create rejected transaction that should NOT be detected (too recent)
        $recentTransaction = PaymentTransaction::factory()->create([
            'status' => 'rejected',
            'created_at' => now()->subDays(80), // Newer than cleanup_images
            'proof_image_path' => 'proofs/recent_rejected.jpg'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(1, $result);
        $this->assertEquals($expiredTransaction->id, $result[0]['id']);
        $this->assertEquals('proofs/old_rejected.jpg', $result[0]['proof_image_path']);
    }

    public function test_detect_expired_transactions_with_cancelled_status()
    {
        // Create cancelled transaction that should be detected
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'cancelled',
            'created_at' => now()->subDays(65), // Older than cleanup_images (60 days)
            'proof_image_path' => 'proofs/old_cancelled.jpg'
        ]);

        // Create cancelled transaction that should NOT be detected (too recent)
        $recentTransaction = PaymentTransaction::factory()->create([
            'status' => 'cancelled',
            'created_at' => now()->subDays(50), // Newer than cleanup_images
            'proof_image_path' => 'proofs/recent_cancelled.jpg'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(1, $result);
        $this->assertEquals($expiredTransaction->id, $result[0]['id']);
        $this->assertEquals('proofs/old_cancelled.jpg', $result[0]['proof_image_path']);
    }

    public function test_detect_expired_transactions_excludes_pending_status()
    {
        // Create pending transaction (should never be cleaned up)
        $pendingTransaction = PaymentTransaction::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(1000),
            'proof_image_path' => 'proofs/old_pending.jpg'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(0, $result);
    }

    public function test_detect_expired_transactions_excludes_transactions_without_proof_images()
    {
        // Create expired transaction without proof image
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => null
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(0, $result);
    }

    public function test_detect_expired_transactions_excludes_transactions_with_pending_reviews()
    {
        // Create expired transaction
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => 'proofs/old_approved.jpg'
        ]);

        // Create pending review for this transaction
        PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $expiredTransaction->id,
            'status' => 'pending'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(0, $result);
    }

    public function test_detect_expired_transactions_includes_transactions_with_resolved_reviews()
    {
        // Create expired transaction
        $expiredTransaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => 'proofs/old_approved.jpg'
        ]);

        // Create resolved review for this transaction
        PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $expiredTransaction->id,
            'status' => 'approved'
        ]);

        $result = $this->paymentCleanupService->detectExpiredTransactions();

        $this->assertCount(1, $result);
        $this->assertEquals($expiredTransaction->id, $result[0]['id']);
    }

    public function test_safe_delete_images_success()
    {
        // Create test image files
        Storage::disk('local')->put('proofs/test1.jpg', 'fake image content 1');
        Storage::disk('local')->put('proofs/test2.jpg', 'fake image content 2');

        $transactions = [
            ['id' => 1, 'proof_image_path' => 'proofs/test1.jpg'],
            ['id' => 2, 'proof_image_path' => 'proofs/test2.jpg']
        ];

        $result = $this->paymentCleanupService->safeDeleteImages($transactions);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['not_found']);
        $this->assertCount(2, $result['successful_ids']);
        $this->assertContains(1, $result['successful_ids']);
        $this->assertContains(2, $result['successful_ids']);
        $this->assertEmpty($result['errors']);

        // Verify images were deleted
        $this->assertFalse(Storage::disk('local')->exists('proofs/test1.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('proofs/test2.jpg'));
    }

    public function test_safe_delete_images_handles_missing_images()
    {
        // Create one test image file
        Storage::disk('local')->put('proofs/existing.jpg', 'fake image content');

        $transactions = [
            ['id' => 1, 'proof_image_path' => 'proofs/existing.jpg'],
            ['id' => 2, 'proof_image_path' => 'proofs/missing.jpg'],
            ['id' => 3, 'proof_image_path' => 'proofs/another_missing.jpg']
        ];

        $result = $this->paymentCleanupService->safeDeleteImages($transactions);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(2, $result['not_found']);
        $this->assertCount(1, $result['successful_ids']);
        $this->assertCount(2, $result['not_found_ids']);
        $this->assertContains(1, $result['successful_ids']);
        $this->assertContains(2, $result['not_found_ids']);
        $this->assertContains(3, $result['not_found_ids']);
    }

    public function test_safe_delete_images_handles_empty_paths()
    {
        $transactions = [
            ['id' => 1, 'proof_image_path' => ''],
            ['id' => 2, 'proof_image_path' => null],
            ['id' => 3, 'proof_image_path' => 'proofs/valid.jpg']
        ];

        // Create one valid image
        Storage::disk('local')->put('proofs/valid.jpg', 'fake image content');

        $result = $this->paymentCleanupService->safeDeleteImages($transactions);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['not_found']);
        $this->assertCount(1, $result['successful_ids']);
        $this->assertContains(3, $result['successful_ids']);
    }

    public function test_safe_delete_images_handles_storage_exceptions()
    {
        // Mock Storage to throw exception
        Storage::shouldReceive('disk')
            ->with('local')
            ->andReturnSelf();
        
        Storage::shouldReceive('exists')
            ->andReturn(true);
        
        Storage::shouldReceive('delete')
            ->andThrow(new \Exception('Storage error'));

        $transactions = [
            ['id' => 1, 'proof_image_path' => 'proofs/test.jpg']
        ];

        $result = $this->paymentCleanupService->safeDeleteImages($transactions);

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals(0, $result['not_found']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Storage error', $result['errors'][0]);
    }

    public function test_update_database_after_image_deletion_success()
    {
        // Create transactions
        $transaction1 = PaymentTransaction::factory()->create([
            'proof_image_path' => 'proofs/test1.jpg'
        ]);
        $transaction2 = PaymentTransaction::factory()->create([
            'proof_image_path' => 'proofs/test2.jpg'
        ]);

        $successfulIds = [$transaction1->id, $transaction2->id];

        $result = $this->paymentCleanupService->updateDatabaseAfterImageDeletion($successfulIds);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['updated_count']);
        $this->assertCount(2, $result['transaction_ids']);

        // Verify database was updated
        $transaction1->refresh();
        $transaction2->refresh();
        $this->assertNull($transaction1->proof_image_path);
        $this->assertNull($transaction2->proof_image_path);
    }

    public function test_update_database_after_image_deletion_with_empty_array()
    {
        $result = $this->paymentCleanupService->updateDatabaseAfterImageDeletion([]);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(0, $result['updated_count']);
        $this->assertEquals('No database updates needed - no successful deletions', $result['message']);
    }

    public function test_cleanup_expired_proof_images_integration()
    {
        // Create expired transactions with proof images
        $expiredTransaction1 = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => 'proofs/old1.jpg'
        ]);

        $expiredTransaction2 = PaymentTransaction::factory()->create([
            'status' => 'rejected',
            'created_at' => now()->subDays(95),
            'proof_image_path' => 'proofs/old2.jpg'
        ]);

        // Create test image files
        Storage::disk('local')->put('proofs/old1.jpg', 'fake image content 1');
        Storage::disk('local')->put('proofs/old2.jpg', 'fake image content 2');

        // Run cleanup
        $this->paymentCleanupService->cleanupExpiredProofImages();

        // Verify images were deleted
        $this->assertFalse(Storage::disk('local')->exists('proofs/old1.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('proofs/old2.jpg'));

        // Verify database was updated
        $expiredTransaction1->refresh();
        $expiredTransaction2->refresh();
        $this->assertNull($expiredTransaction1->proof_image_path);
        $this->assertNull($expiredTransaction2->proof_image_path);
    }

    public function test_cleanup_expired_proof_images_with_no_expired_transactions()
    {

        // Create recent transactions (not expired)
        PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1000),
            'proof_image_path' => 'proofs/recent.jpg'
        ]);

        Storage::disk('local')->put('proofs/recent.jpg', 'fake image content');

        // Run cleanup
        $this->paymentCleanupService->cleanupExpiredProofImages();

        // Verify no images were deleted
        $this->assertTrue(Storage::disk('local')->exists('proofs/recent.jpg'));
    }

    public function test_cleanup_expired_proof_images_with_mixed_results()
    {
        // Create expired transaction with existing image
        $expiredWithImage = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => 'proofs/existing.jpg'
        ]);

        // Create expired transaction with missing image
        $expiredWithoutImage = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(1100),
            'proof_image_path' => 'proofs/missing.jpg'
        ]);

        // Create test image file for one transaction
        Storage::disk('local')->put('proofs/existing.jpg', 'fake image content');

        // Run cleanup
        $this->paymentCleanupService->cleanupExpiredProofImages();

        // Verify existing image was deleted
        $this->assertFalse(Storage::disk('local')->exists('proofs/existing.jpg'));

        // Verify both database records were updated
        $expiredWithImage->refresh();
        $expiredWithoutImage->refresh();
        $this->assertNull($expiredWithImage->proof_image_path);
        $this->assertNull($expiredWithoutImage->proof_image_path);
    }
} 