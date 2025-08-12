<div class="p-4 bg-gray-50 border-t border-gray-400">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- User Type --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.user_type') }}</h4>
            <p class="text-sm text-gray-600">
                @php
                    $enum = App\Enums\UserTypeEnum::tryFrom($row->coinPricing?->user_type);
                @endphp
                {{ $enum?->label() ?? ucfirst($row->coinPricing?->user_type) }}
            </p>
        </div>

        {{-- Created at --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.created_at') }}</h4>
            <p class="text-sm text-gray-600">
                {{ \App\Helpers\DateTimeHelper::toLocalString($row->created_at) }}
            </p>
        </div>

        {{-- Description --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.description') }}</h4>
            <p class="text-sm text-gray-600">
                {{ $row->description ?: __('payment.offers.no_description') }}
            </p>
        </div>

        {{-- Date Range --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.date_range') }}</h4>
            <p class="text-sm text-gray-600">
                {{ \App\Helpers\DateTimeHelper::toLocalString($row->start_date) }} - {{ \App\Helpers\DateTimeHelper::toLocalString($row->end_date) }}
            </p>
        </div>

        {{-- Created By --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.created_by') }}</h4>
            <p class="text-sm text-gray-600">
                {{ $row->createdByAdmin?->name ?? 'N/A' }}
            </p>
        </div>

        {{-- Pricing Details --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.pricing_details') }}</h4>
            <p class="text-sm text-gray-600">
                {{ $row->coinPricing?->rate_description ?? 'N/A' }}
            </p>
        </div>

        {{-- Discount Details --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.discount_details') }}</h4>
            <p class="text-sm text-gray-600">
                {{ $row->discount_percentage }}% {{ __('payment.offers.discount_off') }}
            </p>
        </div>

        {{-- Status Details --}}
        <div class="space-y-1">
            <h4 class="text-sm font-medium text-gray-900">{{ __('payment.offers.fields.status_details') }}</h4>
            <div class="flex items-center gap-2">
                @include('components.ui_widgets.status-widget', [
                    'status' => $row->status,
                    'text' => __('payment.offers.status.' . $row->status),
                ])
                <span class="text-xs text-gray-500">
                    @if($row->expired)
                        {{ __('payment.offers.expired_on') }} {{ \App\Helpers\DateTimeHelper::toLocalString($row->end_date) }}
                    @else
                        {{ __('payment.offers.active_until') }} {{ \App\Helpers\DateTimeHelper::toLocalString($row->end_date) }}
                    @endif
                </span>
            </div>
        </div>
    </div>
</div> 