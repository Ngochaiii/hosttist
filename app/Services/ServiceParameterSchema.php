<?php

namespace App\Services;

/**
 * Nguồn sự thật DUY NHẤT về các trường thông số kỹ thuật của một dịch vụ ĐANG CHẠY,
 * dùng cho module admin "Quản lý dịch vụ đang chạy".
 *
 * Khác với Categories::SERVICE_TYPES (field lúc khách đặt hàng) — ở đây là field
 * vận hành mà admin chỉnh sau khi dịch vụ đã active (vd VPS: IP, root password, specs).
 *
 * Mỗi field: [
 *   'name'     => string,                       // key trong provision_data
 *   'label'    => string,
 *   'type'     => text|password|textarea|select|number|date|email|url|checkbox,
 *   'required' => bool (mặc định false),
 *   'options'  => [value => label]   // chỉ với select
 *   'encrypted'=> bool,              // lưu bằng encrypt(), hiển thị mask
 *   'file'     => bool,              // cho phép upload file đính kèm (SSL)
 *   'accept'   => string,           // accept attr cho input file
 *   'readonly' => bool,
 *   'help'     => string,
 * ]
 */
class ServiceParameterSchema
{
    /** Các loại được module này quản lý trực tiếp (không gồm domain — domain có module riêng). */
    public const MANAGED_TYPES = [
        'vps', 'ssl', 'hosting', 'cloud_hosting',
        'advertising', 'web_design', 'seo', 'email',
    ];

    public static function forType(?string $type): array
    {
        return match ($type) {
            'vps'                     => self::vps(),
            'ssl'                     => self::ssl(),
            'hosting', 'cloud_hosting' => self::hosting(),
            'advertising'             => self::advertising(),
            'web_design'              => self::webDesign(),
            'seo'                     => self::seo(),
            'email'                   => self::email(),
            default                   => self::generic(),
        };
    }

    /** Domain dùng module kho tên miền riêng → không sửa thông số tại đây. */
    public static function isManaged(?string $type): bool
    {
        return in_array($type, self::MANAGED_TYPES, true);
    }

    /** Tên các field nhạy cảm (mã hóa) của một loại — tiện cho việc loại trừ khỏi log. */
    public static function encryptedFields(?string $type): array
    {
        return array_values(array_map(
            fn ($f) => $f['name'],
            array_filter(self::forType($type), fn ($f) => !empty($f['encrypted']))
        ));
    }

    /** Các field hỗ trợ upload file (SSL). */
    public static function fileFields(?string $type): array
    {
        return array_values(array_filter(self::forType($type), fn ($f) => !empty($f['file'])));
    }

    // ===== Định nghĩa theo loại =====

