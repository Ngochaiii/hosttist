# Hostist VPS — Test Cases

> Tài liệu này mô tả các kịch bản cần kiểm thử thủ công (manual) và gợi ý cho automated test (PHPUnit/Pest).
> Ký hiệu: ✅ Pass | ❌ Fail | ⚠️ Edge case

---

## MODULE 1 — Authentication

### TC-AUTH-01: Đăng ký tài khoản mới
| Bước | Input | Expected |
|------|-------|----------|
| Truy cập `/register` | — | Form đăng ký hiển thị |
| Nhập email hợp lệ, password ≥ 8 ký tự | `test@gmail.com / password123` | Tạo tài khoản thành công, redirect dashboard |
| Đăng ký email đã tồn tại | email trùng | Báo lỗi "Email đã được sử dụng" |
| Nhập password < 8 ký tự | `pass` | Validation error |
| Nhập email sai định dạng | `notanemail` | Validation error |

### TC-AUTH-02: Đăng nhập
| Bước | Input | Expected |
|------|-------|----------|
| Đăng nhập đúng | email + pass đúng | Redirect trang chủ / dashboard |
| Đăng nhập sai password | pass sai | Báo lỗi xác thực |
| Đăng nhập tài khoản inactive | — | Không thể đăng nhập |
| Truy cập route auth khi chưa đăng nhập | `/cart` | Redirect `/login` |

### TC-AUTH-03: Admin access
| Bước | Expected |
|------|----------|
| User thường truy cập `/admin/dashboard` | Redirect / 403 |
| Admin đăng nhập truy cập `/admin/dashboard` | Hiển thị dashboard |

---

## MODULE 2 — Cart (Giỏ hàng)

### TC-CART-01: Thêm sản phẩm vào giỏ
| Bước | Input | Expected |
|------|-------|----------|
| Click "Thêm vào giỏ" sản phẩm active | product_id hợp lệ | Sản phẩm xuất hiện trong giỏ, cart count tăng |
| Thêm sản phẩm đã có trong giỏ | cùng product_id | Số lượng tăng HOẶC báo đã có (tùy logic) |
| Thêm sản phẩm inactive/không tồn tại | product_id sai | Báo lỗi 404 / redirect |
| Thêm vào giỏ khi chưa đăng nhập | — | Redirect `/login` |

### TC-CART-02: Cập nhật giỏ hàng
| Bước | Input | Expected |
|------|-------|----------|
| Tăng số lượng item | quantity + 1 | Total price cập nhật đúng |
| Giảm số lượng về 0 | quantity = 0 | Item bị xóa khỏi giỏ |
| Nhập số lượng âm | `-1` | Validation error |
| Xóa item | click Remove | Item biến mất, total cập nhật |

### TC-CART-03: Giỏ hàng sau logout/login
| Bước | Expected |
|------|----------|
| Thêm vào giỏ (database cart) → logout → login | Giỏ hàng vẫn còn |
| User khác không thấy giỏ hàng của user này | ✅ Isolated |

---

## MODULE 3 — Checkout & Payment

### TC-PAY-01: Thanh toán bằng ví (đủ số dư)
| Bước | Expected |
|------|----------|
| Cart có sản phẩm, số dư ví ≥ tổng tiền | Thanh toán thành công ngay |
| Số dư bị trừ đúng | `balance_trước - total_amount = balance_sau` |
| Order status = `completed` | ✅ |
| Invoice status = `paid` | ✅ |
| ServiceProvision được tạo với status = `pending` | ✅ |
| Cart bị xóa sau thanh toán | ✅ |
| Redirect đến `/customer/orders` với thông báo thành công | ✅ |

### TC-PAY-02: Thanh toán bằng ví (không đủ số dư)
| Bước | Expected |
|------|----------|
| Số dư ví < tổng tiền | Hiển thị trang bank transfer |
| Payment record được tạo với status = `pending` | ✅ |
| Order chưa bị mark completed | ✅ |

### TC-PAY-03: Thanh toán chuyển khoản ngân hàng
| Bước | Expected |
|------|----------|
| Checkout với bank transfer | Hiển thị thông tin ngân hàng + transaction_code |
| Transaction code duy nhất mỗi lần | ✅ |
| Admin thấy payment ở `/admin/payments` với status `pending` | ✅ |

### TC-PAY-04: Admin duyệt payment + provision
| Bước | Expected |
|------|----------|
| Admin click "Duyệt + Nhập thông tin" | Form nhập thông tin dịch vụ hiện ra |
| Admin submit form VPS (IP, root password) | ServiceProvision được tạo, status = `completed` |
| Email gửi cho khách | ✅ |
| CustomerService record được tạo | ✅ |
| Order status = `processing` | ✅ |

### TC-PAY-05: Admin từ chối payment
| Bước | Expected |
|------|----------|
| Admin từ chối với lý do | Payment status = `failed` |
| Email thông báo từ chối gửi cho khách | ✅ |
| Order vẫn ở trạng thái cũ (không completed) | ✅ |

---

## MODULE 4 — Webhook & Auto Payment (Phase 1)

