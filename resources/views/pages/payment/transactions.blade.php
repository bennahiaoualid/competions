@extends('layouts.payment.master')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('payment.nav.transactions') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('payment.transactions_description') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.pending') }}</p>
                    <p class="text-2xl font-bold text-gray-900" id="pending-count">-</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.approved') }}</p>
                    <p class="text-2xl font-bold text-gray-900" id="approved-count">-</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-times text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.rejected') }}</p>
                    <p class="text-2xl font-bold text-gray-900" id="rejected-count">-</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-ban text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('payment.stats.cancelled') }}</p>
                    <p class="text-2xl font-bold text-gray-900" id="cancelled-count">-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <livewire:payment-user-transaction-table />
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update stats counts (this would be populated by the backend)
    // For now, we'll leave them as placeholders
    const stats = {
        pending: 0,
        approved: 0,
        rejected: 0,
        cancelled: 0
    };
    
    // Update the stats display
    document.getElementById('pending-count').textContent = stats.pending;
    document.getElementById('approved-count').textContent = stats.approved;
    document.getElementById('rejected-count').textContent = stats.rejected;
    document.getElementById('cancelled-count').textContent = stats.cancelled;
});
</script>
@endsection 