<div class="p-4 text-sm border-t border-gray-400">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="space-y-2">
            <h4 class="text-lg mb-2">{{ __('payment.transaction_details') }}</h4>
            <p><span class="text-base font-medium">{{ __('payment.payment_transaction.fields.transaction_id') }}:</span> {{ $row->paymentTransaction?->uuid ?? 'N/A' }}</p>
            <p><span class="text-base font-medium">{{ __('payment.payment_transaction.fields.payer') }}:</span> {{ $row->paymentTransaction?->payable?->name ?? 'N/A' }}</p>
            <p><span class="text-base font-medium">{{ __('payment.payment_transaction.fields.amount') }}:</span> {{ number_format($row->paymentTransaction?->amount ?? 0, 2) }} DZD</p>
            <p><span class="text-base font-medium">{{ __('payment.payment_transaction.fields.coins_credited') }}:</span> {{ $row->paymentTransaction?->coins_credited ?? 'N/A' }}</p>
        </div>
        <div>
            <h4 class="text-lg mb-2">{{ __('payment.review.detail.review_info') }}</h4>
            <p>
                <span class="text-base font-medium">{{ __('payment.review.detail.status') }}:</span> 
                {{ __('payment.review.status.' . $row->status) }}
            </p>
            <p>
                <span class="text-base font-medium">{{ __('payment.review.detail.request_reason') }}:</span> 
                {{ $row->request_reason }}
            </p>
            <p>
                @php
                    $name = $row->reviewer?->name;
                @endphp
                <span class="text-base font-medium">{{ __('payment.review.detail.reviewed_by') }}:</span> 
                @if(!$name && $row->status === 'rejected')
                    {{ __('payment.review.detail.auto_rejected') }}
                @else
                    {{ $name ?? '—' }}
                @endif
            </p>
            <p>
                <span class="text-base font-medium">{{ __('payment.review.detail.reviewed_at') }}:</span> 
                {{ optional($row->reviewed_at)->format('Y-m-d H:i') ?? '—' }}
            </p>
        </div>
        <div>
            <h4 class="text-lg mb-2">{{ __('payment.review.detail.observation') }}</h4>
            <p>{{ $row->review_observation ?? __('payment.review.detail.no_observation') }}</p>
        </div>
    </div>
</div> 