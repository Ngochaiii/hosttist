<?php

namespace App\Services;

use App\Models\CustomerService;
use App\Models\ProvisionLog;
use App\Models\ServiceProvision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Đọc/ghi thông số kỹ thuật của một CustomerService đang chạy.
 *
 * Dữ liệu lưu trong service_provisions.provision_data (provision đã link với CS) —
 * tái dùng đúng kho dữ liệu của luồng fulfill, không tạo bảng mới.
 *
 * - Field nhạy cảm (encrypted): bọc bằng Crypt::encryptString() y như
 *   ProvisionController::processFormData (giữ tương thích).
 * - Field SSL (file): lưu nội dung ra disk private storage/app/ssl/{cs_id}/...,
 *   provision_data chỉ giữ đường dẫn. Private key mã hóa at rest.
 */
class ServiceParameterService
{
    /** Tên file trên disk theo từng field SSL. */
    private const SSL_FILES = [
        'certificate' => 'certificate.pem',
        'private_key' => 'private_key.enc',
        'ca_bundle'   => 'ca_bundle.pem',
    ];

    public function schema(CustomerService $service): array
    {
        return ServiceParameterSchema::forType($service->service_type);
    }

    /** Quy tắc validate dựng từ schema. */
    public function rules(string $type): array
    {
        $rules = [];
        foreach (ServiceParameterSchema::forType($type) as $field) {
            $name = $field['name'];
            $rules[$name] = match ($field['type']) {
                'email'  => 'nullable|email',
                'url'    => 'nullable|url',
                'number' => 'nullable|numeric',
                'date'   => 'nullable|date',
                'select' => 'nullable|in:' . implode(',', array_keys($field['options'] ?? [])),
                'checkbox' => 'nullable|boolean',
                default  => $name === 'server_ip' ? 'nullable|ip' : 'nullable|string',
            };
            if (!empty($field['file'])) {
                $rules[$name . '_file'] = 'nullable|file|max:10240';
            }
        }
        return $rules;
    }

