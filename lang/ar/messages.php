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
            'activated' => 'تمت عملية تفعيل',
            'finish' => 'تمت عملية الإنهاء',
            'response_audited' => 'تم اضافة العلامة للاجابة رقم :number ',
            'approved' => 'تم الاعتماد',
        ],
        'fail' =>[
            'saved' => 'حدث خطأ أثناء الحفظ',
            'updated' => 'حدث خطأ أثناء التعديل',
            'deleted' => 'حدث خطأ أثناء الازالة',
            'activated' => 'فشلت عملية تفعيل',
            'something_went_wrong' => 'حدث خطأ',
            'finish' => 'فشلت عملية الإنهاء',
            'approved' => 'فشل الاعتماد',
        ],
        '404' => [
            'user' => 'المستخدم غير موجود',
        ],
        'not_allow' => [
            'user_delete' => 'ليس لديك الصلاحيات لحذف مستخدم لم تقم باضافته',
            'admin_delete' => 'ليس لديك الصلاحيات لحذف مدير لم تقم باضافته',
            'competition_update' => 'ليس لديك الصلاحيات لتعديل مسابقة لم تقم باضافتها',
            'competition_delete' => 'ليس لديك الصلاحيات لازالة مسابقة لم تقم باضافتها',
            'active_competition_update' => 'لا يمكنك تعديل معلومات مسابقة تم تنشيطها',
            "level_time_conflict" => 'يوجد مرحلة لها توقيت يتعارض مع تةقيت المرحلة المضافة',
            "competition_max_levels" => 'عدد المراحل وصل للحد الاقصى',
            'active_level_update' => 'لا يمكنك تعديل معلومات مرحلة تم تنشيطها',
            'question_update' => 'لا يمكنك اضافة او تعديل اسئلة لمرحلة لست مسؤول عنها',
            'active_level_question_update' => 'لا يمكنك تعديل معلومات اسئلة مرحلة تم تنشيطها',
            'competition_activate_less_auditor' => 'لا يمكن تفعيل مسابقة لا تحتوي على مصحح واحد على الاقل',
            'competition_activate_less_competitors' => 'لا يمكن تفعيل مسابقة لا تحتوي على ثلاث متسابقين على الاقل',
            'competition_activate_match_levels' => 'لا يمكن تفعيل مسابقة , عدد المراحل المدرجة لا يطابق العدد في معلومات المسابقة',
            'competition_activate_early' => 'لا يمكن تفعيل مسابقة قبل موعد انطلاقها المحدد',
            'competition_activate_level_pass' => 'يوجد مراحل وقت انطلاقها تخطى الوقت الحالي يرجى تعديلها اولا',
            'level_activate_before_competition' => 'لا يمكنك تفعيل مرحلة قبل تفعيل المسابقة',
            'level_activate_early' => 'لا يمكن تفعيل مرحلة قبل موعد انطلاقها المحدد',
            'level_activate_match_questions' => 'لا يمكن تفعيل المرحلة , عدد الاسئلة المدرجة لا يطابق العدد في معلومات المرحلة',
            'level_activate_not_its_tour' => 'هنالك مرحلة تسبق هاته المرحلة  يجب ان تنتهي اولا',
            'level_activate_time_conflict' => 'يوجد مراحل يتداخل وقت انطلاقها مع وقت هاته المرحلة اعتبارا من الان يرجى تعديلها اولا',
            'level_finish_still_active' => 'لا يمكن انهاء المرحلة قبل تجاوز مدة اجتيازها',
            'level_activate_previous_not_audit' => 'لا يمكن تفعيل مرحلة قبل تصحيح اجابات المرحلة السابقة',
            'audit_score_greater_then_max' => 'لا يمكنك اضافة العلامة للاجابة رقم :number لانها اكبر من العلامة الاقصى الخاصة بهذا السؤال',
            'remove_auditor_only_one' => 'لا يمكنك ازالة المصحح الوحيد في المسابقة',
            'global_question_choices' => 'عدد الخيارات يجب ان يكون بين 2 الى 5 خيارات',

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
            'competition_user_delete' => 'سيتم ازالة كل السجلات الخاصة بهذا المستخدم في المسابقة لا يمكن الغاء العملية',
             'user_not_part_of_competition'=>'أنت لست مسجل في هاته المسابقة',
            'time_response_end'=> 'وقت الاجابة لهاته المرحلة إنتهى',
            'response_early'=> 'لا يمكنك الاجابة على أسئلة هاته المرحلة الان',
            'leave_without_response'=>'اغلاق المتصفح او مغادرة الصفحة سيؤدي تلقائيا لتحديد اجابتك على انها فارغة',
            'you_cant_change_audited_responses' => 'لا يمكنك تغيير العلامات الممنوحة بعد الحفظ',
            'response_final_score_calc' => 'العلامة النهائية = العلامة - (مدة الاجابة / المدة * (العلامة / 2))',
            'delete_competition' => 'هذا الاجراء سيؤدي لازالة المسابقة بكل بياناتها المراحل والمتسابقين والمصصحين والنتائج بشكل نهائي لا يمكن التراجع لاحقا',

        ],
    ],
    'global' => [
        'no' => 'الرقم',
        'minute'=>'دقيقة',
        'minutes'=>'دقائق',
        'seconds'=>'ثواني',
        'second'=>'ثانية',
        'see_all' => 'رؤية الكل',
        'see_more' => 'رؤية المزيد',
        'details' => 'التفاصيل',
        'order'=>'الترتيب',
        'no_records' => 'لا يوجد اي سجلات',
        'site_brief' => 'شارك في العديد من المسابقات الفكرية والثقافية , تنافس مع أقرانك وأثبت أنك الأفضل.',
        'site_name' => 'كن مبدع',
        'select_lang' => 'اختر اللغة',
        'not_determinate' => 'لم يحدد',
        'created_by' => 'أنشأ بواسطة',
        'approved_by' => 'معتمد',
        'not_approved' => 'غير معتمد',
    ],
    'mail' => [
        'welcome' => 'مرحبا :user',
        'new_competition' => 'تمت اضافتك لمسابقة جديدة كمتسابق :competition ',
        'update_competition' => 'مسابقتك :competition تم تعديل توقيتها',
        'admin_level' => 'تم تعيينك كمسؤول عن المرحلة :level من المسابقة :competition , يمكنك الان تحديد الاسئلة',
        'update_level' => 'مسابقتك :competition تم تعديل توقيت مرحلتها :level',
        'activate_competition' =>  'مسابقتك :competition تم تفعيلها',
        'activate_level' =>  'مسابقتك :competition تم تفعيل مرحلتها :level يمكنك البدء بالاجابة عن الاسئلة',
        'new_auditor' => 'تم اضافتك كمصحح في المسابقة :competition',
        'finish_level' =>  'مسابقة انت مصصح فيها :competition قد تم انهاء مرحلتها :level يمكنك البدء في تصحيح اجابات المتسابقين',
    ]

];
