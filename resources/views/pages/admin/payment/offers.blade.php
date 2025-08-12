@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('payment.offers.title')}}
    @stop
@endsection

@section('page_title')
    {{ __('payment.offers.title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('payment.offers.title')}}</h1>
    </div>

    @can("manage payment_offer")
        {{-- Delete Offer Modal --}}
        <x-modal name="delete-offer-modal" title="{{__('payment.offers.delete.title')}}" :show="$errors->hasBag('deleteOffer')">
            <x-slot:modalhead>
                {{__('payment.offers.delete.title')}}
            </x-slot>
            <form id="delete-form" method="post" action="{{ route('admin.payment.coin_offers.destroy') }}" class="space-y-2">
                @csrf
                @method('DELETE')

                <div>
                    <input type="hidden" name="offer_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.offers.delete.message')}}
                    </p>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('payment.offers.delete.confirm') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

    @endcan
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:coin-offer-table/>
    </div>
@endsection 