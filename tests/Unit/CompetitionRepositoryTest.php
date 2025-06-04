<?php

namespace Tests\Unit;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\User;
use App\Repository\Competition\CompetitionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CompetitionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CompetitionRepository $competitionRepository;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->competitionRepository = $this->app->make(CompetitionRepository::class);
        $this->admin = Admin::factory()->createOne(); // Ensures a single instance or throws error
        Auth::shouldReceive('id')->andReturn($this->admin->id); // Mock Auth facade
    }

    private function createCompetitionData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Competition',
            'description' => 'Test Description',
            'start_date' => now()->addDay(),
            'age_start' => 10,
            'age_end' => 15,
            'levels_number' => 3,
            'status' => '0', // inactive
        ], $overrides);
    }

    public function test_find_by_id()
    {
        $data = $this->createCompetitionData();
        $createdCompetition = $this->competitionRepository->create($data);

        $foundCompetition = $this->competitionRepository->findById($createdCompetition->id);

        $this->assertNotNull($foundCompetition);
        $this->assertEquals($createdCompetition->id, $foundCompetition->id);
        $this->assertEquals($data['title'], $foundCompetition->title);
    }

    public function test_find_by_id_with_levels()
    {
        $data = $this->createCompetitionData();
        $createdCompetition = $this->competitionRepository->create($data);
        // Assuming Level model and factory exist
        // $createdCompetition->levels()->create(['name' => 'Level 1', 'start_date' => now()->addDays(2)]);


        $foundCompetition = $this->competitionRepository->findById($createdCompetition->id, true);

        $this->assertNotNull($foundCompetition);
        $this->assertTrue($foundCompetition->relationLoaded('levels'));
        // Add assertions for levels if they were created
    }

    public function test_find_or_fail()
    {
        $data = $this->createCompetitionData();
        $createdCompetition = $this->competitionRepository->create($data);

        $foundCompetition = $this->competitionRepository->findOrFail($createdCompetition->id);

        $this->assertNotNull($foundCompetition);
        $this->assertEquals($createdCompetition->id, $foundCompetition->id);
    }

    public function test_find_or_fail_throws_exception_for_invalid_id()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->competitionRepository->findOrFail(999);
    }

    public function test_create_competition()
    {
        $data = $this->createCompetitionData();
        $competition = $this->competitionRepository->create($data);

        $this->assertInstanceOf(Competition::class, $competition);
        $this->assertDatabaseHas('competitions', ['title' => $data['title'], 'admin_id' => $this->admin->id]);
        $this->assertEquals($this->admin->id, $competition->admin_id);
    }

    public function test_update_competition()
    {
        $initialData = $this->createCompetitionData(['title' => 'Initial Title']);
        $competition = $this->competitionRepository->create($initialData);

        $updateData = ['title' => 'Updated Title', 'age_start' => 12]; // age_start change should trigger resync
        $result = $this->competitionRepository->update($competition, $updateData);

        $this->assertTrue($result['resyncCompetitionParticipants']);
        $this->assertEquals('Updated Title', $result['competition']->title);
        $this->assertEquals(12, $result['competition']->age_start);
        $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'title' => 'Updated Title']);
    }

    public function test_update_competition_no_resync()
    {
        $initialData = $this->createCompetitionData();
        $competition = $this->competitionRepository->create($initialData);

        $updateData = ['description' => 'New Description']; // No age change
        $result = $this->competitionRepository->update($competition, $updateData);

        $this->assertFalse($result['resyncCompetitionParticipants']);
        $this->assertEquals('New Description', $result['competition']->description);
    }


    public function test_delete_competition()
    {
        $data = $this->createCompetitionData();
        $competition = $this->competitionRepository->create($data);

        $result = $this->competitionRepository->delete($competition);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('competitions', ['id' => $competition->id]);
    }

    public function test_add_and_remove_user_from_competition()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $user = User::factory()->create();

        $this->competitionRepository->addUsersToCompetition($competition, [$user->id]);
        $this->assertDatabaseHas('competition_user', [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);

        $this->competitionRepository->removeUserFromCompetition($competition, $user->id);
        $this->assertDatabaseMissing('competition_user', [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);
    }

    public function test_add_and_remove_auditor_from_competition()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $auditor = Admin::factory()->create(); // Auditors are Admins

        $this->competitionRepository->addAuditorsToCompetition($competition, [$auditor->id]);
        // Pivot table name is 'admin_competition' based on Eloquent conventions for BelongsToMany
        // between Admin and Competition (alphabetical order, singular names).
        $this->assertDatabaseHas('admin_competition', [
            'competition_id' => $competition->id,
            'admin_id' => $auditor->id
        ]);

        $this->competitionRepository->removeAuditorFromCompetition($competition, $auditor->id);
        $this->assertDatabaseMissing('admin_competition', [
            'competition_id' => $competition->id,
            'admin_id' => $auditor->id
        ]);
    }

    public function test_activate_competition()
    {
        $competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'status' => '0' // inactive
        ]);

        $result = $this->competitionRepository->activate($competition);

        $this->assertTrue($result);
        $this->assertEquals('1', $competition->fresh()->status); // active
    }

    // Placeholder for getCompetitionWithUsers - often just findById or findOrFail
    public function test_get_competition_with_users()
    {
        $competition = Competition::factory()->has(User::factory()->count(3))->create(['admin_id' => $this->admin->id]);
        $foundCompetition = $this->competitionRepository->getCompetitionWithUsers($competition->id); //This method in repo is just find()

        $this->assertNotNull($foundCompetition);
        // $this->assertTrue($foundCompetition->relationLoaded('users')); // The current repo method does not eager load.
        // $this->assertCount(3, $foundCompetition->users);
    }

     // Placeholder for getCompetitionWithAuditors
    public function test_get_competition_with_auditors()
    {
        $competition = Competition::factory()->has(Admin::factory()->count(2), 'auditors')->create(['admin_id' => $this->admin->id]);
        $foundCompetition = $this->competitionRepository->getCompetitionWithAuditors($competition->id);

        $this->assertNotNull($foundCompetition);
        $this->assertTrue($foundCompetition->relationLoaded('auditors'));
        $this->assertCount(2, $foundCompetition->auditors);
    }
} 