### TC-WEBHOOK-01: Webhook với signature hợp lệ
| Bước | Input | Expected |
|------|-------|----------|
| POST `/api/webhook/payment/vnpay` | payload với signature đúng | HTTP 200, job được dispatch |
| Job chạy → payment = `completed` | — | Order + invoice cập nhật |
| Webhook gọi 2 lần (trùng transaction_id) | — | Lần 2 trả 200 nhưng không xử lý lại (idempotent) |

### TC-WEBHOOK-02: Webhook với signature sai
| Bước | Expected |
|------|----------|
| POST với signature không hợp lệ | HTTP 400 |
| Payment không thay đổi | ✅ |

### TC-WEBHOOK-03: Webhook provider không hỗ trợ
| Bước | Expected |
|------|----------|
| POST `/api/webhook/payment/unknown_provider` | HTTP 400 "Provider not supported" |

### TC-WEBHOOK-04: Payment status polling
| Bước | Expected |
|------|----------|
| GET `/payment/{id}/status` khi payment = pending | `{"status":"pending","redirect_url":null}` |
| GET khi payment = completed | `{"status":"completed","redirect_url":"/payment/{id}/success"}` |
| GET với payment của user khác | HTTP 403 |

---

## MODULE 5 — Service Lifecycle (Phase 2)

### TC-LIFECYCLE-01: Kích hoạt dịch vụ khi provision done
| Bước | Expected |
|------|----------|
| Admin mark provision = `completed` | CustomerService record được tạo |
| `status` = `active` | ✅ |
| `started_at` = now | ✅ |
| `expires_at` = 1 năm sau (yearly) / 1 tháng sau (monthly) | ✅ |
| `next_renewal_date` = `expires_at - 7 ngày` | ✅ |
| `renewal_price` = giá sản phẩm | ✅ |

### TC-LIFECYCLE-02: Gia hạn dịch vụ (đủ số dư)
| Bước | Expected |
|------|----------|
| Khách click gia hạn, số dư đủ | Ví bị trừ đúng `renewal_price` |
| `expires_at` gia hạn thêm 1 chu kỳ | ✅ |
| `notified_*d_at` reset về null | ✅ (để nhắc lại chu kỳ mới) |
| Thông báo thành công + ngày hết hạn mới | ✅ |

### TC-LIFECYCLE-03: Gia hạn dịch vụ (không đủ số dư)
| Bước | Expected |
|------|----------|
| Số dư < `renewal_price` | Báo lỗi "Số dư không đủ", gợi ý nạp tiền |
| Service không bị thay đổi | ✅ |
| Redirect nạp tiền | ✅ |

### TC-LIFECYCLE-04: Command kiểm tra hết hạn
| Bước | Expected |
|------|----------|
| Chạy `php artisan services:check-expiry` | Không lỗi |
| Service hết hạn + auto_renew = false | Mark `expired`, không gửi email gia hạn |
| Service hết hạn + auto_renew = true + đủ số dư | Tự gia hạn, không mark expired |
| Service hết hạn + auto_renew = true + thiếu số dư | Mark expired, không tự gia hạn |

### TC-LIFECYCLE-05: Email nhắc hết hạn
| Scenario | Expected |
|----------|----------|
| Service còn 30 ngày, `notified_30d_at` = null | Gửi email, set `notified_30d_at` = now |
| Chạy command lần 2 cùng ngày | Không gửi lại (idempotent) |
| Service còn 15 ngày | Gửi email 15d (nếu chưa gửi) |
| Service còn 7 ngày | Gửi email 7d |
| Service còn 1 ngày | Gửi email 1d |
| Sau gia hạn, `notified_*d_at` = null | Chu kỳ mới được nhắc lại ✅ |

### TC-LIFECYCLE-06: Hủy dịch vụ
| Bước | Expected |
|------|----------|
| Khách yêu cầu hủy | `status` = `cancelled` |
| Không thể gia hạn dịch vụ đã hủy | Báo lỗi |
| Không ảnh hưởng đến data provision cũ | ✅ |

---

## MODULE 6 — Nạp tiền (Deposit)

### TC-DEPOSIT-01: Tạo yêu cầu nạp tiền
| Bước | Expected |
|------|----------|
| Nhập số tiền hợp lệ (≥ 10,000đ) | Deposit record được tạo status = `pending` |
| Admin thấy ở `/admin/deposits` | ✅ |
| Nhập số tiền âm / 0 | Validation error |

### TC-DEPOSIT-02: Admin duyệt nạp tiền
| Bước | Expected |
|------|----------|
| Admin approve | Số dư ví tăng đúng số tiền |
| Deposit status = `approved` | ✅ |
| Email thông báo gửi cho khách | ✅ |

### TC-DEPOSIT-03: Admin từ chối nạp tiền
| Bước | Expected |
|------|----------|
| Admin reject | Số dư không thay đổi |
| Deposit status = `rejected` | ✅ |

---

## MODULE 7 — Hóa đơn & Báo giá

