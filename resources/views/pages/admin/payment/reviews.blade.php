@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{ __('payment.review.page_title') }}
    @stop
@endsection

@section('page_title')
    {{ __('payment.review.page_title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{ __('payment.review.page_title') }}</h1>
    </div>

    @can('manage payment')
        {{-- Approve Review Modal --}}
        <x-modal name="approve-review-modal" title="{{ __('payment.review.actions.approve') }}" :show="$errors->hasBag('approveReview')">
            <x-slot:modalhead>
                {{ __('payment.review.actions.approve') }}
            </x-slot>
            <form id="approve-review-form" method="post" action="{{ route('admin.payment.reviews.approve') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="review_id" x-model="inputValue" />
                    <p class="my-1">
                        {{ __('payment.review.messages.approve_confirmation') }}
                    </p>
                </div>

                <div>
                    <x-input-label for="approve_observation" :value="__('payment.payment_transaction.fields.observation')" />
                    <x-text-area id="approve_observation" name="observation" class="mt-1 block w-full" rows="3" placeholder="{{ __('payment.payment_transaction.messages.optional_observation') }}" />
                    <x-input-error :messages="$errors->approveReview->get('observation')" class="mt-2" />
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="approve-review-form" color_type="success">{{ __('payment.review.actions.approve') }}</x-button>
                </div>
            </x-slot:modalfooter>
        </x-modal>

        {{-- Reject Review Modal --}}
        <x-modal name="reject-review-modal" title="{{ __('payment.review.actions.reject') }}" :show="$errors->hasBag('rejectReview')">
            <x-slot:modalhead>
                {{ __('payment.review.actions.reject') }}
            </x-slot>
            <form id="reject-review-form" method="post" action="{{ route('admin.payment.reviews.reject') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="review_id" x-model="inputValue" />
                    <p class="my-1">
                        {{ __('payment.review.messages.reject_confirmation') }}
                    </p>
                </div>

                <div>
                    <x-input-label for="reject_observation" :value="__('payment.payment_transaction.fields.observation')" />
                    <x-text-area id="reject_observation" name="observation" class="mt-1 block w-full" rows="3" placeholder="{{ __('payment.payment_transaction.messages.required_observation') }}" required />
                    <x-input-error :messages="$errors->rejectReview->get('observation')" class="mt-2" />
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="reject-review-form" color_type="danger">{{ __('payment.review.actions.reject') }}</x-button>
                </div>
            </x-slot:modalfooter>
        </x-modal>
    @endcan

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:payment-review-request-table />
    </div>
@endsection 