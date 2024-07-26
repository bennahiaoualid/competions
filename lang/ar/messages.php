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
            'admin_delete' => 'ليس لديك الصلاحيات لحذف مدير لم تقم باضافته'
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
        ]
    ]

];
