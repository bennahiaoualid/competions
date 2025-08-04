@extends('layouts.payment.master')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('payment.create_transaction') }}</h1>
        <p class="mt-2 text-gray-600">{{ __('payment.create_transaction_description') }}</p>
    </div>

    <!-- Coin Balance Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('payment.current_balance') }}</h3>
                <p class="text-2xl font-bold text-blue-600">{{ auth()->user()->coinBalance?->balance ?? 0 }} {{ __('payment.coins') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">{{ __('payment.total_earned') }}</p>
                <p class="text-lg font-semibold text-green-600">{{ auth()->user()->coinBalance?->total_earned ?? 0 }} {{ __('payment.coins') }}</p>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('payment.payment_details') }}</h2>
        </div>

        <form action="{{ route('payment.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <!-- Amount Input -->
            <div>
                <x-input-label for="amount" :value="__('payment.amount_dzd')" />
                <div class="relative">
                    <x-text-input id="amount" 
                                  name="amount" 
                                  type="number"
                                  :value="old('amount')"
                                  min="1" 
                                  step="0.01"
                                  class="mt-1 block w-full pr-12"
                                  :placeholder="__('payment.enter_amount')" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">DZD</span>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">{{ __('payment.amount_help') }}</p>
            </div>

            <!-- Payment Method -->
            <div class="mt-6" x-data="{ selectedMethod: '{{ old('payment_method') }}' }">
                <x-input-label :value="__('payment.payment_method')" />
                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500 transition-all duration-200"
                           :class="{ 'border-blue-500 bg-blue-50': selectedMethod === 'cash' }"
                           @click="selectedMethod = 'cash'; $refs.cashRadio.checked = true; $refs.cashRadio.focus()">
                        <input type="radio" 
                               name="payment_method" 
                               value="cash" 
                               x-ref="cashRadio"
                               class="sr-only" 
                               {{ old('payment_method') == 'cash' ? 'checked' : '' }}>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">{{ __('payment.cash') }}</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">{{ __('payment.cash_description') }}</span>
                            </span>
                        </span>
                        <span class="pointer-events-none absolute -inset-px rounded-lg border-2 transition-all duration-200" 
                              :class="{ 'border-blue-500': selectedMethod === 'cash', 'border-transparent': selectedMethod !== 'cash' }" 
                              aria-hidden="true"></span>
                    </label>

                    <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500 transition-all duration-200"
                           :class="{ 'border-blue-500 bg-blue-50': selectedMethod === 'bank_transfer' }"
                           @click="selectedMethod = 'bank_transfer'; $refs.bankRadio.checked = true; $refs.bankRadio.focus()">
                        <input type="radio" 
                               name="payment_method" 
                               value="bank_transfer" 
                               x-ref="bankRadio"
                               class="sr-only" 
                               {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">{{ __('payment.bank_transfer') }}</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">{{ __('payment.bank_transfer_description') }}</span>
                            </span>
                        </span>
                        <span class="pointer-events-none absolute -inset-px rounded-lg border-2 transition-all duration-200" 
                              :class="{ 'border-blue-500': selectedMethod === 'bank_transfer', 'border-transparent': selectedMethod !== 'bank_transfer' }" 
                              aria-hidden="true"></span>
                    </label>

                    <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500 transition-all duration-200"
                           :class="{ 'border-blue-500 bg-blue-50': selectedMethod === 'mobile_money' }"
                           @click="selectedMethod = 'mobile_money'; $refs.mobileRadio.checked = true; $refs.mobileRadio.focus()">
                        <input type="radio" 
                               name="payment_method" 
                               value="mobile_money" 
                               x-ref="mobileRadio"
                               class="sr-only" 
                               {{ old('payment_method') == 'mobile_money' ? 'checked' : '' }}>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">{{ __('payment.mobile_money') }}</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">{{ __('payment.mobile_money_description') }}</span>
                            </span>
                        </span>
                        <span class="pointer-events-none absolute -inset-px rounded-lg border-2 transition-all duration-200" 
                              :class="{ 'border-blue-500': selectedMethod === 'mobile_money', 'border-transparent': selectedMethod !== 'mobile_money' }" 
                              aria-hidden="true"></span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
            </div>

            <!-- Proof Image Upload -->
            <div class="mt-6">
                <x-input-label for="proof_image" :value="__('payment.proof_image')" />
                <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-500 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="proof_image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>{{ __('payment.upload_file') }}</span>
                                <input id="proof_image" name="proof_image" type="file" class="sr-only" accept="image/*" required>
                            </label>
                            <p class="pl-1">{{ __('payment.or_drag_drop') }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ __('payment.image_requirements') }}</p>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('proof_image')" class="mt-2" />
            </div>

            <!-- Preview Section -->
            <div id="image-preview" class="mt-6 hidden">
                <x-input-label :value="__('payment.image_preview')" />
                <div class="mt-2 relative inline-block">
                    <img id="preview-img" src="" alt="Preview" class="max-w-xs rounded-lg shadow-sm">
                    <button type="button" id="remove-image" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm hover:bg-red-600">
                        ×
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <x-secondary-button type="button" onclick="window.history.back()">
                    {{ __('common.cancel') }}
                </x-secondary-button>
                <x-button color_type="primary">
                    {{ __('payment.submit_payment') }}
                </x-button>
            </div>
        </form>
    </div>

    <!-- Information Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">{{ __('payment.important_info') }}</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('payment.info_1') }}</li>
                            <li>{{ __('payment.info_2') }}</li>
                            <li>{{ __('payment.info_3') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">{{ __('payment.coin_usage') }}</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('payment.usage_1') }}</li>
                            <li>{{ __('payment.usage_2') }}</li>
                            <li>{{ __('payment.usage_3') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('proof_image');
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const removeBtn = document.getElementById('remove-image');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    removeBtn.addEventListener('click', function() {
        fileInput.value = '';
        preview.classList.add('hidden');
        previewImg.src = '';
    });

    // Handle drag and drop
    const dropZone = fileInput.closest('.border-dashed');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection 