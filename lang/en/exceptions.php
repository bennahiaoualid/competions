<?php

return [
    'admin_already_decided' => 'The Admin You Choosed As Manger for this level has already made a decision either approved or rejected',
    'admin_not_available_as_level_manager' => 'The selected admin is not available as a level manager',
    'insufficient_balance' => 'Insufficient balance',
    'invalid_parameters' => 'Invalid parameters',
    
    // AI Auditing Exception Translations
    'ai_auditing' => [
        'score_validation_error' => 'AI score validation failed. Please check the data integrity.',
        'response_parsing_error' => 'Failed to parse AI response. The AI service may have returned invalid data.',
        'score_processing_error' => 'Failed to process AI scores. Please try again or contact support.',
        'data_integrity_error' => 'AI auditing data integrity check failed. Some data may be missing or corrupted.',
        'batch_processing_error' => 'AI batch processing failed. The system will retry automatically.',
    ],
]; 