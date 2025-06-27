<?php

return [
    'fields' => [
        'error_message' => 'Error Message',
        'result' => 'Result',
        'started_at' => 'Started At',
        'completed_at' => 'Completed At',
        'failed_at' => 'Failed At',
        'job_type' => 'Job Type',
        'status' => 'Status',
        'attempts' => 'Attempts',
    ],
    'messages' => [
        'completed' => 'job completed successfully',
        'failed' => 'job failed',
        'auditor_deleted' => 'auditor :admin has been deleted successfully',
        'auditor_delete_failed' => 'auditor :admin has been deleted but failed to remove as an auditor',
        'admin_deleted' => 'admin :admin has been deleted and removed as an auditor successfully',
        'admin_delete_failed' => 'admin :admin has been deleted but failed to remove as an auditor',
        'admin_restored' => 'admin :admin has been restored',
        'success_duplicate_job_found' => 'Duplicate successful job found at :time',
        'already_handled' => 'Job already handled',
    ],
    'status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],
    'job_type' => [ 
        'delete_auditor' => 'Delete Auditor',
        'delete_admin' => 'Delete Admin',
    ],
    'result_keys' => [
        'auditor_id' => 'Auditor ID',
        'competition_id' => 'Competition ID',
        'deleted_admin_id' => 'Deleted Admin ID',
        'completed_at' => 'Completed At',
        'auditor' => 'Auditor',
        'competition' => 'Competition',
        'notice' => 'Notice',
    ],
];