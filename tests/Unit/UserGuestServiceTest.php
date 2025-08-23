<?php

namespace Tests\Unit;

use Mockery;
use Mockery\Mock;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Services\GuestUsers\UserGuestService;
use App\Contracts\TransactionManagerInterface;
use App\Models\GuestUsers\GlobalQuestion;
use App\Repository\GuestUsers\UserGuestRepository;

class UserGuestServiceTest extends TestCase
{
    /** @var TransactionManagerInterface&\Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface&\Mockery\MockInterface */
    protected $flasher;
    /** @var UserGuestRepository&\Mockery\MockInterface */
    protected $userGuestRepository;
    protected $service;
    protected $auth_user;

    public function setUp(): void
    {
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->userGuestRepository = Mockery::mock(UserGuestRepository::class);
        $this->service = new UserGuestService(
            $this->userGuestRepository,
            $this->flasher,
            $this->transactionManager
        );

        $this->auth_user = Mockery::mock(User::class);
        $this->auth_user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        Auth::shouldReceive('id')->andReturn($auth_user->id);
        Auth::shouldReceive('user')->andReturn($auth_user);
    
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_random_question()
    {
        $question = GlobalQuestion::factory()->approved()->make();
        $this->userGuestRepository->shouldReceive('getRandomEligibleQuestionForUser')
            ->with($this->auth_user->id)->andReturn($question);

        $this->transactionManager->shouldReceive('run')->once();

        $result = $this->service->getRandomQuestion();
        $this->assertEquals($question->id, $result['question_id']);
        $this->assertEquals($this->auth_user->id, $result['user_id']);
        $this->assertEquals(0, $result['score']);
        $this->assertEquals(0, $result['response_duration']);
        $this->assertEquals(null, $result['choice_id']);
    }
}
