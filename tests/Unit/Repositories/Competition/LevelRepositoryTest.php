<?php

namespace Tests\Unit\Repositories\Competition;

use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use App\Repository\Competition\LevelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected LevelRepositoryInterface $levelRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->levelRepository = new LevelRepository(); // Instantiate the concrete repository
    }

    public function test_create_level_successfully()
    {
        /** @var Competition $competition */
        $competition = Competition::factory()->create();
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $levelData = [
            'name' => 'Test Level 1',
            'description' => 'A test description for level 1.',
            'start_date' => now()->addDays(1)->toDateTimeString(),
            'duration' => 60, // minutes
            'questions_number' => 10,
            'competition_id' => $competition->id,
            'admin_id' => $admin->id,
            'status' => 0,
        ];

        $createdLevel = $this->levelRepository->create($levelData);

        $this->assertInstanceOf(Level::class, $createdLevel);
        $this->assertDatabaseHas('levels', [
            'id' => $createdLevel->id,
            'name' => 'Test Level 1',
            'competition_id' => $competition->id,
            'admin_id' => $admin->id,
        ]);
        $this->assertEquals($levelData['start_date'], $createdLevel->start_date->toDateTimeString());
    }

    public function test_check_competition_max_level_numbers_returns_true_when_max_reached()
    {
        // Assume Competition model uses 'levels_number' attribute as the max limit
        // And Competition::competitionMaxLevelNumbers() compares count of levels to this.
        /** @var Competition $competition */
        $competition = Competition::factory()->create(['levels_number' => 2]); // Use levels_number
        Level::factory()->count(2)->create(['competition_id' => $competition->id]);

        $this->assertTrue($this->levelRepository->checkCompetitionMaxLevelNumbers($competition->id));
    }

    public function test_check_competition_max_level_numbers_returns_false_when_not_max_reached()
    {
        /** @var Competition $competition */
        $competition = Competition::factory()->create(['levels_number' => 2]); // Use levels_number
        Level::factory()->count(1)->create(['competition_id' => $competition->id]);

        $this->assertFalse($this->levelRepository->checkCompetitionMaxLevelNumbers($competition->id));
    }

    public function test_get_unanswered_questions_for_user()
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Level $level */
        $level = Level::factory()->create();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Question> $questions */
        $questions = Question::factory()->count(3)->create(['level_id' => $level->id]);

        // Question 1: Answered by the user
        Response::factory()->create([
            'question_id' => $questions[0]->id,
            'user_id' => $user->id,
            'response_text' => 'Answer for Q1'
        ]);

        // Question 2: Not answered by the user (no response entry for this user and question)

        // Question 3: Answered by a different user
        /** @var User $anotherUser */
        $anotherUser = User::factory()->create();
        Response::factory()->create([
            'question_id' => $questions[2]->id,
            'user_id' => $anotherUser->id,
            'response_text' => 'Another user answer for Q3'
        ]);

        $unansweredQuestions = $this->levelRepository->getUnansweredQuestionsForUser($level, $user);

        // $this->assertInstanceOf(\Illuminate\Support\Collection::class, $unansweredQuestions);
        // $this->assertCount(1, $unansweredQuestions, "Expected only one unanswered question for the user.");
        // $this->assertTrue($unansweredQuestions->contains($questions[1]), "The collection should contain the unanswered question (Question 2).");
        // $this->assertFalse($unansweredQuestions->contains($questions[0]), "The collection should NOT contain the answered question (Question 1).");
        // $this->assertFalse($unansweredQuestions->contains($questions[2]), "The collection should NOT contain the question answered by another user (Question 3 if we consider it unanswered by current user, but the specific test setup makes it that Q3 is indeed unanswered by the primary user).");
        // Correcting the last assertion logic: Question 2 is the only one expected for $user
        // The setup for question 3 being answered by *another* user means it's still unanswered by *$user*
        // So, the query should return Q2 and Q3 for $user
        // Let's re-evaluate the query: `whereDoesntHave('responses', function ($query) use ($user) { $query->where('user_id', $user->id); })`
        // This means it fetches questions where NO response from THIS user exists

        // Revised assertions for clarity based on the query:
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $unansweredQuestions); // Keep this check
        $unansweredIds = $unansweredQuestions->pluck('id')->toArray();
        $this->assertCount(2, $unansweredQuestions, "User should have 2 unanswered questions."); // Q1 and Q2 (using original indexing: questions[1] and questions[2])
        $this->assertContains($questions[1]->id, $unansweredIds, "Question 2 (index 1) should be in unanswered.");
        $this->assertContains($questions[2]->id, $unansweredIds, "Question 3 (index 2) (answered by another user) should be in unanswered for THIS user.");
        $this->assertNotContains($questions[0]->id, $unansweredIds, "Question 1 (index 0) (answered by this user) should NOT be in unanswered.");
    }

    public function test_assign_auditors_to_users_in_pivot_assigns_correctly()
    {
        /** @var Level $level */
        $level = Level::factory()->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::factory()->count(5)->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Admin> $auditors */
        $auditors = Admin::factory()->count(2)->create();

        $this->levelRepository->assignAuditorsToUsersInPivot($level, $users, $auditors);

        $this->assertDatabaseCount('level_admin_user', 5); // All 5 users should be assigned

        $assignments = \Illuminate\Support\Facades\DB::table('level_admin_user')->where('level_id', $level->id)->get();

        $userAssignmentsCounts = array_count_values($assignments->pluck('admin_id')->toArray());
        // With 5 users and 2 auditors, one auditor gets 3, the other gets 2.
        // The exact distribution depends on the shuffle and modulo, but counts should be close.
        $this->assertTrue(in_array(3, $userAssignmentsCounts) && in_array(2, $userAssignmentsCounts));

        foreach ($users as $user) {
            $this->assertDatabaseHas('level_admin_user', [
                'level_id' => $level->id,
                'user_id' => $user->id,
                // We can also check that the admin_id is one of the auditor ids
            ]);
            $assignment = $assignments->firstWhere('user_id', $user->id);
            $this->assertNotNull($assignment, "User {$user->id} should have an assignment.");
            $this->assertTrue($auditors->pluck('id')->contains($assignment->admin_id), "Assigned admin_id should be a valid auditor ID.");
        }
    }

    public function test_assign_auditors_to_users_in_pivot_empty_users_or_auditors()
    {
        /** @var Level $level */
        $level = Level::factory()->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::factory()->count(3)->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Admin> $auditors */
        $auditors = Admin::factory()->count(2)->create();

        // Test with empty users
        $this->levelRepository->assignAuditorsToUsersInPivot($level, collect(), $auditors);
        $this->assertDatabaseCount('level_admin_user', 0);

        // Test with empty auditors
        $this->levelRepository->assignAuditorsToUsersInPivot($level, $users, collect());
        $this->assertDatabaseCount('level_admin_user', 0);
    }

    // We will add more tests here
} 