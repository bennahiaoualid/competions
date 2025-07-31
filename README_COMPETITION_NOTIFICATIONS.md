# Competition Notification System

## Overview

This system provides real-time notifications for competition-related events. It uses a single generic notification class that can handle all competition events with proper translation support.

## Architecture

### 1. Generic Notification Class
- **File**: `app/Notifications/User/CompetitionNotification.php`
- **Purpose**: Handles all competition-related notifications
- **Features**: 
  - Multi-language support (English/Arabic)
  - Real-time broadcasting
  - Database storage
  - Priority types (info, success, warning, danger)
  - Direct links to relevant pages

### 2. Notification Service
- **File**: `app/Services/Notification/CompetitionNotificationService.php`
- **Purpose**: Provides clean methods to send notifications
- **Features**:
  - Send to all competition users
  - Send to specific users
  - Send to single user
  - Automatic eligibility checking

## Supported Events

### Competition Events
1. **Competition Created** (`created`)
   - Sent to eligible users based on age criteria
   - Priority: `info`

2. **Competition Updated** (`updated`)
   - Sent to all competition participants
   - Priority: `info`

3. **Competition Activated** (`activated`)
   - Sent to all competition participants
   - Priority: `success`

4. **User Added to Competition** (`user_added`)
   - Sent to newly added users
   - Priority: `info`

### Level Events
1. **Level Created** (`level_created`)
   - Sent to all competition participants
   - Priority: `info`

2. **Level Updated** (`level_updated`)
   - Sent to all competition participants
   - Priority: `info`

3. **Level Activated** (`level_activated`)
   - Sent to all competition participants
   - Priority: `success`
   - Includes start time and duration

4. **Level Finished** (`level_finished`)
   - Sent to all competition participants
   - Priority: `info`

## Usage Examples

### In Services

```php
// In CompetitionService
public function createCompetition(array $data): bool
{
    try {
        $result = $this->transactionManager->run(function () use ($data) {
            $competition = Competition::create(array_merge($data, ['admin_id' => Auth::id()]));
            
            // Send notification to eligible users
            $this->notificationService->competitionCreated($competition);
            
            return true;
        });
        return $result;
    } catch (Exception $exception) {
        // Handle error
    }
}

// In CompetitionService - Adding users
public function addCompetitionUsers(Competition $competition, array $user_ids): bool
{
    try {
        $result = $this->transactionManager->run(function () use ($competition, $user_ids) {
            $this->competitionRepository->addUsersToCompetition($competition, $user_ids);
            
            // Get the newly added users for notifications
            $newUsers = User::whereIn('id', $user_ids)->get();
            
            // Send notification to newly added users
            $this->notificationService->notifyUsers($newUsers, $competition, 'user_added');
            
            return true;
        });
        return $result;
    } catch (Exception $exception) {
        // Handle error
    }
}

// In LevelService
public function activateLevel(Level $level): bool
{
    try {
        // Validation logic...
        
        $updated = $this->levelRepository->update($level, ['status' => 'active']);
        
        if ($updated) {
            // Send notification to competition users
            $this->notificationService->levelActivated($level->competition, $level);
        }
        
        return true;
    } catch (Exception $exception) {
        // Handle error
    }
}
```

### Direct Usage

```php
// Send to all competition users
$notificationService->notifyCompetitionUsers($competition, 'level_activated', $level);

// Send to specific users
$notificationService->notifyUsers($users, $competition, 'level_created', $level);

// Send to single user
$notificationService->notifyUser($user, $competition, 'activated');
```

## Translation Keys

### English (`lang/en/notifications.php`)
```php
'competition' => [
    'created' => [
        'title' => 'New Competition Created',
        'message' => 'A new competition ":competition_title" has been created and you are eligible to participate.',
    ],
    'level_activated' => [
        'title' => 'Level Started',
        'message' => 'Level ":level_name" in competition ":competition_title" has started! Duration: :duration',
    ],
    // ... more events
],
```

### Arabic (`lang/ar/notifications.php`)
```php
'competition' => [
    'created' => [
        'title' => 'تم إنشاء مسابقة جديدة',
        'message' => 'تم إنشاء مسابقة جديدة ":competition_title" ويمكنك المشاركة فيها.',
    ],
    'level_activated' => [
        'title' => 'بدأ المستوى',
        'message' => 'بدأ المستوى ":level_name" في المسابقة ":competition_title"! المدة: :duration',
    ],
    // ... more events
],
```

## Frontend Integration

The notifications are automatically handled by the existing `NotificationManager.js` which:
- Displays real-time notifications
- Shows appropriate styling based on priority
- Provides clickable links
- Supports locale changes

## Benefits

1. **Single Class**: One notification class handles all competition events
2. **Maintainable**: Easy to add new event types
3. **Consistent**: Same behavior across all competition notifications
4. **Translated**: Full multi-language support
5. **Real-time**: Instant notifications via WebSockets
6. **Flexible**: Can target specific users or all competition participants

## Adding New Events

To add a new competition event:

1. **Add translation keys** in `lang/en/notifications.php` and `lang/ar/notifications.php`
2. **Add event handling** in `CompetitionNotificationService.php`
3. **Update the notification class** if needed (usually not necessary)
4. **Call the service** in your business logic

Example:
```php
// In CompetitionNotificationService.php
public function levelDeleted(Competition $competition, Level $level): void
{
    $this->notifyCompetitionUsers($competition, 'level_deleted', $level);
}

// In your service
$this->notificationService->levelDeleted($competition, $level);
``` 