<?php

namespace Database\Seeders;

use App\Models\Categories;
use App\Models\Products;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Gói Cloud Server tham khảo từ longvan.net/cloud-server (giá 7/2026).
 * Chạy: php artisan db:seed --class=CloudServerSeeder
 * Chạy lại an toàn — updateOrCreate theo slug.
 */
class CloudServerSeeder extends Seeder
{
    public function run(): void
    {
        $category = Categories::updateOrCreate(
            ['slug' => 'cloud-server'],
            [
                'name'        => 'Cloud Server',
                'description' => 'Cloud Server NVMe tốc độ cao — từ gói phổ thông cho website, ứng dụng nhỏ đến gói Xeon Gold chuyên dụng cho hệ thống tải nặng. Kích hoạt nhanh, backup hằng ngày, network 10Gbps.',
                'status'      => 'active',
                'sort_order'  => 2,
                'meta_data'   => ['service_type' => 'vps'],
            ]
        );

        $plans = [
            // [name, price, cpu, ram, storage, extra features]
            // --- Smart Cloud Server ---
            [
                'name'     => 'Cloud Server C1',
                'price'    => 49000,
                'cpu'      => 1,
                'ram'      => 2,
                'storage'  => 50,
                'features' => [
                    '1 vCPU, 2GB RAM, 50GB NVMe',
                    'Băng thông chia sẻ, traffic không giới hạn',
                    '1 IPv4 + IPv6, hỗ trợ hệ điều hành Linux',
                    'Backup tự động hằng ngày',
                    'Giá ưu đãi khi cam kết 12 tháng',
                ],
                'short'    => 'Gói khởi đầu tiết kiệm — phù hợp website cá nhân, blog, môi trường học tập và thử nghiệm.',
            ],
            [
                'name'     => 'Cloud Server C2',
                'price'    => 242250,
                'cpu'      => 2,
                'ram'      => 4,
                'storage'  => 50,
                'features' => [
                    '2 vCPU, 4GB RAM, 50GB NVMe',
                    'Băng thông 200Mb/s, traffic không giới hạn',
                    'Network nội bộ 10Gb/s',
                    '1 IPv4 + IPv6, Linux/Windows',
                    'Backup tự động hằng ngày',
                ],
                'short'    => 'Cân bằng giữa chi phí và hiệu năng — chạy tốt website doanh nghiệp, landing page, cửa hàng nhỏ.',
            ],
            [
                'name'     => 'Cloud Server C3',
                'price'    => 442000,
                'cpu'      => 4,
                'ram'      => 4,
                'storage'  => 100,
                'features' => [
                    '4 vCPU, 4GB RAM, 100GB NVMe',
                    'Băng thông 200Mb/s, traffic không giới hạn',
                    'Network nội bộ 10Gb/s',
                    '1 IPv4 + IPv6, Linux/Windows',
                    'Backup tự động hằng ngày',
                ],
                'short'    => 'Nhiều nhân CPU cho website traffic khá, WordPress nhiều plugin, hệ thống quản lý nội bộ.',
            ],
            [
                'name'     => 'Cloud Server C4',
                'price'    => 501500,
                'cpu'      => 4,
                'ram'      => 8,
                'storage'  => 100,
                'features' => [
                    '4 vCPU, 8GB RAM, 100GB NVMe',
                    'Băng thông 200Mb/s, traffic không giới hạn',
                    'Network nội bộ 10Gb/s',
                    '1 IPv4 + IPv6, Linux/Windows',
                    'Backup tự động hằng ngày',
                ],
                'short'    => 'RAM gấp đôi cho ứng dụng web, API và database vừa — vận hành mượt khi tải tăng.',
            ],
            // --- Cloud Server chuyên dụng Xeon Gold ---
            [
                'name'     => 'Cloud Server XD1 Xeon Gold',
                'price'    => 1272000,
                'cpu'      => 8,
                'ram'      => 8,
                'storage'  => 300,
                'features' => [
                    '8 Core Xeon Gold, 8GB DDR4, 300GB NVMe Pro',
                    'Internet 400Mbps + 300Mbps miễn phí',
                    'Network nội bộ 10Gbps',
                    '1 IPv4, backup tự động hằng ngày',
                    'CPU chuyên dụng — không chia sẻ tài nguyên',
                ],
                'short'    => 'CPU Xeon Gold chuyên dụng — hiệu năng ổn định cho hệ thống production đòi hỏi cao.',
            ],
            [
                'name'     => 'Cloud Server XD2 Xeon Gold',
                'price'    => 2208000,
                'cpu'      => 16,
                'ram'      => 16,
                'storage'  => 300,
                'features' => [
                    '16 Core Xeon Gold, 16GB DDR4, 300GB NVMe Pro',
                    'Internet 400Mbps + 300Mbps miễn phí',
                    'Network nội bộ 10Gbps',
                    '1 IPv4, backup tự động hằng ngày',
                    'CPU chuyên dụng — không chia sẻ tài nguyên',
                ],
                'short'    => 'Sức mạnh 16 nhân cho ứng dụng doanh nghiệp, hệ thống e-commerce lớn, CI/CD.',
            ],
            [
                'name'     => 'Cloud Server XD3 Xeon Gold',
                'price'    => 2648000,
                'cpu'      => 24,
                'ram'      => 32,
                'storage'  => 300,
                'features' => [
                    '24 Core Xeon Gold, 32GB DDR4, 300GB NVMe Pro',
                    'Internet 400Mbps + 300Mbps miễn phí',
                    'Network nội bộ 10Gbps',
                    '1 IPv4, backup tự động hằng ngày',
                    'CPU chuyên dụng — không chia sẻ tài nguyên',
                ],
                'short'    => 'Xử lý dữ liệu nặng, game server, hệ thống nhiều dịch vụ chạy đồng thời.',
            ],
            [
                'name'     => 'Cloud Server XD4 Xeon Gold',
                'price'    => 3236000,
                'cpu'      => 32,
                'ram'      => 64,
                'storage'  => 300,
                'features' => [
                    '32 Core Xeon Gold, 64GB DDR4, 300GB NVMe Pro',
                    'Internet 400Mbps + 300Mbps miễn phí',
                    'Network nội bộ 10Gbps',
                    '1 IPv4, backup tự động hằng ngày',
                    'CPU chuyên dụng — không chia sẻ tài nguyên',
                ],
                'short'    => 'Cấu hình cao nhất — AI, big data, ảo hóa lồng nhau và hạ tầng cho nhiều ứng dụng lớn.',
            ],
        ];

        foreach ($plans as $index => $plan) {
            $slug = Str::slug($plan['name']);

            Products::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id'       => $category->id,
                    'name'              => $plan['name'],
                    'sku'               => strtoupper(str_replace('cloud-server-', 'CLOUD-', $slug)),
                    'short_description' => $plan['short'],
                    'description'       => '<ul>' . implode('', array_map(fn ($f) => '<li>' . e($f) . '</li>', $plan['features'])) . '</ul>',
                    'price'             => $plan['price'],
                    'type'              => 'product',
                    'product_status'    => 'active',
                    'stock'             => -1,
                    'is_recurring'      => true,
                    'recurring_period'  => 1, // tháng
                    'is_featured'       => false,
                    'sort_order'        => $index + 1,
                    'meta_data'         => [
                        'features' => $plan['features'],
                        'specs'    => [
                            'cpu'     => $plan['cpu'],
                            'ram'     => $plan['ram'],
                            'storage' => $plan['storage'],
                        ],
                    ],
                ]
            );
        }
    }
}
