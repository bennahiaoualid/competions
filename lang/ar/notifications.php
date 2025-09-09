<?php

return [
    'auditor_required' => [
        'title' => 'مطلوب مراجع',
        'message' => 'يوجد مراجع واحد فقط (:auditor_name) في المسابقة ":competition_title". إذا لم تقم بتعيين مراجع آخر خلال يوم واحد، سيتم حذف مسابقتك.',
    ],
    'admin_deletion_failed' => [
        'title' => 'فشل حذف المدير',
        'message' => 'لا يمكن حذف المدير: هم المراجع الوحيد في بعض المسابقات.',
    ],
    'job_completed' => [
        'title' => 'تم إكمال المهمة',
        'message' => 'تم إكمال المهمة بنجاح.',
    ],
    'job_failed' => [
        'title' => 'فشلت المهمة',
        'message' => 'فشلت المهمة. يرجى التحقق من التفاصيل.',
    ],
    // Competition notifications
    'competition' => [
        'created' => [
            'title' => 'تم إنشاء مسابقة جديدة',
            'message' => 'تم إنشاء مسابقة جديدة ":competition_title" ويمكنك المشاركة فيها.',
        ],
        'updated' => [
            'title' => 'تم تحديث المسابقة',
            'message' => 'تم تحديث المسابقة ":competition_title" بمعلومات جديدة.',
        ],
        'activated' => [
            'title' => 'بدأت المسابقة',
            'message' => 'بدأت المسابقة ":competition_title"! استعد للمشاركة.',
        ],
        'user_added' => [
            'title' => 'تم إضافتك للمسابقة',
            'message' => 'تم إضافتك إلى المسابقة ":competition_title". مرحباً بك!',
        ],
        'level_created' => [
            'title' => 'تم إضافة مستوى جديد',
            'message' => 'تم إضافة مستوى جديد ":level_name" إلى المسابقة ":competition_title".',
        ],
        'level_updated' => [
            'title' => 'تم تحديث المستوى',
            'message' => 'تم تحديث المستوى ":level_name" في المسابقة ":competition_title".',
        ],
        'level_activated' => [
            'title' => 'بدأ المستوى',
            'message' => 'بدأ المستوى ":level_name" في المسابقة ":competition_title"! المدة: :duration',
        ],
        'level_finished' => [
            'title' => 'تم إكمال المستوى',
            'message' => 'تم إكمال المستوى ":level_name" في المسابقة ":competition_title".',
        ],
        'auditor_requested' => [
            'title' => 'طلب مراجع',
            'message' => 'تم طلبك كمراجع للمسابقة ":competition_title"',
        ],
        'level_manager_requested' => [
            'title' => 'طلب مدير مستوى',
            'message' => 'تم طلبك كمدير مستوى لـ ":level_name" في ":competition_title"',
        ],
        'audit_level_requested' => [
            'title' => 'طلب مراجعة مستوى',
            'message' => 'تم إكمال المستوى ":level_name" في المسابقة ":competition_title". تم طلبك لمراجعة اجابات المتسابقين في اسرع وقت ممكن',
        ],
        'confirm_ai_auditing_level_requested' => [
            'title' => 'طلب تأكيد مراجعة مستوى بواسطة الذكاء الاصطناعي',
            'message' => 'تم إكمال المستوى ":level_name" في المسابقة ":competition_title". تم طلبك لتأكيد العلامات الممنوحة للمتسابقين بواسطة الذكاء الاصطناعي.  بعد :minutes دقيقة سيتم اعتبارك مؤكدا',
        ],
    ],
    // Link text translations
    'link_text' => [
        'detail' => 'عرض التفاصيل',
        'see_more' => 'عرض المزيد',
        'view' => 'عرض',
    ],
    'notifications' => 'الاشعارات',
    'type' => 'النوع',
    'title' => 'الاشعار',
    'data' => 'التفاصيل',
    'created_at' => 'تاريخ الإنشاء',
    'detail_modal' => [
        'title' => 'تفاصيل الإشعار',
    ],
    'view_all_notifications' => 'عرض جميع الاشعارات',
    // Approval notifications
    'approval' => [
        'level_manager_requested' => [
            'title' => 'طلب مدير مستوى',
            'message' => 'تم طلبك كمدير مستوى لـ ":level_name" في ":competition_title"',
        ],
        'auditor_requested' => [
            'title' => 'طلب مراجع',
            'message' => 'تم طلبك كمراجع لـ ":competition_title"',
        ],
        'approved' => [
            'title' => 'تمت الموافقة',
            'message' => ':admin_name وافق على طلبك :approval_type لـ ":competition_title"',
        ],
        'rejected' => [
            'title' => 'تم الرفض',
            'message' => ':admin_name رفض طلبك :approval_type لـ ":competition_title"',
        ],
    ],
    // Payment notifications
    'payment' => [
        // Admin Notifications (minimal data)
        'transaction_created' => [
            'title' => 'تم إرسال دفعة جديدة',
            'message' => ':payer_name (:payer_type) أرسل دفعة بقيمة :amount. المعاملة: :transaction_uuid'
        ],
        'review_requested' => [
            'title' => 'تم طلب مراجعة الدفعة',
            'message' => ':payer_name (:payer_type) طلب مراجعة للمعاملة :transaction_uuid'
        ],
        
        // User Notifications (minimal data)
        'transaction_approved' => [
            'title' => 'تمت الموافقة على الدفعة',
            'message' => 'تمت الموافقة على دفعتك بقيمة :amount! تم إضافة :coins_credited عملة.'
        ],
        'transaction_rejected' => [
            'title' => 'تم رفض الدفعة',
            'message' => 'تم رفض دفعتك بقيمة :amount. يرجى التحقق من التفاصيل.'
        ],
        'transaction_cancelled' => [
            'title' => 'تم إلغاء الدفعة',
            'message' => 'تم إلغاء دفعتك بقيمة :amount.'
        ],
        'review_approved' => [
            'title' => 'تمت الموافقة على المراجعة',
            'message' => 'تمت الموافقة على طلب المراجعة الخاص بك. تم إضافة :coins_credited عملة.'
        ],
        'review_rejected' => [
            'title' => 'تم رفض المراجعة',
            'message' => 'تم رفض طلب المراجعة الخاص بك. يرجى التواصل مع الدعم.'
        ]
    ],
]; 