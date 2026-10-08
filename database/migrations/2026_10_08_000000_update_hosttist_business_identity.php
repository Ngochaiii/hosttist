<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'site_name' => ['TechAccount Store', 'Hosttist'],
            'url' => ['https://techaccountstore.com', 'https://hosttist.com'],
            'author' => ['TechAccount Store', 'Hosttist'],
            'description' => ['Chuyên cung cấp các giải pháp công nghệ, tài khoản premium, thiết kế website, lắp đặt PC Gaming. Đem đến những sản phẩm và dịch vụ công nghệ chất lượng cao với giá cả hợp lý.', 'Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng.'],
            'keywords' => ['tài khoản premium, thiết kế website, lắp đặt PC, case máy tính, giải pháp công nghệ, account game, tài khoản Netflix, tài khoản Spotify', 'hosting, VPS, tên miền, SSL, Hosttist'],
            'facebook_author' => ['TechAccountStore', ''],
            'facebook_page' => ['TechAccountStorePage', ''],
            'fb_app_id' => ['123456789', ''],
            'twitter_creator' => ['@techaccountstore', ''],
            'disqus_shortname' => ['techaccountstore', ''],
        ];

        foreach ($replacements as $column => [$old, $new]) {
            DB::table('configs')->where($column, $old)->update([$column => $new]);
        }

        DB::table('configs')->where(function ($query) {
            $query->whereNull('company_name')->orWhereIn('company_name', ['', 'Hostist company', 'TechAccount Store']);
        })->update(['company_name' => 'Hosttist']);

        DB::table('configs')->update(['company_email' => 'admin@hosttist.com']);
    }

    public function down(): void
    {
        // Giữ thông tin doanh nghiệp khi rollback để không khôi phục nội dung mẫu.
    }
};
