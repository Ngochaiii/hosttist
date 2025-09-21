<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Categories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'status',
        'sort_order',
        'meta_data', // Thêm field này
    ];

    // Cast meta_data thành JSON
    protected $casts = [
        'meta_data' => 'array',
        'sort_order' => 'integer',
    ];

    // Định nghĩa các loại dịch vụ và fields cần thiết
    const SERVICE_TYPES = [
        'ssl' => [
            'label' => 'SSL Certificate',
            'fields' => [
                [
                    'name' => 'domain',
                    'label' => 'Tên miền',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'example.com',
                    'validation' => 'domain',
                    'description' => 'Nhập tên miền cần cài đặt SSL'
                ]
            ]
        ],
        'vps' => [
            'label' => 'VPS/Cloud Server',
            'fields' => [
                [
                    'name' => 'username',
                    'label' => 'Tên đăng nhập',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'username',
                    'validation' => 'alphanumeric',
                    'description' => 'Tên đăng nhập cho VPS (3-16 ký tự, chỉ chữ và số)'
                ],
                [
                    'name' => 'os',
                    'label' => 'Hệ điều hành',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'ubuntu-22.04' => 'Ubuntu 22.04 LTS',
                        'ubuntu-20.04' => 'Ubuntu 20.04 LTS',
                        'centos-7' => 'CentOS 7',
                        'debian-11' => 'Debian 11',
                        'windows-2019' => 'Windows Server 2019'
                    ],
                    'default' => 'ubuntu-22.04'
                ]
            ]
        ],
        'domain' => [
            'label' => 'Tên miền',
            'fields' => [
                [
                    'name' => 'domain',
                    'label' => 'Tên miền',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'example.com',
                    'validation' => 'domain',
                    'description' => 'Tên miền muốn đăng ký'
                ],
                [
                    'name' => 'dns_management',
                    'label' => 'Quản lý DNS',
                    'type' => 'checkbox',
                    'required' => false,
                    'default' => true,
                    'description' => 'Sử dụng DNS của chúng tôi'
                ]
            ]
        ],
        'advertising' => [
            'label' => 'Chạy quảng cáo',
            'fields' => [
                [
                    'name' => 'platform',
                    'label' => 'Nền tảng',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'facebook' => 'Facebook',
                        'tiktok' => 'TikTok',
                        'google' => 'Google Ads',
                        'youtube' => 'YouTube'
                    ],
                    'default' => 'facebook'
                ],
                [
                    'name' => 'link',
                    'label' => 'Link Fanpage/Tài khoản',
                    'type' => 'url',
                    'required' => true,
                    'placeholder' => 'https://facebook.com/yourpage',
                    'validation' => 'url',
                    'description' => 'Link fanpage hoặc tài khoản cần chạy quảng cáo'
                ],
                [
                    'name' => 'budget',
                    'label' => 'Ngân sách dự kiến/tháng',
                    'type' => 'number',
                    'required' => false,
                    'min' => 1000000,
                    'step' => 100000,
                    'placeholder' => '5000000',
                    'description' => 'Ngân sách quảng cáo hàng tháng (VNĐ)'
                ]
            ]
        ],
        'web_design' => [
            'label' => 'Thiết kế website',
            'fields' => [
                [
                    'name' => 'phone',
                    'label' => 'Số điện thoại',
                    'type' => 'tel',
                    'required' => true,
                    'placeholder' => '0901234567',
                    'validation' => 'phone_vn',
                    'description' => 'Số điện thoại liên hệ'
                ],
                [
                    'name' => 'business_type',
                    'label' => 'Loại hình kinh doanh',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Ví dụ: Nhà hàng, Spa, Bất động sản...',
                    'description' => 'Lĩnh vực kinh doanh của bạn'
                ],
                [
                    'name' => 'reference_url',
                    'label' => 'Website mẫu (nếu có)',
                    'type' => 'url',
                    'required' => false,
                    'placeholder' => 'https://example.com',
                    'description' => 'Link website mẫu bạn thích'
                ]
            ]
        ],
        'hosting' => [
            'label' => 'Web Hosting',
            'fields' => [
                [
                    'name' => 'domain',
                    'label' => 'Tên miền',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'example.com (tùy chọn)',
                    'validation' => 'domain',
                    'description' => 'Tên miền sẽ sử dụng cho hosting (nếu có)'
                ],
                [
                    'name' => 'migration_from',
                    'label' => 'Chuyển từ hosting cũ',
                    'type' => 'checkbox',
                    'required' => false,
                    'description' => 'Bạn cần hỗ trợ chuyển dữ liệu từ hosting cũ?'
                ]
            ]
        ],
        'email' => [
            'label' => 'Email doanh nghiệp',
            'fields' => [
                [
                    'name' => 'domain',
                    'label' => 'Tên miền',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'example.com',
                    'validation' => 'domain',
                    'description' => 'Tên miền cho email doanh nghiệp'
                ],
                [
                    'name' => 'num_accounts',
                    'label' => 'Số lượng tài khoản',
                    'type' => 'number',
                    'required' => true,
                    'min' => 1,
                    'max' => 1000,
                    'default' => 5,
                    'description' => 'Số lượng tài khoản email cần tạo'
                ],
                [
                    'name' => 'main_email',
                    'label' => 'Email chính',
                    'type' => 'email',
                    'required' => false,
                    'placeholder' => 'admin@example.com',
                    'description' => 'Email quản trị chính'
                ]
            ]
        ],
        'seo' => [
            'label' => 'Dịch vụ SEO',
            'fields' => [
                [
                    'name' => 'website_url',
                    'label' => 'Website cần SEO',
                    'type' => 'url',
                    'required' => true,
                    'placeholder' => 'https://yourwebsite.com',
                    'validation' => 'url',
                    'description' => 'Link website cần SEO'
                ],
                [
                    'name' => 'keywords',
                    'label' => 'Từ khóa mục tiêu',
                    'type' => 'textarea',
                    'required' => true,
                    'placeholder' => 'Nhập các từ khóa, mỗi từ một dòng',
                    'description' => 'Các từ khóa bạn muốn lên top'
                ]
            ]
        ]
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Categories::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Categories::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }

    // Helper Methods
    public function getServiceType()
    {
        // Nếu meta_data là string, decode nó
        if (is_string($this->meta_data)) {
            $metaData = json_decode($this->meta_data, true);
            return $metaData['service_type'] ?? null;
        }

        // Nếu đã là array
        return $this->meta_data['service_type'] ?? null;
    }

    public function getServiceFields()
    {
        $type = $this->getServiceType();

        // Debug để xem type là gì
        Log::info('Service type: ' . $type);

        if ($type && isset(self::SERVICE_TYPES[$type])) {
            return self::SERVICE_TYPES[$type]['fields'] ?? [];
        }

        return [];
    }

    public function hasServiceFields()
    {
        return !empty($this->getServiceFields());
    }

    public function getServiceLabel()
    {
        $type = $this->getServiceType();
        return $type ? (self::SERVICE_TYPES[$type]['label'] ?? '') : '';
    }
    /**
     * Scope để lấy active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope để lấy parent categories
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Kiểm tra có thể xóa category không
     */
    public function canBeDeleted()
    {
        return $this->children()->count() == 0 && $this->products()->count() == 0;
    }

    /**
     * Lấy full path của category (Parent > Child)
     */
    public function getFullPath()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    /**
     * Lấy all available service types
     */
    public static function getAvailableServiceTypes()
    {
        return array_keys(self::SERVICE_TYPES);
    }

    /**
     * Validate service type
     */
    public static function isValidServiceType($type)
    {
        return in_array($type, self::getAvailableServiceTypes());
    }
    
}
