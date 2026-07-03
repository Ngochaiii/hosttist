<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Thông báo in-app của người dùng (chuông thông báo + trang danh sách).
 * Route nằm sau middleware frontend.auth — luôn có user đăng nhập.
 */
class NotificationController extends Controller
{
    /**
     * Trang danh sách thông báo (phân trang)
     */
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return view('source.web.profile.notifications', compact('notifications', 'unreadCount'));
    }

    /**
     * Click vào 1 thông báo: đánh dấu đã đọc rồi chuyển tới action_url (nếu có)
     */
    public function go(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return redirect()->route('notifications.index');
        }

        $notification->markAsRead();

        $actionUrl = $notification->data['action_url'] ?? null;

        return redirect($actionUrl ?: route('notifications.index'));
    }

    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public function markRead(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * Đánh dấu tất cả đã đọc
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
