<?php

namespace App\Repositories;

use App\Models\Customers;
use App\Repositories\Support\AbstractRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthRepository extends AbstractRepository
{
    public function model()
    {
        return User::class;
    }

    public function login(array $credentials)
    {
        try {
            if (!Auth::attempt($credentials)) {
                return [
                    'success' => false,
                    'message' => 'Email hoặc mật khẩu không chính xác'
                ];
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'success' => true,
                'user' => $user,
                'token' => $token
            ];
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Đăng nhập thất bại. Vui lòng thử lại sau.'
            ];
        }
    }

    // App\Repositories\AuthRepository.php
    public function register(array $data)
    {
        try {
            // Tạo user. role/is_active không mass-assign được (ngoài $fillable)
            // → gán tường minh sau khi khởi tạo.
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
            $user->role = 'user'; // Mặc định là user thường
            $user->is_active = true;
            $user->save();

            // Tạo customer cho user
            $customer = Customers::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'] ?? $data['name'],
                'tax_code' => $data['tax_code'] ?? null,
                'business_type' => $data['business_type'] ?? 'individual',
                'industry' => $data['industry'] ?? null,
                'website' => $data['website'] ?? null,
                'status' => 'active',
                'source' => 'website',
            ]);

            return [
                'success' => true,
                'user' => $user,
                'customer' => $customer
            ];
        } catch (\Exception $e) {
            Log::error('Register failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Đăng ký thất bại. Vui lòng thử lại sau.'
            ];
        }
    }

    public function logout()
    {
        try {
            Auth::user()->tokens()->delete();
            Auth::logout();

            return [
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ];
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Đăng xuất thất bại. Vui lòng thử lại sau.'
            ];
        }
    }
}
