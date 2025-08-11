# Payment System Implementation Plan

## Overview
This document outlines the implementation plan for the payment system that allows users to purchase coins for AI services (Global Questions and AI Auditing).

## System Architecture

### User Types & Coin Usage
- **Regular Users**: Purchase coins → Use for Global AI Questions
- **Admins (Super Admin + Owner)**: Purchase coins → Use for AI Auditing in competitions
- **Accountant**: Financial oversight and payment approval
- **Other Admins**: No coin purchasing (future consideration)

### Payment Flow
1. User/Admin requests payment with amount
2. User/Admin uploads payment proof
3. Accountant reviews and approves/rejects
4. Coins automatically credited to user account
5. User can use coins for AI services

## Implementation Steps

### ✅ Step 1: Accountant Role & Permissions
**Status**: ✅ COMPLETED
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- ✅ Create accountant role in database
- ✅ Define accountant permissions
- ✅ Update RoleSeeder.php

#### Database Changes:
- ✅ Updated RoleSeeder.php with accountant role and permissions

#### Files Created/Modified:
- ✅ `database/seeders/RoleSeeder.php` - Added accountant role and payment permissions
- ✅ `lang/en/permissions.php` & `lang/ar/permissions.php` - Added payment permission translations

#### Accountant Permissions:
- ✅ `view payment` - Read-only access to payment transactions
- ✅ `manage payment` - Full control (approve, reject, cancel payments)
- ✅ `export payment` - Export transaction data
- ✅ `view payment_audit` - View audit logs
- ✅ `manage coin_pricing` - Manage coin pricing
- ✅ `create payment_offer` - Create special offers

#### Accountant Restrictions:
- ❌ Create competitions
- ❌ Manage users
- ❌ Access competition data
- ❌ Modify system settings

---

### ✅ Step 2: Payment Database Schema
**Status**: ✅ COMPLETED
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- ✅ Create payment_transactions table
- ✅ Create coin_balances table
- ✅ Create payment_audit_logs table
- ✅ Add UUID field to payment_transactions

#### Database Tables:
```sql
-- payment_transactions table
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL, -- Public-facing transaction ID
    
    -- User making the payment (morph)
    payable_id BIGINT UNSIGNED NOT NULL,
    payable_type VARCHAR(255) NOT NULL, -- 'App\Models\User' or 'App\Models\Admin\Admin'
    
    -- Payment approval
    approver_admin_id BIGINT UNSIGNED NULL, -- Accountant who approved
    approved_at TIMESTAMP NULL,
    
    -- Payment details
    amount DECIMAL(10,2) NOT NULL, -- Money amount (DZD)
    coins_credited INTEGER NOT NULL, -- Coins given
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money') NOT NULL,
    
    -- Proof and status
    proof_image_path VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    
    -- Accountant observation (simple text field)
    accountant_observation TEXT NULL, -- Why approved/rejected/cancelled
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes (only custom indexes, not foreign keys)
    INDEX idx_payable (payable_id, payable_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    -- Foreign keys (indexes created automatically)
    FOREIGN KEY (approver_admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- coin_balances table
CREATE TABLE coin_balances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    balanceable_id BIGINT UNSIGNED NOT NULL,
    balanceable_type VARCHAR(255) NOT NULL, -- 'App\Models\User' or 'App\Models\Admin\Admin'
    balance INTEGER DEFAULT 0,
    total_earned INTEGER DEFAULT 0,
    total_spent INTEGER DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes (only custom indexes, not unique constraints)
    INDEX idx_balanceable (balanceable_id, balanceable_type),
    
    -- Unique constraint (index created automatically)
    UNIQUE KEY unique_balanceable (balanceable_id, balanceable_type)
);

-- payment_audit_logs table
CREATE TABLE payment_audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_transaction_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL, -- 'created', 'approved', 'rejected', 'modified'
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys (indexes created automatically)
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
```

#### Files Created/Modified:
- ✅ `database/migrations/2024_12_19_000002_create_payment_transactions_table.php`
- ✅ `database/migrations/2024_12_19_000003_create_coin_balances_table.php`
- ✅ `database/migrations/2024_12_19_000004_create_payment_audit_logs_table.php`
- ✅ `app/Models/Payment/PaymentTransaction.php` - Central payment entity with UUID auto-generation
- ✅ `app/Models/Payment/CoinBalance.php` - Coin balance management
- ✅ `app/Models/Payment/PaymentAuditLog.php` - Audit log model
- ✅ `app/Services/Payment/PaymentService.php` - Core business logic with TransactionManagerInterface
- ✅ `app/Http/Controllers/Payment/PaymentTransactionController.php` - User-facing operations
- ✅ `app/Models/User.php` - Added polymorphic payment relationships
- ✅ `app/Models/Admin/Admin.php` - Added polymorphic payment relationships
- ✅ `app/Enums/PaymentStatusEnum.php` - Payment status definitions
- ✅ `app/Enums/PaymentTypeEnum.php` - Payment type definitions
- ✅ `lang/en/payment.php` & `lang/ar/payment.php` - Payment translations
- ✅ `database/factories/Payment/PaymentTransactionFactory.php` - Test data factory
- ✅ `database/factories/Payment/CoinBalanceFactory.php` - Coin balance factory
- ✅ `database/factories/Payment/PaymentAuditLogFactory.php` - Audit log factory
- ✅ `database/seeders/PaymentSeeder.php` - Comprehensive test data with cleanup

