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
    'validation' =>[
        'success' =>[
            'saved' => 'data have been saved',
            'updated' => 'data have been updated',
            'deleted' => 'data have been deleted',
        ],
        'fail' =>[
            'saved' => 'something went wrong while saving',
            'updated' => 'something went wrong while updating',
            'deleted' => 'something went wrong while deleting',
        ],
        '404' => [
            'user' => 'user not found',
        ],
        'not_allow' => [
            'user_delete' => 'you have no permission to delete a user who doesnt created by you',
            'admin_delete' => 'you have no permission to delete an admin who doesnt created by you',
            'competition_update' => 'you have no permission to update a competition that doesnt created by you',
            'active_competition_update' => 'you cant update a competition that already activated',
            "level_time_conflict" => 'there is a level that hast time conflict with this level',
            "competition_max_levels" => 'competition has maximum levels number',
            'active_level_update' => 'you cant update a level that already activated',
            'question_update' => 'you have no permission to update or create a question for level you are not responsible on it',
            'active_level_question_update' => 'you cant update or create a question for level already activated',

        ],
        'contact' => 'contact administrator',
    ],
    'alert'=>[
        'type' =>[
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            'danger' => 'danger',
        ],
        'content' =>[
            'data_cant_change' => 'you can not change this data later be careful',
            'competition_user_delete' => 'all records of this user in competition will be removed, this operation cannot be canceled',

        ]
    ],
    'global' => [
        'no' => 'N°'
    ],

];
