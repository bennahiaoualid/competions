<div class="px-4 py-3">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.audit.detail.transaction') }}</h4>
            <dl class="space-y-2">
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.transaction_uuid') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $row->paymentTransaction?->uuid ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.payer') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $row->paymentTransaction?->payable?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.admin') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $row->admin?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.action') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $row->action }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.created_at') }}</dt>
                    <dd class="text-sm text-gray-900">{{ optional($row->created_at)->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.audit.detail.changes') }}</h4>
            <div class="bg-gray-50 rounded p-3 overflow-x-auto">
                <div class="mb-3">
                    <div class="text-xs text-gray-500 mb-1">{{ __('payment.audit.detail.old_values') }}</div>
                    <pre class="text-xs text-gray-800 whitespace-pre-wrap">{{ json_encode($row->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <div class="text-xs text-gray-500 mb-1">{{ __('payment.audit.detail.new_values') }}</div>
                    <pre class="text-xs text-gray-800 whitespace-pre-wrap">{{ json_encode($row->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.ip_address') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $row->ip_address ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">{{ __('payment.audit.columns.user_agent') }}</dt>
                    <dd class="text-sm text-gray-900 truncate">{{ $row->user_agent ?? 'N/A' }}</dd>
                </div>
            </div>
        </div>
    </div>
</div> 