#### Important Note:
**payment.php in lang is the central entity for all payment-related data. Each payment entity (transactions, reviews, pricing, offers) will have its own key-value pairs stored in the payment_transactions table for easy access and management.**

---

### Step 3: Payment Cleanup System
**Status**: Planned
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- [ ] Create payment cleanup command
- [ ] Implement scheduled cleanup job
- [ ] Add secure file storage configuration
- [ ] Create admin cleanup interface
- [ ] Add backup system for approved payments
- [ ] Add review protection logic to cleanup
- [ ] Test cleanup functionality

#### Cleanup Rules:
```php
const CLEANUP_RULES = [
    'approved' => [
        'keep_days' => 365,    // Keep approved payments for 1 year
        'cleanup_images' => 30  // Delete proof images after 30 days
    ],
    'rejected' => [
        'keep_days' => 30,     // Keep rejected payments for 30 days
        'cleanup_images' => true // Delete proof images after 30 days
    ],
    'cancelled' => [
        'keep_days' => 7,      // Keep cancelled payments for 7 days
        'cleanup_images' => true // Delete proof images after 7 day
    ],
    'pending' => [
        'keep_days' => null,   // NEVER auto-clean pending payments
        'cleanup_images' => false // Keep proof images for pending payments
    ]
];

// Additional cleanup protection rules
const CLEANUP_PROTECTION_RULES = [
    'pending_review' => [
        'keep_payment' => true,    // Never clean payments with pending reviews
        'keep_images' => true,     // Keep proof images for pending reviews
        'reason' => 'Payment has pending review request'
    ]
];
```

#### Files to Create/Modify:
- `app/Console/Commands.php` (add cleanup method)
- `app/Services/Payment/PaymentCleanupService.php`
- `app/Http/Controllers/Payment/Admin/PaymentCleanupController.php`
- `app/Listeners/CleanupPaymentProof.php`
- `config/filesystems.php` (add payment_proofs disk)
- `routes/console.php` (add scheduled cleanup)
- `resources/views/payment/admin/cleanup/index.blade.php`

#### Security Features:
- Secure file storage with private visibility
- IP-based access control for proof images
- Backup system before deletion
- Complete audit trail for cleanup actions
- Admin-only access to cleanup interface

#### Scheduled Cleanup:
- **Daily cleanup**: 2 AM automatic cleanup
- **Weekly backup**: Payment proofs backup
- **Manual cleanup**: Admin interface for immediate cleanup

---

### Step 4: Payment Review System
**Status**: 🚧 IN PROGRESS
**Priority**: Medium
**Estimated Time**: 2 days

#### Tasks:
- [x] Create payment_review_requests table
- [x] Implement review request functionality (uses transaction_id)
- [x] Create accountant review interface (PowerGrid table + modals)
- [ ] Add review notifications
- [x] Implement review approval/rejection logic
- [x] Test review workflow (basic)

#### Database Tables:
```sql
-- payment_review_requests table
CREATE TABLE payment_review_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_transaction_id BIGINT UNSIGNED NOT NULL,
    request_reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by_admin_id BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    review_observation TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes (only custom indexes, not foreign keys)
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    -- Foreign keys (indexes created automatically)
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

> Note: We intentionally did not add any new columns to `payment_transactions` (no `has_review_request` / `review_request_id`).

#### Files Created/Modified:
- ✅ `database/migrations/2025_01_01_000001_create_payment_review_requests_table.php`
- ✅ `app/Models/Payment/PaymentReviewRequest.php`
- ✅ `app/Services/Payment/PaymentReviewService.php`
- ✅ `app/Http/Controllers/Payment/PaymentTransactionController.php` (added `orderReview` action)
- ✅ `app/Http/Controllers/Payment/Admin/ReviewManagementController.php` (index/approve/reject)
- ✅ `app/Http/Requests/Payment/OrderReviewRequest.php`
- ✅ `app/Http/Requests/Payment/ApproveReviewRequest.php`
- ✅ `app/Http/Requests/Payment/RejectReviewRequest.php`
- ✅ `app/Livewire/PaymentReviewRequestTable.php` (PowerGrid table with detail rows & actions)
- ✅ `resources/views/components/tables/payment/review-detail-row.blade.php`
- ✅ `resources/views/pages/admin/payment/reviews.blade.php` (admin list with modals)
- ✅ `resources/views/pages/payment/show.blade.php` (Order Review button, modal, review info block)
- ✅ `routes/web.php` (POST `payment/reviews/order`)
- ✅ `routes/admin.php` (payment reviews routes group)
- ✅ `resources/views/layouts/admin/sidebar.blade.php` (Payment > Reviews link)
- ✅ `lang/en/payment.php` & `lang/ar/payment.php` (review labels, actions; added `messages.already_exists`)
- ✅ `lang/en/links.php` & `lang/ar/links.php` (sidebar link labels for Reviews)

#### Review Features (current):
- User/Admin can order a review from the transaction detail page when status is `rejected` or `cancelled` and no existing review.
- Requests validated with `transaction_id` and reason; ownership enforced and status restricted.
- Accountant sees a reviews list (PowerGrid) with essential columns and detail rows.
- Actions: Approve (updates review to approved and delegates to `PaymentService->approvePayment` to credit coins/update transaction) and Reject (marks review rejected with observation).

#### Business Rules Enforced:
- Only owner of the transaction can place a review request.
- Only `rejected`/`cancelled` transactions are eligible for review.
- No schema changes to `payment_transactions` as per decision.
- All writes wrapped with `TransactionManagerInterface`; UI feedback via `FlasherInterface`.
- Timestamps/logic use UTC.
#### Review Rules:
- ✅ Only rejected/cancelled payments can be reviewed
- ✅ Only payment owner can request review
- ✅ 7-day deadline to request review
- ✅ 48-hour deadline for accountant response
- ✅ Auto-reject if no response within 48 hours
---

### Step 5: Dynamic Coin Pricing System
**Status**: ✅ COMPLETED
**Priority**: Medium
**Estimated Time**: 2 days

#### Tasks:
- ✅ Create coin_pricing table
- ✅ Create coin_offers table
- ✅ Implement dynamic pricing service
- ✅ Create pricing management interface
- ✅ Add offer creation functionality
- ✅ Test pricing calculations

#### Database Tables:
```sql
-- coin_pricing table
CREATE TABLE coin_pricing (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL, -- Short, unique name for pricing rule
    display_name VARCHAR(255) NULL, -- Optional longer description
    user_type ENUM('user', 'admin', 'both') NOT NULL, -- Updated to include 'both'
    base_amount DECIMAL(10,2) NOT NULL, -- Money amount (DZD)
    base_coins INTEGER NOT NULL, -- Coins given
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_type (user_type),
    INDEX idx_is_active (is_active),
    INDEX idx_name (name), -- Index for name lookups
    
    -- Foreign keys
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE RESTRICT
);

