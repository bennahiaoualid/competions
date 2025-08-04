@extends('layouts.payment.master')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('payment.transaction_details') }}</h1>
                <p class="mt-2 text-gray-600">{{ __('payment.transaction_id') }}: {{ $paymentTransaction->uuid }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('payment.transactions') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    {{ __('payment.nav.transactions') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('payment.transaction_info') }}</h2>
        </div> 

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('payment.basic_info') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.transaction_id') }}</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $paymentTransaction->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.amount') }}</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($paymentTransaction->amount, 2) }} DZD</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.coins_credited') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $paymentTransaction->coins_credited }} {{ __('payment.coins') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.payment_method') }}</dt>
                            <dd class="text-sm text-gray-900">{{ __('payment.payment_transaction.payment_method.' . $paymentTransaction->payment_method) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.status') }}</dt>
                            <dd class="text-sm">
                                @php
                                    $statusEnum = \App\Enums\PaymentStatusEnum::tryFrom($paymentTransaction->status);
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($paymentTransaction->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($paymentTransaction->status === 'approved') bg-green-100 text-green-800
                                    @elseif($paymentTransaction->status === 'rejected') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $statusEnum?->label() ?? ucfirst($paymentTransaction->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.created_at') }}</dt>
                            <dd class="text-sm text-gray-900">{{ \App\Helpers\DateTimeHelper::toLocalString($paymentTransaction->created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Approval Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('payment.approval_info') }}</h3>
                    <dl class="space-y-3">
                        @if($paymentTransaction->approver)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.approver') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $paymentTransaction->approver->name }}</dd>
                        </div>
                        @endif
                        @if($paymentTransaction->approved_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.approved_at') }}</dt>
                            <dd class="text-sm text-gray-900">{{ \App\Helpers\DateTimeHelper::toLocalString($paymentTransaction->approved_at) }}</dd>
                        </div>
                        @endif
                        @if($paymentTransaction->accountant_observation)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('payment.payment_transaction.fields.accountant_observation') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $paymentTransaction->accountant_observation }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Proof Image -->
            @if($paymentTransaction->proof_image_path)
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('payment.payment_transaction.fields.proof_image') }}</h3>
                <div class="max-w-md">
                    <img src="{{ asset('storage/' . $paymentTransaction->proof_image_path) }}" 
                         alt="Payment Proof" 
                         class="rounded-lg shadow-sm max-w-full h-auto">
                </div>
            </div>
            @endif

            <!-- Audit Log -->
            @if($paymentTransaction->auditLogs->count() > 0)
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('payment.audit_log') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="space-y-3">
                        @foreach($paymentTransaction->auditLogs->sortBy('created_at') as $log)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">
                                    <span class="font-medium">{{ $log->admin->name }}</span>
                                    {{ __('payment.audit_action_' . $log->action) }}
                                </p>
                                <p class="text-xs text-gray-500">{{ \App\Helpers\DateTimeHelper::toLocalString($log->created_at) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection 