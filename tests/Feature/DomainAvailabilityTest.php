<?php

namespace Tests\Feature;

use App\Services\DomainAvailabilityService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3 — kiểm tra khả dụng tên miền. Dùng Http::fake để giả lập RDAP,
 * không gọi mạng thật, không cần DB.
 */
class DomainAvailabilityTest extends TestCase
{
    private function svc(): DomainAvailabilityService
    {
        return app(DomainAvailabilityService::class);
    }

    /** RDAP 404 → tên miền còn trống. */
    public function test_rdap_404_means_available(): void
    {
        Http::fake(['rdap.org/*' => Http::response('', 404)]);

        $r = $this->svc()->check('ten-mien-con-trong-xyz123.com');

        $this->assertSame(DomainAvailabilityService::AVAILABLE, $r['status']);
        $this->assertTrue($r['available']);
    }

    /** RDAP 200 → tên miền đã đăng ký. */
    public function test_rdap_200_means_taken(): void
    {
        Http::fake(['rdap.org/*' => Http::response(['ldhName' => 'GOOGLE.COM'], 200)]);

        $r = $this->svc()->check('google.com');

        $this->assertSame(DomainAvailabilityService::TAKEN, $r['status']);
        $this->assertFalse($r['available']);
    }

    /** Sai định dạng → invalid, không gọi mạng. */
    public function test_invalid_format(): void
    {
        Http::fake();

        $r = $this->svc()->check('khong-phai-domain');

        $this->assertSame(DomainAvailabilityService::INVALID, $r['status']);
        $this->assertNull($r['available']);
        Http::assertNothingSent();
    }

    /** .vn → unknown (kiểm tra thủ công), không gọi RDAP. */
    public function test_vn_returns_unknown_without_http(): void
    {
        Http::fake();

        $r = $this->svc()->check('vidu.vn');

        $this->assertSame(DomainAvailabilityService::UNKNOWN, $r['status']);
        $this->assertStringContainsString('Nhân Hòa', $r['message']);
        Http::assertNothingSent();
    }

    /** Lỗi mạng / 5xx → unknown (không khẳng định sai). */
    public function test_server_error_means_unknown(): void
    {
        Http::fake(['rdap.org/*' => Http::response('', 503)]);

        $r = $this->svc()->check('something.net');

        $this->assertSame(DomainAvailabilityService::UNKNOWN, $r['status']);
        $this->assertNull($r['available']);
    }
}
