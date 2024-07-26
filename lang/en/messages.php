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
        ]
    ]



];
