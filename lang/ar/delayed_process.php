<?php

return [
    'fields' => [
        'id' => 'المعرف',
        'process_type' => 'نوع العملية',
        'target_type' => 'نوع الهدف',
        'target_id' => 'معرف الهدف',
        'initiator_id' => 'معرف المبتدئ',
        'context_data' => 'بيانات السياق',
        'check_period_hours' => 'فترة الفحص (ساعات)',
        'created_at' => 'تاريخ الإنشاء',
        'status' => 'الحالة',
        'time_display' => 'الوقت',
    ],
    'values' => [
        'process_type' => [
            'delete_auditor' => 'حذف المصحح',
        ],
        'target_type' => [
            'admin' => 'مدير',
            'user' => 'مستخدم',
        ],
        'status' => [
            'ready' => 'جاهز',
            'pending' => 'قيد الانتظار',
        ],
    ],
    'actions' => [
        'retry' => 'إعادة المحاولة',
        'delete' => 'حذف',
        'retry_success' => 'تم بدء إعادة محاولة العملية بنجاح.',
        'delete_success' => 'تم حذف العملية بنجاح.',
        'not_ready' => 'العملية ليست جاهزة لإعادة المحاولة أو غير موجودة.',
        'not_found' => 'العملية غير موجودة.',
    ],
    'messages' => [
        'title' => 'العمليات المؤجلة',
        'list' => 'قائمة العمليات المؤجلة',
        'confirm_retry' => 'هل أنت متأكد من إعادة محاولة هذه العملية؟',
        'confirm_delete' => 'هل أنت متأكد من حذف هذه العملية؟',
    ],
    'time' => [
        'overdue' => 'متأخر',
        'ready_in' => 'جاهز خلال',
        'hours' => 'س',
        'minutes' => 'د',
    ],
    // عند إضافة نوع جديد من العمليات المؤجلة يستخدم context_data،
    // لا تنس إضافة مفاتيح السياق هنا للترجمة!
    'context' => [
        'competition_ids' => 'معرفات المسابقات',
        'competition_titles' => 'عناوين المسابقات',
        'total_competitions' => 'إجمالي المسابقات',
        'created_at' => 'تاريخ الإنشاء',
    ],
]; 