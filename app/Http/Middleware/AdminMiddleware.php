<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiếp tục');
        }

        // super_admin cũng là quản trị viên — trước đây bị loại khỏi mọi trang admin.
        if (!in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            // KHÔNG redirect về admin.dashboard: route đó cũng nằm sau middleware này
            // → non-admin bị đá vòng vô hạn (ERR_TOO_MANY_REDIRECTS). Đưa về trang chủ.
            return redirect()->route('homepage')->with('error', 'Bạn không có quyền truy cập trang này');
        }

        return $next($request);
    }
}
