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
            
            <!-- Coin Pricing -->
            <div>
                <x-input-label :value="__('payment.select_coin_pricing')" />
                <x-selected-card-hover name="coin_pricing_id" :value="old('coin_pricing_id')" model="selectedCoinPricingId">
                    @foreach($coinPricing as $pricing)
                        <div class="relative">
                            @php
                                $offer = $pricing->activeOffer->first();
                            @endphp
                            @if($offer && $offer->isCurrentlyValid())
                                <!-- Discount Ribbon -->
                                <div class="absolute top-[2%] -start-2 bg-primary text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg transform rotate-45 z-10">
                                    + {{ $offer->discount_percentage }}%
                                </div>
                            @endif
                            <x-selected-card 
                                :value="$pricing->id" 
                                :selected="old('coin_pricing_id') == $pricing->id"
                                model="selectedCoinPricingId">
                                
                                <div class="flex flex-1 flex-col items-center gap-1">
                                    <h3 class="md:text-xl text-center font-bold text-primary">{{ $pricing->display_name }}</h3>
                                    <span class="block text-sm md:text-base font-medium text-gray-900">{{ number_format($pricing->base_amount, 2) }} DZD</span>
                                    <span class="mt-1 flex items-center text-sm md:text-base text-gray-500">{{ __('payment.get_coins', ['coins' => $pricing->base_coins]) }}</span>
                                    @if($offer && $offer->isCurrentlyValid())
                                        <span class="mt-1 flex items-center text-sm md:text-base text-primary">
                                            {{ __('payment.extra_coins') .' : ' . $offer->calculateExtraCoins($pricing->base_coins) }} 
                                            <i class="fa-solid fa-coins ms-2 text-yellow-500"></i>
                                        </span>
                                    @endif
                                </div>
                            </x-selected-card>
                        </div>
                    @endforeach
                </x-selected-card-hover>
                <x-input-error :messages="$errors->storePaymentTransaction->get('coin_pricing_id')" class="mt-2" />
            </div>

            <!-- Payment Method -->
            <div class="mt-6">
                <x-input-label :value="__('payment.payment_method')" />
                <x-selected-card-hover name="payment_method" :value="old('payment_method')" model="selectedMethod" grid="grid-cols-1 md:grid-cols-3">
                    <x-selected-card 
                        value="cash" 
                        :selected="old('payment_method') == 'cash'"
                        model="selectedMethod">
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <h3 class="md:text-xl text-center font-semibold text-primary">{{ __('payment.cash') }}</h3>
                            <span class="mt-1 flex items-center text-sm text-gray-500">{{ __('payment.cash_description') }}</span>
                        </div>
                    </x-selected-card>

                    <x-selected-card 
                        value="bank_transfer" 
                        :selected="old('payment_method') == 'bank_transfer'"
                        model="selectedMethod">
                        
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <h3 class="md:text-xl text-center font-semibold text-primary">{{ __('payment.bank_transfer') }}</h3>
                            <span class="mt-1 flex items-center text-sm text-gray-500">{{ __('payment.bank_transfer_description') }}</span>
                        </div>
                    </x-selected-card>
                </x-selected-card-hover>
                <x-input-error :messages="$errors->storePaymentTransaction->get('payment_method')" class="mt-2" />
            </div>

            <!-- Proof Image Upload -->
            <div class="mt-6">
                <x-input-label for="proof_image" :value="__('payment.proof_image')" />
                <div class="drag-drop-zone mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-500 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="proof_image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>{{ __('payment.upload_file') }}</span>
                                <input id="proof_image" name="proof_image" type="file" class="sr-only" accept="image/*" required>
                            </label>
                            <p class="pl-1 ms-2">{{ __('payment.or_drag_drop') }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ __('payment.image_requirements') }}</p>
                    </div>
                </div>
                <x-input-error :messages="$errors->storePaymentTransaction->get('proof_image')" class="mt-2" />
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
@endsection 
@section('custom_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('proof_image');
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const removeBtn = document.getElementById('remove-image');
    
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                compressAndPreviewImage(file);
            }
        });
    
        removeBtn.addEventListener('click', function() {
            fileInput.value = '';
            preview.classList.add('hidden');
            previewImg.src = '';
        });
    
        // Handle drag and drop
        const dropZone = fileInput.closest('.drag-drop-zone');
        
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
            if (files.length > 0) {
                compressAndPreviewImage(files[0]);
            }
        }

        // Image compression function
        function compressAndPreviewImage(file) {
            // Check if file is an image
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                return;
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = function() {
                // Calculate new dimensions (max 1200px width/height)
                let { width, height } = img;
                const maxSize = 1200;
                
                if (width > height) {
                    if (width > maxSize) {
                        height = (height * maxSize) / width;
                        width = maxSize;
                    }
                } else {
                    if (height > maxSize) {
                        width = (width * maxSize) / height;
                        height = maxSize;
                    }
                }

                // Set canvas dimensions
                canvas.width = width;
                canvas.height = height;

                // Draw compressed image
                ctx.drawImage(img, 0, 0, width, height);

                // Convert to blob with compression
                canvas.toBlob(function(compressedBlob) {
                    // Create a new file from the compressed blob
                    const compressedFile = new File([compressedBlob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });

                    // Create a FileList-like object
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    fileInput.files = dataTransfer.files;

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(compressedBlob);

                    // Show compression info
                    const originalSize = (file.size / 1024 / 1024).toFixed(2);
                    const compressedSize = (compressedBlob.size / 1024 / 1024).toFixed(2);
                    console.log(`Image compressed: ${originalSize}MB → ${compressedSize}MB`);
                }, 'image/jpeg', 0.8); // 80% quality
            };

            img.src = URL.createObjectURL(file);
        }
    });
</script>
@endsection