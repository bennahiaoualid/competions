@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.payment.pricing')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.payment.pricing') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.payment.pricing')}}</h1>
        @can("manage coin_pricing")
            <div x-data>
                <x-button
                    name="add_coin_pricing_modal"
                    x-on:click="$dispatch('open-modal', { detail: 'add_coin_pricing_modal' })">
                    <x-slot:icon>
                        <i class="fa-solid fa-plus me-2"></i>
                    </x-slot:icon>
                    {{__("form.actions.add")}}
                </x-button>
            </div>
        @endcan
    </div>

    {{-- Add Pricing Modal --}}
    @can("manage coin_pricing")
        <x-modal name="add_coin_pricing_modal" title="{{__('payment.pricing.actions.add')}}" :show="$errors->hasBag('createCoinPricing')">
            <x-slot:modalhead>
                {{__("payment.pricing.actions.add")}}
            </x-slot>
            <form id="add-form-pricing" method="post" action="{{ route('admin.payment.coin_pricing.store') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <x-input-label for="name" :value="__('payment.pricing.fields.name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Standard User Package" />
                    <x-input-error :messages="$errors->createCoinPricing->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="display_name" :value="__('payment.pricing.fields.display_name')" />
                    <x-text-input id="display_name" name="display_name" type="text" class="mt-1 block w-full" placeholder="Standard user package with basic features" />
                    <x-input-error :messages="$errors->createCoinPricing->get('display_name')" class="mt-2" />
                </div>

                <div>
                    @php
                        $options = [];
                        foreach ($types as $type) {
                            $options[] = ['value' => $type, 'text' => __('payment.pricing.user_type.' . $type), 'selected' => false];
                        }
                    @endphp
                    <x-input-label for="user_type" :value="__('payment.pricing.fields.user_type')" />
                    <x-form.select-box id="user_type" name="user_type" :options="$options">
                    </x-form.select-box>
                    <x-input-error :messages="$errors->createCoinPricing->get('user_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="base_amount" :value="__('payment.pricing.fields.base_amount')" />
                    <x-text-input id="base_amount" name="base_amount" type="number" step="1" min="10" max="10000" class="mt-1 block w-full" placeholder="100" />
                    <x-input-error :messages="$errors->createCoinPricing->get('base_amount')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="base_coins" :value="__('payment.pricing.fields.base_coins')" />
                    <x-text-input id="base_coins" name="base_coins" type="number" min="1" max="1000000" class="mt-1 block w-full" placeholder="50" />
                    <x-input-error :messages="$errors->createCoinPricing->get('base_coins')" class="mt-2" />
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="add-form-pricing" color_type="success" >{{ __('form.actions.save') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{-- Delete Pricing Modal --}}
        <x-modal name="delete-pricing-modal" title="{{__('payment.pricing.actions.delete')}}" :show="$errors->hasBag('deletePricing')">
            <x-slot:modalhead>
                {{__('payment.pricing.actions.delete')}}
            </x-slot>
            <form id="delete-form" method="post" action="{{ route('admin.payment.coin_pricing.destroy') }}" class="space-y-2">
                @csrf
                @method('DELETE')

                <div>
                    <input type="hidden" name="pricing_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.pricing.messages.delete_confirmation')}}
                    </p>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('payment.pricing.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{-- Activate Pricing Modal --}}
        <x-modal name="activate-pricing-modal" title="{{__('payment.pricing.actions.activate')}}" :show="$errors->hasBag('activatePricing')">
            <x-slot:modalhead>
                {{__('payment.pricing.actions.activate')}}
            </x-slot>
            <form id="activate-form" method="post" action="{{ route('admin.payment.coin_pricing.activate') }}" class="space-y-2">
                @csrf
                @method('PATCH')

                <div>
                    <input type="hidden" name="pricing_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.pricing.messages.activate_confirmation')}}
                    </p>
                </div>


            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="activate-form" color_type="success" >{{ __('payment.pricing.actions.activate') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{-- Deactivate Pricing Modal --}}
        <x-modal name="deactivate-pricing-modal" title="{{__('payment.pricing.actions.deactivate')}}" :show="$errors->hasBag('deactivatePricing')">
            <x-slot:modalhead>
                {{__('payment.pricing.actions.deactivate')}}
            </x-slot>
            <form id="deactivate-form" method="post" action="{{ route('admin.payment.coin_pricing.deactivate') }}" class="space-y-2">
                @csrf
                @method('PATCH')

                <div>
                    <input type="hidden" name="pricing_id" x-model="inputValue"/>
                    <p class="my-1">
                        {{__('payment.pricing.messages.deactivate_confirmation')}}
                    </p>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="deactivate-form" color_type="warning" >{{ __('payment.pricing.actions.deactivate') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endcan

    @can("manage payment_offer")
        <x-modal name="add_coin_offer_modal" title="{{__('payment.offers.create.title')}}" :show="$errors->hasBag('createCoinOffer')">
            <x-slot:modalhead>
                {{__("payment.offers.create.title")}}
                <p x-text="payload.coinPrice" class="text-sm text-gray-500 mt-2">
                </p>
            </x-slot>
            <form id="add-form-offer" method="post" action="{{ route('admin.payment.coin_offers.store') }}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="coin_pricing_id" x-model="inputValue"/>
                    <x-input-label for="coin_pricing_id" :value="__('payment.offers.fields.coin_pricing')" />
                </div>

                <div>
                    <x-input-label for="name" :value="__('payment.offers.fields.name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Weekend Special" />
                    <x-input-error :messages="$errors->createCoinOffer->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('payment.offers.fields.description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" placeholder="Extra coins for weekend purchases"></textarea>
                    <x-input-error :messages="$errors->createCoinOffer->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="discount_percentage" :value="__('payment.offers.fields.discount_percentage')" />
                    <x-text-input id="discount_percentage" name="discount_percentage" type="number" min="5" max="90" class="mt-1 block w-full" placeholder="20" />
                    <x-input-error :messages="$errors->createCoinOffer->get('discount_percentage')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="start_date" :value="__('payment.offers.fields.start_date')" />
                    <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full" />
                    <x-input-error :messages="$errors->createCoinOffer->get('start_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="end_date" :value="__('payment.offers.fields.end_date')" />
                    <x-text-input id="end_date" name="end_date" type="text" class="date-input mt-1 block w-full" />
                    <x-input-error :messages="$errors->createCoinOffer->get('end_date')" class="mt-2" />
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="add-form-offer" color_type="success" >{{ __('form.actions.save') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endcan
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:coin-pricing-table/>
    </div>
@endsection 
@section('custom_js')
    <script>
        flatpickr(".date-input", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "en"
        });
    </script>
@endsection