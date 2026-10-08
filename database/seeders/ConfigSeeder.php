<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    public function run()
    {
        Config::create([
            // Thông tin cơ bản website
            'site_name' => 'Hosttist',
            'url' => 'https://hosttist.com',
            'description' => 'Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng.',
            'keywords' => 'hosting, VPS, tên miền, SSL, Hosttist',
            'author' => 'Hosttist',
            'company_name' => 'Hosttist',
            'company_email' => 'admin@hosttist.com',

            // Theme và Giao diện
            'theme_color' => '#2563eb', // Blue-600
            'favicon' => '/images/favicon.ico',
            'og_image' => '/images/og-image.jpg',
            'no_thumb_image' => '/images/no-thumb.jpg',

            // Social Media
            'facebook_author' => '',
            'facebook_page' => '',
            'fb_app_id' => '',
            'twitter_creator' => '',

            // Google Adsense
            'adsense_platform_account' => '',
            'adsense_platform_domain' => '',
            'adsense_non_personalized' => true,

            // Blog/Content Settings
            'related_posts_num' => 4,
            'posts_per_page' => 12,
            'comments_system' => 'facebook',
            'disqus_shortname' => '',

            // Ngôn ngữ
            'months_name' => json_encode([
                1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3',
                4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6',
                7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9',
                10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
            ]),
            'page_of_text' => json_encode([
                'vi' => 'Trang',
                'en' => 'Page'
            ]),

            // Text UI
            'show_more_text' => 'Xem thêm',
            'follow_by_email_text' => 'Đăng ký nhận tin',
            'related_posts_text' => 'Bài viết liên quan',
            'load_more_text' => 'Tải thêm',

            // Cookie Consent
            'cookie_message' => 'Website này sử dụng cookie để đảm bảo bạn có được trải nghiệm tốt nhất.',
            'cookie_accept_text' => 'Đồng ý',
            'cookie_learn_more_text' => 'Tìm hiểu thêm',
            'cookie_policy_url' => '/cookie-policy',

            // Feature Flags
            'enable_rss' => true,
            'enable_adsense' => false,
            'enable_twitter' => true,
            'enable_youtube' => true,
            'enable_instagram' => true,
            'enable_pinterest' => false,
            'enable_linkedin' => true,
            'enable_disqus' => false,
            'enable_github' => true,
            'enable_gravatar' => true,

            // Additional URLs
            'blog_service_url' => '/blog',
            'profile_url' => '/profile',
        ]);
    }
}
