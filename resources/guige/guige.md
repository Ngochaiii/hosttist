# Hostist VPS — Tài liệu dự án

## Tổng quan

**Loại:** Nền tảng bán dịch vụ hosting/VPS cho thị trường Việt Nam
**Tech stack:** Laravel + MySQL + Bootstrap + DomPDF
**Mô hình:** B2B/B2C — bán dịch vụ hosting, admin giao thông tin dịch vụ thủ công sau khi thanh toán

---

## Dịch vụ bán

| Loại            | Ghi chú                                  |
|-----------------|------------------------------------------|
| VPS             | Admin giao IP + root password sau TT     |
| Hosting         | Admin giao cPanel URL + FTP credentials  |
| SSL Certificate | Admin upload file .crt / .key / .ca      |
| Domain          | Admin cung cấp auth code + nameserver    |
| Email Hosting   | Tương tự Hosting                         |
| Web Design      | Dịch vụ tư vấn, không tự động           |
| SEO / Quảng cáo | Dịch vụ tư vấn, không tự động           |

---

## Luồng vận hành hiện tại

```
Khách xem dịch vụ
       ↓
Thêm vào giỏ hàng  →  Xem báo giá (PDF / email)
       ↓
   Thanh toán
       ├── Đủ số dư ví     → Trừ tiền ngay → Order completed → Provision pending
       └── Chuyển khoản    → Pending → Admin duyệt tay → Provision completed
                                ↓
                    Admin nhập thông tin dịch vụ (IP, pass, cPanel...)
                                ↓
                    Hệ thống gửi email + lưu ServiceProvision
                                ↓
                    Khách vào dashboard xem credentials
```

---

## Modules hiện có

| Module         | Trạng thái | Ghi chú                                     |
|----------------|-----------|---------------------------------------------|
| Auth           | ✅ Hoàn thiện | Đăng ký / đăng nhập / logout               |
| Catalog        | ✅ Hoàn thiện | Danh mục, trang chi tiết sản phẩm          |
| Cart           | ✅ Hoàn thiện | Database (user) + session (guest)          |
| Quote          | ✅ Hoàn thiện | Xuất PDF, gửi email báo giá                |
| Order/Invoice  | ✅ Hoàn thiện | Tự động tạo khi checkout, PDF hóa đơn      |
| Payment        | ✅ Cơ bản    | Ví nội bộ + bank transfer thủ công         |
| Deposit        | ✅ Cơ bản    | Nạp ví, admin duyệt tay                    |
| Provision      | ✅ Cơ bản    | Admin nhập tay, email khách                |
| Cashback       | ✅ Cơ bản    | Khách yêu cầu, admin duyệt                 |
| Admin Panel    | ✅ Cơ bản    | Quản lý toàn bộ                            |
| Renewal        | ❌ Chưa có  | ServiceController::renewService() là stub  |
| Notification   | ❌ Chưa có  | Không có nhắc hết hạn                      |
| Support Ticket | ❌ Chưa có  | Khách liên hệ qua Zalo/email ngoài hệ thống|
| Payment API    | ❌ Chưa có  | VNPay / MoMo / ZaloPay chưa tích hợp thực |

---

## Ưu điểm

**Kiến trúc**
- Tách Service Layer rõ ràng (OrderService, PaymentService, InvoiceService, ProvisionService)
- BaseService có transaction(), logActivity(), generateUniqueNumber() — tái sử dụng tốt
- Middleware phân biệt admin / frontend

**Tính năng**
- Nhiều phương thức thanh toán phù hợp thị trường VN (bank, MoMo, ZaloPay, ví nội bộ)
- Provision system lưu JSON, mật khẩu được encrypt
- Config system linh hoạt — thay đổi ngân hàng, QR code không cần code
- Cashback system — ít thấy ở hosting VN, lợi thế cạnh tranh

---

## Nhược điểm cần khắc phục

**Bảo mật**
- Mật khẩu trong `credentials` array của provision_data lưu plain text — cần encrypt đồng nhất
- Không có rate limiting trên login / cart — dễ bị brute force
- Admin logs trước đây accessible bởi frontend user (đã fix)

**Chức năng**
- 100% manual provision — không scale được khi nhiều đơn
- Không có renewal tự động — khách quên gia hạn dẫn đến mất dịch vụ
- Không có notification hết hạn — không giữ chân khách hàng
- Không có support ticket — khách phải liên hệ ngoài app

