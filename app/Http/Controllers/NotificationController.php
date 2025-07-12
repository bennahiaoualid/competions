<?php

namespace App\Http\Controllers;

use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get the current authenticated notifiable (user or admin).
     */
    protected function getNotifiable()
    {
        // Try admin guard first, then user
        return Auth::guard('admin')->user() ?? Auth::user();
    }

    /**
     * Get notifications for the current notifiable
     */
    public function index(Request $request)
    {
        $notifiable = $this->getNotifiable();
    
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $notifications = $notifiable->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5) // Limit to 5 notifications as requested
            ->get();
        $unreadCount = $notifiable->unreadNotifications()->count();

        // Transform and translate notifications
        $transformedNotifications = $this->transformNotifications($notifications);

        // If AJAX, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'notifications' => $transformedNotifications,
                'unread_count' => $unreadCount,
            ]);
        }
        // Otherwise, return a view (optional, for full page)
        return view('notifications.index', [
            'notifications' => $transformedNotifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Return notifications as JSON for AJAX requests
     */
    public function getLatestNotificationJson(Request $request)
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $notifications = $notifiable->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5) // Limit to 5 notifications as requested
            ->get();
        $unreadCount = $notifiable->unreadNotifications()->count();

        // Transform and translate notifications
        $transformedNotifications = $this->transformNotifications($notifications);

        return response()->json([
            'notifications' => $transformedNotifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Transform notifications to include only essential data with server-side translation
     */
    protected function transformNotifications($notifications)
    {
        return $notifications->map(function ($notification) {
            $data = $notification->data;
            
            // If notification has translation key, translate it server-side
            if (isset($data['translation_key'])) {
                $translationKey = $data['translation_key'];
                $translationData = $data['translation_data'] ?? [];
                
                $titleKey = $translationKey . '.title';
                $messageKey = $translationKey . '.message';
                
                $translatedTitle = __($titleKey, $translationData);
                $translatedMessage = __($messageKey, $translationData);
                
                // Get translated link text if link exists
                $linkText = null;
                if (!empty($data['link'])) {
                    $linkText = __('notifications.link_text.detail');
                }
                
                // Return only essential data with new fields
                return [
                    'id' => $notification->id,
                    'title' => $translatedTitle,
                    'message' => $translatedMessage,
                    'notification_priority_type' => $data['notification_priority_type'] ?? 'info',
                    'link' => $data['link'] ?? null,
                    'link_text' => $linkText,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            }
            
            // Fallback for notifications without translation keys
            $linkText = null;
            if (!empty($data['link'])) {
                $linkText = __('notifications.link_text.detail');
            }
            
            return [
                'id' => $notification->id,
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'notification_priority_type' => $data['notification_priority_type'] ?? 'info',
                'link' => $data['link'] ?? null,
                'link_text' => $linkText,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request)
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $notification = $notifiable->notifications()->find($request->notification_id);

        if (!$notification) {
            return $request->ajax() ? response()->json(['message' => 'Notification not found'], 404) : back()->with('error', 'Notification not found');
        }
        $notification->markAsRead();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Notification marked as read',
                'unread_count' => $notifiable->unreadNotifications()->count(),
            ]);
        }
        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $notifiable->unreadNotifications()->update(['read_at' => now()]);
        if ($request->ajax()) {
            return response()->json([
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
            ]);
        }
        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, string $id)
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $notification = $notifiable->notifications()->find($id);
        if (!$notification) {
            return $request->ajax() ? response()->json(['message' => 'Notification not found'], 404) : back()->with('error', 'Notification not found');
        }
        $notification->delete();
        if ($request->ajax()) {
            return response()->json([
                'message' => 'Notification deleted',
                'unread_count' => $notifiable->unreadNotifications()->count(),
            ]);
        }
        return back()->with('success', 'Notification deleted');
    }

    /**
     * Get notification statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $stats = [
            'total' => $notifiable->notifications()->count(),
            'unread' => $notifiable->unreadNotifications()->count(),
            'read' => $notifiable->readNotifications()->count(),
        ];

        return response()->json($stats);
    }
} 