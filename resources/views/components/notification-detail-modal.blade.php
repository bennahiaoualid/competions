<div x-data="{
    show: false,
    detail: null,
    open(detailObj) {
        this.detail = detailObj;
        this.show = true;
    },
    close() {
        this.show = false;
        this.detail = null;
    }
}"
     x-init="window.addEventListener('show-notification-detail', e => open(e.detail.object));"
     x-show="show"
     style="display: none;"
     class="fixed inset-0 z-50 flex items-center justify-center sm:items-center sm:justify-center bg-black bg-opacity-50">
    <div @click.away="close" class="bg-white rounded-xl shadow-2xl w-full max-w-lg relative transform transition-all duration-300 ease-out mt-16 sm:mt-0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('notifications.detail_modal.title') }}</h3>
            <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6">
        <template x-if="detail">
            <div>
                <!-- Header with icon and title -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                            <i :class="detail.icon || 'fas fa-bell'" class="text-xl text-gray-600"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-gray-900" x-text="detail.title || 'Detail'"></h2>
                        <p class="text-sm text-gray-500" x-text="detail.created_at ? new Date(detail.created_at).toLocaleString() : ''"></p>
                    </div>
                </div>
                
                <!-- Message content -->
                <div class="mb-4">
                    <div class="text-gray-700 leading-relaxed" x-text="detail.message || detail.content || ''"></div>
                </div>
                
                <!-- Action link -->
                <template x-if="detail.link">
                    <div class="border-t pt-4 flex justify-end">
                        <a :href="detail.link" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition-colors duration-200" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>
                            <span x-text="detail.link_text || 'Open Link'"></span>
                        </a>
                    </div>
                </template>
            </div>
        </template>
        </div>
    </div>
</div> 