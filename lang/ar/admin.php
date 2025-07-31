<?php

return [
    'admin' => [
        'information' => 'معلوماتك الشخصية',
        'title' => 'User Profile',
        'yours' => 'ملفك الشخصي',
        'name' => 'الاسم',
        'email' => 'البريد الالكتروني',
        'birthdate' => 'تاريخ الميلاد',
        'password' => [
            "password" => "كلمة السر",
            'update' =>  'تغيير كلمة السر',
            'new' =>  'كلمة السر الجديدة',
            'current' =>  'كلمة السر الحالية',
            'confirm' =>  'تأكيد كلمة السر',
        ],
        'genders' => [
            'gender' => 'الجنس',
            'male' => 'ذكر',
            'female' => 'أنثى',
        ],
    ],

    'availability' => [
        'title' => 'توفر المسؤول',
        'auditor' => 'مدقق',
        'level_manager' => 'مدير مستوى',
        'ownership_transfer' => 'نقل الملكية',
        'desc' => [
            'auditor' => 'ستكون متاحًا ليتم تعيينك كمدقق في مسابقات أخرى.',
            'level_manager' => 'ستكون متاحًا ليتم تعيينك كمدير مستوى في مسابقات أخرى وإدارة أسئلة المستوى.',
            'ownership_transfer' => 'ستكون متاحًا لاستبدال المسؤولين المحذوفين وتولي ملكية مسابقاتهم والإجابات التي تم تدقيقها.'
        ],
        'on' => 'تشغيل',
        'off' => 'إيقاف',
        'update' => 'تحديث',
    ],
    'availability_updated' => 'تم تحديث التوفر بنجاح.',
    'admin_approval' => [
        'title' => 'طلبات موافقة المسؤول',
        'fields' => [
            'admin' => 'المسؤول',
                            'entity' => 'نوع الكيان',
            'type' => 'النوع',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
        ],
        'types' => [
            'auditor' => 'مدقق',
            'level_manager' => 'مدير مستوى',
        ],
        'status' => [
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
        ],
        'descriptions' => [
            'auditor' => 'تعيين المسؤول كمدقق للمسابقة',
            'level_manager' => 'تعيين المسؤول كمدير مستوى لمستوى المسابقة',
        ],
        'actions' => [
                    'approve' => 'موافقة',
                    'reject' => 'رفض',
                    'view_details' => 'عرض التفاصيل',
                    'view' => 'عرض التفاصيل',
                ],
        'messages' => [
            'approved' => 'تمت الموافقة على الطلب بنجاح.',
            'rejected' => 'تم رفض الطلب بنجاح.',
            'no_pending' => 'لا توجد طلبات موافقة معلقة.',
            'approve_confirmation' => 'هل أنت متأكد من أنك تريد الموافقة على هذا الطلب؟',
            'reject_confirmation' => 'هل أنت متأكد من أنك تريد رفض هذا الطلب؟',
            'delete_confirmation' => 'هل أنت متأكد من أنك تريد حذف طلب الموافقة هذا؟',
        ],
    ],

];