-- coin_offers table
CREATE TABLE coin_offers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    coin_pricing_id BIGINT UNSIGNED NOT NULL, -- Link to coin pricing rule
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    discount_percentage INTEGER NOT NULL CHECK (discount_percentage BETWEEN 5 AND 90), -- 5-90% range
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    expired BOOLEAN DEFAULT FALSE, -- Whether this offer is expired
    created_by_admin_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_coin_pricing_id (coin_pricing_id),
    INDEX idx_expired (expired),
    INDEX idx_date_range (start_date, end_date),
    
    -- Unique constraint: Only one active offer per pricing rule
    UNIQUE KEY unique_active_offer_per_pricing (coin_pricing_id, expired),
    
    -- Foreign keys
    FOREIGN KEY (coin_pricing_id) REFERENCES coin_pricing(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE RESTRICT
);
```

#### Enhanced User Type System:
- **User**: Pricing applies only to regular users
- **Admin**: Pricing applies only to admin users  
- **Both**: Pricing applies to both users and admins (new feature)

#### Pricing Management Permissions:
- **Owner**: Set base pricing, create any offers, override pricing
- **Accountant**: Create temporary offers, adjust pricing within limits, view history

#### Files Created/Modified:
- ✅ `database/migrations/2024_12_19_000005_create_coin_pricing_table.php` - Base pricing rules
- ✅ `database/migrations/2024_12_19_000006_create_coin_offers_table.php` - Enhanced offers system with pricing relationships
- ✅ `app/Models/Payment/CoinPricing.php` - Pricing model with offer relationships and helper methods
- ✅ `app/Models/Payment/CoinOffer.php` - Enhanced offer model with pricing relationship and 5-90% validation
- ✅ `app/Services/Payment/CoinPricingService.php` - Core pricing business logic
- ✅ `app/Services/Payment/CoinOfferService.php` - Core offer business logic
- ✅ `app/Http/Controllers/Payment/Admin/CoinPricingController.php` - Pricing management
- ✅ `app/Http/Controllers/Payment/Admin/CoinOfferController.php` - Offer management (index, store, destroy, activate, deactivate)
- ✅ `app/Livewire/CoinPricingTable.php` - PowerGrid table for pricing management
- ✅ `app/Livewire/CoinOfferTable.php` - PowerGrid table for offer management with detail rows
- ✅ `resources/views/pages/admin/payment/pricing.blade.php` - Pricing management interface
- ✅ `resources/views/pages/admin/payment/offers.blade.php` - Offers management interface with modals
- ✅ `resources/views/components/tables/payment/offer-detail-row.blade.php` - Detail row component for offers
- ✅ `app/Http/Requests/Payment/CreateCoinPricingRequest.php` - Form validation
- ✅ `app/Http/Requests/Payment/CreateCoinOfferRequest.php` - Offer form validation
- ✅ `app/Enums/UserTypeEnum.php` - User type definitions with labels and colors
- ✅ `database/factories/CoinPricingFactory.php` - Test data generation
- ✅ `database/factories/CoinOfferFactory.php` - Enhanced offer test data with pricing relationships
- ✅ `database/seeders/PaymentSeeder.php` - Updated with pricing and offers data
- ✅ `lang/en/payment.php` & `lang/ar/payment.php` - Complete translations including offers and detail rows
- ✅ `lang/en/links.php` & `lang/ar/links.php` - Navigation links including offers
- ✅ `lang/en/validation.php` & `lang/ar/validation.php` - Validation attributes
- ✅ `routes/admin.php` - Added coin offers routes with proper middleware

#### Pricing Features:
- ✅ Dynamic base pricing for users and admins
- ✅ Special offers linked to specific pricing rules (5-90% discount range)
- ✅ Automatic coin calculation with offers (implemented in service)
- ✅ Pricing history and audit trail
- ✅ Offer management interface (fully implemented)
- ✅ PowerGrid table with filtering and actions
- ✅ Detail row components for comprehensive information display
- ✅ Form validation with translation support
- ✅ Test data generation and seeding

#### Enhanced Offer System:
- **Linked to Pricing**: Each offer is linked to a specific coin pricing rule
- **Discount Range**: 5-90% discount percentage with validation
- **Unique Constraint**: Only one active offer per pricing rule
- **Status Management**: Simple expired/active status with date validation
- **Performance**: Indexed fields for efficient queries

#### Example Pricing Scenarios:
- **Base Pricing**: Users (100 DZD = 50 coins), Admins (100 DZD = 100 coins)
- **Special Offers**: Weekend specials (20% extra), new user bonus (25% extra)
- **Time-based Offers**: Limited time promotions with start/end dates
- **Pricing-specific Offers**: Different offers for different pricing tiers

---

### Step 5.2: Enhanced Coin Pricing System with Names and "Both" User Type
**Status**: ✅ COMPLETED
**Priority**: Medium
**Estimated Time**: 1 day

#### Tasks:
- ✅ Add name and display_name fields to coin_pricing table
- ✅ Update user_type enum to include 'both' option
- ✅ Update models with new fields and validation
- ✅ Update services to handle 'both' user type logic
- ✅ Update PowerGrid tables to display names
- ✅ Update forms to include name fields
- ✅ Update translations for new fields
- ✅ Test enhanced functionality
- ✅ Apply consistent PowerGrid pattern with detail rows
- ✅ Fix CoinOffer structure in PaymentSeeder

#### Database Changes:
```sql
-- Add to coin_pricing table
ALTER TABLE coin_pricing 
ADD COLUMN name VARCHAR(100) UNIQUE NOT NULL AFTER id,
ADD COLUMN display_name VARCHAR(255) NULL AFTER name,
MODIFY COLUMN user_type ENUM('user', 'admin', 'both') NOT NULL;

