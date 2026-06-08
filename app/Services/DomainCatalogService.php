<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\Products;
use Carbon\Carbon;
use Exception;

/**
 * Quản lý danh mục TLD và nhập tên miền.
 *
 * Phase 1: tách sld/tld, import domain đã mua sẵn từ Nhân Hòa (snapshot lãi).
 * (Sync product cho cart, fulfill đơn... ở các phase sau.)
 */
class DomainCatalogService extends BaseService
{
    /**
     * Tách 'abc.com.vn' → ['sld' => 'abc', 'tld' => 'com.vn', 'tld_model' => DomainTld|null].
     * Khớp đuôi DÀI NHẤT trong danh mục (để 'com.vn' thắng 'vn').
     */
    public function splitDomain(string $domainName): array
    {
        $domainName = strtolower(trim($domainName));
        $tlds = DomainTld::query()->pluck('tld')->all();

        $matched = null;
        foreach ($tlds as $tld) {
            if (str_ends_with($domainName, '.' . $tld)) {
                if ($matched === null || strlen($tld) > strlen($matched)) {
                    $matched = $tld;
                }
            }
        }

        if ($matched === null) {
            // Fallback: lấy phần sau dấu chấm đầu tiên (chưa có trong danh mục).
            $dot = strpos($domainName, '.');
            $matched = $dot === false ? '' : substr($domainName, $dot + 1);
        }

        $sld = $matched === '' ? $domainName : substr($domainName, 0, -(strlen($matched) + 1));

        return [
            'sld'       => $sld,
            'tld'       => $matched,
            'tld_model' => $matched ? DomainTld::where('tld', $matched)->first() : null,
        ];
    }

    /**
     * Category "Tên miền" (service_type=domain) — neo cho product domain.
     */
    public function ensureDomainCategory(): Categories
    {
        return Categories::firstOrCreate(
            ['slug' => 'ten-mien'],
            ['name' => 'Tên miền', 'status' => 'active', 'meta_data' => ['service_type' => 'domain']]
        );
    }

    /**
     * Product "neo" dùng chung cho mọi đơn domain — chỉ là móc nối để
     * order_items/service_provisions có product_id. Giá thực tính theo TLD,
     * nên product này để inactive (không hiển thị trong listing thường).
     */
    public function ensureAnchorProduct(): Products
    {
        $category = $this->ensureDomainCategory();

        return Products::firstOrCreate(
            ['sku' => 'DOMAIN-ANCHOR'],
            [
                'name'           => 'Đăng ký tên miền',
                'slug'           => 'dang-ky-ten-mien',
                'category_id'    => $category->id,
                'type'           => 'domain',
                'price'          => 0,
                'product_status' => 'inactive',
            ]
        );
    }

    /**
     * Dựng 1 dòng giỏ hàng cho đơn đăng ký domain MỚI.
     * Giá = register_price (đã gồm markup) × số năm. Snapshot cost/sell vào options
     * để fulfill và báo lãi về sau.
     *
     * @return array{product_id:int, name:string, unit_price:float, options:array}
     */
    public function buildCartLine(DomainTld $tld, string $domainName, int $years, array $registrant = [], bool $dnsManagement = true): array
    {
        $years      = max($tld->min_years, min($years, $tld->max_years));
        $domainName = strtolower(trim($domainName));
        $parts      = $this->splitDomain($domainName);

        $unitPrice = (float) $tld->register_price * $years;
        $costPrice = (float) $tld->register_cost * $years;

        $anchor = $this->ensureAnchorProduct();

        $options = [
            'service_type'   => 'domain',
            'domain'         => $domainName,
            'sld'            => $parts['sld'],
            'tld'            => $tld->tld,
            'tld_id'         => $tld->id,
            'period'         => $years, // khóa 'period' (năm) dùng chung với engine
            'years'          => $years,
            'dns_management' => $dnsManagement,
            'registrant'     => $registrant ?: null,
            'cost_price'     => $costPrice, // snapshot → báo lãi khi fulfill
            'sell_price'     => $unitPrice,
        ];

        return [
            'product_id' => $anchor->id,
            'name'       => 'Tên miền ' . $domainName . " ({$years} năm)",
            'unit_price' => $unitPrice,
            'options'    => $options,
        ];
    }

    /**
     * Import 1 tên miền đã mua sẵn từ Nhân Hòa vào hệ thống.
     *
     * @param array $data {
     *   domain_name* , customer_id?, cost_price* , sell_price?,
     *   years?, registered_at?, expires_at?, status?,
     *   registrant?(array), auth_code?, nameservers?(array), notes?
     * }
     * sell_price: nếu bỏ trống và đuôi có trong danh mục → tính theo markup của đuôi.
     */
    public function importExisting(array $data): Domain
    {
        if (empty($data['domain_name'])) {
            throw new Exception('Thiếu domain_name');
        }
        if (!isset($data['cost_price'])) {
            throw new Exception('Thiếu cost_price (giá gốc Nhân Hòa)');
        }

        return $this->transaction(function () use ($data) {
            $parts = $this->splitDomain($data['domain_name']);
            $cost  = (float) $data['cost_price'];

            // Giá bán: ưu tiên giá admin nhập tay; nếu trống thì tính từ markup của đuôi.
            $sell = isset($data['sell_price']) && $data['sell_price'] !== ''
                ? (float) $data['sell_price']
                : ($parts['tld_model']?->computePrice($cost) ?? $cost);

            $domain = Domain::create([
                'customer_id'   => $data['customer_id'] ?? null,
                'tld_id'        => $parts['tld_model']?->id,
                'domain_name'   => strtolower(trim($data['domain_name'])),
                'sld'           => $parts['sld'],
                'tld'           => $parts['tld'],
                'status'        => $data['status'] ?? Domain::STATUS_ACTIVE,
                'years'         => (int) ($data['years'] ?? 1),
                'registered_at' => isset($data['registered_at']) ? Carbon::parse($data['registered_at']) : now(),
                'expires_at'    => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                'cost_price'    => $cost,
                'sell_price'    => $sell,
                'profit'        => round($sell - $cost, 2),
                'registrant'    => $data['registrant'] ?? null,
                'auth_code'     => $data['auth_code'] ?? null,
                'nameservers'   => $data['nameservers'] ?? null,
                'registrar'     => $data['registrar'] ?? 'nhanhoa',
                'auto_renew'    => (bool) ($data['auto_renew'] ?? false),
                'source'        => Domain::SOURCE_IMPORT,
                'notes'         => $data['notes'] ?? null,
            ]);

            $this->logActivity('Domain imported', [
                'domain_id'   => $domain->id,
                'domain_name' => $domain->domain_name,
                'profit'      => $domain->profit,
            ]);

            return $domain;
        });
    }
}
