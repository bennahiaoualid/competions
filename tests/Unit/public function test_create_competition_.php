








public function test_add_competition_users_success()
{
    // Arrange
    $data = $this->createCompetitionData();
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    foreach ($data as $key => $value) {
        $competition->$key = $value;
    }
    $user_ids = [1, 2, 3];
    
    // Set up specific expectations for this test
    $this->competitionRepository
        ->shouldReceive('addUsersToCompetition')
        ->with($competition, $user_ids)
        ->once()
        ->andReturn(true);  

    // Act
    $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
    
    // Assert
    $this->assertTrue($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.success.saved'),
        Session::get('messages')[0]['message']
    );
}

public function test_add_competition_users_failure()
{
    // Arrange
    $data = $this->createCompetitionData();
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    foreach ($data as $key => $value) {
        $competition->$key = $value;
    }
    $user_ids = [1, 2, 3];  
    
    // Set up specific expectations for this test
    $this->competitionRepository
        ->shouldReceive('addUsersToCompetition')
        ->with($competition, $user_ids)
        ->once()
        ->andThrow(new Exception('Database error')); 

    // Act
        $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));    
    $this->assertStringContainsString(
        trans('messages.validation.fail.saved'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_competition_user_success()
{
    // Arrange
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    $user_id = 1;
    
    // Mock repository behavior
    $this->competitionRepository
        ->shouldReceive('findById')
        ->with($competition->id)
        ->once()
        ->andReturn($competition);
        
    $this->competitionRepository
        ->shouldReceive('removeUserFromCompetition')
        ->with($competition, $user_id)
        ->once()
        ->andReturn(true);

    // Act
    $result = $this->competitionService->removeCompetitionUser($competition->id, $user_id);
    
    // Assert - Test only what the SERVICE does
    $this->assertTrue($result);
    $this->assertStringContainsString(
        trans('messages.validation.success.deleted'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_competition_user_when_competition_not_found()
{
    // Arrange
    $competition_id = 999;
    $user_id = 1;
    
    $this->competitionRepository
        ->shouldReceive('findById')
        ->with($competition_id)
        ->once()
        ->andReturn(null);

    // Act
    $result = $this->competitionService->removeCompetitionUser($competition_id, $user_id);
    
    // Assert
    $this->assertFalse($result);
}

public function test_remove_competition_user_when_repository_throws_exception()
{
    // Arrange
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    $user_id = 1;
    
    $this->competitionRepository
        ->shouldReceive('findById')
        ->with($competition->id)
        ->once()
        ->andReturn($competition);
        
    $this->competitionRepository
        ->shouldReceive('removeUserFromCompetition')
        ->with($competition, $user_id)
        ->once()
        ->andThrow(new Exception('Database error'));

    // Act
    $result = $this->competitionService->removeCompetitionUser($competition->id, $user_id);
    
    // Assert
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.fail.deleted'),
        Session::get('messages')[0]['message']
    );
}

public function test_add_competition_auditors_success()
{
    // Arrange
    $data = $this->createCompetitionData();
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    foreach ($data as $key => $value) {
        $competition->$key = $value;
    }
    $auditor_ids = [1, 2, 3];
    
    // Set up specific expectations for this test
    $this->competitionRepository
        ->shouldReceive('addAuditorsToCompetition')
        ->with($competition, $auditor_ids)
        ->once()
        ->andReturn(true);  

    // Act
    $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
    
    // Assert
    $this->assertTrue($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.success.saved'),
        Session::get('messages')[0]['message']
    );
}

public function test_add_competition_auditors_failure()
{
    // Arrange
    $data = $this->createCompetitionData();
    /** @var Competition|\Mockery\MockInterface $competition */
    $competition = $this->competition_partial;
    $competition->id = 1;
    foreach ($data as $key => $value) {
        $competition->$key = $value;
    }
    $auditor_ids = [1, 2, 3];  
    
    // Set up specific expectations for this test
    $this->competitionRepository
        ->shouldReceive('addAuditorsToCompetition')
        ->with($competition, $auditor_ids)
        ->once()
        ->andThrow(new Exception('Database error')); 

    // Act
        $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));    
    $this->assertStringContainsString(
        trans('messages.validation.fail.saved'),
        Session::get('messages')[0]['message']
    );
}

public function test_add_competition_auditor_unauthorized()
{
    // Arrange
    $auditor_ids = [1, 2, 3];

    // Mock Auth user without owner role
    $user = Mockery::mock(Admin::class);
    $user->shouldReceive('hasRole')->with('owner')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($user);

    // Create a mock competition that returns false for canEdit
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(false);
    $mock_competition->id = 1; // Set an ID if needed
    
    // Act
    $result = $this->competitionService->addCompetitionAuditors($mock_competition, $auditor_ids);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_update'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_auditor_competition_not_found()
{
    // Mock: findById returns null
    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturn(null);

    $result = $this->competitionService->removeCompetitionAuditor(999, 1);
    
    $this->assertFalse($result);
}

public function test_remove_auditor_unauthorized()
{
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(false);

    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($mock_competition);

    $result = $this->competitionService->removeCompetitionAuditor(1, 1);
    
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_update'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_auditor_only_one_auditor_left()
{
    // Mock auditors collection with count = 1
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(1);

    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(true);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($mock_competition);

    $result = $this->competitionService->removeCompetitionAuditor(1, 1);
    
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.remove_auditor_only_one'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_auditor_save_delete_fails()
{
    // Mock auditors collection with count > 1
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);

    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(true);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($mock_competition);

    // Mock the static method using Mockery's static mock
    $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
    $auditorSaveDelete->shouldReceive('deleteAuditor')
        ->with(1, $mock_competition)
        ->once()
        ->andThrow(new Exception("Error Processing Request"));

    $result = $this->competitionService->removeCompetitionAuditor(1, 1);
    
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.fail.deleted'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_auditor_success()
{
    // Mock auditors collection with count > 1
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);

    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(true);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($mock_competition);

    // Mock static method to return true
    $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
    $auditorSaveDelete->shouldReceive('deleteAuditor')
        ->with(1, $mock_competition)
        ->once()
        ->andReturn(true);

    // Mock repository removal
    $this->competitionRepository
        ->shouldReceive('removeAuditorFromCompetition')
        ->with($mock_competition, 1)
        ->once()
        ->andReturn(true);

    $result = $this->competitionService->removeCompetitionAuditor(1, 1);
    
    $this->assertTrue($result);
    $this->assertStringContainsString(
        trans('messages.validation.success.deleted'),
        Session::get('messages')[0]['message']
    );
}

public function test_remove_auditor_repository_exception()
{
    // Mock auditors collection with count > 1
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);

    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    $mock_competition->shouldReceive('canEdit')->andReturn(true);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

    $this->competitionRepository
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($mock_competition);

    $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
    $auditorSaveDelete->shouldReceive('deleteAuditor')
        ->with(1, $mock_competition)
        ->once()
        ->andReturn(true);

    // Mock repository to throw exception
    $this->competitionRepository
        ->shouldReceive('removeAuditorFromCompetition')
        ->with($mock_competition, 1)
        ->once()
        ->andThrow(new Exception('Database error'));

    $result = $this->competitionService->removeCompetitionAuditor(1, 1);
    
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.fail.deleted'),
        Session::get('messages')[0]['message']
    );
}  

public function test_activate_competition_fails_if_start_date_is_future_or_now()
{
    // Create a mock date that's in the future (should fail)
    $futureDate = now()->addDay();
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    // Mock the start_date attribute to return a Carbon instance
    $mock_competition->shouldReceive('getAttribute')
        ->with('start_date')
        ->andReturn($futureDate);
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_activate_early'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_fails_if_levels_count_mismatch()
{
    // Start date is in the past (should pass first check)
    $pastDate = now()->subDay();
    
    // Levels collection with wrong count
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(2); // Different from levels_number
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    // Mock attributes
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3); // Mismatch!
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_activate_match_levels'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_fails_if_insufficient_users()
{
    // Mock all previous validations to pass
    $pastDate = now()->subDay();
    
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(3);
    
    $usersCollection = Mockery::mock();
    $usersCollection->shouldReceive('count')->andReturn(2); // <= 2 should fail
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
    $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_activate_less_competitors'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_fails_if_no_auditors()
{
    // Mock all previous validations to pass
    $pastDate = now()->subDay();
    
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(3);
    
    $usersCollection = Mockery::mock();
    $usersCollection->shouldReceive('count')->andReturn(5); // > 2, should pass
    
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(0); // Should fail
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
    $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_activate_less_auditor'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_fails_if_levels_not_all_after_now()
{
    // Mock all previous validations to pass
    $pastDate = now()->subDay();
    
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(3);
    
    $usersCollection = Mockery::mock();
    $usersCollection->shouldReceive('count')->andReturn(5);
    
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
    $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
    
    // Mock the model method
    $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(false); // Should fail
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertTrue(Session::has('messages'));
    $this->assertStringContainsString(
        trans('messages.validation.not_allow.competition_activate_level_pass'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_success()
{
    // Mock all validations to pass
    $pastDate = now()->subDay();
    
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(3);
    
    $usersCollection = Mockery::mock();
    $usersCollection->shouldReceive('count')->andReturn(5);
    
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
    $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
    $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
    
    // Mock setAttribute for start_date assignment
    $mock_competition->shouldReceive('setAttribute')->with('start_date', Mockery::any())->andReturnSelf();
    
    // Mock database operations
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    
    // Mock repository
    $this->competitionRepository->shouldReceive('activate')
        ->with($mock_competition)
        ->once()
        ->andReturn(true);
    
    // Mock notification
    $userNotifyEmailMock = Mockery::mock('alias:' . UserNotifyEmail::class);

    $userNotifyEmailMock->shouldReceive('usersActivateCompetition')
        ->with($mock_competition)
        ->once();
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertTrue($result);
    $this->assertStringContainsString(
        trans('messages.validation.success.activated'),
        Session::get('messages')[0]['message']
    );
}

public function test_activate_competition_database_exception()
{
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('rollback')->once();
    // Mock all validations to pass
    $pastDate = now()->subDay();
    
    $levelsCollection = Mockery::mock();
    $levelsCollection->shouldReceive('count')->andReturn(3);
    
    $usersCollection = Mockery::mock();
    $usersCollection->shouldReceive('count')->andReturn(5);
    
    $auditorsCollection = Mockery::mock();
    $auditorsCollection->shouldReceive('count')->andReturn(2);
    
    /** @var Competition|\Mockery\MockInterface $mock_competition */
    $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
    
    $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
    $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
    $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
    $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
    $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
    $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
    $mock_competition->shouldReceive('setAttribute')->with('start_date', Mockery::any())->andReturnSelf();
    
    // Mock database operations with exception
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('rollback')->once();
    
    // Mock repository to throw exception
    $this->competitionRepository->shouldReceive('activate')
        ->with($mock_competition)
        ->once()
        ->andThrow(new Exception('Database error'));
    
    // Act
    $result = $this->competitionService->activateCompetition($mock_competition);
    
    // Assert
    $this->assertFalse($result);
    $this->assertStringContainsString(
        trans('messages.validation.fail.activated'),
        Session::get('messages')[0]['message']);
}