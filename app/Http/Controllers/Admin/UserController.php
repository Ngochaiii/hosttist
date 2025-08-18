<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        // Lấy tất cả users kể cả bị soft delete
        $users = User::withTrashed()->get();
        
        return view('source.admin.users.index', compact('users'));
    }
    
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        
        // Password mặc định
        $defaultPassword = 'hosttist.123';
        
        // Hash và lưu password mặc định vào database
        $user->update([
            'password' => Hash::make($defaultPassword)
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Password đã được reset về mặc định',
            'default_password' => $defaultPassword,
            'reset_time' => now()->format('d/m/Y H:i:s')
        ]);
    }
}