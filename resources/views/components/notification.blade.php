@if(session()->has('messages'))
    <div id="notification-container" x-data="notificationHandler()" x-init="init()">
        <!-- Notifications will be rendered here by Alpine.js -->
    </div>

    <script>
        function notificationHandler() {
            return {
                notifications: @json(session('messages')),
                
                init() {
                    this.showNotifications();
                },
                
                showNotifications() {
                    if (typeof toastr === 'undefined') {
                        console.warn('Toastr library is not loaded');
                        this.fallbackNotifications();
                        return;
                    }

                    // Configure toastr once
                    toastr.options = {
                        timeOut: 8000,
                        extendedTimeOut: 2000,
                        progressBar: true,
                        closeButton: true,
                        preventDuplicates: true,
                        positionClass: document.documentElement.dir === "rtl" 
                            ? "toast-top-left" 
                            : "toast-top-right",
                        showMethod: 'slideDown',
                        hideMethod: 'slideUp',
                        showDuration: 300,
                        hideDuration: 300
                    };

                    // Show each notification with staggered timing
                    this.notifications.forEach((notification, index) => {
                        if (this.isValidNotification(notification)) {
                            setTimeout(() => {
                                this.showNotification(notification);
                            }, index * 200);
                        }
                    });
                },
                
                isValidNotification(notification) {
                    return notification && 
                           notification.message && 
                           (notification['alert-type'] || notification.type);
                },
                
                showNotification(notification) {
                    const message = this.escapeHtml(notification.message);
                    const type = notification['alert-type'] || notification.type || 'info';

                    switch (type) {
                        case 'success':
                            toastr.success(message);
                            break;
                        case 'error':
                        case 'danger':
                            toastr.error(message);
                            break;
                        case 'warning':
                            toastr.warning(message);
                            break;
                        case 'info':
                        default:
                            toastr.info(message);
                            break;
                    }
                },
                
                fallbackNotifications() {
                    // Fallback for when toastr is not available
                    this.notifications.forEach(notification => {
                        if (this.isValidNotification(notification)) {
                            console.log(`${notification['alert-type']}: ${notification.message}`);
                        }
                    });
                },
                
                escapeHtml(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, m => map[m]);
                }
            }
        }
    </script>
@endif
{{-- Include notification scripts --}}
@vite(['resources/js/notifications/NotificationManager.js', 'resources/js/notifications/init.js'])

