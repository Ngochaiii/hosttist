<?php

namespace Database\Seeders;

use App\Models\DomainTld;
use Illuminate\Database\Seeder;

/**
 * Vài đuôi mẫu để bắt đầu — giá tham khảo, admin chỉnh lại trong panel.
 * Chạy: php artisan db:seed --class=DomainTldSeeder
 */
class DomainTldSeeder extends Seeder
{
    public function run(): void
    {
        $tlds = [
            // tld,    is_vn, register_cost, renew_cost, transfer_cost, markup_value, sort
            ['com',    false, 280000, 290000, 280000, 70000, 1],
            ['net',    false, 290000, 300000, 290000, 70000, 2],
            ['vn',     true,  480000, 480000, null,   120000, 3],
            ['com.vn', true,  350000, 350000, null,   100000, 4],
        ];

        foreach ($tlds as [$tld, $isVn, $regCost, $renewCost, $transferCost, $markup, $sort]) {
            DomainTld::updateOrCreate(
                ['tld' => $tld],
                [
                    'is_vn'         => $isVn,
                    'register_cost' => $regCost,
                    'renew_cost'    => $renewCost,
                    'transfer_cost' => $transferCost,
                    'markup_type'   => DomainTld::MARKUP_AMOUNT,
                    'markup_value'  => $markup,
                    'round_to'      => 1000,
                    'min_years'     => 1,
                    'max_years'     => 10,
                    'is_active'     => true,
                    'sort_order'    => $sort,
                ]
            );
        }
    }
}