-- Add index for name field
ALTER TABLE coin_pricing ADD INDEX idx_name (name);
```

#### Enhanced Features:
- **Name Field**: Short, unique names for pricing rules (e.g., "Standard User", "Premium Admin")
- **Display Name**: Optional longer descriptions (e.g., "Standard user package with basic features")
- **Both User Type**: Single pricing rule can apply to both users and admins
- **Improved UX**: Better organization and identification of pricing rules
- **Consistent UI Pattern**: PowerGrid tables with detail rows following project standards

#### Files Updated:
- ✅ `database/migrations/2024_12_19_000005_create_coin_pricing_table.php` - Updated with name fields and 'both' user type
- ✅ `app/Models/Payment/CoinPricing.php` - Added name fields and enhanced scope logic
- ✅ `app/Enums/UserTypeEnum.php` - Added 'both' option with label
- ✅ `app/Services/Payment/CoinPricingService.php` - Updated logic for 'both' type
- ✅ `app/Livewire/CoinPricingTable.php` - Applied consistent PowerGrid pattern with detail rows
- ✅ `app/Http/Requests/Payment/CreateCoinPricingRequest.php` - Added name validation
- ✅ `resources/views/pages/admin/payment/pricing.blade.php` - Updated form fields
- ✅ `resources/views/components/tables/payment/pricing-detail-row.blade.php` - Created detail row component
- ✅ `lang/en/payment.php` & `lang/ar/payment.php` - Added name field translations
- ✅ `database/factories/Payment/CoinPricingFactory.php` - Updated with name generation
- ✅ `database/seeders/PaymentSeeder.php` - Updated with named pricing rules and fixed CoinOffer structure

#### Business Logic Updates:
```php
// Enhanced service logic for 'both' user type
public function getApplicablePricing($userType): ?CoinPricing
{
    return CoinPricing::active()
        ->where(function($query) use ($userType) {
            $query->where('user_type', $userType)
                  ->orWhere('user_type', 'both');
        })
        ->orderBy('user_type', 'desc') // 'both' comes after specific types
        ->first();
}
```

#### UI Enhancements:
- **PowerGrid Table**: Clean main table with 5 essential columns (Name, User Type, Rate, Status, Actions)
- **Detail Rows**: Comprehensive information display including display name, coins per DZD, created by, timestamps, and active offers
- **Forms**: Add name and display name input fields with proper validation
- **Validation**: Ensure unique names and proper formatting
- **Consistent Pattern**: Follows same structure as PaymentTransactionTable and CoinOfferTable

#### Translation Updates:
- **Name Labels**: "Pricing Name", "Display Name"
- **User Type Labels**: "User Only", "Admin Only", "User & Admin"
- **Validation Messages**: Name uniqueness and format validation
- **Help Text**: Guidance for naming conventions
- **Active Offers**: Added translations for offer display in detail rows

#### Fixed Issues:
- **CoinOffer Structure**: Corrected PaymentSeeder to use proper `coin_pricing_id` relationships
- **PowerGrid Pattern**: Applied consistent detail row pattern across all payment tables
- **Performance**: Optimized queries with proper eager loading
- **UX Consistency**: Unified table behavior and styling across payment system

---

### Step 5.3: Enhanced Image Processing System with Security
**Status**: ✅ COMPLETED
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- ✅ Create comprehensive image configuration system
- ✅ Implement enhanced ImageManipulation trait with security features
- ✅ Add driver detection (Imagick preferred, GD fallback)
- ✅ Implement memory management and validation
- ✅ Create storage link for public images
- ✅ Configure private vs public image storage
- ✅ Integrate image processing with payment system
- ✅ Add transaction-specific image optimization
- ✅ Remove thumbnail generation for transaction proofs
- ✅ Fix CoinPricing query builder issue in PaymentService

#### Database Changes:
```sql
-- Storage link created for public image access
-- public/storage → storage/app/public (symbolic link)
```

#### Enhanced Features:
- **Driver Detection**: Automatically uses Imagick if available, falls back to GD
- **Memory Management**: Checks available memory before processing large images
- **Security**: Private storage for sensitive images (transaction proofs)
- **Optimization**: Automatic resizing, format conversion, and quality compression
- **Configuration-Driven**: All settings managed via config/image.php
- **Error Handling**: Proper exception handling with rethrowing for calling code

#### Files Created/Modified:
- ✅ `config/image.php` - Comprehensive image configuration system
- ✅ `app/Traits/ImageManipulation.php` - Enhanced trait with security and optimization
- ✅ `app/Services/Payment/PaymentService.php` - Integrated image processing with config-based settings
- ✅ `storage/app/public/` - Public image storage directory
- ✅ `storage/app/transactions/` - Private transaction proof storage

#### Image Processing Features:
- **Format Conversion**: All images converted to JPEG for consistency
- **Quality Compression**: 85% quality for transaction proofs (balance of quality/size)
- **Size Optimization**: Max 1200x1200px for transaction proofs
- **Private Storage**: Transaction proofs stored securely on `local` disk
- **Memory Safety**: Prevents processing images that exceed memory limits
- **Collision Detection**: Unique filename generation with storage checking

#### Configuration System:
```php
// config/image.php
'private_types' => [
    'transaction' => [
        'disk' => 'local',
        'path' => 'transactions',
        'max_width' => 1200,
        'max_height' => 1200,
        'quality' => 85,
    ],
],
'public_types' => [
    'competition' => ['disk' => 'public', 'path' => 'competitions'],
    'system' => ['disk' => 'public', 'path' => 'system'],
],
```

#### Security Implementation:
- **Private Images**: Transaction proofs stored in `storage/app/transactions/` (not publicly accessible)
- **Public Images**: Competition/system images stored in `storage/app/public/` (accessible via /storage/)
- **Authentication Required**: Private images require authenticated routes to access
- **Storage Link**: Created for public image access while maintaining security

#### Performance Optimizations:
- **File Size Reduction**: ~80% smaller files through optimization
- **Memory Management**: Prevents server crashes from large image processing
- **Driver Optimization**: Uses best available image processing driver
- **No Thumbnails**: Simplified storage for transaction proofs (single optimized file)

#### Bug Fixes:
- **CoinPricing Query Issue**: Fixed `findOrFail()->with()` returning query builder instead of model
- **Exception Handling**: Removed registerLog calls, properly rethrow exceptions
- **Configuration Integration**: Replaced hardcoded values with config-based settings

#### Technical Improvements:
- **Error Propagation**: Exceptions properly bubble up to calling code
- **Configuration Centralization**: All image settings in one place
- **Environment Flexibility**: Settings can be changed via .env variables
- **Type Safety**: Proper configuration structure with type definitions

#### File Structure:
```
storage/
├── app/
│   ├── public/          ← Public images (accessible via /storage/)
│   │   ├── competitions/
│   │   └── system/
│   └── transactions/    ← Private transaction proofs (secure)
public/
└── storage → storage/app/public  ← Symbolic link for public access
```

#### Benefits:
- **Security**: Sensitive transaction proofs are private and secure
- **Performance**: Optimized images load faster and use less bandwidth
- **Flexibility**: Easy to configure different settings for different image types
- **Maintainability**: Centralized configuration and consistent processing
- **Scalability**: Memory management prevents server issues with large images

---

### Step 5.1: Auto-Expire Offers Command
**Status**: Planned
**Priority**: Medium
**Estimated Time**: 0.5 day

#### Tasks:
- [ ] Create auto-expire offers command
- [ ] Implement scheduled job for daily execution
- [ ] Add command to console routes
- [ ] Test auto-expire functionality
- [ ] Add logging for expired offers

#### Command Features:
```php
// Auto-expire command logic
public function handle(): void
{
    $expiredOffers = CoinOffer::where('expired', false)
        ->where('end_date', '<', now())
        ->get();
    
    foreach ($expiredOffers as $offer) {
        $offer->update(['expired' => true]);
        $this->logExpiredOffer($offer);
    }
    
    $this->info("Expired {$expiredOffers->count()} offers");
}
```

#### Scheduled Execution:
- **Daily at 1 AM**: Automatic offer expiration check
- **Manual execution**: `php artisan payment:expire-offers`
- **Logging**: Track all expired offers for audit

#### Files to Create/Modify:
- `app/Console/Commands/ExpireCoinOffersCommand.php` - Auto-expire command
- `routes/console.php` - Add scheduled command
- `app/Jobs/ExpireOffersJob.php` - Background job (optional)
- `database/migrations/create_offer_expiration_logs_table.php` - Logging table (optional)

#### Business Rules:
- ✅ Only expire offers where `expired = false` AND `end_date < now()`
- ✅ Update `expired` field to `true`
- ✅ Log expiration for audit trail
- ✅ Send notification to admin (optional)
- ✅ Handle bulk operations efficiently

#### Example Usage:
```bash
# Manual execution
php artisan payment:expire-offers

