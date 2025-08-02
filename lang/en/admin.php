<?php

return [
    'admin' => [
        'information' => 'profile information',
        'title' => 'User Profile',
        'yours' => 'your profile',
        'name' => 'Name',
        'email' => 'email',
        'birthdate' => 'birthdate',
    ],
    'availability' => [
        'title' => 'Admin Availability',
        'auditor' => 'Auditor',
        'level_manager' => 'Level Manager',
        'ownership_transfer' => 'Ownership Transfer',
        'desc' => [
            'auditor' => 'You will be available to be assigned as auditor in other competitions.',
            'level_manager' => 'You are available to be assigned as a level manager in other competitions and manage level questions.',
            'ownership_transfer' => 'You will be available to replace deleted admins and take ownership of their competitions and audited responses.'
        ],
        'on' => 'On',
        'off' => 'Off',
        'update' => 'Update',
    ],
    'availability_updated' => 'Availability updated successfully.',
    'admin_approval' => [
        'title' => 'Admin Approval Requests',
        'fields' => [
            'admin' => 'Admin',
            'entity' => 'Entity Type',
            'type' => 'Type',
            'status' => 'Status',
            'created_at' => 'Created At',
            'actions' => 'Actions',
        ],
        'types' => [
            'auditor' => 'Auditor',
            'level_manager' => 'Level Manager',
        ],
        'status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'descriptions' => [
            'auditor' => 'Assign admin as auditor for competition',
            'level_manager' => 'Assign admin as level manager for competition level',
        ],
            'actions' => [
                'approve' => 'Approve',
                'reject' => 'Reject',
                'view_details' => 'View Details',
                'view' => 'View Details',
            ],
        'messages' => [
            'approved' => 'Request approved successfully.',
            'rejected' => 'Request rejected successfully.',
            'no_pending' => 'No pending approval requests.',
            'approve_confirmation' => 'Are you sure you want to approve this request?',
            'reject_confirmation' => 'Are you sure you want to reject this request?',
            'delete_confirmation' => 'Are you sure you want to delete this approval request?',
        ],
    ],
]; 