---

## ROADMAP

---

### PHASE 1 — Hoàn thiện luồng thanh toán tự động *(LÀM TRƯỚC)*

> **Mục tiêu:** Xây dựng toàn bộ flow xử lý thanh toán tự động — logic, database, queue, webhook handler.
> Khi tích hợp API thật (VNPay/MoMo) sau này chỉ cần **thay 1 chỗ** là chạy ngay.

**Lý do làm trước:**
- Hiện tại mỗi đơn hàng admin phải ngồi duyệt tay → không scale được
- Flow tự động có thể chạy với bank transfer giả lập trước, sau gắn API thật vào là xong
- Cấu trúc code sạch từ đầu dễ bảo trì hơn

**Việc cần làm:**

#### 1.1 Webhook handler tổng quát
```
Tạo: App\Http\Controllers\Webhook\PaymentWebhookController
- POST /webhook/payment/{provider}   ← nhận callback từ VNPay/MoMo/ZaloPay
- Verify signature (mỗi provider có thuật toán khác nhau)
- Dispatch job: ProcessPaymentWebhook
```

#### 1.2 Job Queue cho xử lý thanh toán
```
Tạo: App\Jobs\ProcessPaymentWebhook
- Nhận payment_id + provider + raw_data
- Gọi PaymentService::confirmPaymentFromGateway()
- Nếu thành công → tự động approve → tạo provision → gửi email
- Nếu thất bại → log + notify admin
```

#### 1.3 Cập nhật PaymentService
```php
// Thêm method mới vào PaymentService
public function confirmPaymentFromGateway(string $transactionId, string $provider, array $data): array
// - Verify transaction từ provider
// - Update payment status → completed
// - Update order status → processing
// - Dispatch ProvisionCreated event
// - Clear cart
// - Gửi email xác nhận cho khách
```

#### 1.4 Cập nhật flow thanh toán phía khách
```
Hiện tại: POST /quote/proceed-to-payment → view bank_transfer (khách chụp ảnh CK)
Thêm:     GET  /payment/{id}/status      → polling / redirect sau khi thanh toán
          GET  /payment/{id}/success     → trang thành công
          GET  /payment/{id}/failed      → trang thất bại + retry
```

#### 1.5 Provider abstraction layer
```php
// Tạo: App\Services\Payment\PaymentGatewayInterface
interface PaymentGatewayInterface {
    public function createPaymentUrl(Orders $order): string;
    public function verifyWebhook(Request $request): bool;
    public function getTransactionStatus(string $transactionId): string;
}

// Implement:
// App\Services\Payment\VNPayGateway    implements PaymentGatewayInterface
// App\Services\Payment\MoMoGateway     implements PaymentGatewayInterface
// App\Services\Payment\ZaloPayGateway  implements PaymentGatewayInterface
// App\Services\Payment\ManualGateway   implements PaymentGatewayInterface (bank transfer hiện tại)
```

**Kết quả Phase 1:**
- Flow tự động hoạt động với ManualGateway (admin vẫn nhập tay nhưng qua interface thống nhất)
- Khi có API key VNPay/MoMo → chỉ fill implement của VNPayGateway là tự chạy
- Không cần đụng vào PaymentService, OrderService, hay các controller khác

---

### PHASE 2 — Service Lifecycle (Gia hạn + Thông báo hết hạn)

> **Mục tiêu:** Khách có thể tự quản lý dịch vụ, hệ thống tự nhắc gia hạn.

#### 2.1 Thêm bảng customer_services
```sql
customer_services:
  id, customer_id, provision_id, product_id
  status (active / expired / suspended / cancelled)
  started_at, expires_at, next_renewal_date
  auto_renew (boolean)
  renewal_price
```

#### 2.2 Scheduled job nhắc hết hạn
```
App\Console\Commands\CheckServiceExpiry  (chạy daily)
- Query dịch vụ expires_at trong 30/15/7/1 ngày tới
- Gửi email nhắc nhở với link gia hạn
- Nếu auto_renew = true + đủ ví → tự tạo renewal order
```

#### 2.3 Trang quản lý dịch vụ đầy đủ
```
/customer/services           → Danh sách tất cả dịch vụ + trạng thái
/customer/services/{id}      → Chi tiết: thông tin kỹ thuật, lịch sử, credentials
/customer/services/{id}/renew → Gia hạn online (tạo order mới tự động)
```

