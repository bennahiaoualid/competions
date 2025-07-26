/**
 * Notification System Initialization
 * Simple initialization for the notification manager
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification manager
    const notificationManager = new NotificationManager();
    
    // Make it globally available
    window.notificationManager = notificationManager;
    
    console.log('Notification system initialized');
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        NotificationManager: window.NotificationManager
    };
} 