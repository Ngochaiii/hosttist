<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Repositories\ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// class ConfigController extends Controller
// {
//     protected $configRepository;

//     public function __construct(ConfigRepository $configRepository)
//     {
//         $this->configRepository = $configRepository;
//     }

//     public function edit()
//     {
//         $config = $this->configRepository->getCurrent();
//         return view('source.admin.config.index', compact('config'));
//     }

//     public function update(Request $request)
//     {
//         try {
//             // Log data trước khi update
//             Log::info('Config update data:', $request->all());

//             // Xử lý các trường boolean
//             $data = $request->all();
//             $booleanFields = [
//                 'enable_rss', 'enable_adsense', 'enable_twitter',
//                 'enable_youtube', 'enable_instagram', 'enable_pinterest',
//                 'enable_linkedin', 'enable_disqus', 'enable_github',
//                 'enable_gravatar', 'adsense_non_personalized'
//             ];

//             foreach ($booleanFields as $field) {
//                 $data[$field] = $request->has($field);
//             }

//             // Xử lý các trường JSON
//             if ($request->has('months_name')) {
//                 $data['months_name'] = json_decode($request->months_name, true);
//             }
//             if ($request->has('page_of_text')) {
//                 $data['page_of_text'] = json_decode($request->page_of_text, true);
//             }

//             // Update config
//             $config = $this->configRepository->updateConfig($data);

//             // Log kết quả
//             Log::info('Config updated successfully:', [
//                 'config_id' => $config->id,
//                 'updated_data' => $config->toArray()
//             ]);

//             return redirect()->back()->with('success', 'Cập nhật cấu hình thành công');
//         } catch (\Exception $e) {
//             Log::error('Config update failed:', [
//                 'error' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString()
//             ]);

//             return redirect()->back()
//                 ->withInput()
//                 ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
//         }
//     }
// }
class ConfigController extends Controller
{
    /**
     * Hiển thị form cấu hình thanh toán
     */
    public function paymentSettings()
    {
        $config = Config::current() ?? new Config();
        return view('source.admin.config.payment_settings', compact('config'));
    }

    /**
     * Cập nhật cấu hình thanh toán
     */
    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'company_bank_name' => 'nullable|string|max:255',
            'company_bank_account_number' => 'nullable|string|max:50',
            'company_bank_account_name' => 'nullable|string|max:255',
            'company_bank_branch' => 'nullable|string|max:255',
            // 'company_bank_qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'deposit_instruction' => 'nullable|string',
            'deposit_note_format' => 'nullable|string|max:255',
            'min_deposit_amount' => 'nullable|numeric|min:0',
            'max_deposit_amount' => 'nullable|numeric|min:0',
            'momo_phone_number' => 'nullable|string|max:20',
            'momo_account_name' => 'nullable|string|max:255',
            'momo_qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'zalopay_phone_number' => 'nullable|string|max:20',
            'zalopay_account_name' => 'nullable|string|max:255',
            'zalopay_qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Chuẩn bị dữ liệu cập nhật
        $updateData = [
            'company_bank_name' => $request->company_bank_name,
            'company_bank_account_number' => $request->company_bank_account_number,
            'company_bank_account_name' => $request->company_bank_account_name,
            'company_bank_branch' => $request->company_bank_branch,
            'deposit_instruction' => $request->deposit_instruction,
            'deposit_note_format' => $request->deposit_note_format,
            'min_deposit_amount' => $request->min_deposit_amount,
            'max_deposit_amount' => $request->max_deposit_amount,
            'momo_phone_number' => $request->momo_phone_number,
            'momo_account_name' => $request->momo_account_name,
            'zalopay_phone_number' => $request->zalopay_phone_number,
            'zalopay_account_name' => $request->zalopay_account_name,
        ];

        // Xử lý upload QR code ngân hàng
        if ($request->hasFile('company_bank_qr_code')) {
            $config = Config::current();
            if ($config && $config->company_bank_qr_code) {
                Storage::delete('public/' . $config->company_bank_qr_code);
            }
            $path = $request->file('company_bank_qr_code')->store('qrcodes', 'public');
            $updateData['company_bank_qr_code'] = $path;
        }

        // Xử lý upload QR code MoMo
        if ($request->hasFile('momo_qr_code')) {
            $config = Config::current();
            if ($config && $config->momo_qr_code) {
                Storage::delete('public/' . $config->momo_qr_code);
            }
            $path = $request->file('momo_qr_code')->store('qrcodes', 'public');
            $updateData['momo_qr_code'] = $path;
        }

        // Xử lý upload QR code ZaloPay
        if ($request->hasFile('zalopay_qr_code')) {
            $config = Config::current();
            if ($config && $config->zalopay_qr_code) {
                Storage::delete('public/' . $config->zalopay_qr_code);
            }
            $path = $request->file('zalopay_qr_code')->store('qrcodes', 'public');
            $updateData['zalopay_qr_code'] = $path;
        }

        // Cập nhật cấu hình
        Config::updateConfig($updateData);

        return redirect()->route('admin.configs.payment')
            ->with('success', 'Cấu hình thanh toán đã được cập nhật thành công.');
    }
}
