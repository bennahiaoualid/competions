
/**
 * Simple Notification Manager
 * Manages a stack of notifications (max 5) and updates the existing dropdown
 * Now works with server-side translated notifications and priority types
 */
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.maxNotifications = 5;
        this.currentLocale = this.detectCurrentLocale();
        
        // Get user ID from meta tag or global variable
        this.userId = this.getUserId();
        
        this.init();
    }

    /**
     * Detect current locale from document
     */
    detectCurrentLocale() {
        // Try to get from document lang attribute
        const docLang = document.documentElement.lang;
        if (docLang) {
            return docLang;
        }
        
        // Try to get from meta tag
        const metaTag = document.querySelector('meta[name="locale"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        // Try to get from global variable
        if (window.currentLocale) {
            return window.currentLocale;
        }
        
        // Default to English
        return 'en';
    }

    /**
     * Get the current user ID from meta tag or global variable
     */
    getUserId() {
        // Try to get from meta tag first
        const metaTag = document.querySelector('meta[name="user-id"]');

        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        // Fallback to global variable
        if (window.userId) {
            return window.userId;
        }
        
        // Fallback to auth user ID
        if (window.authUser && window.authUser.id) {
            return window.authUser.id;
        }
        
        console.warn('NotificationManager: No user ID found');
        return null;
    }

    /**
     * Get notification type styles and icon
     */
    getNotificationTypeStyles(type) {
        const styles = {
            info: {
                bg: 'bg-blue-50',
                border: 'border-blue-200',
                icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                iconColor: 'text-blue-600',
                bgColor: 'bg-blue-100'
            },
            warning: {
                bg: 'bg-yellow-50',
                border: 'border-yellow-200',
                icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
                iconColor: 'text-yellow-600',
                bgColor: 'bg-yellow-100'
            },
            danger: {
                bg: 'bg-red-50',
                border: 'border-red-200',
                icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                iconColor: 'text-red-600',
                bgColor: 'bg-red-100'
            },
            success: {
                bg: 'bg-green-50',
                border: 'border-green-200',
                icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                iconColor: 'text-green-600',
                bgColor: 'bg-green-100'
            }
        };
        
        return styles[type] || styles.info;
    }

    init() {
        if (!this.userId) {
            console.error('NotificationManager: Cannot initialize without user ID');
            return;
        }
        
        this.setupEchoListener();
        this.loadInitialNotifications();
        this.updateBadge();
        this.updateDropdown();
    }

    /**
     * Load initial notifications from server
     */
    async loadInitialNotifications() {
        try {
            // Get current locale from document or default to 'en'
            const currentLocale = this.currentLocale;
            const response = await fetch(`/${currentLocale}/notifications/get-latest-notification-json`);
            const data = await response.json();

            this.notifications = data.notifications || [];
            this.unreadCount = data.unread_count || 0;
            
            this.updateBadge();
            this.updateDropdown();
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    /**
     * Setup Laravel Echo listener for real-time notifications
     */
    setupEchoListener() {
        if (typeof Echo === 'undefined') {
            console.error('NotificationManager: Laravel Echo not found');
            return;
        }

        if (!this.userId) {
            console.error('NotificationManager: Cannot setup Echo listener without user ID');
            return;
        }

        // Try admin channel first, then user channel
        const adminChannel = `App.Models.Admin.Admin.${this.userId}`;

        
        // Listen to both channels to handle both admin and user notifications
        Echo.private(adminChannel)
            .notification((notification) => {
                this.handleNewNotification(notification);
            });
            
    }

    /**
     * Handle incoming real-time notification
     */
    handleNewNotification(notification) {
        console.log('New notification received:', notification);
        
        // Add to notifications array (stack behavior)
        this.notifications.unshift(notification);
        
        // Keep only latest 5 notifications
        if (this.notifications.length > this.maxNotifications) {
            this.notifications = this.notifications.slice(0, this.maxNotifications);
        }
        
        // Update unread count
        this.unreadCount++;
        
        // Update UI
        this.updateBadge();
        this.updateDropdown();
        this.showToast(notification);
    }

    /**
     * Show toast notification
     */
    showToast(notification) {
        if (typeof toastr === 'undefined') {
            console.warn('Toastr library is not loaded');
            return;
        }

        // Use pre-translated data from server
        const title = notification.title || 'Notification';
        const message = notification.message || '';

        toastr.options = {
            timeOut: 8000,
            extendedTimeOut: 2000,
            progressBar: true,
            closeButton: true,
            preventDuplicates: true,
            positionClass: document.documentElement.dir === "rtl" 
                ? "toast-top-left" 
                : "toast-top-right",
        };

        toastr.info(message, title);
    }

    /**
     * Update notification badge
     */
    updateBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'block' : 'none';
        }
    }

    /**
     * Update notification dropdown
     */
    updateDropdown() {
        const dropdown = document.getElementById('notification-list');

        if (!dropdown) {
            console.warn('NotificationManager: notification-list element not found');
            return;
        }

        const notificationsHtml = this.notifications.map(notification => {
            // Use pre-translated data from server
            const title = notification.title || 'Notification';
            const message = notification.message || '';
            const isRead = notification.read_at ? 'read' : 'unread';
            const priorityType = notification.notification_priority_type || 'info';
            // Get styles and icon for notification type
            const typeStyles = this.getNotificationTypeStyles(priorityType);

            // Detail icon button (end of row)
            const detailButton = `
                <button class="icon-notification-detail ml-2 text-gray-400 hover:text-blue-600 focus:outline-none" title="Detail" data-id="${notification.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            `;
            return `
                <div class="notification-item ${isRead} p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors duration-200 ${typeStyles.bg} ${typeStyles.border}" data-id="${notification.id}">
                    <div class="flex items-start gap-3 justify-between">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 ${typeStyles.bgColor} rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 ${typeStyles.iconColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${typeStyles.icon}" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">${title}</p>
                                    <div class="flex items-center gap-2">
                                        ${!notification.read_at ? '<span class="w-2 h-2 bg-blue-500 rounded-full"></span>' : ''}
                                        <span class="text-xs text-gray-500">${this.formatTime(notification.created_at)}</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">${message}</p>
                            </div>
                        </div>
                        ${detailButton}
                    </div>
                </div>
            `;
        }).join('');

        if (this.notifications.length === 0) {
            const emptyStateText = this.currentLocale === 'ar' ? 'لا توجد إشعارات بعد' : 'No notifications yet';
            dropdown.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2 text-sm">${emptyStateText}</p>
                </div>
            `;
        } else {
            dropdown.innerHTML = notificationsHtml;
        }

        // Add click handlers to mark as read
        dropdown.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', (e) => {
                // Don't mark as read if clicking on link button or detail button
                if (e.target.tagName === 'A' || e.target.closest('a') || e.target.closest('.icon-notification-detail')) {
                    return;
                }
                const notificationId = item.dataset.id;
                this.markAsRead(notificationId);
            });
        });

        // Add click handlers for detail buttons
        dropdown.querySelectorAll('.icon-notification-detail').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const notificationId = btn.dataset.id;
                this.showNotificationDetail(notificationId);
            });
        });
    }

    /**
     * Format notification time
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        // Time translations
        const timeTranslations = {
            en: {
                justNow: 'Just now',
                minutesAgo: 'm ago',
                hoursAgo: 'h ago'
            },
            ar: {
                justNow: 'الآن',
                minutesAgo: 'دقائق مضت',
                hoursAgo: 'ساعات مضت'
            }
        };
        
        const translations = timeTranslations[this.currentLocale] || timeTranslations.en;
        
        if (diff < 60000) return translations.justNow;
        if (diff < 3600000) return `${Math.floor(diff / 60000)}${translations.minutesAgo}`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}${translations.hoursAgo}`;
        return date.toLocaleDateString(this.currentLocale);
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const currentLocale = this.currentLocale;
            const response = await fetch(`/${currentLocale}/notifications/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        try {
            const currentLocale = this.currentLocale;
            const response = await fetch(`/${currentLocale}/notifications/mark-all-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });
            
            if (response.ok) {
                this.unreadCount = 0;
                this.updateBadge();
                this.updateDropdown();
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    /**
     * Change locale for notifications
     */
    changeLocale(locale) {
        this.currentLocale = locale;
        this.updateDropdown();
    }

    /**
     * Refresh locale detection and update notifications
     */
    refreshLocale() {
        const newLocale = this.detectCurrentLocale();
        if (newLocale !== this.currentLocale) {
            this.currentLocale = newLocale;
            this.updateDropdown();
        }
    }

    /**
     * Show notification detail modal (to be implemented)
     */
    showNotificationDetail(notificationId) {
        // Find the notification object by ID
        const notification = this.notifications.find(n => n.id == notificationId);
        if (notification) {
            window.dispatchEvent(new CustomEvent('show-notification-detail', { detail: { object: notification } }));
        } else {
            console.warn('NotificationManager: Notification object not found for ID', notificationId);
        }
    }
}

// Export for use in other modules
window.NotificationManager = NotificationManager; 

// Make markAsRead globally accessible for Alpine.js
window.markNotificationAsRead = (id) => {
    if (window.notificationManager) {
        window.notificationManager.markAsRead(id);
    }
}; 