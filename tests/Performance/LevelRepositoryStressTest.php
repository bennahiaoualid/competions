<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;

use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use App\Repository\Competition\LevelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;


class LevelRepositoryStressTest extends TestCase
{
    use RefreshDatabase;

    private LevelRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LevelRepository();
    }

    public function test_large_insertion_is_possible()
    {
        $competition = Competition::factory()->create();
        $level = Level::factory()->create(['competition_id' => $competition->id]);
        $users = User::factory()->count(1000)->create();
        $questions = Question::factory()->count(100)->create(['level_id' => $level->id]);

        $competition->users()->attach($users->pluck('id'));

        $start = microtime(true);
        $this->repository->insertMissingResponsesForLevel($level, 1000);
        $end = microtime(true);

        $expectedCount = 1000 * 100;
        $this->assertEquals($expectedCount, Response::count());

        echo "\nInserted $expectedCount responses in " . round($end - $start, 2) . " seconds.";
    } 
    
}
