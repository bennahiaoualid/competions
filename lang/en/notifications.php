<?php

return [
    'auditor_required' => [
        'title' => 'Auditor Required',
        'message' => 'There is only one auditor (:auditor_name) in competition ":competition_title". If you don\'t assign another auditor within 1 day, your competition will be deleted.',
    ],
    'admin_deletion_failed' => [
        'title' => 'Admin Deletion Failed',
        'message' => 'Cannot delete admin: they are the only auditor in some competitions.',
    ],
    'job_completed' => [
        'title' => 'Job Completed',
        'message' => 'The job has been completed successfully.',
    ],
    'job_failed' => [
        'title' => 'Job Failed',
        'message' => 'The job has failed. Please check the details.',
    ],
    // Competition notifications
    'competition' => [
        'created' => [
            'title' => 'New Competition Created',
            'message' => 'A new competition ":competition_title" has been created and you are eligible to participate.',
        ],
        'updated' => [
            'title' => 'Competition Updated',
            'message' => 'The competition ":competition_title" has been updated with new information.',
        ],
        'activated' => [
            'title' => 'Competition Started',
            'message' => 'The competition ":competition_title" has started! Get ready to participate.',
        ],
        'level_created' => [
            'title' => 'New Level Added',
            'message' => 'A new level ":level_name" has been added to competition ":competition_title".',
        ],
        'level_updated' => [
            'title' => 'Level Updated',
            'message' => 'The level ":level_name" in competition ":competition_title" has been updated.',
        ],
        'level_activated' => [
            'title' => 'Level Started',
            'message' => 'Level ":level_name" in competition ":competition_title" has started! Duration: :duration',
        ],
        'level_finished' => [
            'title' => 'Level Completed',
            'message' => 'Level ":level_name" in competition ":competition_title" has been completed.',
        ],
    ],
    // Link text translations
    'link_text' => [
        'detail' => 'Show Detail',
        'see_more' => 'See More',
        'view' => 'View',
    ],
    'notifications' => 'Notifications',
    'type' => 'Type',
    'title' => 'Notification',
    'data' => 'Detail',
    'created_at' => 'Created At',
    'detail_modal' => [
        'title' => 'Notification Details',
    ],
    'view_all_notifications' => 'View All Notifications',
]; 