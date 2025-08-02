# Competition Process Documentation

## Overview

This document provides a comprehensive guide to the competition management system, covering the complete lifecycle from creation to activation. The system is designed with a multi-layered architecture that includes validation, approval workflows, notifications, and audit trails.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Competition Lifecycle](#competition-lifecycle)
3. [Database Schema](#database-schema)
4. [Service Layer](#service-layer)
5. [Validation Rules](#validation-rules)
6. [Notification System](#notification-system)
7. [Approval Workflow](#approval-workflow)
8. [API Endpoints](#api-endpoints)
9. [Error Handling](#error-handling)
10. [Testing Guidelines](#testing-guidelines)

## Architecture Overview

The competition system follows a layered architecture:

```
Controller Layer (CompetitionController)
    ↓
Service Layer (CompetitionService)
    ↓
Repository Layer (CompetitionRepository)
    ↓
Model Layer (Competition Model)
    ↓
Database Layer
```

### Key Components

- **Controllers**: Handle HTTP requests and responses
- **Services**: Business logic and validation
- **Repositories**: Data access and queries
- **Models**: Eloquent models with relationships
- **Traits**: Reusable functionality (RegisterLogs, CrudOperationNotificationAlert)

## Competition Lifecycle

### 1. Creation Process

#### Prerequisites
- Admin authentication required
- Valid competition data (title, description, start_date, age_range, levels_number)

#### Process Flow
```php
// 1. Request validation (StoreCompetitionRequest)
// 2. Service method call
$competitionService->createCompetition($data);

// 3. Database transaction
$competition = Competition::create([
    'admin_id' => Auth::id(),
    'title' => $data['title'],
    'description' => $data['description'],
    'start_date' => $data['start_date'],
    'age_start' => $data['age_start'],
    'age_end' => $data['age_end'],
    'levels_number' => $data['levels_number'],
    'status' => 'pending'
]);

// 4. Background job dispatch
SyncCompetitionParticipants::dispatch($competition);

// 5. Notification dispatch
$notificationService->competitionCreated($competition);
```

#### Validation Rules
- Title: required, string
- Description: nullable, text
- Start date: required, future date
- Age range: required, integers, logical range
- Levels number: required, integer, positive

### 2. Update Process

#### Prerequisites
- Competition must be in 'pending' status
- Admin must be the competition creator
- Valid update data

#### Process Flow
```php
// 1. Validation checks
if (!$competition->canEdit()) {
    return false;
}

// 2. Update competition data
$result = $competitionRepository->update($competition, $data);

// 3. Check if participant sync is needed
if ($result['resyncCompetitionParticipants']) {
    SyncCompetitionParticipants::dispatch($competition, isUpdate: true);
} else {
    UserNotifyEmail::usersUpdateCompetition($competition);
}

// 4. Send notifications
$notificationService->competitionUpdated($competition);
```

### 3. Activation Process

#### Prerequisites
- Competition must have all required levels created
- Competition must have at least 3 participants
- Competition must have at least 1 auditor
- All levels must have future start dates
- Competition start date must be in the past

#### Process Flow
```php
// 1. Validation checks
if ($competition->start_date->greaterThanOrEqualTo(now())) {
    return false; // Too early
}

if ($competition->levels->count() != $competition->levels_number) {
    return false; // Level count mismatch
}

if ($competition->users->count() <= 2) {
    return false; // Insufficient participants
}

if ($competition->auditors->count() == 0) {
    return false; // No auditors
}

if (!$competition->isAllLevelAfterNow()) {
    return false; // Level timing conflict
}

// 2. Activate competition
$competition->start_date = now();
$competition->update(['status' => Competition::STATUS_ACTIVE]);

// 3. Send notifications
UserNotifyEmail::usersActivateCompetition($competition);
$notificationService->competitionActivated($competition);
```

## Database Schema

### Competitions Table
```sql
CREATE TABLE competitions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    admin_id BIGINT NULL,
    start_date DATETIME NOT NULL,
    age_start INT NOT NULL,
    age_end INT NOT NULL,
    levels_number INT NOT NULL,
    status ENUM('pending','active','finished') DEFAULT 'pending',
    participants_sync_status ENUM('pending','in_progress','completed','failed') DEFAULT 'pending',
    is_suspended BOOLEAN DEFAULT FALSE,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

### Key Relationships
- **Competition → Admin**: BelongsTo (competition creator)
- **Competition → Users**: BelongsToMany (participants)
- **Competition → Admins**: BelongsToMany (auditors)
- **Competition → Levels**: HasMany (competition levels)

## Service Layer

### CompetitionService Methods

#### createCompetition(array $data): bool
- Creates new competition with validation
- Dispatches participant sync job
- Sends creation notifications
- Returns success/failure status

#### updateCompetition(Competition $competition, array $data): bool
- Updates competition data
- Handles participant resync if needed
- Sends update notifications
- Returns success/failure status

#### activateCompetition(Competition $competition): bool
- Validates activation prerequisites
- Updates competition status to active
- Sends activation notifications
- Returns success/failure status

#### addCompetitionUsers(Competition $competition, array $user_ids): bool
- Adds users to competition
- Sends notifications to new participants
- Returns success/failure status

#### requestAuditorAssignment(Competition $competition, array $auditorIds): bool
- Creates approval requests for auditor assignment
- Validates existing approval status
- Sends notification requests
- Returns success/failure status

## Validation Rules

### Competition Creation
```php
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'start_date' => 'required|date|after:now',
'age_start' => 'required|integer|min:1|max:100',
'age_end' => 'required|integer|min:1|max:100|gte:age_start',
'levels_number' => 'required|integer|min:1|max:10'
```

### Competition Update
```php
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'start_date' => 'required|date',
'age_start' => 'required|integer|min:1|max:100',
'age_end' => 'required|integer|min:1|max:100|gte:age_start',
'levels_number' => 'required|integer|min:1|max:10'
```

### Activation Requirements
- Competition status must be 'pending'
- All levels must be created (count matches levels_number)
- At least 3 participants required
- At least 1 auditor required
- All levels must have future start dates
- Competition start date must be in the past

## Notification System

### Notification Events
1. **Competition Created**: Notifies eligible users about new competition
2. **Competition Updated**: Notifies participants about changes
3. **Competition Activated**: Notifies participants about activation
4. **User Added**: Notifies newly added participants
5. **Auditor Requested**: Notifies admins about auditor assignment requests

### Notification Channels
- **Database**: Stored notifications for in-app display
- **Email**: Bulk email notifications to participants
- **Real-time**: Broadcasting for instant notifications

### Notification Service Usage
```php
// Send to all competition users
$notificationService->competitionCreated($competition);

// Send to specific users
$notificationService->notifyUsers($users, $competition, 'user_added');

// Send to single user
$notificationService->notifyUser($user, $competition, 'activated');
```

## Approval Workflow

### Auditor Assignment Process
1. **Request Creation**: Admin requests auditor assignment
2. **Approval Check**: System validates admin availability
3. **Request Storage**: Creates approval record in database
4. **Notification**: Notifies requested admins
5. **Approval/Rejection**: Admins respond to requests
6. **Assignment**: System assigns approved auditors

### Approval Status Types
- **Pending**: Request awaiting response
- **Approved**: Request accepted
- **Rejected**: Request declined

## API Endpoints

### Competition Management
```
POST   /admin/competitions              # Create competition
GET    /admin/competitions              # List competitions
GET    /admin/competitions/{id}/edit    # Edit competition form
PUT    /admin/competitions/{id}         # Update competition
DELETE /admin/competitions/{id}         # Delete competition
POST   /admin/competitions/{id}/activate # Activate competition
```

### Participant Management
```
GET    /admin/competitions/{id}/users   # List participants
POST   /admin/competitions/{id}/users   # Add participants
DELETE /admin/competitions/{id}/users   # Remove participant
```

### Auditor Management
```
GET    /admin/competitions/{id}/auditors # List auditors
POST   /admin/competitions/{id}/auditors # Request auditor assignment
DELETE /admin/competitions/{id}/auditors # Remove auditor
```

## Error Handling

### Common Error Scenarios
1. **Validation Errors**: Invalid input data
2. **Permission Errors**: Unauthorized access
3. **Business Logic Errors**: Invalid state transitions
4. **Database Errors**: Transaction failures
5. **Notification Errors**: Failed message delivery

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
$this->registerLogs('Competition creation error: ', $exception);
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
// Test competition creation
public function test_competition_can_be_created()
{
    $data = [
        'title' => 'Test Competition',
        'description' => 'Test Description',
        'start_date' => now()->addDays(7),
        'age_start' => 18,
        'age_end' => 25,
        'levels_number' => 3
    ];
    
    $result = $this->competitionService->createCompetition($data);
    
    $this->assertTrue($result);
    $this->assertDatabaseHas('competitions', [
        'title' => 'Test Competition',
        'status' => 'pending'
    ]);
}

// Test activation validation
public function test_competition_cannot_be_activated_without_levels()
{
    $competition = Competition::factory()->create([
        'levels_number' => 3,
        'status' => 'pending'
    ]);
    
    $result = $this->competitionService->activateCompetition($competition);
    
    $this->assertFalse($result);
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

#### Competition Won't Activate
- Check if all levels are created
- Verify participant count (minimum 3)
- Ensure auditor count (minimum 1)
- Check level start dates are in future

#### Notification Not Sending
- Check notification service configuration
- Verify user email addresses
- Check queue worker status
- Review notification logs

#### Database Transaction Errors
- Check foreign key constraints
- Verify data integrity
- Review transaction isolation level
- Check for deadlocks

### Debug Tools
- **Laravel Telescope**: Monitor requests and queries
- **Laravel Debugbar**: Debug performance issues
- **Log Files**: Check application logs
- **Database Logs**: Monitor SQL queries

## Future Enhancements

### Planned Features
1. **Advanced Scheduling**: More flexible competition scheduling
2. **Real-time Updates**: WebSocket-based real-time notifications
3. **Analytics Dashboard**: Competition performance metrics
4. **Bulk Operations**: Mass participant management
5. **API Versioning**: RESTful API with versioning

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