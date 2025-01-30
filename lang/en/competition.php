<?php

return [
    /*
    |--------------------------------------------------------------------------
    | validation Language Lines
    |--------------------------------------------------------------------------
    |
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */
    'info' => [
        'competition' => 'competition',
        'information' => 'competition information',
        'title' => 'title',
        'description' => 'description',
        'created_by' => 'created by',
        'start_date' => 'start date',
        'users_age' => 'users age',
        'from' => 'from',
        'to' => 'to',
        'age_start' => 'min age',
        'age_end' => 'max age',
        'status' =>[
            'state' =>  'state',
            'active' =>  'active',
            'inactive' =>  'coming',
            'finished' =>  'finished',
        ],
        'coming' =>  'coming competitions',
        'active' =>  'active competitions',
        'finished' =>  'finished competitions',
        'levels_number' => 'levels number',
        'competitor' => 'competitor',
        'competitors' => 'competitors',
        'competitors_not_in' => 'competitors not in competition',
        'user_auth' => 'my competitions',
        'auditor' =>[
            'info' => 'auditor information',
            'information' => 'auditors information',
            'list' => 'auditors list',
            'not_in' => 'admins Not Auditors',
            'all_audited' => 'audited done',
            'not_audited' => 'not audited',
            'in_competition' => 'auditing in competitions'
        ]

    ],
    'level' => [
        'level' => 'level',
        'info' => 'level information',
        'information' => 'levels information',
        'name' => 'name',
        'duration' => 'duration',
        'questions_number' => 'questions number',
        'admin' => 'level responsible'
    ],
    'question' => [
        'the_question' => 'question',
        'info' => 'question information',
        'list' => 'question list',
        'question_text' => 'question text ',
        'max_score' => 'max score',
        'duration' => 'duration',
        'level' => 'level',
        'start_solve' => 'start solve',
        'no_question'=> 'there is no available question',
        'try_later' => 'try later',

    ],
    'response' =>[
        'info' => 'response information',
        'list' => 'responses list',
        'response' => 'response',
        'response_text' => 'response text',
        'response_duration' => 'response duration',
        'score' => 'score',
        'user_response' => 'user response',
        'final_score' => 'final score',
        'congratulation'=> 'congratulation',
        'failed' => 'oops! that\' not right',
        'try_again' => 'try again',
        'play_more' => 'keep playing',
    ],
    'result' => [
        'level' => 'the level results',
        'competition' => 'the competition results',
        'temp' => 'temporary results',
    ],
    'global' => [
        'choices' => 'choices',
        'choice' => 'choice',
        'question' => 'global question',
        'condition' => [
            'random' => 'Random question will be selected every time.',
            'choices_number' => 'Each question has from 2 to 5 choices.',
            'wrong_response' => 'you can answer for the same question only twice.',
            'time' => 'each question has a response duration the longer it lasts, the lower the mark .',
            'final_score' => 'score = question score - (response duration / duration * (question score / 2))',
        ],
    ],


];
