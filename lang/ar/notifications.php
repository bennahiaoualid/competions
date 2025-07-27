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
]; 