# Route Changes Guide

## Overview
This document outlines the critical dependencies and required actions when changing routes in the competition system. Route changes can impact multiple components including admin approvals, email notifications, and user interfaces.

## Critical Route Dependencies

### 1. Admin Approval System Routes
**File:** `app/Enums/AdminApprovalTypeEnum.php`

```php
// Current routes that MUST be updated if changed:
self::AUDITOR => [
    'name' => 'admin.competitions.edit',
    'params' => ['id' => base64_encode($entityId)]
],
self::LEVEL_MANAGER => [
    'name' => 'admin.competitions.level.edit',
    'params' => ['id' => base64_encode($entityId)]
],
```

**Impact:** Admin approval table "View Details" buttons will break if routes change.

### 2. Email Notification Routes
**File:** `app/Helpers/UserNotifyEmail.php`

```php
// Routes used in email notifications:
'link' => route('competitions.detail', ['competition' => $competition])
'link' => route('admin.competitions.level.edit', ['id' => base64_encode($level->id)])
'link' => route('admin.competitions.edit', ['id' => base64_encode($competition->id)])
```

**Impact:** Email links will be broken, affecting user experience.

### 3. Notification Service Routes
**File:** `app/Services/Notification/OptimizedCompetitionNotificationService.php`

```php
// Routes used in notification system:
return route('competitions.level', $level);
return route('competitions.detail', $competition);
```

**Impact:** In-app notifications will have broken links.

## Route Change Impact Matrix

| Route Pattern | Critical Files | Test Files | Impact Level |
|---------------|----------------|------------|--------------|
| `admin.competitions.edit` | `AdminApprovalTypeEnum.php` | All admin tests | 🔴 **Critical** |
| `admin.competitions.level.edit` | `AdminApprovalTypeEnum.php`, `UserNotifyEmail.php` | Level tests | 🔴 **Critical** |
| `competitions.detail` | `UserNotifyEmail.php`, `OptimizedCompetitionNotificationService.php` | User tests | 🟡 **High** |
| `competitions.level` | `OptimizedCompetitionNotificationService.php` | User tests | 🟡 **High** |
| `admin.competitions.*` | Various admin files | Admin tests | 🟢 **Medium** |
| `user.competitions.*` | Various user files | User tests | 🟢 **Medium** |

## Pre-Change Checklist

### Before Changing Any Route:

- [ ] **Search for route usage** across the entire codebase
- [ ] **Identify all dependent files** using the route
- [ ] **Check test files** for route references
- [ ] **Review email templates** for hardcoded routes
- [ ] **Check notification services** for route dependencies
- [ ] **Verify admin approval system** route usage
- [ ] **Document current route** for rollback purposes

### Critical Files to Check:

1. **Enum Files:**
   - `app/Enums/AdminApprovalTypeEnum.php`

2. **Service Files:**
   - `app/Helpers/UserNotifyEmail.php`
   - `app/Services/Notification/OptimizedCompetitionNotificationService.php`

3. **Test Files:**
   - `tests/Feature/Controllers/Competition/*`
   - `tests/Feature/Controllers/User/*`
   - `tests/Feature/Controllers/Admin/*`

4. **View Files:**
   - All Blade templates in `resources/views/`
   - Livewire components

## Route Change Process

### Step 1: Preparation
```bash
# Search for route usage
grep -r "route('admin.competitions.edit'" .
grep -r "route('admin.competitions.level.edit'" .
grep -r "route('competitions.detail'" .
grep -r "route('competitions.level'" .
```

### Step 2: Update Critical Files

#### Update AdminApprovalTypeEnum.php
```php
// If changing admin.competitions.edit route:
self::AUDITOR => [
    'name' => 'NEW_ROUTE_NAME', // Update this
    'params' => ['id' => base64_encode($entityId)]
],

// If changing admin.competitions.level.edit route:
self::LEVEL_MANAGER => [
    'name' => 'NEW_ROUTE_NAME', // Update this
    'params' => ['id' => base64_encode($entityId)]
],
```

#### Update UserNotifyEmail.php
```php
// Update email notification links:
'link' => route('NEW_ROUTE_NAME', ['competition' => $competition])
'link' => route('NEW_ROUTE_NAME', ['id' => base64_encode($level->id)])
```

#### Update OptimizedCompetitionNotificationService.php
```php
// Update notification service links:
return route('NEW_ROUTE_NAME', $level);
return route('NEW_ROUTE_NAME', $competition);
```

