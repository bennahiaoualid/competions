# Level and Auditing Process Documentation

## Overview

This document provides a comprehensive guide to the level management and auditing system, covering the complete lifecycle from level creation to score calculation and auditing. The system includes sophisticated validation rules, approval workflows, real-time scoring, and comprehensive audit trails.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Level Lifecycle](#level-lifecycle)
3. [Auditing Process](#auditing-process)
4. [Scoring System](#scoring-system)
5. [Database Schema](#database-schema)
6. [Service Layer](#service-layer)
7. [Validation Rules](#validation-rules)
8. [Approval Workflow](#approval-workflow)
9. [API Endpoints](#api-endpoints)
10. [Error Handling](#error-handling)
11. [Testing Guidelines](#testing-guidelines)

## Architecture Overview

The level and auditing system follows a multi-layered architecture:

```
Controller Layer (LevelController, AuditController)
    ↓
Service Layer (LevelService, AuditService)
    ↓
Repository Layer (LevelRepository, AuditRepository)
    ↓
Model Layer (Level, Question, Response Models)
    ↓
Database Layer
```

### Key Components

- **LevelService**: Manages level creation, updates, activation, and finishing
- **AuditService**: Handles user response auditing and score calculation
- **UserResponseCalculation**: Trait for score calculation algorithms
- **Approval System**: Manages level manager assignments
- **Notification System**: Real-time updates and notifications

## Level Lifecycle

### 1. Level Creation Process

#### Prerequisites
- Competition must be in 'pending' status
- Competition must not have reached maximum levels
- Valid level data (name, description, start_date, duration, questions_number)
- No time conflicts with existing levels

#### Process Flow
```php
// 1. Validation checks
if ($competition->hasReachedMaxLevels()) {
    return false; // Maximum levels reached
}

if ($competition->start_date->gt(Carbon::parse($data["start_date"]))) {
    return false; // Level start date before competition start
}

if ($this->levelRepository->hasTimeConflict($competition->id, $data["start_date"], $data["duration"])) {
    return false; // Time conflict with existing levels
}

// 2. Create level
$level = $this->levelRepository->create([
    'competition_id' => $competition->id,
    'name' => $data['name'],
    'description' => $data['description'],
    'start_date' => $data['start_date'],
    'duration' => $data['duration'],
    'questions_number' => $data['questions_number'],
    'admin_id' => null, // Will be set via approval
    'status' => 'pending'
]);

// 3. Request level manager assignment if provided
if ($adminId) {
    $this->requestLevelManagerAssignment($level, $adminId);
}

// 4. Send notifications
$notificationService->levelCreated($competition, $level);
```

#### Validation Rules
- Name: required, string, max 255 characters
- Description: nullable, text
- Start date: required, future date, after competition start
- Duration: required, integer, positive (minutes)
- Questions number: required, integer, positive
- Admin ID: nullable, must be available as level manager

### 2. Level Update Process

#### Prerequisites
- Competition must be in 'pending' status
- Level must be in 'pending' status
- Admin must be competition creator
- No time conflicts with existing levels

#### Process Flow
```php
// 1. Validation checks
if ($competition->status != Competition::STATUS_PENDING) {
    return false; // Active competition cannot be updated
}

if ($level->status != Level::STATUS_PENDING) {
    return false; // Active level cannot be updated
}

// 2. Check for time conflicts if start date changed
$startDateChanged = !$newStartDate->eq($originalStartDate);
if ($startDateChanged) {
    if ($this->levelRepository->hasTimeConflict($level->competition_id, $data['start_date'], $level->duration, $level->id)) {
        return false; // Time conflict
    }
}

// 3. Update level data
$updateData = Arr::only($data, ['name', 'description', 'start_date', 'duration']);
$updated = $this->levelRepository->update($level, $updateData);

// 4. Handle manager change if needed
if ($level->admin_id != $data['admin_id'] && $updated) {
    $this->requestLevelManagerAssignment($level, $data['admin_id'], deletePending: true);
}

// 5. Send notifications
$notificationService->levelUpdated($competition, $level);
```

### 3. Level Activation Process

#### Prerequisites
- Competition must be in 'active' status
- Level must be in 'pending' status
- Level must be the earliest pending level
- Previous level must be audited (if exists)
- All required questions must be added
- Level start date must be in the past

#### Process Flow
```php
// 1. Validation checks
if ($competition->status != Competition::STATUS_ACTIVE) {
    return false; // Competition not active
}

if ($level->start_date->gte(now())) {
    return false; // Level not ready to start
}

if ($level->questions->count() != $level->questions_number) {
    return false; // Question count mismatch
}

if (!$level->isTheEarliest()) {
    return false; // Not the next level in sequence
}

if (!$level->isThePreviousAudit()) {
    return false; // Previous level not audited
}

// 2. Activate level
$newStartDate = now();
$updated = $this->levelRepository->update($level, [
    'start_date' => $newStartDate,
    'status' => 'active'
]);

// 3. Send notifications
UserNotifyEmail::usersActivateLevel($competition, $level);
$notificationService->levelActivated($competition, $level);
```

### 4. Level Finishing Process

#### Prerequisites
- Level must be in 'active' status
- Level duration must have elapsed
- Admin must have edit permissions

#### Process Flow
```php
// 1. Validation checks
if (!($level->status == Level::STATUS_ACTIVE && !$level->isStillActive())) {
    return false; // Level still active or not active
}

// 2. Finish level
FinishLevelJob::dispatchSync($level);

// 3. Send notifications
$notificationService->levelFinished($level->competition, $level);
```

## Auditing Process

### 1. Audit Preparation

#### Prerequisites
- Level must be finished
- User must have participated in the level
- Admin must be authorized to audit the level

#### Process Flow
```php
// 1. Get competitions for audit
$competitions = $this->auditRepository->getCompetitionsForAudit($filters);

// 2. Get users for specific level
$users = $this->auditRepository->getUsersForLevel($level->id);

// 3. Get user responses
$questions = $this->auditRepository->getLevelQuestionsWithUserResponses($level->id, $user->id);
```

### 2. Score Submission Process

#### Prerequisites
- Admin must be authorized to audit the user
- Responses must exist and be unprocessed
- Scores must be within valid range

#### Process Flow
```php
// 1. Authorization check
if (!$this->auditRepository->isAdminAllowedToAuditUser($level, $user)) {
    return false; // Unauthorized
}

// 2. Get targeted responses
$responses = $this->auditRepository->getTargetedUserResponses($level->id, $user->id, $responseIds);

// 3. Calculate final scores
$notifications = $this->calculateUserResponseFinalScores($responses, $scores);

// 4. Update response records
foreach ($responses as $response) {
    $response->score = $score;
    $response->final_score = $finalScore;
    $response->admin_id = Auth::id();
    $response->save();
}
```

## Scoring System

### 1. Score Calculation Algorithm

The scoring system uses a sophisticated algorithm that considers multiple factors:

```php
public function calculateUserResponseFinalScores($responses, $scores)
{
    foreach ($responses as $response) {
        $score = (float) $scores[$response->id];
        
        // Validate score against maximum
        if ($score > $response->question->max_score) {
            continue; // Invalid score
        }
        
        // Calculate base score
        if ($score === 0 || $response->response_duration >= $response->question->duration) {
            $calc_score = $score / 2; // Time penalty
        } else {
            $calc_score = $score - $response->response_duration / $response->question->duration * ($score / 2);
        }
        
        // Apply penalty
        $penalty = $calc_score * $response->penalty;
        $final_score = round($calc_score - $penalty, 2);
        
        // Save results
        $response->score = $score;
        $response->final_score = $final_score;
        $response->admin_id = Auth::id();
        $response->save();
    }
}
```

### 2. Penalty Calculation

The system calculates penalties based on suspicious behavior:

```php
public function calculatePenalty(int $duration, ?int $keystrokes, ?string $answer): array
{
    $flags = [];
    $penalty = 0;
    
    $length = strlen($answer);
    $wordCount = str_word_count(strip_tags($answer));
    
    // Penalty: Answer too fast and long (likely copy/paste)
    $cps = $length / max($duration, 0.01); // characters per second
    if ($cps > 10) {
        $flags[] = 'too_fast_long_answer';
        $penalty += 0.2;
    }
    
    // Penalty: Very low keystroke-to-length ratio (likely pasted)
    $keystrokeRatio = $keystrokes > 0 ? $length / $keystrokes : $length;
    if ($keystrokeRatio > 10) {
        $flags[] = 'low_keystrokes';
        $penalty += 0.2;
    }
    
    // Penalty: Words/minute exceed human threshold
    $wpm = $duration > 0 ? ($wordCount / $duration) * 60 : 0;
    if ($wpm > 90) {
        $flags[] = 'suspicious_wpm';
        $penalty += 0.1;
    }
    
    // Penalty: Tab switch detected
    if (session('tab_switched')) {
        $flags[] = 'tab_switch';
        $penalty += 0.15;
    }
    
    return [
        'flags' => json_encode($flags),
        'penalty' => min(1.0, $penalty)
    ];
}
```

### 3. Score Validation

- Scores must not exceed question maximum score
- Final scores are rounded to 2 decimal places
- Penalties are capped at 100% of base score
- Time penalties are applied for incomplete responses

## Database Schema

### Levels Table
```sql
CREATE TABLE levels (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    competition_id BIGINT NOT NULL,
    admin_id BIGINT NULL,
    questions_number INT NOT NULL,
    start_date DATETIME NOT NULL,
    duration INT NOT NULL,
    status ENUM('pending','active','finished') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

### Questions Table
```sql
CREATE TABLE questions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    level_id BIGINT NOT NULL,
    question_text TEXT NOT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    duration INT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
);
```

### Responses Table
```sql
CREATE TABLE responses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    question_id BIGINT NOT NULL,
    answer TEXT NULL,
    response_duration DECIMAL(8,2) NULL,
    keystrokes INT NULL,
    penalty DECIMAL(3,2) DEFAULT 0,
    flags JSON NULL,
    score DECIMAL(5,2) NULL,
    final_score DECIMAL(5,2) NULL,
    admin_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

## Service Layer

### LevelService Methods

#### create(array $data, Competition $competition): bool
- Creates new level with validation
- Handles level manager assignment requests
- Sends creation notifications
- Returns success/failure status

#### update(Level $level, array $data): bool
- Updates level data with validation
- Handles time conflict checks
- Manages level manager changes
- Sends update notifications

#### activateLevel(Level $level): bool
- Validates activation prerequisites
- Updates level status to active
- Sends activation notifications
- Returns success/failure status

#### finishLevel(Level $level): bool
- Validates finishing prerequisites
- Dispatches finish job
- Sends finish notifications
- Returns success/failure status

### AuditService Methods

#### auditCompetitions(array $filters = [])
- Retrieves competitions available for audit
- Applies filtering options
- Returns paginated results

#### auditUserResponses(Level $level, string $userIdentifier): array
- Retrieves user responses for specific level
- Validates user participation
- Returns questions with user responses

#### submitAudit(array $responses, Level $level, User $user): bool
- Validates admin authorization
- Calculates final scores
- Updates response records
- Returns success/failure status

## Validation Rules

### Level Creation
```php
'name' => 'required|string|max:255',
'description' => 'nullable|string',
'start_date' => 'required|date|after:competition_start',
'duration' => 'required|integer|min:1|max:480',
'questions_number' => 'required|integer|min:1|max:50',
'admin_id' => 'nullable|exists:admins,id'
```

### Level Update
```php
'name' => 'required|string|max:255',
'description' => 'nullable|string',
'start_date' => 'required|date',
'duration' => 'required|integer|min:1|max:480',
'questions_number' => 'required|integer|min:1|max:50',
'admin_id' => 'nullable|exists:admins,id'
```

### Score Submission
```php
'scores' => 'required|array',
'scores.*' => 'required|numeric|min:0|max:question_max_score'
```

## Approval Workflow

### Level Manager Assignment Process
1. **Request Creation**: Admin requests level manager assignment
2. **Availability Check**: System validates admin availability
3. **Request Storage**: Creates approval record
4. **Notification**: Notifies requested admin
5. **Approval/Rejection**: Admin responds to request
6. **Assignment**: System assigns approved manager

### Approval Status Types
- **Pending**: Request awaiting response
- **Approved**: Request accepted
- **Rejected**: Request declined

## API Endpoints

### Level Management
```
POST   /admin/levels                    # Create level
GET    /admin/levels/{id}/edit          # Edit level form
PUT    /admin/levels/{id}               # Update level
DELETE /admin/levels/{id}               # Delete level
POST   /admin/levels/{id}/activate      # Activate level
POST   /admin/levels/{id}/finish        # Finish level
```

### Auditing
```
GET    /admin/audit/competitions        # List competitions for audit
GET    /admin/audit/levels/{id}/users   # List users for level audit
GET    /admin/audit/users/{id}/responses # Get user responses
POST   /admin/audit/submit              # Submit audit scores
```

## Error Handling

### Common Error Scenarios
1. **Validation Errors**: Invalid input data
2. **Permission Errors**: Unauthorized access
3. **Business Logic Errors**: Invalid state transitions
4. **Time Conflict Errors**: Overlapping level schedules
5. **Score Validation Errors**: Invalid score ranges

### Error Response Format
```php
// Success
$this->flasher->crudSuccess('saved');

// Error
$this->flasher->crudFailure('saved');
$this->flasher->error(__('messages.validation.error.specific_error'));
```

### Logging
```php
// Log errors with context
$this->registerLogs('LevelService creation error: ', $exception);
```

## Testing Guidelines

### Unit Tests
- Test service methods in isolation
- Mock dependencies (repositories, notifications)
- Test validation logic
- Test error scenarios

### Feature Tests
- Test complete request/response cycles
- Test database transactions
- Test notification delivery
- Test authorization rules

### Test Examples
```php
// Test level creation
public function test_level_can_be_created()
{
    $competition = Competition::factory()->create();
    $data = [
        'name' => 'Test Level',
        'description' => 'Test Description',
        'start_date' => now()->addDays(1),
        'duration' => 60,
        'questions_number' => 5
    ];
    
    $result = $this->levelService->create($data, $competition);
    
    $this->assertTrue($result);
    $this->assertDatabaseHas('levels', [
        'name' => 'Test Level',
        'status' => 'pending'
    ]);
}

// Test score calculation
public function test_score_calculation_with_penalty()
{
    $response = Response::factory()->create([
        'score' => 80,
        'penalty' => 0.2,
        'response_duration' => 30,
        'question' => Question::factory()->create(['duration' => 60])
    ]);
    
    $scores = [$response->id => 80];
    $notifications = $this->auditService->calculateUserResponseFinalScores([$response], $scores);
    
    $this->assertEquals(64, $response->final_score); // 80 - (80 * 0.2)
}
```

## Best Practices

### Code Organization
1. **Single Responsibility**: Each service method has one clear purpose
2. **Dependency Injection**: Use constructor injection for dependencies
3. **Transaction Management**: Wrap database operations in transactions
4. **Error Handling**: Use try-catch blocks with proper logging
5. **Validation**: Validate data at multiple layers

### Performance Considerations
1. **Eager Loading**: Load relationships when needed
2. **Background Jobs**: Use queues for heavy operations
3. **Caching**: Cache frequently accessed data
4. **Database Indexes**: Index frequently queried columns

### Security Considerations
1. **Authorization**: Check permissions at service level
2. **Input Validation**: Validate all user inputs
3. **SQL Injection**: Use Eloquent ORM for queries
4. **XSS Protection**: Escape output data
5. **CSRF Protection**: Use Laravel's built-in CSRF protection

## Troubleshooting

### Common Issues

#### Level Won't Activate
- Check if competition is active
- Verify question count matches required number
- Ensure level is the earliest pending level
- Check if previous level is audited
- Verify level start date is in the past

#### Score Calculation Errors
- Check if scores exceed maximum values
- Verify response data integrity
- Review penalty calculation logic
- Check for null values in calculations

#### Audit Authorization Issues
- Verify admin permissions for level
- Check if admin is assigned to level
- Review audit repository logic
- Check user participation in level


### Technical Debt
1. **Code Refactoring**: Improve service layer organization
2. **Test Coverage**: Increase test coverage to 90%+
3. **Documentation**: Add inline code documentation
4. **Performance**: Optimize database queries
5. **Security**: Implement additional security measures

---

**Last Updated**: 2025-02-08  
**Version**: 1.0  
**Maintainer**: Development Team 