    private static function vps(): array
    {
        return [
            ['name' => 'server_name', 'label' => 'Tên server / Hostname', 'type' => 'text'],
            ['name' => 'server_ip', 'label' => 'IP', 'type' => 'text', 'help' => 'Địa chỉ IPv4'],
            ['name' => 'os', 'label' => 'Hệ điều hành', 'type' => 'select', 'options' => [
                'ubuntu-22.04' => 'Ubuntu 22.04 LTS',
                'ubuntu-20.04' => 'Ubuntu 20.04 LTS',
                'debian-12'    => 'Debian 12',
                'debian-11'    => 'Debian 11',
                'centos-7'     => 'CentOS 7',
                'almalinux-9'  => 'AlmaLinux 9',
                'windows-2019' => 'Windows Server 2019',
                'windows-2022' => 'Windows Server 2022',
            ]],
            ['name' => 'username', 'label' => 'Tài khoản (root/admin)', 'type' => 'text'],
            ['name' => 'password', 'label' => 'Mật khẩu', 'type' => 'password', 'encrypted' => true,
                'help' => 'Mã hóa trước khi lưu. Để trống nếu không đổi.'],
            ['name' => 'cpu', 'label' => 'CPU (vCPU)', 'type' => 'number'],
            ['name' => 'ram', 'label' => 'RAM (GB)', 'type' => 'number'],
            ['name' => 'disk', 'label' => 'Ổ cứng (GB)', 'type' => 'number'],
            ['name' => 'bandwidth', 'label' => 'Băng thông (GB)', 'type' => 'number'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function ssl(): array
    {
        return [
            ['name' => 'domain', 'label' => 'Tên miền', 'type' => 'text'],
            ['name' => 'ssl_provider', 'label' => 'Nhà cung cấp', 'type' => 'select', 'options' => [
                'letsencrypt' => "Let's Encrypt",
                'comodo'      => 'Comodo',
                'digicert'    => 'DigiCert',
                'sectigo'     => 'Sectigo',
                'other'       => 'Khác',
            ]],
            ['name' => 'certificate', 'label' => 'Certificate (.crt)', 'type' => 'textarea',
                'file' => true, 'accept' => '.crt,.pem,.cer,.txt',
                'help' => 'Dán nội dung hoặc tải file lên.'],
            ['name' => 'private_key', 'label' => 'Private Key (.key)', 'type' => 'textarea',
                'file' => true, 'accept' => '.key,.pem,.txt', 'encrypted' => true,
                'help' => 'Mã hóa khi lưu. Để trống nếu không đổi.'],
            ['name' => 'ca_bundle', 'label' => 'CA Bundle', 'type' => 'textarea',
                'file' => true, 'accept' => '.crt,.pem,.ca-bundle,.txt'],
            ['name' => 'valid_from', 'label' => 'Hiệu lực từ', 'type' => 'date'],
            ['name' => 'valid_to', 'label' => 'Hiệu lực đến', 'type' => 'date'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function hosting(): array
    {
        return [
            ['name' => 'server_name', 'label' => 'Tên server', 'type' => 'text'],
            ['name' => 'server_ip', 'label' => 'IP', 'type' => 'text'],
            ['name' => 'control_panel', 'label' => 'Control Panel', 'type' => 'select', 'options' => [
                'cpanel'      => 'cPanel',
                'plesk'       => 'Plesk',
                'directadmin' => 'DirectAdmin',
            ]],
            ['name' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['name' => 'password', 'label' => 'Mật khẩu', 'type' => 'password', 'encrypted' => true,
                'help' => 'Mã hóa trước khi lưu. Để trống nếu không đổi.'],
            ['name' => 'ftp_details', 'label' => 'Thông tin FTP', 'type' => 'textarea'],
            ['name' => 'disk_space', 'label' => 'Dung lượng (GB)', 'type' => 'number'],
            ['name' => 'bandwidth', 'label' => 'Băng thông (GB)', 'type' => 'number'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function advertising(): array
    {
        return [
            ['name' => 'platform', 'label' => 'Nền tảng', 'type' => 'select', 'options' => [
                'facebook' => 'Facebook',
                'tiktok'   => 'TikTok',
                'google'   => 'Google Ads',
                'youtube'  => 'YouTube',
            ]],
            ['name' => 'link', 'label' => 'Link Fanpage/Tài khoản', 'type' => 'url'],
            ['name' => 'account_id', 'label' => 'ID tài khoản quảng cáo', 'type' => 'text'],
            ['name' => 'campaign_status', 'label' => 'Trạng thái chiến dịch', 'type' => 'select', 'options' => [
                'setup'   => 'Đang setup',
                'running' => 'Đang chạy',
                'paused'  => 'Tạm dừng',
                'ended'   => 'Đã kết thúc',
            ]],
            ['name' => 'budget', 'label' => 'Ngân sách/tháng (VNĐ)', 'type' => 'number'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function webDesign(): array
    {
        return [
            ['name' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text'],
            ['name' => 'business_type', 'label' => 'Loại hình kinh doanh', 'type' => 'text'],
            ['name' => 'project_status', 'label' => 'Tiến độ', 'type' => 'select', 'options' => [
                'survey'    => 'Khảo sát',
                'designing' => 'Đang thiết kế',
                'review'    => 'Chờ duyệt',
                'delivered' => 'Đã bàn giao',
            ]],
            ['name' => 'reference_url', 'label' => 'Website mẫu', 'type' => 'url'],
            ['name' => 'demo_url', 'label' => 'Link demo/bàn giao', 'type' => 'url'],
            ['name' => 'admin_account', 'label' => 'Tài khoản quản trị bàn giao', 'type' => 'textarea',
                'encrypted' => true, 'help' => 'Mã hóa khi lưu. Để trống nếu không đổi.'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function seo(): array
    {
        return [
            ['name' => 'website_url', 'label' => 'Website cần SEO', 'type' => 'url'],
            ['name' => 'keywords', 'label' => 'Từ khóa mục tiêu', 'type' => 'textarea'],
            ['name' => 'report_url', 'label' => 'Link báo cáo', 'type' => 'url'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function email(): array
    {
        return [
            ['name' => 'domain', 'label' => 'Tên miền', 'type' => 'text'],
            ['name' => 'num_accounts', 'label' => 'Số tài khoản', 'type' => 'number'],
            ['name' => 'main_email', 'label' => 'Email chính', 'type' => 'email'],
            ['name' => 'webmail_url', 'label' => 'Link webmail', 'type' => 'url'],
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }

    private static function generic(): array
    {
        return [
            ['name' => 'notes', 'label' => 'Ghi chú nội bộ', 'type' => 'textarea'],
        ];
    }
}
