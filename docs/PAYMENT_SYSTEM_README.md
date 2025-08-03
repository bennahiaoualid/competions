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
**Status**: Planned
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- [ ] Create accountant role in database
- [ ] Define accountant permissions
- [ ] Create accountant seeder

#### Database Changes:
- update RoleSeeder

#### Files to Create/Modify:
- `app/Models/Admin/Admin.php` (add accountant role methods)
- `app/Http/Controllers/Admin/AccountantController.php`

#### Accountant Permissions:
- ✅ Approve all user payments
- ✅ Approve all admin payments (including owner)
- ✅ View all transaction history
- ✅ Manage payment settings
- ✅ Generate financial reports
- ✅ Export transaction data
- ✅ Manage coin pricing
- ✅ View audit logs

#### Accountant Restrictions:
- ❌ Create competitions
- ❌ Manage users
- ❌ Access competition data
- ❌ Modify system settings

---

### Step 2: Payment Database Schema
**Status**: Planned
**Priority**: High
**Estimated Time**: 1 day

#### Tasks:
- [ ] Create payment_transactions table
- [ ] Create coin_balances table
- [ ] Enhance users table with coin fields
- [ ] Create payment_audit_logs table

#### Database Tables:
```sql
-- payment_transactions table
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
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

#### Files to Create/Modify:
- `database/migrations/create_payment_transactions_table.php`
- `database/migrations/create_coin_balances_table.php`
- `database/migrations/create_payment_audit_logs_table.php`
- `app/Models/Payment/PaymentTransaction.php`
- `app/Models/Payment/CoinBalance.php`
- `app/Models/Payment/PaymentAuditLog.php`
- `app/Services/Payment/PaymentService.php`
- `app/Http/Controllers/Payment/PaymentTransactionController.php`
- `app/Models/User.php` (add morph relationship)
- `app/Models/Admin/Admin.php` (add morph relationship)

#### Important Note:
**payments.php in lang will be the central entity for all payment-related data. Each payment entity (transactions, reviews, pricing, offers) will have its own key-value pairs stored in the payment_transactions table for easy access and management.**

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
**Status**: Planned
**Priority**: Medium
**Estimated Time**: 2 days

#### Tasks:
- [ ] Create payment_review_requests table
- [ ] Implement review request functionality
- [ ] Create accountant review interface
- [ ] Add review notifications
- [ ] Implement review approval/rejection logic
- [ ] Test review workflow

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

-- Add to payment_transactions table
has_review_request BOOLEAN DEFAULT FALSE,
review_request_id BIGINT UNSIGNED NULL,
FOREIGN KEY (review_request_id) REFERENCES payment_review_requests(id) ON DELETE SET NULL
```

#### Review Rules:
- ✅ Only rejected/cancelled payments can be reviewed
- ✅ Only payment owner can request review
- ✅ 7-day deadline to request review
- ✅ 48-hour deadline for accountant response
- ✅ Auto-reject if no response within 48 hours

#### Files to Create/Modify:
- `database/migrations/create_payment_review_requests_table.php`
- `database/migrations/add_review_fields_to_payment_transactions.php`
- `app/Models/Payment/PaymentReviewRequest.php`
- `app/Services/Payment/PaymentReviewService.php`
- `app/Http/Controllers/Payment/PaymentReviewController.php`
- `app/Http/Controllers/Payment/Admin/ReviewManagementController.php`
- `app/Notifications/Payment/PaymentReviewRequested.php`
- `app/Notifications/Payment/PaymentReviewDecision.php`
- `resources/views/payment/reviews/request.blade.php`
- `resources/views/payment/admin/reviews/pending.blade.php`

#### Review Features:
- User/Admin review request form
- Accountant review dashboard
- Review status notifications
- Review history tracking
- Review decision notifications

---

### Step 5: Dynamic Coin Pricing System
**Status**: Planned
**Priority**: Medium
**Estimated Time**: 2 days

#### Tasks:
- [ ] Create coin_pricing table
- [ ] Create coin_offers table
- [ ] Implement dynamic pricing service
- [ ] Create pricing management interface
- [ ] Add offer creation functionality
- [ ] Test pricing calculations

#### Database Tables:
```sql
-- coin_pricing table
CREATE TABLE coin_pricing (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('user', 'admin') NOT NULL,
    base_amount DECIMAL(10,2) NOT NULL, -- Money amount (DZD)
    base_coins INTEGER NOT NULL, -- Coins given
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_type (user_type),
    INDEX idx_is_active (is_active),
    
    -- Foreign keys
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE RESTRICT
);

-- coin_offers table
CREATE TABLE coin_offers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    user_type ENUM('user', 'admin', 'both') NOT NULL,
    discount_percentage INTEGER NOT NULL, -- 10 = 10% extra coins
    min_amount DECIMAL(10,2) NULL, -- Minimum purchase for offer
    max_amount DECIMAL(10,2) NULL, -- Maximum purchase for offer
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_type (user_type),
    INDEX idx_is_active (is_active),
    INDEX idx_date_range (start_date, end_date),
    
    -- Foreign keys
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE RESTRICT
);
```

#### Pricing Management Permissions:
- **Owner**: Set base pricing, create any offers, override pricing
- **Accountant**: Create temporary offers, adjust pricing within limits, view history

#### Files to Create/Modify:
- `database/migrations/create_coin_pricing_table.php`
- `database/migrations/create_coin_offers_table.php`
- `app/Models/Payment/CoinPricing.php`
- `app/Models/Payment/CoinOffer.php`
- `app/Services/Payment/CoinPricingService.php`
- `app/Http/Controllers/Payment/Admin/CoinPricingController.php`
- `app/Http/Controllers/Payment/Admin/CoinOfferController.php`
- `resources/views/payment/admin/pricing/index.blade.php`
- `resources/views/payment/admin/offers/index.blade.php`

#### Pricing Features:
- Dynamic base pricing for users and admins
- Special offers with time limits and conditions
- Automatic coin calculation with offers
- Pricing history and audit trail
- Offer management interface

#### Example Pricing Scenarios:
- **Base Pricing**: Users (100 DZD = 50 coins), Admins (100 DZD = 100 coins)
- **Special Offers**: New user bonus, weekend specials, bulk discounts
- **Time-based Offers**: Limited time promotions
- **Conditional Offers**: Minimum purchase requirements

---

## Notes
- This plan will be updated as we discuss each step
- Each step will be marked as completed when implemented
- Additional steps may be added based on requirements
- Security and testing are prioritized throughout 