### TC-INVOICE-01: Xem báo giá
| Bước | Expected |
|------|----------|
| Vào `/quote` với giỏ hàng có sản phẩm | Hiển thị báo giá đúng |
| Giỏ hàng trống | Redirect cart với thông báo |
| Download PDF | File PDF tải về, nội dung đúng |

### TC-INVOICE-02: Hóa đơn sau thanh toán
| Bước | Expected |
|------|----------|
| Sau khi thanh toán thành công | Invoice được tạo |
| Download hóa đơn PDF | ✅ |
| User khác không download được hóa đơn của mình | HTTP 403 |

---

## MODULE 8 — Admin Panel

### TC-ADMIN-01: Quản lý sản phẩm
| Bước | Expected |
|------|----------|
| Tạo sản phẩm mới | Hiển thị trong catalog |
| Cập nhật giá | Giỏ hàng hiển thị giá mới |
| Xóa sản phẩm đang có trong cart | Kiểm tra xem giỏ hàng xử lý thế nào |
| Toggle inactive | Sản phẩm ẩn khỏi catalog |

### TC-ADMIN-02: Quản lý khách hàng
| Bước | Expected |
|------|----------|
| Adjust balance (cộng tiền) | Số dư tăng đúng |
| Adjust balance (trừ tiền) | Số dư giảm, không âm |
| Xem lịch sử đơn hàng của khách | ✅ |

### TC-ADMIN-03: Quản lý provision
| Bước | Expected |
|------|----------|
| Xem danh sách provision pending | ✅ |
| Start processing | Status = `processing` |
| Complete với thông tin đầy đủ | Email gửi cho khách, CustomerService tạo |
| Mark failed | Email thông báo lỗi |
| Resend email | Email được gửi lại |

---

## MODULE 9 — Bảo mật

### TC-SEC-01: Authorization
| Bước | Expected |
|------|----------|
| User A truy cập order của User B | 403 / Redirect |
| User A xem payment của User B | 403 |
| User A xem provision của User B | 403 |
| User A xem CustomerService của User B | 403 |

### TC-SEC-02: Input validation
| Bước | Expected |
|------|----------|
| XSS trong form tên | Escaped đúng trong HTML |
| SQL injection trong search | Không ảnh hưởng DB |
| CSRF attack | Token validation thất bại |

### TC-SEC-03: Admin route protection
| Bước | Expected |
|------|----------|
| User thường truy cập `/admin/*` | 403 / Redirect |
| Unauthenticated truy cập `/admin/*` | Redirect `/login` |

---

## Gợi ý Automated Tests (PHPUnit / Pest)

```php
// Test ví dụ cho TC-PAY-01
public function test_wallet_payment_deducts_balance_and_creates_provision()
{
    $customer = Customer::factory()->create(['balance' => 500000]);
    $product  = Product::factory()->create(['price' => 200000]);
    $cart     = Cart::factory()->for($customer->user)->create();
    CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1]);

    $this->actingAs($customer->user)
         ->post(route('proceed.payment'))
         ->assertRedirect(route('customer.orders'));

    $this->assertEquals(300000, $customer->fresh()->balance);
    $this->assertDatabaseHas('service_provisions', ['customer_id' => $customer->id]);
    $this->assertDatabaseMissing('carts', ['id' => $cart->id]); // cart cleared
}

// Test TC-LIFECYCLE-02
public function test_renew_service_deducts_balance_and_extends_expiry()
{
    $customer = Customer::factory()->create(['balance' => 500000]);
    $service  = CustomerService::factory()->create([
        'customer_id'   => $customer->id,
        'status'        => 'active',
        'renewal_price' => 300000,
        'expires_at'    => now()->addDays(3),
        'billing_cycle' => 'yearly',
    ]);

    $lifecycle = app(ServiceLifecycleService::class);
    $result    = $lifecycle->renew($service, $customer);

    $this->assertTrue($result['success']);
    $this->assertEquals(200000, $customer->fresh()->balance);
    $this->assertTrue($result['new_expiry']->isAfter(now()->addMonths(11)));
}

// Test TC-WEBHOOK-01 idempotent
public function test_webhook_processes_payment_only_once()
{
    $payment = Payment::factory()->create(['status' => 'pending', 'transaction_id' => 'TXN123']);

    $paymentService = app(PaymentService::class);
    $paymentService->confirmPaymentFromGateway('TXN123', 'manual', []);
    $paymentService->confirmPaymentFromGateway('TXN123', 'manual', []); // lần 2

    $this->assertEquals(1, Payment::where('transaction_id', 'TXN123')
                                   ->where('status', 'completed')->count());
}
```

---

## Checklist Regression Test khi deploy

```
[ ] Đăng ký / đăng nhập hoạt động
[ ] Thêm vào giỏ, checkout wallet payment
[ ] Checkout bank transfer — admin duyệt
[ ] Provision completed — email gửi — CustomerService tạo
[ ] Gia hạn dịch vụ qua ví
[ ] `php artisan services:check-expiry` chạy không lỗi
[ ] Download PDF hóa đơn
[ ] Admin panel: products, customers, payments, provisions
[ ] Route không bị 404 / 500 trên các trang chính
```
