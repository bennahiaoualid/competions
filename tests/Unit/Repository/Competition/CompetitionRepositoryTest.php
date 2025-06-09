<?php

namespace Tests\Unit\Repository\Competition;

use App\Models\Competition\Competition;
use App\Repository\Competition\CompetitionRepository;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Exception;

class CompetitionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CompetitionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CompetitionRepository();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_create_competition_success()
    {
        // Arrange
        $admin = Admin::factory()->create();
        Auth::login($admin);
        $data = [
            'title' => 'Test Competition',
            'description' => 'Test Description',
            'start_date' => now(),
            'age_start' => 18,
            'age_end' => 30,
            'levels_number' => 1,
        ];

        // Act
        $competition = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Competition::class, $competition);
        $this->assertEquals($data['title'], $competition->title);
        $this->assertEquals($admin->id, $competition->admin_id);
    }

    public function test_update_competition_success_without_age_range_change()
    {
        // Arrange
        $competition = Competition::factory()->create();
        $updateData = [
            'description' => 'Updated Description',
        ];

        // Act
        $result = $this->repository->update($competition, $updateData);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('competition', $result);
        $this->assertFalse($result['resyncCompetitionParticipants']);
        $this->assertEquals($updateData['description'], $result['competition']->description);
    }

    public function test_update_competition_success_with_age_range_change()
    {
        // Arrange
        $competition = Competition::factory()->create(['age_start' => 18, 'age_end' => 30]);
        $updateData = [
            'age_start' => 19,
            'age_end' => 31,
        ];

        // Act
        $result = $this->repository->update($competition, $updateData);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('competition', $result);
        $this->assertTrue($result['resyncCompetitionParticipants']);
        $this->assertEquals($updateData['age_start'], $result['competition']->age_start);
        $this->assertEquals($updateData['age_end'], $result['competition']->age_end);
    }

    public function test_delete_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create();

        // Act
        $result = $this->repository->delete($competition);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('competitions', ['id' => $competition->id]);
    }
    
    public function test_add_users_to_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create();
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        // Act
        $result = $this->repository->addUsersToCompetition($competition, $userIds);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(3, $competition->users()->count());
    }

    
    public function test_remove_user_from_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create();
        $user = User::factory()->create();
        $competition->users()->attach($user->id);

        // Act
        $result = $this->repository->removeUserFromCompetition($competition, $user->id);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $competition->users()->count());
    }

    
    public function test_add_auditors_to_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create();
        $auditors = Admin::factory()->count(2)->create();
        $auditorIds = $auditors->pluck('id')->toArray();

        // Act
        $result = $this->repository->addAuditorsToCompetition($competition, $auditorIds);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(2, $competition->auditors()->count());
    }

    
    public function test_remove_auditor_from_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create();
        $auditor = Admin::factory()->create();
        $competition->auditors()->attach($auditor->id);

        // Act
        $result = $this->repository->removeAuditorFromCompetition($competition, $auditor->id);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $competition->auditors()->count());
    }

    
    public function test_activate_competition_success()
    {
        // Arrange
        $competition = Competition::factory()->create(['status' => '0']);

        // Act
        $result = $this->repository->activate($competition);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('1', $competition->fresh()->status);
    }
} 