# Check scheduled tasks
php artisan schedule:list

# Test command
php artisan payment:expire-offers --dry-run
```

---

## ✅ **Additional Completed Components**

### **UI/UX Implementation**
**Status**: ✅ COMPLETED

#### Components Created:
- ✅ `app/Livewire/PaymentTransactionTable.php` - PowerGrid table with detail rows
- ✅ `resources/views/pages/admin/payment/transactions.blade.php` - Admin dashboard
- ✅ `resources/views/components/payment/transaction-detail-row.blade.php` - Detail row component
- ✅ `resources/views/layouts/admin/sidebar.blade.php` - Added payment navigation
- ✅ `lang/en/links.php` & `lang/ar/links.php` - Sidebar link translations

#### Features Implemented:
- ✅ PowerGrid table with essential columns (UUID, Payer, Amount, Status, Created At, Actions)
- ✅ Detail rows for comprehensive transaction information
- ✅ Action modals for Approve, Reject, Cancel operations
- ✅ Stats cards showing pending, approved, rejected, cancelled counts
- ✅ Responsive design with proper localization

### **Dedicated Payment Layout System**
**Status**: ✅ COMPLETED

#### Layout Structure Created:
- ✅ `resources/views/layouts/payment/master.blade.php` - Payment master layout
- ✅ `resources/views/layouts/payment/main-header.blade.php` - Clean payment navigation
- ✅ `resources/views/layouts/payment/head.blade.php` - CSS, JS, PowerGrid integration
- ✅ `resources/views/layouts/payment/footer-scripts.blade.php` - JavaScript functionality

#### Payment Pages Created:
- ✅ `resources/views/pages/payment/transactions.blade.php` - Shared transactions page
- ✅ `resources/views/pages/payment/create.blade.php` - Modern payment form with Alpine.js
- ✅ `resources/views/pages/payment/show.blade.php` - Transaction detail page

#### Navigation Integration:
- ✅ **Admin Sidebar**: Separated payment links for regular admins vs accountants
- ✅ **User Header**: "Buy Coins" link in main navigation
- ✅ **Role-Based Access**: Different navigation based on admin role

#### Features Implemented:
- ✅ **Clean UI**: Modern, responsive design with Tailwind CSS
- ✅ **Alpine.js Integration**: Interactive payment method selection with focus management
- ✅ **Form Components**: Uses standard Blade components (x-input-label, x-text-input, x-input-error)
- ✅ **Drag & Drop**: File upload with preview functionality
- ✅ **Status Widgets**: Color-coded payment status indicators
- ✅ **Audit Trail**: Complete transaction history display
- ✅ **Balance Display**: Current coin balance and statistics
- ✅ **Multi-language**: Complete English and Arabic translations

### **User-Specific Payment Table**
**Status**: ✅ COMPLETED

#### Components Created:
- ✅ `app/Livewire/PaymentUserTransactionTable.php` - User-specific transaction table

#### Features Implemented:
- ✅ **User-Specific Data**: Shows only authenticated user's transactions
- ✅ **Responsive Columns**: Amount, Coins, Status, Created At, Row Number
- ✅ **Clean Actions**: View button only for transaction details
- ✅ **Status Filtering**: Filter by payment status
- ✅ **PowerGrid Integration**: Uses TailwindStriped theme

### **User Transaction Cards with Server-Side Filtering**
**Status**: ✅ COMPLETED

#### Components Updated:
- ✅ `resources/views/pages/payment/transactions.blade.php` - Converted to card-based layout with server-side filtering
- ✅ `app/Services/Payment/PaymentService.php` - Enhanced with filtering methods
- ✅ `app/Http/Controllers/Payment/PaymentTransactionController.php` - Updated to use service layer

#### Features Implemented:
- ✅ **Card-Based Layout**: Replaced PowerGrid table with responsive collapsible cards
- ✅ **Server-Side Filtering**: Search and status filters processed on server for better performance
- ✅ **Form-Based Filters**: GET form submission maintains filter state in URL
- ✅ **Mobile-First Design**: Collapsible filter container with mobile toggle
- ✅ **Filter Persistence**: Filter state maintained across pagination
- ✅ **Clean Architecture**: Controller delegates filtering to PaymentService
- ✅ **Pagination Support**: Laravel pagination with filter state preservation
- ✅ **Responsive Stats**: Real-time status counts from database

#### Filter Implementation:
```php
// PaymentService methods
public function getTransactionsForUser(array $filters = [], int $perPage = 10)
public function getUserTransactionStatusCounts(): array

