<?php

return [
    // Payment layout translations
    'title' => 'Payment System',
    'subtitle' => 'Manage your coin balance and transactions',
    
    // Navigation
    'nav' => [
        'transactions' => 'Transactions',
        'create' => 'Buy Coins',
        'balance' => 'Balance',
        'back_to_dashboard' => 'Back to Dashboard',
    ],

    // Page descriptions
    'transactions_description' => 'View all your payment transactions and their status',
    'balance_description' => 'Check your current coin balance and transaction history',

    // Stats
    'stats' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    // Detail page
    'transaction_details' => 'Transaction Details',
    'transaction_id' => 'Transaction ID',
    'transaction_info' => 'Transaction Information',
    'basic_info' => 'Basic Information',
    'approval_info' => 'Approval Information',
    'audit_log' => 'Audit Log',
    'audit_action_created' => 'created this payment',
    'audit_action_approved' => 'approved this payment',
    'audit_action_rejected' => 'rejected this payment',
    'audit_action_cancelled' => 'cancelled this payment',
    'audit_action_modified' => 'modified this payment',

    // User-facing payment translations
    'create_transaction' => 'Create Payment Transaction',
    'create_transaction_description' => 'Purchase coins to use for AI services and competitions',
    'current_balance' => 'Current Balance',
    'total_earned' => 'Total Earned',
    'coins' => 'Coins',
    'extra_coins' => 'Extra Coins',
    'payment_details' => 'Payment Details',
    'select_amount' => 'Select Amount',
    'select_coin_pricing' => 'select offer',
    'get_coins' => 'Get :coins coins',
    'amount_help' => 'Choose from available pricing options',
    'payment_method' => 'Payment Method',
    'cash' => 'Cash',
    'cash_description' => 'Direct cash payment',
    'bank_transfer' => 'Bank Transfer',
    'bank_transfer_description' => 'Bank transfer payment',
    'mobile_money' => 'Mobile Money',
    'mobile_money_description' => 'Mobile money payment',
    'proof_image' => 'Payment Proof',
    'upload_file' => 'Upload a file',
    'or_drag_drop' => 'or drag and drop',
    'image_requirements' => 'PNG, JPG, JPEG up to 10MB',
    'image_preview' => 'Image Preview',
    'submit_payment' => 'Submit Payment',
    'important_info' => 'Important Information',
    'info_1' => 'Payment will be reviewed by our accountant',
    'info_2' => 'Coins will be credited after approval',
    'info_3' => 'Keep your payment proof for reference',
    'coin_usage' => 'Coin Usage',
    'usage_1' => 'Use coins for Global AI Questions',
    'usage_2' => 'Use coins for AI Auditing in competitions',
    'usage_3' => 'Coins are non-refundable once used',

    'payment_transaction' => [
        'status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ],
        'type' => [
            'user' => 'User',
            'admin' => 'Admin',
        ],
        'payment_method' => [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
        ],
        'fields' => [
            'transaction_id' => 'Transaction ID',
            'payer' => 'Payer',
            'payer_type' => 'Payer Type',
            'amount' => 'Amount',
            'coins_credited' => 'Coins Credited',
            'payment_method' => 'Payment Method',
            'status' => 'Status',
            'approver' => 'Approver',
            'approved_at' => 'Approved At',
            'observation' => 'Observation',
            'accountant_observation' => 'Accountant Observation',
            'created_at' => 'Created At',
            'actions' => 'Actions',
            'proof_image' => 'Proof Image',
        ],
    ],

    // Coin Offers translations
    'offers' => [
        'title' => 'Coin Offers Management',
        'description' => 'Manage special offers and discounts for coin purchases',
        'create' => [
            'title' => 'Create New Offer',
            'button' => 'Create Offer',
        ],
        'list' => [
            'title' => 'All Offers',
        ],
        'status' => [
            'active' => 'Active',
            'scheduled' => 'Scheduled',
            'expired' => 'Expired',
        ],
        'fields' => [
            'name' => 'Offer Name',
            'description' => 'Description',
            'discount_percentage' => 'Discount (%)',
            'pricing_name' => 'Pricing Name',
            'user_type' => 'User Type',
            'date_range' => 'Date Range',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'actions' => 'Actions',
            'coin_pricing' => 'Pricing Rule',
            'select_pricing' => 'Select Pricing Rule',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'pricing_details' => 'Pricing Details',
            'discount_details' => 'Discount Details',
            'status_details' => 'Status Details',
        ],
        'delete' => [
            'title' => 'Delete Offer',
            'message' => 'Are you sure you want to delete this offer? This action cannot be undone.',
            'confirm' => 'Delete Offer',
        ],
        'activate' => [
            'title' => 'Activate Offer',
            'message' => 'Are you sure you want to activate this offer?',
            'confirm' => 'Activate Offer',
        ],
        'deactivate' => [
            'title' => 'Deactivate Offer',
            'message' => 'Are you sure you want to deactivate this offer?',
            'confirm' => 'Deactivate Offer',
        ],
        'no_description' => 'No description provided',
        'discount_off' => 'discount off',
        'expired_on' => 'Expired on',
        'active_until' => 'Active until',
        'active_offers' => 'Active Offers',
    ],

    'actions' => [
        'approve' => 'Approve Payment',
        'reject' => 'Reject Payment',
        'cancel' => 'Cancel Payment',
    ],
    'messages' => [
        'approve_confirmation' => 'Are you sure you want to approve this payment?',
        'reject_confirmation' => 'Are you sure you want to reject this payment?',
        'cancel_confirmation' => 'Are you sure you want to cancel this payment?',
        'optional_observation' => 'Optional observation (optional)',
        'required_observation' => 'Please provide a reason for rejection',
    ],

    'pricing' => [
        'user_type' => [
            'user' => 'User Only',
            'admin' => 'Admin Only',
            'both' => 'User & Admin',
        ],
        'status' => [
            'active' => 'Active',
            'disabled' => 'Disabled',
        ],
        'fields' => [
            'name' => 'Pricing Name',
            'display_name' => 'Display Name',
            'user_type' => 'User Type',
            'base_amount' => 'Base Amount (DZD)',
            'base_coins' => 'Base Coins',
            'rate' => 'Rate',
            'coins_per_dzd' => 'Coins per DZD',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'actions' => 'Actions',
            'reason' => 'Reason',
        ],
        'actions' => [
            'add' => 'Add Pricing',
            'delete' => 'Delete Pricing',
            'activate' => 'Activate Pricing',
            'deactivate' => 'Deactivate Pricing',
        ],
        'messages' => [
            'delete_confirmation' => 'Are you sure you want to delete this pricing? This action cannot be undone.',
            'activate_confirmation' => 'Are you sure you want to activate this pricing?',
            'deactivate_confirmation' => 'Are you sure you want to deactivate this pricing?',
            'optional_reason' => 'Optional reason (optional)',
        ],
    ],

    // Additional payment fields
    'proof_image' => 'Proof Image',
    'created_at' => 'Created At',
    'actions' => 'Actions',
    'coins' => 'Coins',
    
    // Success messages
    'approved_successfully' => 'Payment approved successfully',
    'rejected_successfully' => 'Payment rejected successfully',
    'cancelled_successfully' => 'Payment cancelled successfully',
    'coins_credited' => 'Coins have been credited to the account',
    
    // Error messages
    'payment_id_required' => 'Payment ID is required',
    'payment_not_found' => 'Payment transaction not found',
    'payment_not_pending' => 'Payment is not in pending status',
    'observation_too_long' => 'Observation must not exceed 1000 characters',
    'cannot_approve_own_payment' => 'You cannot approve your own payment',
    'cannot_reject_own_payment' => 'You cannot reject your own payment',
    'cannot_cancel_own_payment' => 'You cannot cancel your own payment',
]; 