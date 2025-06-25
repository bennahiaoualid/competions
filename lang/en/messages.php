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
            'activated' => 'activation done',
            'finish' => 'finish operation done',
            'response_audited' => 'response number :number score updated',
            'approved' => 'approved',
            'response_audited' => 'response number :number has been audited',
        ],
        'fail' =>[
            'saved' => 'something went wrong while saving',
            'updated' => 'something went wrong while updating',
            'deleted' => 'something went wrong while deleting',
            'activated' => 'activation failed',
            'something_went_wrong' => 'something went wrong',
            'finish' => 'finish operation failed',
            'approved' => 'approve fail',
        ],
        '404' => [
            'user' => 'user not found',
        ],
        'not_allow' => [
            'user_delete' => 'you have no permission to delete a user who doesnt created by you',
            'admin_delete' => 'you have no permission to delete an admin who doesnt created by you',
            'competition_update' => 'you have no permission to update a competition that doesnt created by you',
            'competition_delete' => 'you have no permission to delete a competition that doesnt created by you',
            'active_competition_update' => 'you cant update a competition that already activated',
            "level_time_conflict" => 'there is a level has a time conflict with this level',
            "competition_max_levels" => 'competition has maximum levels number',
            'active_level_update' => 'you cant update a level that already activated',
            'question_update' => 'you have no permission to update or create a question for level you are not responsible on it',
            'question_update_max_number' => 'you cant add more than :number questions',
            'active_level_question_update' => 'you cant update or create a question for level already activated',
            'competition_activate_less_auditor' => 'competition cant activate, should at least 1 auditor',
            'competition_activate_less_competitors' => 'competition cant activate, should at least has 3 competitors',
            'competition_activate_match_levels' => 'competition cant activate, levels number not much the number in competition information',
            'competition_activate_early' => 'you cant activate competition before the start date',
            'competition_activate_level_pass' => 'there is levels that its start time already passed , update it',
            'level_activate_before_competition' => 'you cant activate level before activate competition',
            'level_activate_early' => 'you cant activate level before the start date',
            'level_activate_match_questions' => 'level cant activate, questions number not much the number in level information',
            'level_activate_not_its_tour' => 'there is another level needs to be finished before this level',
            'level_activate_time_conflict' => 'there is levels with start date conflict with this level now',
            'level_finish_still_active' => 'you cant finish the level before the duration passed',
            'level_activate_previous_not_audit' => 'you cant activate level before audit the previous level responses',
            'audit_score_greater_then_max' => 'you cant update response number :number because the giving score greater then question max score',
            'remove_auditor_only_one' => 'you cant delete the only auditor in a competition',
            'global_question_choices' => 'choices number should be at between 2 or 5 choices',
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
            'user_not_part_of_competition'=>'you are not competitor in  this competition',
            'time_response_end'=>'time response for this level already finished',
            'response_early'=>'you cant answer the question level yet',
            'leave_without_response'=>'closing the browser or leaving the page mark your response as empty',
            'you_cant_change_audited_responses' => 'you cant change the giving scores after saving',
            'response_final_score_calc' => 'final score = score - (response duration / duration * (score / 2))',
            'response_penalty_calc' => 'the score will by decreased by percentage of the penalty',
            'delete_competition' => 'This process will remove competition and its information including levels competitors auditors and results, you cant undo ',
            'correct_choice' => 'the first choice will be chose as the correct one'
        ]
    ],
    'global' => [
        'no' => 'N°',
        'minute'=>'minute',
        'minutes'=>'minutes',
        'seconds'=>'seconds',
        'second'=>'second',
        'see_all' => 'see all',
        'see_more' => 'see more',
        'details' => 'details',
        'order'=>'order',
        'no_records' => 'No Records Found',
        'site_brief' => 'Participate in many intellectual and cultural competitions , compete with your peers and prove that you are the best.',
        'site_name' => 'be creative',
        'select_lang' => 'select a language',
        'not_determinate' => 'not Determinate',
        'created_by' => 'created by',
        'approved_by' => 'approved by',
        'not_approved' => 'not approved',
        'global_order'=> 'global order',
        'your_order' => 'your order is',
        'check' => 'check it',
        'detail' => 'detail',
        'text_dir' => 'text direction',
        'ltr' => 'left to right',
        'rtl' => 'right to left',
    ],
    'mail' => [
        'welcome' => 'Welcome dear :user',
        'new_competition' => 'you have been added to new Competition :competition as competitor',
        'update_competition' => 'a competition that you a competitor on it :competition has been updated',
        'admin_level' => 'you have been appointed as the administrator of level :level of competition :competition , you can now select questions',
        'update_level' => 'a competition that you a competitor on it :competition has been updated the level :level',
        'activate_competition' =>  'a competition that you a competitor on it :competition has been activated',
        'activate_level' =>  'a competition that you a competitor on it :competition has been activate the level :level you can start answering',
        'new_auditor' => 'you have been added as an auditor in new Competition :competition',
        'finish_level' =>  'a competition that you are an auditor on it :competition has been finish the level :level you can start checking responses',

    ],
    'job' => [
        'completed' => 'job completed successfully',
        'failed' => 'job failed',
        'auditor_deleted' => 'auditor :admin has been deleted successfully',
        'auditor_delete_failed' => 'auditor :admin has been deleted but failed to remove as an auditor',
        'admin_deleted' => 'admin :admin has been deleted and removed as an auditor successfully',
        'admin_delete_failed' => 'admin :admin has been deleted but failed to remove as an auditor',
        'admin_restored' => 'admin :admin has been restored',
    ]

];