// Controller usage
$filters = ['search' => $request->get('search'), 'status' => $request->get('status')];
$transactions = $this->paymentService->getTransactionsForUser($filters, 10);
$statusCounts = $this->paymentService->getUserTransactionStatusCounts();
```

#### Filter Features:
- **Search Filter**: Searches across UUID, amount, and coins_credited fields
- **Status Filter**: Filter by pending, approved, rejected, or cancelled status
- **URL State**: Filter parameters preserved in URL for sharing/bookmarking
- **Mobile Optimization**: Filters collapse on mobile with toggle button
- **Real-Time Updates**: Stats cards show accurate counts regardless of filters

#### Architecture Benefits:
- ✅ **Separation of Concerns**: Business logic in service, HTTP logic in controller
- ✅ **Reusability**: Service methods can be used by other parts of application
- ✅ **Testability**: Service methods can be unit tested independently
- ✅ **Performance**: Server-side filtering handles large datasets efficiently
- ✅ **Scalability**: Easy to add new filters without changing architecture

### **Transaction Detail Access**
**Status**: ✅ COMPLETED

#### Updates:
- ✅ Transaction detail route: `GET /payment/transactions/{paymentTransaction}` named `payment.transactions.show` (route-model binding by ID)
- ✅ Controller method: `PaymentTransactionController@show(PaymentTransaction $paymentTransaction)` returns the detail view
- ✅ Proof image: Detail view uses secure proof route `transactions.proof` instead of public storage
- 🔜 Ownership check: Controller-level enforcement comparing `payable_id` and `payable_type` to the authenticated principal (user/admin) will be added in a follow-up

### **Form Requests & Validation**
**Status**: ✅ COMPLETED

#### Files Created:
- ✅ `app/Http/Requests/Payment/ApprovePaymentRequest.php`
- ✅ `app/Http/Requests/Payment/RejectPaymentRequest.php`
- ✅ `app/Http/Requests/Payment/CancelPaymentRequest.php`

#### Features Implemented:
- ✅ Clean input validation (no business logic in form requests)
- ✅ Authorization checks (`manage payment` permission)
- ✅ Custom validation messages with localization
- ✅ Helper methods for retrieving payment transactions

### **Routes & Controllers**
**Status**: ✅ COMPLETED

#### Files Created/Modified:
- ✅ `routes/web.php` - Added payment routes with either auth middleware
- ✅ `routes/admin.php` - Added admin payment routes
- ✅ `app/Http/Controllers/Payment/PaymentTransactionController.php` - Updated for new layout

#### Features Implemented:
- ✅ Role-protected routes (`owner|accountant`)
- ✅ Web-based responses (redirects, views)
- ✅ Proper separation of concerns (validation vs business logic)
- ✅ Integration with PaymentService for business logic

### **Testing Infrastructure**
**Status**: ✅ COMPLETED

#### Files Created:
- ✅ `database/factories/Payment/PaymentTransactionFactory.php`
- ✅ `database/factories/Payment/CoinBalanceFactory.php`
- ✅ `database/factories/Payment/PaymentAuditLogFactory.php`
- ✅ `database/seeders/PaymentSeeder.php`

#### Features Implemented:
- ✅ Comprehensive test data generation
- ✅ Cleanup functionality with proper foreign key handling
- ✅ Various payment states and scenarios
- ✅ Error handling for storage operations

### **Translation System**
**Status**: ✅ COMPLETED

#### Files Created/Modified:
- ✅ `lang/en/payment.php` - Complete English translations
- ✅ `lang/ar/payment.php` - Complete Arabic translations
- ✅ `lang/en/links.php` - Payment navigation links
- ✅ `lang/ar/links.php` - Arabic payment navigation links

#### Translation Categories:
- ✅ **Layout & Navigation**: Payment system title, navigation links
- ✅ **Form Elements**: Labels, placeholders, validation messages
- ✅ **Status & Actions**: Payment statuses, action buttons
- ✅ **Information Cards**: Help text, usage instructions
- ✅ **Error Messages**: Validation and system error messages

---

### **Admin: Payment Audit Logs**
**Status**: ✅ COMPLETED (index UI) / ❌ Deletion (disabled) / 📋 Archive (planned)

#### Features:
- ✅ Admin page listing audit logs using PowerGrid with detail rows
- ✅ Detail row shows transaction UUID, payer, admin, action, timestamps, IP, user agent, old/new values (pretty JSON)
- ✅ Action: Delete button opens modal (disabled server-side; flashes warning)
- ✅ Routes:
  - GET `/admin/payment/audit-logs` → `PaymentAuditController@index` (permission: `view payment_audit`)
  - POST `/admin/payment/audit-logs/delete` → `PaymentAuditController@delete` (role: `owner`; currently blocked)
- ✅ Translations: `lang/en/payment.php` and `lang/ar/payment.php` under `audit` keys
- ✅ Navigation: Admin sidebar Payment menu includes an "Audit Logs" link
- ❌ Deletion: Intentionally disabled to keep audit logs immutable
- 📋 Archive: Planned (see below)

#### Files Created/Modified:
- ✅ `app/Livewire/PaymentAuditLogTable.php`
- ✅ `resources/views/components/tables/payment/audit-log-detail-row.blade.php`
- ✅ `resources/views/pages/admin/payment/audit_logs.blade.php`
- ✅ `app/Http/Controllers/Payment/Admin/PaymentAuditController.php`
- ✅ `routes/admin.php` (audit routes)
- ✅ `resources/views/layouts/admin/sidebar.blade.php` (added Audit Logs link)
- ✅ `lang/en/payment.php` & `lang/ar/payment.php` (audit translations)
- ✅ `lang/en/links.php` & `lang/ar/links.php` (sidebar link labels)

### **Planned: Audit Logs Archiving**
**Status**: Planned

#### Goals:
- Preserve audit integrity; avoid deletion
- Enable archiving of logs older than N days (default 365) to a separate table or storage

#### Approach:
- Create `payment_audit_logs_archive` table mirroring `payment_audit_logs`
- Command + schedule (daily) to move eligible records
- Optional on-demand archive action for owner
- Indexes on `created_at` for range moves
- UI: Replace Delete button with Archive when implemented

#### Configuration:
```php
'audit' => [
    'archive_after_days' => 365,
]
```

#### Files to Create/Modify:
- `app/Console/Commands/ArchivePaymentAuditLogsCommand.php`
- `routes/console.php` (schedule daily)
- `database/migrations/create_payment_audit_logs_archive_table.php`
- `app/Services/Payment/PaymentAuditArchiveService.php` (optional)
- `app/Http/Controllers/Payment/Admin/PaymentAuditController.php` (archive action)
- `resources/views/pages/admin/payment/audit_logs.blade.php` (archive modal)

## 📊 **Implementation Summary**

### **✅ Completed Steps:**
1. ✅ **Step 1: Accountant Role & Permissions** - Fully implemented
2. ✅ **Step 2: Payment Database Schema** - Fully implemented with UUID support
3. ✅ **Dedicated Payment Layout System** - Complete layout with navigation
4. ✅ **User-Specific Payment Table** - PowerGrid table for user transactions
5. ✅ **User Transaction Cards with Server-Side Filtering** - Card-based layout with server-side filtering
6. ✅ **Modern Payment Form** - Alpine.js integration with form components
7. ✅ **Form Requests** - Clean validation with proper separation
8. ✅ **Controllers & Routes** - Web-based responses with role protection
9. ✅ **Testing Infrastructure** - Factories and seeders with cleanup
10. ✅ **Translation System** - Complete English and Arabic support
11. ✅ **Step 5: Dynamic Coin Pricing System** - Fully completed (pricing + offers management with detail rows)
12. ✅ **Step 5.2: Enhanced Coin Pricing System with Names and "Both" User Type** - Fully completed (name fields, display names, 'both' user type, consistent UI pattern)
13. ✅ **Step 5.3: Enhanced Image Processing System with Security** - Fully completed (secure image processing, configuration system, memory management)
14. 📋 **Step 5.1: Auto-Expire Offers Command** - Planned (scheduled command for offer expiration)

### **🔄 Next Steps:**
1. **Step 5.1: Auto-Expire Offers Command** - Scheduled command to automatically expire offers
2. **Step 3: Payment Cleanup System** - Automated cleanup commands
3. **Step 4: Payment Review System** - Review request functionality

### **🎯 Current Status:**
- **Core Payment System**: ✅ Production Ready
- **Dedicated Payment Layout**: ✅ Fully Functional
- **User Payment Interface**: ✅ Complete with Alpine.js
- **Database Schema**: ✅ Complete with UUIDs and enhanced offer relationships
- **Dynamic Pricing System**: ✅ Fully complete (pricing + offers management with detail rows)
- **Enhanced Offer System**: ✅ Fully implemented with UI and management
- **Enhanced Pricing System**: ✅ Fully complete (names, display names, 'both' user type, consistent UI pattern)
- **Enhanced Image Processing**: ✅ Fully complete (secure processing, configuration system, memory management)
- **UI Pattern Consistency**: ✅ All PowerGrid tables follow same detail row pattern
- **Auto-Expire System**: 📋 Planned (Step 5.1)
- **Security Features**: ✅ Implemented (including secure image storage)
- **Testing Infrastructure**: ✅ Comprehensive
- **Multi-language Support**: ✅ English and Arabic

---

## Notes
- This plan will be updated as we discuss each step
- Each step will be marked as completed when implemented
- Additional steps may be added based on requirements
- Security and testing are prioritized throughout

---

## 📝 **Commit Message Management**

### **Current Commit Message:**

#### **Latest Commit: Payment review system (service, UI, routes), order review in detail page, sidebar link, translations; README update**
```
feat(payment-review): implement review requests (service, UI, routes) and admin management

