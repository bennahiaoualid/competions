@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.payment.transactions')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.payment.transactions') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.payment.transactions')}}</h1>
    </div>

    {{-- Approve Payment Modal --}}
    @can('manage payment')
        <x-modal name="approve-payment-modal" title="{{__('payment.payment_transaction.actions.approve')}}" :show="$errors->hasBag('approvePayment')">
            <x-slot:modalhead>
                {{__('payment.payment_transaction.actions.approve')}}
            </x-slot>
            <form id="approve-form" method="post" action="{{ route('admin.payment.approve') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="transaction_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.payment_transaction.messages.approve_confirmation')}}
                    </p>
                </div>

                <div>
                    <x-input-label for="approve_observation" :value="__('payment.payment_transaction.fields.observation')" />
                    <x-text-area id="approve_observation" name="observation" class="mt-1 block w-full" rows="3" placeholder="{{__('payment.payment_transaction.messages.optional_observation')}}" />
                    <x-input-error :messages="$errors->approvePayment->get('observation')" class="mt-2" />
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="approve-form" color_type="success" >{{ __('payment.payment_transaction.actions.approve') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{-- Reject Payment Modal --}}
        <x-modal name="reject-payment-modal" title="{{__('payment.payment_transaction.actions.reject')}}" :show="$errors->hasBag('rejectPayment')">
            <x-slot:modalhead>
                {{__('payment.payment_transaction.actions.reject')}}
            </x-slot>
            <form id="reject-form" method="post" action="{{ route('admin.payment.reject') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="transaction_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.payment_transaction.messages.reject_confirmation')}}
                    </p>
                </div>

                <div>
                    <x-input-label for="reject_observation" :value="__('payment.payment_transaction.fields.observation')" />
                    <x-text-area id="reject_observation" name="observation" class="mt-1 block w-full" rows="3" placeholder="{{__('payment.payment_transaction.messages.required_observation')}}" required />
                    <x-input-error :messages="$errors->rejectPayment->get('observation')" class="mt-2" />
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="reject-form" color_type="danger" >{{ __('payment.payment_transaction.actions.reject') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{-- Cancel Payment Modal --}}
        <x-modal name="cancel-payment-modal" title="{{__('payment.payment_transaction.actions.cancel')}}" :show="$errors->hasBag('cancelPayment')">
            <x-slot:modalhead>
                {{__('payment.payment_transaction.actions.cancel')}}
            </x-slot>
            <form id="cancel-form" method="post" action="{{ route('admin.payment.cancel') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="transaction_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.payment_transaction.messages.cancel_confirmation')}}
                    </p>
                </div>

                <div>
                    <x-input-label for="cancel_observation" :value="__('payment.payment_transaction.fields.observation')" />
                    <x-text-area id="cancel_observation" name="observation" class="mt-1 block w-full" rows="3" placeholder="{{__('payment.payment_transaction.messages.optional_observation')}}" />
                    <x-input-error :messages="$errors->cancelPayment->get('observation')" class="mt-2" />
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="cancel-form" color_type="warning" >{{ __('payment.payment_transaction.actions.cancel') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endcan
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:payment-transaction-table/>
    </div>
@endsection 