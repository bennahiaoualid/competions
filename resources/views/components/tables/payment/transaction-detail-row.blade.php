<div class="bg-gray-50 p-4 border-t border-gray-400">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Payment Method -->
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.payment_method') }}</h4>
            <p class="text-sm text-gray-900">{{ $row->payment_method }}</p>
        </div>

        <!-- Coins Credited -->
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.coins_credited') }}</h4>
            <p class="text-sm text-gray-900">{{ number_format($row->coins_credited) }} {{ __('payment.payment_transaction.fields.coins_credited') }}</p>
        </div>

        <!-- Payer Type -->
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.payer_type') }}</h4>
            <p class="text-sm text-gray-900">{{ $row->payer_type }}</p>
        </div>

        <!-- Approver -->
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.approver') }}</h4>
            <p class="text-sm text-gray-900">{{ $row->approver_name }}</p>
        </div>

        <!-- Approved At -->
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.approved_at') }}</h4>
            <p class="text-sm text-gray-900">{{ $row->approved_at_formatted }}</p>
        </div>

        <!-- Accountant Observation -->
        <div class="bg-white p-3 rounded-lg shadow-sm md:col-span-2 lg:col-span-3">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.accountant_observation') }}</h4>
            <p class="text-sm text-gray-900">{{ $row->accountant_observation }}</p>
        </div>

        <!-- Proof Image -->
        @if($row->proof_image_url)
            <div class="bg-white p-3 rounded-lg shadow-sm md:col-span-2 lg:col-span-3">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('payment.payment_transaction.fields.proof_image') }}</h4>
                <div class="flex justify-center">
                    <img src="{{ $row->proof_image_url }}" 
                        alt="{{ __('payment.payment_transaction.fields.proof_image') }}" 
                        class="max-w-full max-h-48 object-contain rounded-lg shadow-sm border border-gray-200">
                </div>
            </div>
        @endif
    </div>
</div> 