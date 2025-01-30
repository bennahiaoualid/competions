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
        'competition' => 'المسابقة',
        'information' => 'معلومات المسابقة',
        'title' => 'اسم المسابقة',
        'description' => 'الوصف',
        'created_by' => 'أنشئ بواسطة',
        'start_date' => 'تاريخ الانطلاق',
        'users_age' => 'عمر المتسابقين',
        'from' => 'من',
        'to' => 'الى',
        'age_start' => 'العمر الادنى',
        'age_end' => 'العمر الاقضى',
        'status' =>[
            'state' =>  'الحالة',
            'active' =>  'جارية',
            'inactive' =>  'غير نشط',
            'finished' =>  'منتهية',
        ],
        'coming' =>  'المسابقات القادمة',
        'active' =>  'المسابقات الجارية',
        'finished' =>  'المسابقات المنتهية',
        'levels_number' => 'عدد المراحل',
        'competitor' => 'المتسابق',
        'competitors' => 'المتسابقين',
        'competitors_not_in' => 'المتسابقين الذين ليسوا مشتركين',
        'user_auth' => 'مسابقاتي',
        'auditor' =>[
            'info' => 'معلومات المصحح',
            'information' => 'معلومات المصححين',
            'list' => 'قائمة المصححين',
            'not_in' => 'مسؤولين ليسوا في قائمة المصححين',
            'all_audited' => 'تم التصحيح',
            'not_audited' => 'لم يتم التصحيح',
            'in_competition' => 'مصحح في المسابقات'
        ]
    ],
    'level' => [
        'level' => 'المرحلة',
        'info' => 'معلومات المرحلة',
        'information' => 'معلومات المراحل',
        'name' => 'اسم المرحلة',
        'duration' => 'المدة',
        'questions_number' => 'عدد الاسئلة',
        'admin' => 'مسؤول المرحلة'
    ],
    'question' => [
        'the_question' => 'السؤال',
        'info' => 'معلومات السؤال',
        'list' => 'قائمة الاسئلة',
        'question_text' => 'نص السؤال ',
        'max_score' => 'العلامة الكاملة',
        'duration' => 'المدة',
        'level' => 'المرحلة',
        'start_solve' => 'إبدأ الاجابة',
        'no_question'=> 'لا يوجد أسئلة متاحة',
        'try_later' => 'أعد المحاولة لاحقا',

    ],
    'response' =>[
        'info' => 'اجاباتك',
        'list' => 'قائمة الاجابات',
        'response' => 'الاجابة',
        'response_text' => 'نص الاجابة',
        'response_duration' => 'مدة الاجابة',
        'score' => 'العلامة',
        'user_response' => 'إجابة المتسابق',
        'final_score' => 'العلامة النهائية',
        'congratulation'=> 'تهانينا, إجابة صحيحة',
        'failed' => 'للأسف, إجابة خاطئة',
        'try_again' => 'حاول مجددا',
        'play_more' => 'واصل اللعب',

    ],
    'result' => [
        'level' => 'نتائج المرحلة',
        'competition' => 'نتائج المسابقة',
        'temp' => 'نتائج مؤقتة',
    ],
    'global' => [
        'choices' => 'الاختيارات',
        'choice' => 'الاختيار',
        'question' => 'الأسئلة العامة',
        'condition' => [
            'random' => 'سيتم اختيار سؤال عشوائي في كل مرة.',
            'choices_number' => 'كل سؤال يمتلك من خيارين الى خمسة احدهم هو الصحيح.',
            'wrong_response' => 'لن يظهر لك نفس السؤال أكثر من مرتين في حالة الخطأ.',
            'time' => 'كل سؤال له مدة اجابة كلما طالت هاته المدة, نقصت العلامة المحصل عليها.',
            'final_score' => 'العلامة = علامة السؤال - (مدة الاجابة / المدة * (علامة السؤال / 2))',
        ],
    ]
];