    /** provision_data thô (chưa giải mã) dưới dạng mảng. */
    public function rawData(CustomerService $service): array
    {
        $provision = $service->provision;
        if (!$provision) {
            return [];
        }
        $data = $provision->provision_data;
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Giá trị để hiển thị (đã giải mã field nhạy cảm, đã nạp nội dung file SSL).
     * Trang admin tự mask + reveal.
     */
    public function displayValues(CustomerService $service): array
    {
        $data = $this->rawData($service);
        $out  = [];
        foreach (ServiceParameterSchema::forType($service->service_type) as $field) {
            $name = $field['name'];
            if (!empty($field['file'])) {
                $out[$name] = $this->readSslFile($service, $name) ?? '';
                continue;
            }
            $value = $data[$name] ?? '';
            if (!empty($field['encrypted']) && $value !== '' && $value !== null) {
                $value = $this->safeDecrypt($value);
            }
            $out[$name] = $value;
        }
        return $out;
    }

    /**
     * Áp dụng cập nhật từ form. Ghi vào provision_data; lưu file SSL ra disk private.
     * Field nhạy cảm/file để TRỐNG => giữ nguyên giá trị cũ (không xoá).
     */
    public function applyUpdate(CustomerService $service, Request $request): void
    {
        $provision = $service->provision;
        if (!$provision) {
            throw new \RuntimeException('Dịch vụ chưa có provision liên kết để lưu thông số.');
        }

        $type    = $service->service_type;
        $current = $this->rawData($service);
        $changed = [];

        foreach (ServiceParameterSchema::forType($type) as $field) {
            $name = $field['name'];

            // ----- Field SSL (file) -----
            if (!empty($field['file'])) {
                $content = null;
                if ($request->hasFile($name . '_file')) {
                    $content = $request->file($name . '_file')->get();
                } elseif (filled($request->input($name))) {
                    $content = $request->input($name);
                }
                if ($content !== null && trim($content) !== '') {
                    $current[$name . '_path'] = $this->storeSslFile($service, $name, $content, !empty($field['encrypted']));
                    $current[$name . '_uploaded_at'] = now()->toISOString();
                    $changed[] = $name;
                }
                continue;
            }

            // ----- Field nhạy cảm (encrypt, để trống = giữ nguyên) -----
            if (!empty($field['encrypted'])) {
                $value = $request->input($name);
                if (filled($value)) {
                    $current[$name] = Crypt::encryptString($value);
                    $changed[] = $name;
                }
                continue;
            }

            // ----- Field thường -----
            if ($request->exists($name)) {
                $value = $field['type'] === 'checkbox' ? $request->boolean($name) : $request->input($name);
                $current[$name] = $value;
                $changed[] = $name;
            }
        }

        $current['updated_at'] = now()->toISOString();
        $provision->update(['provision_data' => json_encode($current)]);

        // Audit — KHÔNG log giá trị field nhạy cảm, chỉ log tên field đã đổi.
        // Bọc try/catch: lỗi ghi log không bao giờ được làm hỏng việc lưu thông số.
        $secret   = ServiceParameterSchema::encryptedFields($type);
        $loggable = array_values(array_diff($changed, $secret));
        try {
            ProvisionLog::create([
                'provision_id' => $provision->id,
                'action'       => 'service_params_updated',
                'performed_by' => auth()->id(),
                'notes'        => 'CS#' . $service->id . ' — cập nhật: ' . (implode(', ', $loggable) ?: '(chỉ field nhạy cảm)'),
                'source'       => 'manual',
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProvisionLog service_params_updated thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Danh sách field để HIỂN THỊ CHO KHÁCH (portal) theo provision.
     * Giải mã field nhạy cảm, map select→label, bỏ ghi chú nội bộ và field rỗng.
     * Trả về list ['label','value','secret','file','kind'].
     */
    public function customerFieldsForProvision(ServiceProvision $provision, ?CustomerService $service = null): array
    {
        $type = $provision->provision_type;
        $data = $provision->provision_data;
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $data = is_array($data) ? $data : [];

        $rows = [];
        foreach (ServiceParameterSchema::forType($type) as $field) {
            $name = $field['name'];
            if ($name === 'notes') {
                continue; // ghi chú nội bộ — không cho khách xem
            }

            // File SSL: chỉ hiển thị nút tải nếu file tồn tại (cần CS để biết path).
            if (!empty($field['file'])) {
                if ($service && $this->sslFilePath($service, $name) !== null) {
                    $rows[] = ['label' => $field['label'], 'value' => '', 'secret' => false, 'file' => true, 'kind' => $name];
                }
                continue;
            }

            $value = $data[$name] ?? '';
            if (!empty($field['encrypted']) && $value !== '' && $value !== null) {
                $value = $this->safeDecrypt($value);
            }
            if ($value === '' || $value === null) {
                continue;
            }
            if (($field['type'] ?? '') === 'select' && isset($field['options'][$value])) {
                $value = $field['options'][$value];
            }

            $rows[] = [
                'label'  => $field['label'],
                'value'  => $value,
                'secret' => !empty($field['encrypted']),
                'file'   => false,
                'kind'   => $name,
            ];
        }
        return $rows;
    }

    /** Đường dẫn file SSL (relative trên disk) nếu có. */
    public function sslFilePath(CustomerService $service, string $kind): ?string
    {
        return $this->rawData($service)[$kind . '_path'] ?? null;
    }

    /** Đọc nội dung file SSL (giải mã nếu là private key). Null nếu không có. */
    public function readSslFile(CustomerService $service, string $kind): ?string
    {
        $path = $this->sslFilePath($service, $kind);
        if (!$path) {
            return null;
        }
        // Dùng hàm file thuần thay cho Storage facade — Storage::disk('local') kích hoạt
        // bộ dò MIME (finfo) vốn không có trên VPS (Class "finfo" not found).
        $abs = storage_path('app/' . $path);
        if (!is_file($abs)) {
            return null;
        }
        $raw = file_get_contents($abs);
        if ($kind === 'private_key') {
            return $this->safeDecrypt($raw);
        }
        return $raw;
    }

    // ===== private =====

    private function storeSslFile(CustomerService $service, string $kind, string $content, bool $encrypt): string
    {
        $path = 'ssl/' . $service->id . '/' . (self::SSL_FILES[$kind] ?? ($kind . '.pem'));
        // Ghi bằng hàm file thuần (tránh Storage facade → finfo).
        $abs = storage_path('app/' . $path);
        if (!is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0755, true);
        }
        file_put_contents($abs, $encrypt ? Crypt::encryptString($content) : $content);
        return $path;
    }

    private function safeDecrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
