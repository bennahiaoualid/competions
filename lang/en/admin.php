<?php

return [
    'admin' => [
        'information' => 'profile information',
        'title' => 'User Profile',
        'yours' => 'your profile',
        'name' => 'Name',
        'email' => 'email',
        'birthdate' => 'birthdate',
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
    ],
]; 