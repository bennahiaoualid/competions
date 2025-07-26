<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Repository\GuestUsers\UserGuestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserGuestRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserGuestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserGuestRepository();
    }

    public function test_get_latest_pending_response_returns_latest_pending()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create();
        $pending1 =GlobalResponse::factory()->for($user)->for($question,'question')->create(['choice_id' => null, 'created_at' => now()->subMinute()]);
        $pending2 =GlobalResponse::factory()->for($user)->for($question,'question')->create(['choice_id' => null, 'created_at' => now()]);
        $found = $this->repository->getLatestPendingResponse($question->id, $user->id);
        $this->assertEquals($pending2->id, $found->id);
    }

    public function test_get_latest_pending_response_returns_null_if_none()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create();
        $found = $this->repository->getLatestPendingResponse($question->id, $user->id);
        $this->assertNull($found);
    }

    public function test_get_user_responded_questions_paginated_returns_only_responded()
    {
        $user = User::factory()->create();
        $question1 = GlobalQuestion::factory()->approved()->create();
        $question2 = GlobalQuestion::factory()->approved()->create();
        $question3 = GlobalQuestion::factory()->approved()->create();
        GlobalResponse::factory()->for($user)->for($question1,'question')->create();
        GlobalResponse::factory()->for($user)->for($question2,'question')->create();
        $result = $this->repository->getUserRespondedQuestionsPaginated($user->id);
        $ids = $result->pluck('id')->toArray();
        $this->assertContains($question1->id, $ids);
        $this->assertContains($question2->id, $ids);
        $this->assertNotContains($question3->id, $ids);
    }

    public function test_get_user_responded_questions_paginated_returns_empty_if_none()
    {
        $user = User::factory()->create();
        $result = $this->repository->getUserRespondedQuestionsPaginated($user->id);
        $this->assertEquals(0, $result->total());
    }
} 