@extends('layouts.payment.master')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('payment.nav.transactions') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('payment.transactions_description') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-2">
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <div class="text-center md:text-start flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.pending') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statusCounts['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-2">
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                    <i class="fas fa-check text-lg"></i>
                </div>
                <div class="text-center md:text-start flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.approved') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statusCounts['approved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-2">
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                    <i class="fas fa-times text-lg"></i>
                </div>
                <div class="text-center md:text-start flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.rejected') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statusCounts['rejected'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-2">
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
                    <i class="fas fa-ban text-lg"></i>
                </div>
                <div class="text-center md:text-start flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.cancelled') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statusCounts['cancelled'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Container -->
    <div class="flex justify-start items-center my-2 p-4 shadow-sm max-w-lg gap-2">
        <div x-data>
            <x-button
                name="filters"
                color_type="secondary"
                x-on:click="$dispatch('open-modal', { detail: 'filters' })">
                <x-slot:icon>
                    <i class="fa-solid fa-filter me-2"></i>
                </x-slot:icon>
                {{__("payment.filters.title")}}
            </x-button>
        </div>
    </div>

    <!-- Transaction Cards -->
    <div id="transactions-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($transactions as $transaction)
            @php
                $cardColor = match($transaction->status) {
                    'pending' => 'primary',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled' => 'warning',
                };
            @endphp
            <x-collapsible-card 
                :title="'Transaction #' . substr($transaction->uuid, 0, 8)"
                :isopen="false"
                :type="$cardColor">
                
                <div class="space-y-2">
                    <!-- UUID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.transaction_id') }}</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $transaction->uuid }}</p>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.amount') }}</label>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($transaction->amount, 2) }} DZD</p>
                    </div>

                    <!-- Coins -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.coins_credited') }}</label>
                        <p class="text-lg font-bold text-blue-600">
                            {{ $transaction->coins_credited }} 
                            <i class="fa-solid fa-coins text-warning"></i>
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.status') }}</label>
                        @php
                            $statusEnum = \App\Enums\PaymentStatusEnum::tryFrom($transaction->status);
                            $statusClass = $statusEnum ? $statusEnum->badgeClass() : 'bg-gray-500 text-white';
                            $statusText = $statusEnum ? $statusEnum->label() : ucfirst($transaction->status);
                        @endphp
                        <x-status-widget :status="$transaction->status" :text="__('payment.payment_transaction.status.' . $transaction->status)" />
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="my-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.created_at') }}</label>
                            <p class="text-sm text-gray-900">{{ \App\Helpers\DateTimeHelper::toLocalString($transaction->created_at) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('payment.payment_transaction.fields.payment_method') }}</label>
                            <p class="text-sm text-gray-900">{{ __('payment.payment_transaction.payment_method.' . $transaction->payment_method) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-center">
                    <x-button
                        :islink="true"
                        href="{{ route('payment.transactions.show', ['paymentTransaction' => $transaction->uuid]) }}"
                        size="sm"
                        >
                        <x-slot:icon>
                            <i class="fa-solid fa-eye me-2"></i>
                        </x-slot:icon>
                        {{ __('payment.payment_transaction.fields.view_details') }}
                    </x-button>
                </div>
            </x-collapsible-card>
        @empty
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <i class="fas fa-receipt text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('payment.no_transactions.title') }}</h3>
                <p class="text-gray-600">{{ __('payment.no_transactions.description') }}</p>
                <a href="{{ route('payment.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">
                    {{ __('payment.create_first_transaction') }}
                </a>
            </div>
        @endforelse
    </div>
</div>

<div class="mt-6">
    <x-pagination :paginator="$transactions" />
</div>

<x-modal name="filters" title="My Modal" :show="$errors->hasBag('deleteAdmin')" :inputValue="old('id')">
    <x-slot:modalhead>
        {{__("payment.filters.title")}}
    </x-slot>
    <form id="filters-form" method="GET" action="{{ route('payment.transactions') }}">
        <!-- Filters Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <!-- Search Box -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('payment.filters.search') }}
                </label>
                <input type="text" 
                    name="search"
                    id="search" 
                    value="{{ request('search') }}"
                    placeholder="{{ __('payment.filters.search_placeholder') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('payment.filters.status') }}
                </label>
                <select name="status" 
                        id="status-filter" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">{{ __('payment.filters.all_statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('payment.payment_transaction.status.pending') }}</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('payment.payment_transaction.status.approved') }}</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('payment.payment_transaction.status.rejected') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('payment.payment_transaction.status.cancelled') }}</option>
                </select>
            </div>
        </div>
    </form>
    <x-slot:modalfooter>
        <!-- Filter Actions -->
        <div class="flex gap-2 justify-end">
            <x-button
                type="submit"
                color_type="primary"
                form="filters-form"
                >
                <x-slot:icon>
                    <i class="fas fa-search me-2"></i>
                </x-slot:icon>
                {{ __('payment.filters.apply') }}
            </x-button>

            <x-button
                :islink="true"
                href="{{ route('payment.transactions') }}"
                color_type="secondary"
                >
                <x-slot:icon>
                    <i class="fas fa-times me-2"></i>
                </x-slot:icon>
                {{ __('payment.filters.clear') }}
            </x-button>
        </div>
    </x-slot>
</x-modal>
@endsection 