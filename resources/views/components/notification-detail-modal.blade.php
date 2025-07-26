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
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div @click.away="close" class="bg-white p-6 rounded shadow-lg w-full max-w-md relative">
        <button @click="close" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <template x-if="detail">
            <div>
                <h2 class="text-lg font-bold mb-2" x-text="detail.title || 'Detail'"></h2>
                <div class="mb-2 text-gray-700" x-text="detail.message || detail.content || ''"></div>
                <div class="mb-2 text-xs text-gray-500" x-text="detail.created_at ? new Date(detail.created_at).toLocaleString() : ''"></div>
                <template x-if="detail.link">
                    <a :href="detail.link" class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors duration-200 mt-2" target="_blank">
                        <span x-text="detail.link_text || 'Open Link'"></span>
                    </a>
                </template>
                <!-- Extend here for more fields if needed -->
            </div>
        </template>
    </div>
</div> 