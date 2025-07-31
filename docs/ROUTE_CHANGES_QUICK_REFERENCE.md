# Route Changes Quick Reference

## 🚨 Critical Files to Update

### 1. Admin Approval System
**File:** `app/Enums/AdminApprovalTypeEnum.php`
```php
// Update these routes if changing admin routes:
self::AUDITOR => ['name' => 'admin.competitions.edit', ...]
self::LEVEL_MANAGER => ['name' => 'admin.competitions.level.edit', ...]
```

### 2. Email Notifications
**File:** `app/Helpers/UserNotifyEmail.php`
```php
// Update these route calls:
route('competitions.detail', ['competition' => $competition])
route('admin.competitions.level.edit', ['id' => base64_encode($level->id)])
route('admin.competitions.edit', ['id' => base64_encode($competition->id)])
```

### 3. Notification Service
**File:** `app/Services/Notification/OptimizedCompetitionNotificationService.php`
```php
// Update these route calls:
return route('competitions.level', $level);
return route('competitions.detail', $competition);
```

## 🔍 Pre-Change Commands

```bash
# Search for route usage
grep -r "route('admin.competitions.edit'" .
grep -r "route('admin.competitions.level.edit'" .
grep -r "route('competitions.detail'" .
grep -r "route('competitions.level'" .

# Check if routes exist
php artisan route:list --name=admin.competitions.edit
php artisan route:list --name=admin.competitions.level.edit
```

## ✅ Post-Change Checklist

- [ ] Updated `AdminApprovalTypeEnum.php`
- [ ] Updated `UserNotifyEmail.php`
- [ ] Updated `OptimizedCompetitionNotificationService.php`
- [ ] Updated all test files
- [ ] Updated view files
- [ ] Tested admin approval buttons
- [ ] Tested email notifications
- [ ] Tested in-app notifications
- [ ] Ran all tests: `php artisan test`

## 🆘 Emergency Rollback

```bash
# If routes break, revert quickly:
git checkout HEAD~1 app/Enums/AdminApprovalTypeEnum.php
git checkout HEAD~1 app/Helpers/UserNotifyEmail.php
git checkout HEAD~1 app/Services/Notification/OptimizedCompetitionNotificationService.php
php artisan test
```

## 📋 Route Naming Patterns

| Route Type | Pattern | Parameter Encoding |
|------------|---------|-------------------|
| Admin Edit | `admin.competitions.edit` | `base64_encode($id)` |
| Level Edit | `admin.competitions.level.edit` | `base64_encode($id)` |
| User Detail | `competitions.detail` | Direct model binding |
| User Level | `competitions.level` | Direct model binding |

## 🎯 Impact Levels

- 🔴 **Critical**: Admin approval system, email notifications
- 🟡 **High**: Notification service, user flows
- 🟢 **Medium**: Tests, views, other components

---

**Remember:** Always check the full documentation at `docs/ROUTE_CHANGES_GUIDE.md` for complete details. 