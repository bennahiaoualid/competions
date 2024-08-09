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
            'saved' => 'تم حفظ البيانات',
            'updated' => 'تم تعديل البيانات',
            'deleted' => 'تم ازالة البيانات',
        ],
        'fail' =>[
            'saved' => 'حدث خطأ أثناء الحفظ',
            'updated' => 'حدث خطأ أثناء التعديل',
            'deleted' => 'حدث خطأ أثناء الازالة',
        ],
        '404' => [
            'user' => 'المستخدم غير موجود',
        ],
        'not_allow' => [
            'user_delete' => 'ليس لديك الصلاحيات لحذف مستخدم لم تقم باضافته',
            'admin_delete' => 'ليس لديك الصلاحيات لحذف مدير لم تقم باضافته',
            'competition_update' => 'ليس لديك الصلاحيات لتعديل مسابقة لم تقم باضافتها',
            'active_competition_update' => 'لا يمكنك تعديل معلومات مسابقة تم تنشيطها',
            "level_time_conflict" => 'يوجد مرحلة لها توقيت يتعارض مع تةقيت المرحلة المضافة',
            "competition_max_levels" => 'عدد المراحل وصل للحد الاقصى',
            'active_level_update' => 'لا يمكنك تعديل معلومات مرحلة تم تنشيطها',
            'question_update' => 'لا يمكنك اضافة او تعديل اسئلة لمرحلة لست مسؤول عنها',
            'active_level_question_update' => 'لا يمكنك تعديل معلومات اسئلة مرحلة تم تنشيطها'
        ],
        'contact' => 'تواصل مع الدعم الفني',
    ],
    'alert'=>[
        'type' =>[
            'success' => 'نجاح',
            'error' => 'خطأ',
            'warning' => 'تحذير',
            'info' => 'معلومات',
            'danger' => 'انذار',
        ],
        'content' =>[
            'data_cant_change' => 'لا يمكن تغيير هاته المعلومات لاحقا',
            'competition_user_delete' => 'سيتم ازالة كل السجلات الخاصة بهذا المستخدم في المسابقة لا يمكن الغاء العملية'
        ],
    ],
    'global' => [
        'no' => 'الرقم'
    ]

];
