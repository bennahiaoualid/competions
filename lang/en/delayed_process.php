<?php

return [
    'fields' => [
        'id' => 'ID',
        'process_type' => 'Process Type',
        'target_type' => 'Target Type',
        'target_id' => 'Target ID',
        'initiator_id' => 'Initiator ID',
        'context_data' => 'Context Data',
        'check_period_hours' => 'Check Period (Hours)',
        'created_at' => 'Created At',
        'status' => 'Status',
        'time_display' => 'Time',
    ],
    'values' => [
        'process_type' => [
            'delete_auditor' => 'Delete Auditor',
        ],
        'target_type' => [
            'admin' => 'Admin',
            'user' => 'User',
        ],
        'status' => [
            'ready' => 'Ready',
            'pending' => 'Pending',
        ],
    ],
    'actions' => [
        'retry' => 'Retry',
        'delete' => 'Delete',
        'retry_success' => 'Process retry initiated successfully.',
        'delete_success' => 'Process deleted successfully.',
        'not_ready' => 'Process is not ready for retry or not found.',
        'not_found' => 'Process not found.',
    ],
    'messages' => [
        'title' => 'Delayed Processes',
        'list' => 'Delayed Processes List',
        'confirm_retry' => 'Are you sure you want to retry this process?',
        'confirm_delete' => 'Are you sure you want to delete this process?',
    ],
    'time' => [
        'overdue' => 'overdue',
        'ready_in' => 'Ready in',
        'hours' => 'h',
        'minutes' => 'm',
    ],
    // When adding a new delayed process type that uses context_data,
    // don't forget to add your context keys here for translation!
    'context' => [
        'competition_ids' => 'Competition IDs',
        'competition_titles' => 'Competition Titles',
        'total_competitions' => 'Total Competitions',
        'created_at' => 'Created At',
    ],
]; 