---

### PHASE 3 — Tích hợp Payment API thực

> **Mục tiêu:** Plug API thật vào interface đã xây ở Phase 1.
> Đây là bước DUY NHẤT cần làm sau khi có API key từ provider.

| Provider | Việc cần làm |
|----------|-------------|
| VNPay    | Fill VNPayGateway: createPaymentUrl() + verifyWebhook() + getTransactionStatus() |
| MoMo     | Fill MoMoGateway tương tự |
| ZaloPay  | Fill ZaloPayGateway tương tự |

**Không cần đụng vào:**
- PaymentController (đã có)
- PaymentService (đã có)
- OrderService (đã có)
- Database (đã có)

---

### PHASE 4 — Support & Communication

> **Mục tiêu:** Khách có kênh liên hệ chính thức trong app, giảm tải Zalo/email.

#### 4.1 Ticket System
```
tickets: id, customer_id, subject, status, priority, service_id (nullable)
ticket_replies: id, ticket_id, user_id, message, attachments

Routes:
  GET  /support                    → Danh sách ticket của khách
  POST /support/create             → Tạo ticket mới
  GET  /support/{id}               → Xem ticket + thread replies
  POST /support/{id}/reply         → Khách trả lời
  GET  /admin/support              → Admin xem tất cả ticket
  POST /admin/support/{id}/reply   → Admin trả lời
```

#### 4.2 Notification Center
```
notifications: id, user_id, type, title, message, data (JSON), read_at
- Hiển thị badge notification trên navbar
- Types: payment_confirmed, service_expiring, ticket_reply, provision_ready
```

---

### PHASE 5 — Growth Features

> **Mục tiêu:** Tăng doanh thu và giữ chân khách hàng.

#### 5.1 Affiliate / Referral
```
- Cashback system đã có base → extend thêm referral_code
- Khách có referral link riêng
- Khi người được giới thiệu thanh toán đơn đầu → cộng % vào ví người giới thiệu
```

#### 5.2 Admin Dashboard thống kê
```
- Doanh thu theo ngày/tháng/năm (chart)
- Top sản phẩm bán chạy
- Số lượng dịch vụ sắp hết hạn (cần action)
- Tỷ lệ chuyển đổi (xem → thêm giỏ → thanh toán)
- Số dư ví trung bình của khách
```

#### 5.3 Nâng cấp bảo mật
```
- Rate limiting: login (5 lần/phút), cart add (20 lần/phút)
- Two-factor authentication cho admin
- Encrypt đồng nhất tất cả credentials trong provision_data
- Audit log cho mọi thao tác admin
```

---

## Thứ tự ưu tiên tổng thể

```
[ĐANG LÀM / LÀM TIẾP]
Phase 1 — Thanh toán tự động (flow + webhook + queue)
    ↓
Phase 2 — Service lifecycle (gia hạn + nhắc hết hạn)
    ↓
Phase 3 — Tích hợp API thực (VNPay / MoMo / ZaloPay)
    ↓
Phase 4 — Support ticket + Notification
    ↓
Phase 5 — Growth (Affiliate, Dashboard, Bảo mật nâng cao)
```

---

## Ghi chú kỹ thuật quan trọng

**Database hiện tại:**
- `service_provisions` — lưu thông tin dịch vụ sau khi admin giao, có encrypt
- `provision_data` (JSON) — cấu trúc khác nhau theo từng loại dịch vụ (vps/hosting/ssl/domain)
- `configs` — 1 bảng duy nhất lưu toàn bộ cấu hình website + ngân hàng + momo + zalopay

**Điểm cần chú ý khi dev:**
- `CartController::addToCart()` dùng `$product->category->getServiceType()` — category phải có meta_data đúng format
- `PaymentService::approvePayment()` dùng cho auto-approve (sản phẩm không cần provision)
- `Admin\PaymentController::approveWithProvision()` dùng cho manual approve + nhập thông tin
- Hai hàm trên KHÔNG gọi nhau — tách biệt hoàn toàn
- Giỏ hàng chỉ xóa SAU KHI payment record được tạo thành công (trong InvoiceController)

**Quy tắc đặt tên route:**
- Web frontend: `cart.*`, `invoice.*`, `quote.*`, `customer.*`, `proceed.payment`
- Admin: `admin.*` (tất cả)
- Tránh đặt tên không có prefix như route `approve-with-provision` cũ (đã fix)
