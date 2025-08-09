@extends('layouts.payment.master')

@section('content')
<div class="max-w-4xl mx-auto px-2 sm:px-0">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-primary text-2xl md:text-3xl font-bold">{{ __('payment.transaction_details') }}</h1>
            </div>
            <div class="flex space-x-3">
                <x-button :islink='true' :outline='true' href="{{ route('payment.transactions') }}">
                    <i class="fas fa-arrow-left me-2"></i>
                    {{ __('payment.nav.transactions') }}
                </x-button>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl md:text-2xl font-semibold text-gray-900 text-center sm:text-start">{{ __('payment.transaction_info') }}</h2>
        </div> 

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4">{{ __('payment.basic_info') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.transaction_id') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900 font-mono">{{ $paymentTransaction->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.amount') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ number_format($paymentTransaction->amount, 2) }} DZD</dd>
                        </div>
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.coins_credited') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ $paymentTransaction->coins_credited }} {{ __('payment.coins') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.payment_method') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ __('payment.payment_transaction.payment_method.' . $paymentTransaction->payment_method) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.status') }}</dt>
                            <dd class="text-sm md:text-base">
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
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.created_at') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ \App\Helpers\DateTimeHelper::toLocalString($paymentTransaction->created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Approval Information -->
                <div class="border-y border-gray-400 py-4 sm:py-0 sm:border-none">
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4">{{ __('payment.approval_info') }}</h3>
                    <dl class="space-y-3">
                        @if($paymentTransaction->approver)
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.approver') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ $paymentTransaction->approver->name }}</dd>
                        </div>
                        @endif
                        @if($paymentTransaction->approved_at)
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.approved_at') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ \App\Helpers\DateTimeHelper::toLocalString($paymentTransaction->approved_at) }}</dd>
                        </div>
                        @endif
                        @if($paymentTransaction->accountant_observation)
                        <div>
                            <dt class="text-sm md:text-base font-medium text-gray-500">{{ __('payment.payment_transaction.fields.accountant_observation') }}</dt>
                            <dd class="text-sm md:text-base text-gray-900">{{ $paymentTransaction->accountant_observation }}</dd>
                        </div>
                        @endif
                        @if($canOrderReview && !$review)
                        <div x-data>
                            <x-button
                                name="myModal"
                                color_type="warning"
                                size="sm"
                                :outline="true"
                                x-on:click="$dispatch('open-modal', { detail: 'order-review-modal' })">
                                <x-slot:icon>
                                    <i class="fa-solid fa-rotate text-base me-2"></i>
                                </x-slot:icon>
                                {{__("payment.review.actions.order")}}
                            </x-button>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Proof Image -->
            @if($paymentTransaction->proof_image_path)
            <div class="mt-6">
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-4">{{ __('payment.payment_transaction.fields.proof_image') }}</h3>
                <div class="max-w-md">
                    <img src="{{ route('transactions.proof', ['transaction' => $paymentTransaction->id]) }}" 
                         alt="Payment Proof" 
                         class="rounded-lg shadow-sm max-w-full h-auto">
                </div>
            </div>
            @endif

            {{-- Review Details --}}
            @if($review)
            <div class="my-3 md:mt-6 md:mb-3 p-2 md:px-4 md:py-2 bg-white rounded-lg shadow-sm border border-gray-300">
                <h2 class="text-xl md:text-2xl font-semibold text-gray-900 text-center sm:text-start">{{ __('payment.review.detail.review_info') }}</h2>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div class="space-y-2">
                        <p><span class="font-medium">{{ __('payment.review.detail.status') }}:</span> {{ __('payment.review.status.' . $review->status) }}</p>
                        <p><span class="font-medium">{{ __('payment.created_at') }}:</span> {{ \App\Helpers\DateTimeHelper::toLocalString($review->created_at) }}</p>
                    </div>
                    <div class="space-y-2">
                        <p><span class="font-medium">{{ __('payment.review.detail.request_reason') }}:</span> {{ $review->request_reason }}</p>
                    </div>
                    <div class="space-y-2">
                        <p><span class="font-medium">{{ __('payment.review.detail.reviewed_by') }}:</span> {{ $review->reviewer?->name ?? '—' }}</p>
                        <p><span class="font-medium">{{ __('payment.review.detail.reviewed_at') }}:</span> {{ $review->reviewed_at ? \App\Helpers\DateTimeHelper::toLocalString($review->reviewed_at) : '—' }}</p>
                        <p><span class="font-medium">{{ __('payment.review.detail.observation') }}:</span> {{ $review->review_observation ?? __('payment.review.detail.no_observation') }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>


@if($canOrderReview)
    {{-- Order Review Modal --}}
    <x-modal name="order-review-modal" title="{{ __('payment.review.actions.order') }}" :show="$errors->hasBag('orderReview')">
        <x-slot:modalhead>
            {{ __('payment.review.actions.order') }}
        </x-slot>
        <form id="order-review-form" method="post" action="{{ route('payment.reviews.order') }}" class="space-y-2">
            @csrf
            @method('post')

            <input type="hidden" name="transaction_id" value="{{ $paymentTransaction->id }}" />

            <div>
                <x-input-label for="order_review_reason" :value="__('payment.pricing.fields.reason')" />
                <x-text-area id="order_review_reason" name="reason" class="mt-1 block w-full" rows="3" placeholder="{{ __('payment.payment_transaction.messages.required_observation') }}" required />
                <x-input-error :messages="$errors->orderReview->get('reason')" class="mt-2" />
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="order-review-form" color_type="primary">{{ __('payment.review.actions.order') }}</x-button>
            </div>
        </x-slot:modalfooter>
    </x-modal>
@endif
@endsection 