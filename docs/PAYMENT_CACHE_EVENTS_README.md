# Payment Cache Events System

This document explains how to use the event-driven payment cache invalidation system.

## Overview

The system automatically invalidates payment caches when payment operations occur, ensuring data consistency without manual cache management.

## How It Works

1. **PaymentService** fires events when payment operations occur
2. **InvalidatePaymentCacheListener** listens to these events
3. **PaymentCacheManagement** handles the actual cache invalidation

## Event Types

### 1. `invalidateAllUserPaymentCaches`
- **When**: Payment created, approved, rejected, or cancelled
- **Parameters**: `['userId' => $userId]`
- **Action**: Invalidates all caches for a specific user

### 2. `invalidateGetUserTransactions`
- **When**: Transaction data changes
- **Parameters**: `['userId' => $userId]`
- **Action**: Invalidates only transaction caches for a user

### 3. `invalidatePaymentStats`
- **When**: Global payment statistics change
- **Parameters**: `[]`
- **Action**: Invalidates payment statistics caches

### 4. `invalidateGlobalPaymentCaches`
- **When**: System-wide payment changes occur
- **Parameters**: `[]`
- **Action**: Flushes all payment-related caches

## Usage Examples

### Firing Events Manually

```php
use App\Events\Payment\PaymentCacheInvalidationEvent;

// Invalidate user caches
PaymentCacheInvalidationEvent::dispatch(
    'invalidateAllUserPaymentCaches',
    ['userId' => 123]
);

// Invalidate global caches
PaymentCacheInvalidationEvent::dispatch(
    'invalidateGlobalPaymentCaches',
    []
);
```

### Adding New Event Types

1. **Add the case in the listener**:
```php
case 'invalidateNewCacheType':
    $this->handleNewCacheType($parameters);
    break;
```

2. **Add the handler method**:
```php
private function handleNewCacheType(array $parameters): void
{
    // Your cache invalidation logic here
    $this->cacheManagement->yourNewMethod($parameters);
}
```

3. **Fire the event**:
```php
PaymentCacheInvalidationEvent::dispatch(
    'invalidateNewCacheType',
    ['param1' => 'value1']
);
```

## Automatic Event Firing

The following methods automatically fire cache invalidation events:

- `createPayment()` - Fires `invalidateAllUserPaymentCaches`
- `approvePayment()` - Fires `invalidateAllUserPaymentCaches`
- `rejectPayment()` - Fires `invalidateAllUserPaymentCaches`
- `cancelPayment()` - Fires `invalidateAllUserPaymentCaches`

## Benefits

1. **Automatic**: No need to manually invalidate caches
2. **Scalable**: Easy to add new cache invalidation types
3. **Consistent**: All payment operations follow the same pattern
4. **Maintainable**: Centralized cache invalidation logic
5. **Queued**: Events are processed asynchronously for better performance

## Monitoring

The system logs all cache invalidation operations. Check your logs for:
- Cache invalidation events
- Processing results
- Any errors that occur

## Future Enhancements

- Add more granular cache invalidation types
- Implement cache warming strategies
- Add cache hit/miss metrics
- Create admin interface for cache management 