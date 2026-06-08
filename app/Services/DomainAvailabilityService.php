<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Kiểm tra tên miền còn trống hay đã đăng ký.
 *
 * gTLD (.com/.net/.org/.info/.xyz...): dùng RDAP miễn phí qua rdap.org
 *   - HTTP 404 → còn trống
 *   - HTTP 200 → đã đăng ký
 * .vn / .com.vn: RDAP công khai không ổn định → trả 'unknown' kèm hướng dẫn
 *   kiểm tra thủ công trên Nhân Hòa (đúng mô hình fulfill thủ công).
 */
class DomainAvailabilityService
{
    private const RDAP_ENDPOINT = 'https://rdap.org/domain/';

    public const AVAILABLE = 'available';
    public const TAKEN     = 'taken';
    public const UNKNOWN   = 'unknown';
    public const INVALID   = 'invalid';

    public function check(string $domain): array
    {
        $domain = strtolower(trim($domain));

        if (!$this->isValidFormat($domain)) {
            return $this->result($domain, self::INVALID, null, 'Tên miền không hợp lệ');
        }

        // .vn không tra cứu tự động đáng tin → để admin kiểm tra tay.
        if (str_ends_with($domain, '.vn')) {
            return $this->result($domain, self::UNKNOWN, null,
                'Đuôi .vn: vui lòng kiểm tra trực tiếp trên Nhân Hòa');
        }

        try {
            $resp = Http::timeout(6)
                ->withHeaders(['Accept' => 'application/rdap+json'])
                ->get(self::RDAP_ENDPOINT . $domain);
        } catch (\Throwable $e) {
            return $this->result($domain, self::UNKNOWN, null,
                'Không kiểm tra được (lỗi kết nối), thử lại sau');
        }

        if ($resp->status() === 404) {
            return $this->result($domain, self::AVAILABLE, true, 'Tên miền còn trống');
        }
        if ($resp->successful()) {
            return $this->result($domain, self::TAKEN, false, 'Tên miền đã được đăng ký');
        }

        return $this->result($domain, self::UNKNOWN, null,
            'Chưa xác định được trạng thái (HTTP ' . $resp->status() . ')');
    }

    private function isValidFormat(string $domain): bool
    {
        // Ít nhất 1 dấu chấm, mỗi nhãn 1-63 ký tự chữ-số-gạch (không bắt đầu/kết thúc bằng gạch).
        return (bool) preg_match(
            '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/',
            $domain
        );
    }

    private function result(string $domain, string $status, ?bool $available, string $message): array
    {
        return compact('domain', 'status', 'available', 'message');
    }
}
