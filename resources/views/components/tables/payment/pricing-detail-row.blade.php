<div class="p-4 bg-gray-50 border-t border-gray-400">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        
        <!-- Display Name -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.display_name') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ $row->display_name ?: __('payment.offers.no_description') }}
            </p>
        </div>

        <!-- Coins per DZD -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.coins_per_dzd') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ number_format($row->coins_per_dzd, 2) }} coins/DZD
            </p>
        </div>

        <!-- Created By -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.created_by') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ $row->createdByAdmin?->name ?? 'N/A' }}
            </p>
        </div>

        <!-- Created At -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.created_at') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ \App\Helpers\DateTimeHelper::toLocalString($row->created_at) }}
            </p>
        </div>

        <!-- User Type Label -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.user_type') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                @php
                    $enum = \App\Enums\UserTypeEnum::tryFrom($row->user_type);
                @endphp
                {{ $enum?->label() ?? ucfirst($row->user_type) }}
            </p>
        </div>

        <!-- Status Details -->
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('payment.pricing.fields.status') }}
            </h4>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                @php
                    $status = $row->is_active ? 'active' : 'disabled';
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                    {{ __('payment.pricing.status.' . $status) }}
                </span>
            </p>
        </div>

    </div>

    <!-- Additional Information -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Base Amount -->
            <div class="space-y-1">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('payment.pricing.fields.base_amount') }}
                </h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ number_format($row->base_amount, 2) }} DZD
                </p>
            </div>

            <!-- Base Coins -->
            <div class="space-y-1">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('payment.pricing.fields.base_coins') }}
                </h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ number_format($row->base_coins) }} coins
                </p>
            </div>

        </div>
    </div>

    <!-- Active Offers Section -->
    @if($row->offers->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                {{ __('payment.offers.active_offers') }}
            </h4>
            <div class="space-y-2">
                @php
                    $offers = $row->offers->where('expired', false);
                @endphp
                @foreach($offers as $offer)
                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded border">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $offer->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $offer->description ?: __('payment.offers.no_description') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">
                                {{ $offer->discount_percentage }}% {{ __('payment.offers.discount_off') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \App\Helpers\DateTimeHelper::toLocalString($offer->start_date) }} - {{ \App\Helpers\DateTimeHelper::toLocalString($offer->end_date) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div> 