<?php

return [
    'fields' => [
        'error_message' => 'رسالة الخطأ',
        'result' => 'النتيجة',
        'started_at' => 'بدء العملية',
        'completed_at' => 'إنهاء العملية',
        'failed_at' => 'فشل العملية',
        'job_type' => 'نوع العملية',
        'status' => 'الحالة',
        'attempts' => 'المحاولات',
    ],
    'messages' => [
        'completed' => 'تم العملية بنجاح',
        'failed' => 'فشل العملية',
        'auditor_deleted' => 'تم حذف المصحح :admin بنجاح',
        'auditor_delete_failed' => 'فشل حذف المصحح :admin',
        'admin_deleted' => 'تم حذف المسؤول :admin وازالته كمصحح بنجاح',
        'admin_delete_failed' => 'تم حذف المسؤول :admin ولكن فشل ازالته كمصحح',
        'admin_restored' => 'تم استرجاع المسؤول :admin',
    ],
    'status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
    ],
    'job_type' => [ 
        'delete_auditor' => 'حذف المصحح',
        'delete_admin' => 'حذف المسؤول',
    ],
];