<?php

return [
    // Payment layout translations
    'title' => 'نظام الدفع',
    'subtitle' => 'إدارة رصيد العملات والمعاملات',
    
    // Navigation
    'nav' => [
        'transactions' => 'المعاملات',
        'create' => 'شراء عملات',
        'balance' => 'الرصيد',
        'back_to_dashboard' => 'العودة للوحة التحكم',
    ],

    // Page descriptions
    'transactions_description' => 'عرض جميع معاملات الدفع وحالتها',
    'balance_description' => 'تحقق من رصيد العملات الحالي وتاريخ المعاملات',

    // Stats
    'stats' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'تمت الموافقة',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
    ],

    // Detail page
    'transaction_details' => 'تفاصيل المعاملة',
    'transaction_id' => 'رقم المعاملة',
    'transaction_info' => 'معلومات المعاملة',
    'basic_info' => 'المعلومات الأساسية',
    'approval_info' => 'معلومات الموافقة',
    'audit_log' => 'سجل التدقيق',
    'audit_action_created' => 'أنشأ هذا الدفع',
    'audit_action_approved' => 'وافق على هذا الدفع',
    'audit_action_rejected' => 'رفض هذا الدفع',
    'audit_action_cancelled' => 'ألغى هذا الدفع',
    'audit_action_modified' => 'عدل هذا الدفع',

    // User-facing payment translations
    'create_transaction' => 'إنشاء معاملة دفع',
    'create_transaction_description' => 'شراء عملات لاستخدامها في خدمات الذكاء الاصطناعي والمسابقات',
    'current_balance' => 'الرصيد الحالي',
    'total_earned' => 'إجمالي المكتسب',
    'coins' => 'العملات',
    'extra_coins' => 'العملات الإضافية',
    'payment_details' => 'تفاصيل الدفع',
    'select_amount' => 'اختر المبلغ',
    'select_coin_pricing' => 'اختر العرض',
    'get_coins' => 'احصل على :coins عملة',
    'amount_help' => 'اختر من خيارات التسعير المتاحة',
    'payment_method' => 'طريقة الدفع',
    'cash' => 'نقداً',
    'cash_description' => 'دفع نقدي مباشر',
    'bank_transfer' => 'تحويل بنكي',
    'bank_transfer_description' => 'دفع بالتحويل البنكي',
    'mobile_money' => 'محفظة إلكترونية',
    'mobile_money_description' => 'دفع بالمحفظة الإلكترونية',
    'proof_image' => 'إثبات الدفع',
    'upload_file' => 'رفع ملف',
    'or_drag_drop' => 'أو اسحب وأفلت',
    'image_requirements' => 'PNG, JPG, JPEG حتى 10 ميجابايت',
    'image_preview' => 'معاينة الصورة',
    'submit_payment' => 'إرسال الدفع',
    'important_info' => 'معلومات مهمة',
    'info_1' => 'سيتم مراجعة الدفع من قبل محاسبنا',
    'info_2' => 'سيتم إضافة العملات بعد الموافقة',
    'info_3' => 'احتفظ بإثبات الدفع للرجوع إليه',
    'coin_usage' => 'استخدام العملات',
    'usage_1' => 'استخدم العملات للأسئلة العالمية للذكاء الاصطناعي',
    'usage_2' => 'استخدم العملات لمراجعة الذكاء الاصطناعي في المسابقات',
    'usage_3' => 'العملات غير قابلة للاسترداد بعد الاستخدام',

    'payment_transaction' => [
        'status' => [
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
        ],
        'type' => [
            'user' => 'مستخدم',
            'admin' => 'مشرف',
        ],
        'payment_method' => [
            'cash' => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'mobile_money' => 'محفظة إلكترونية',
        ],
        'fields' => [
            'transaction_id' => 'رقم المعاملة',
            'payer' => 'المدفوع له',
            'payer_type' => 'نوع المدفوع له',
            'amount' => 'المبلغ',
            'coins_credited' => 'العملات المضافة',
            'payment_method' => 'طريقة الدفع',
            'status' => 'الحالة',
            'approver' => 'المدقق',
            'approved_at' => 'تاريخ الاجراء',
            'observation' => 'الملاحظة',
            'accountant_observation' => 'ملاحظة المدقق',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
            'proof_image' => 'إثبات الدفع',
            'view_details' => 'عرض التفاصيل',
        ],
        'proof_image_removed' => [
            'title' => 'تم إزالة إثبات الدفع',
            'description' => 'تم إزالة هذه الصورة تلقائياً أثناء تنظيف النظام',
        ],
        'actions' => [
            'approve' => 'الموافقة على الدفع',
            'reject' => 'رفض الدفع',
            'cancel' => 'إلغاء الدفع',
        ],
        'messages' => [
            'approve_confirmation' => 'هل أنت متأكد من الموافقة على هذا الدفع؟',
            'reject_confirmation' => 'هل أنت متأكد من رفض هذا الدفع؟',
            'cancel_confirmation' => 'هل أنت متأكد من إلغاء هذا الدفع؟',
            'optional_observation' => 'ملاحظة اختيارية (اختياري)',
            'required_observation' => 'يرجى تقديم سبب للرفض',
        ],
    ],

    'review' => [
        'page_title' => 'مراجعات الدفع',
        'columns' => [
            'transaction_uuid' => 'معرّف المعاملة',
            'payer' => 'المدفوع له',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
        ],
        'status' => [
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الرفض',
        ],
        'actions' => [
            'approve' => 'الموافقة على المراجعة',
            'reject' => 'رفض المراجعة',
            'order' => 'طلب مراجعة',
        ],
        'messages' => [
            'approve_confirmation' => 'هل تريد الموافقة على طلب المراجعة؟',
            'reject_confirmation' => 'هل تريد رفض طلب المراجعة؟',
            'already_exists' => 'لقد تم طلب مراجعة هذه المعاملة بالفعل',
        ],
        'detail' => [
            'review_info' => 'معلومات المراجعة',
            'status' => 'الحالة',
            'request_reason' => 'سبب الطلب',
            'reviewed_by' => 'تمت المراجعة بواسطة',
            'reviewed_at' => 'تاريخ المراجعة',
            'observation' => 'الملاحظة',
            'no_observation' => 'لا توجد ملاحظة',
        ],
    ],

    'pricing' => [
        'user_type' => [
            'user' => 'مستخدم فقط',
            'admin' => 'مشرف فقط',
            'both' => 'مستخدم ومشرف',
        ],
        'status' => [
            'active' => 'نشط',
            'disabled' => 'غير نشط',
        ],
        'fields' => [
            'name' => 'اسم التسعير',
            'display_name' => 'اسم العرض',
            'user_type' => 'نوع المستخدم',
            'base_amount' => 'المبلغ الأساسي (دينار جزائري)',
            'base_coins' => 'العملات الأساسية',
            'rate' => 'المعدل',
            'coins_per_dzd' => 'العملات لكل دينار',
            'status' => 'الحالة',
            'created_by' => 'أنشأ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
            'reason' => 'السبب',
        ],
        'actions' => [
            'add' => 'إضافة تسعير',
            'delete' => 'حذف التسعير',
            'activate' => 'تفعيل التسعير',
            'deactivate' => 'إلغاء تفعيل التسعير',
        ],
        'messages' => [
            'delete_confirmation' => 'هل أنت متأكد من حذف هذا التسعير؟ لا يمكن التراجع عن هذا الإجراء.',
            'activate_confirmation' => 'هل أنت متأكد من تفعيل هذا التسعير؟',
            'deactivate_confirmation' => 'هل أنت متأكد من إلغاء تفعيل هذا التسعير؟',
            'optional_reason' => 'سبب اختياري (اختياري)',
        ],
    ],

    // Coin Offers translations
    'offers' => [
        'title' => 'إدارة عروض العملات',
        'description' => 'إدارة العروض الخاصة والخصومات لشراء العملات',
        'create' => [
            'title' => 'إنشاء عرض جديد',
            'button' => 'إنشاء عرض',
        ],
        'list' => [
            'title' => 'جميع العروض',
        ],
        'status' => [
            'active' => 'نشط',
            'scheduled' => 'مجدول',
            'expired' => 'منتهي الصلاحية',
        ],
        'fields' => [
            'name' => 'اسم العرض',
            'description' => 'الوصف',
            'discount_percentage' => 'نسبة الخصم (%)',
            'pricing_name' => 'اسم  التسعير',
            'user_type' => 'نوع المستخدم',
            'date_range' => 'المدة الزمنية',
            'status' => 'الحالة',
            'created_by' => 'أنشأ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
            'coin_pricing' => 'قاعدة التسعير',
            'select_pricing' => 'اختر قاعدة التسعير',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'pricing_details' => 'تفاصيل التسعير',
            'discount_details' => 'تفاصيل الخصم',
            'status_details' => 'تفاصيل الحالة',
        ],
        'delete' => [
            'title' => 'حذف العرض',
            'message' => 'هل أنت متأكد من حذف هذا العرض؟ لا يمكن التراجع عن هذا الإجراء.',
            'confirm' => 'حذف العرض',
        ],
        'activate' => [
            'title' => 'تفعيل العرض',
            'message' => 'هل أنت متأكد من تفعيل هذا العرض؟',
            'confirm' => 'تفعيل العرض',
        ],
        'deactivate' => [
            'title' => 'إلغاء تفعيل العرض',
            'message' => 'هل أنت متأكد من إلغاء تفعيل هذا العرض؟',
            'confirm' => 'إلغاء التفعيل',
        ],
        'no_description' => 'لا يوجد وصف',
        'discount_off' => 'خصم',
        'expired_on' => 'منتهي الصلاحية في',
        'active_until' => 'نشط حتى',
        'active_offers' => 'العروض النشطة',
    ],

    // Additional payment fields
    'proof_image' => 'إثبات الدفع',
    'created_at' => 'تاريخ الإنشاء',
    'actions' => 'الإجراءات',
    'coins' => 'العملات',
    
    // Filters
    'filters' => [
        'title' => 'فلترة',
        'show_filters' => 'إظهار المرشحات',
        'search' => 'البحث',
        'search_placeholder' => 'البحث برقم المعاملة، المبلغ...',
        'status' => 'الحالة',
        'all_statuses' => 'جميع الحالات',
        'apply' => 'تطبيق الفلترة',
        'clear' => 'مسح الفلترة',
    ],
    
    // No transactions state
    'no_transactions' => [
        'title' => 'لا توجد معاملات بعد',
        'description' => 'لم تقم بأي معاملات دفع بعد. ابدأ بشراء بعض العملات.',
    ],
    
    'create_first_transaction' => 'اشترِ أول عملاتك',
    
    // Success messages
    'approved_successfully' => 'تمت الموافقة على الدفع بنجاح',
    'rejected_successfully' => 'تم رفض الدفع بنجاح',
    'cancelled_successfully' => 'تم إلغاء الدفع بنجاح',
    'coins_credited' => 'تمت إضافة العملات إلى الحساب',
    
    // Error messages
    'payment_id_required' => 'معرف الدفع مطلوب',
    'payment_not_found' => 'معاملة الدفع غير موجودة',
    'payment_not_pending' => 'الدفع ليس في حالة الانتظار',
    'observation_too_long' => 'يجب ألا تتجاوز الملاحظة 1000 حرف',
    'cannot_approve_own_payment' => 'لا يمكنك الموافقة على دفعتك الخاصة',
    'cannot_reject_own_payment' => 'لا يمكنك رفض دفعتك الخاصة',
    'cannot_cancel_own_payment' => 'لا يمكنك إلغاء دفعتك الخاصة',

    // Audit logs (admin)
    'audit' => [
        'page_title' => 'سجل تدقيق الدفع',
        'columns' => [
            'id' => 'المعرف',
            'transaction_uuid' => 'معرّف المعاملة',
            'payer' => 'المدفوع له',
            'action' => 'الإجراء',
            'admin' => 'المشرف',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'الإجراءات',
            'ip_address' => 'عنوان IP',
            'user_agent' => 'وكيل المستخدم',
        ],
        'actions' => [
            'delete' => 'حذف',
            'created' => 'تم الإنشاء',
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الرفض',
            'modified' => 'تم التعديل',
        ],
        'messages' => [
            'delete_confirmation' => 'هل أنت متأكد من حذف سجل التدقيق هذا؟',
            'deletion_disabled_warning' => 'الحذف معطل لسجلات التدقيق. سيتم توفير الأرشفة قريبًا.',
            'deletion_disabled_flash' => 'الحذف معطل. استخدم الأرشفة عند توفرها.',
            'deleted_successfully' => 'تم حذف سجل التدقيق بنجاح.',
        ],
        'detail' => [
            'transaction' => 'المعاملة',
            'changes' => 'التغييرات',
            'old_values' => 'القيم القديمة',
            'new_values' => 'القيم الجديدة',
        ],
    ],
]; 