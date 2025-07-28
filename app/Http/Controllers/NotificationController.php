<?php

namespace App\Http\Controllers;

use Redirect;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Helpers\DateTimeHelper;
use App\Helpers\PaginationHelper;
use Illuminate\Http\JsonResponse;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NotificationTranslator;
use App\Helpers\NotificationIconHelper;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    protected FlasherInterface $flasherInterface;
    public function __construct(FlasherInterface $flasherInterface)
    {
        $this->flasherInterface = $flasherInterface;
    }
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
            abort(404);
        }
        $notifications = $notifiable->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(PaginationHelper::perPage());

        $notifications = $this->transformNotifications($notifications);

        if($notifiable instanceof Admin) {
            $type = 'admin';
        }else{
            $type = 'user';
        }

        return view('pages.notifications.notificatios_list',compact('notifications','type'));

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

        // Use NotificationTranslator for each notification
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
        // If it's a paginated collection, transform the data while preserving pagination
        if (method_exists($notifications, 'getCollection')) {
            $transformedData = $notifications->getCollection()->map(function ($notification) {
                return $this->transformSingleNotification($notification);
            });
            
            // Set the transformed collection back to the paginator
            $notifications->setCollection($transformedData);
            return $notifications;
        }
        
        // For regular collections, just map and return
        return $notifications->map(function ($notification) {
            return $this->transformSingleNotification($notification);
        });
    }

    /**
     * Transform a single notification
     */
    protected function transformSingleNotification($notification)
    {
        $data = $notification->data;
        
        // Get icon for this notification
        $icon = NotificationIconHelper::getIconForNotification($data);
        
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
                'icon' => $icon,
                'link' => $data['link'] ?? null,
                'link_text' => $linkText,
                'read_at' => $notification->read_at,
                'created_at' => DateTimeHelper::toLocalString($notification->created_at),
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
            'icon' => $icon,
            'link' => $data['link'] ?? null,
            'link_text' => $linkText,
            'read_at' => $notification->read_at,
            'created_at' => DateTimeHelper::toLocalString($notification->created_at),
        ];
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
    public function destroy(Request $request)
    {
        $notifiable = $this->getNotifiable();
        
        if (!$notifiable) {
            abort(401,'Not authenticated');
        }

        $notification = $notifiable->notifications()->find($request->notification_id);
        if (!$notification) {
            abort(404,'Notification not found');
        }
        $notification->delete();
        $this->flasherInterface->crudSuccess('deleted');
        return redirect()->back();
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete(Request $request)
    {
        $notifiable = $this->getNotifiable();
        if (!$notifiable) {
            abort(401, 'Not authenticated');
        }
    
        $deleted = $notifiable->notifications()->delete();
        $this->flasherInterface->crudSuccess('deleted');
        return redirect()->back();
    }
} 