### Step 3: Update Tests
```bash
# Run tests to identify broken routes
php artisan test --filter=Competition
php artisan test --filter=Level
php artisan test --filter=User
```

### Step 4: Update Views
```bash
# Search for hardcoded routes in views
grep -r "route(" resources/views/
```

## Post-Change Verification

### Testing Checklist:
- [ ] **Admin approval table** - View Details buttons work
- [ ] **Email notifications** - Links in emails are functional
- [ ] **In-app notifications** - Notification links work
- [ ] **All test suites** - No broken route tests
- [ ] **User flows** - Competition and level pages accessible
- [ ] **Admin flows** - Edit pages accessible

### Manual Testing:
```bash
# Test admin approval system
php artisan serve
# Navigate to admin approvals page
# Click "View Details" buttons
# Verify correct pages load

# Test email notifications
# Create a competition/level
# Check email links work
```

## Route Naming Conventions

### Admin Routes:
- `admin.competitions.edit` - Competition edit page
- `admin.competitions.level.edit` - Level edit page
- `admin.competitions.level.*` - Level management actions

### User Routes:
- `competitions.detail` - Competition detail page
- `competitions.level` - Level detail page
- `user.competitions.*` - User competition actions

### Parameter Encoding:
- **Admin routes:** Use `base64_encode($id)` for security
- **User routes:** Use direct model binding
- **API routes:** Use standard parameter passing

## Adding New Entity Types

### 1. Update AdminApprovalTypeEnum:
```php
case NEW_ENTITY = 'new_entity';

public function getDetailUrl(int $entityId): array
{
    return match($this) {
        // ... existing cases
        self::NEW_ENTITY => [
            'name' => 'admin.new_entities.edit',
            'params' => ['id' => base64_encode($entityId)]
        ],
    };
}
```

### 2. Update Email Notifications:
```php
// In UserNotifyEmail.php
'link' => route('admin.new_entities.edit', ['id' => base64_encode($entity->id)])
```

### 3. Update Notification Service:
```php
// In OptimizedCompetitionNotificationService.php
return route('new_entities.detail', $entity);
```

## Emergency Rollback

### If Routes Break:
1. **Revert route changes** in route files
2. **Update enum file** to match original routes
3. **Update service files** to match original routes
4. **Run tests** to verify functionality
5. **Check email notifications** still work

### Rollback Commands:
```bash
# Revert route changes
git checkout HEAD~1 routes/

# Update enum file
git checkout HEAD~1 app/Enums/AdminApprovalTypeEnum.php

# Update service files
git checkout HEAD~1 app/Helpers/UserNotifyEmail.php
git checkout HEAD~1 app/Services/Notification/OptimizedCompetitionNotificationService.php

# Run tests
php artisan test
```

## Best Practices

### 1. Route Constants
Consider creating route constants to centralize route management:

```php
// app/Constants/Routes.php
class Routes
{
    const ADMIN_COMPETITION_EDIT = 'admin.competitions.edit';
    const ADMIN_LEVEL_EDIT = 'admin.competitions.level.edit';
    const COMPETITION_DETAIL = 'competitions.detail';
    const COMPETITION_LEVEL = 'competitions.level';
}
```

### 2. Automated Testing
Create tests that verify route dependencies:

```php
// tests/Feature/RouteDependencyTest.php
public function test_admin_approval_routes_exist()
{
    $this->assertTrue(Route::has('admin.competitions.edit'));
    $this->assertTrue(Route::has('admin.competitions.level.edit'));
}
```

### 3. Documentation Updates
Always update this documentation when routes change.

## Troubleshooting

### Common Issues:

1. **"Route not found" errors:**
   - Check route file for typos
   - Verify route parameters match
   - Clear route cache: `php artisan route:clear`

2. **Broken email links:**
   - Check UserNotifyEmail.php for correct routes
   - Verify parameter encoding (base64 for admin routes)

3. **Admin approval buttons not working:**
   - Check AdminApprovalTypeEnum.php for correct route names
   - Verify route parameters format

4. **Test failures:**
   - Update test files with new route names
   - Check route parameter changes

### Debug Commands:
```bash
# List all routes
php artisan route:list

# Clear route cache
php artisan route:clear

# Check specific route
php artisan route:list --name=admin.competitions.edit
```

---

**Remember:** Route changes can have cascading effects throughout the system. Always follow this guide to ensure all dependencies are properly updated. 