- db: add payment_review_requests table migration
- model: create PaymentReviewRequest with relations and scopes
- service: PaymentReviewService (request, approve, reject) using transactions + flasher
- user: add orderReview in PaymentTransactionController (transaction_id + reason; ownership + status checks)
- admin: add ReviewManagementController (index, approve, reject)
- requests: add OrderReviewRequest, ApproveReviewRequest, RejectReviewRequest
- routes: POST /payment/reviews/order (web); admin reviews routes (index/approve/reject)
- ui(admin): PaymentReviewRequestTable (PowerGrid) with detail rows and approve/reject actions
- ui(admin): reviews page with modals
- ui(user): show.blade.php – order review button + modal; review info block if exists
- nav: add “Reviews” link under Payment in admin sidebar
- i18n: add review translations (en/ar), sidebar link labels; add messages.already_exists
- docs: update README Step 4 to In Progress with implemented items and rules

Files modified/added:
- database/migrations/2025_01_01_000001_create_payment_review_requests_table.php
- app/Models/Payment/PaymentReviewRequest.php
- app/Services/Payment/PaymentReviewService.php
- app/Http/Controllers/Payment/PaymentTransactionController.php
- app/Http/Controllers/Payment/Admin/ReviewManagementController.php
- app/Http/Requests/Payment/OrderReviewRequest.php
- app/Http/Requests/Payment/ApproveReviewRequest.php
- app/Http/Requests/Payment/RejectReviewRequest.php
- app/Livewire/PaymentReviewRequestTable.php
- resources/views/components/tables/payment/review-detail-row.blade.php
- resources/views/pages/admin/payment/reviews.blade.php
- resources/views/pages/payment/show.blade.php
- resources/views/layouts/admin/sidebar.blade.php
- routes/web.php
- routes/admin.php
- lang/en/payment.php
- lang/ar/payment.php
- lang/en/links.php
- lang/ar/links.php
- docs/PAYMENT_SYSTEM_README.md
```

### **Commit Message Generation Rules:**
1. **When user asks for commit message**: Detect what's new/changed since last commit
2. **Generate new commit message**: Replace the latest commit message with updated content
3. **Follow conventional commit format**: feat:, fix:, refactor:, docs:, etc.
4. **Include specific changes**: List files modified and features added
5. **Maintain chronological order**: Latest commit at the top

### **Example Usage:**
- User: "generate commit message"
- Assistant: Detects new changes (e.g., form request updates)
- Assistant: Replaces "Commit 8" with new commit message reflecting the latest changes 