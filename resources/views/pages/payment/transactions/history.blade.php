@extends('layouts.payment.master')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6 text-center md:text-start md:px-4 space-y-2">
        <h2 class="text-xl md:text-2xl font-bold text-primary">{{ __('payment.transaction_history.title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('payment.transaction_history.description') }}</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                    <i class="fas fa-plus text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.transaction_history.summary.total_earned') }}</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($summary['total_earned']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                    <i class="fas fa-minus text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.transaction_history.summary.total_spent') }}</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($summary['total_spent']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-list text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.transaction_history.summary.total_transactions') }}</p>
                    <p class="text-xl font-bold text-blue-600">{{ number_format($summary['total_transactions']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.transaction_history.summary.earn_transactions') }}</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($summary['earn_transactions']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.transaction_history.summary.spend_transactions') }}</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($summary['spend_transactions']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Container -->
    <div class="flex justify-start items-center my-2 p-4 shadow-sm max-w-lg gap-2 mb-6">
        <div x-data>
            <x-button
                name="filters"
                color_type="secondary"
                x-on:click="$dispatch('open-modal', { detail: 'filters' })">
                <x-slot:icon>
                    <i class="fa-solid fa-filter me-2"></i>
                </x-slot:icon>
                {{ __('payment.transaction_history.filters.title') }}
            </x-button>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ __('payment.transaction_history.title') }}</h3>
        </div>
        
        @if($transactions->count() > 0)
            <!-- Mobile Cards View -->
            <div class="block lg:hidden">
                @foreach($transactions as $transaction)
                    <div class="border-b border-gray-200 last:border-b-0">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $transaction->isEarn() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $transaction->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium 
                                    {{ $transaction->isEarn() ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </div>
                            
                            <div class="space-y-1">
                                <p class="text-sm text-gray-900">
                                    <span class="font-medium">{{ __('payment.transaction_history.table.detail') }}:</span>
                                    {{ $transaction->detail_label }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $transaction->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-start text-md font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('payment.transaction_history.table.date') }}
                                </th>
                                <th class="px-6 py-3 text-start text-md font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('payment.transaction_history.table.type') }}
                                </th>
                                <th class="px-6 py-3 text-start text-md font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('payment.transaction_history.table.detail') }}
                                </th>
                                <th class="px-6 py-3 text-start text-md font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('payment.transaction_history.table.coins') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div>
                                            <div class="font-medium">{{ $transaction->created_at->format('M d, Y') }}</div>
                                            <div class="text-gray-500">{{ $transaction->created_at->format('H:i') }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $transaction->isEarn() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->detail_label }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                        {{ $transaction->isEarn() ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->formatted_amount }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-700">
                        {{ __('payment.transaction_history.pagination.showing') }} {{ $transactions->firstItem() }} {{ __('payment.transaction_history.pagination.to') }} {{ $transactions->lastItem() }} {{ __('payment.transaction_history.pagination.of') }} {{ $transactions->total() }} {{ __('payment.transaction_history.pagination.results') }}
                    </div>
                        <div>
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="px-4 sm:px-6 py-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-receipt text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('payment.transaction_history.empty_state.no_transactions') }}</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('payment.transaction_history.empty_state.no_transactions_description') }}
                </p>
                @if(request()->hasAny(['type', 'detail', 'date_from', 'date_to']))
                    <div class="mt-6">
                        <x-button 
                            :islink="true"
                            href="{{ route('payment.transactions.history') }}" 
                            color_type="primary">
                            {{ __('payment.transaction_history.empty_state.clear_all_filters') }}
                        </x-button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Filters Modal -->
<x-modal name="filters" title="{{ __('payment.transaction_history.filters.title') }}" :show="false">
    <x-slot:modalhead>
        {{ __('payment.transaction_history.filters.title') }}
    </x-slot>
    
    <form id="filters-form" method="GET" action="{{ route('payment.transactions.history') }}" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Transaction Type Filter -->
            <div>
                <x-input-label for="type" :value="__('payment.transaction_history.filters.type')" />
                <x-form.select-box 
                    id="type"
                    name="type" 
                    :placeholder="__('payment.transaction_history.filters.type')"
                    :options="[
                        ['value' => '', 'text' => __('payment.transaction_history.transaction_types.all')],
                        ['value' => 'earn', 'text' => __('payment.transaction_history.transaction_types.earn')],
                        ['value' => 'spend', 'text' => __('payment.transaction_history.transaction_types.spend')]
                    ]"
                    class="w-full"
                />
            </div>
            
            <!-- Transaction Detail Filter -->
            <div>
                <x-input-label for="detail" :value="__('payment.transaction_history.filters.detail')" />
                @php
                    $options = [
                        ['value' => '', 'text' => __('payment.transaction_history.coin_transaction_types.all')]
                    ];
                    foreach($detailTypes as $detailType){
                        $options[] = ['value' => $detailType, 'text' => App\Enums\CoinTransactionTypeEnum::from($detailType)->getLabel()];
                    }
                @endphp
                <x-form.select-box 
                    id="detail"
                    name="detail" 
                    :placeholder="__('payment.transaction_history.filters.detail')"
                    :options="$options"
                    class="w-full"
                />
            </div>
            
            <!-- Date From Filter -->
            <div>
                <x-input-label for="date_from" :value="__('payment.transaction_history.filters.date_from')" />
                <x-text-input 
                    id="date_from"
                    type="text" 
                    name="date_from" 
                    :value="request('date_from')"
                    class="w-full date-input"
                    :placeholder="__('payment.transaction_history.filters.date_from')"
                />
            </div>
            
            <!-- Date To Filter -->
            <div>
                <x-input-label for="date_to" :value="__('payment.transaction_history.filters.date_to')" />
                <x-text-input 
                    id="date_to"
                    type="text" 
                    name="date_to" 
                    :value="request('date_to')"
                    class="w-full date-input"
                    :placeholder="__('payment.transaction_history.filters.date_to')"
                />
            </div>
        </div>
    </form>
    
    <x-slot:modalfooter>
        <div class="flex gap-2 justify-end">
            <x-button
                type="submit"
                color_type="primary"
                form="filters-form"
                >
                <x-slot:icon>
                    <i class="fas fa-filter me-2"></i>
                </x-slot:icon>
                {{ __('payment.transaction_history.filters.apply_filters') }}
            </x-button>

            <x-button
                :islink="true"
                href="{{ route('payment.transactions.history') }}"
                color_type="secondary"
                >
                <x-slot:icon>
                    <i class="fas fa-times me-2"></i>
                </x-slot:icon>
                {{ __('payment.transaction_history.filters.clear_filters') }}
            </x-button>
        </div>
    </x-slot>
</x-modal>

@endsection

@section('custom_js')
<script>
    flatpickr(".date-input", {
        enableTime: false,
        dateFormat: "Y-m-d",
        locale: "en"
    });
</script>
@endsection 