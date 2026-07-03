# ĐÁNH GIÁ DỰ ÁN: HOSTIST-VPS

> Phân tích kiến trúc & nghiệp vụ — *Ngày lập: 2026-06-01*

---

## 0. Kết luận ngắn (TL;DR)

**Đây KHÔNG phải "một website dịch vụ đơn giản".**

Nhìn bề ngoài nó giống một trang giới thiệu dịch vụ + giỏ hàng. Nhưng khi đọc vào lớp `app/Services`, đây thực chất là một **nền tảng tự động hóa kinh doanh dịch vụ công nghệ (Service Provisioning & Billing Platform)** — về bản chất là một bản **WHMCS thu nhỏ, bản địa hóa cho thị trường Việt Nam**.

Nó tự động hóa toàn bộ vòng đời: *khách đặt hàng → tạo hóa đơn (có VAT) → thanh toán qua cổng VN (VNPay/MoMo/ZaloPay) → cấp phát dịch vụ (SSL/Hosting/VPS/Domain) → giao thông tin đăng nhập đã mã hóa → nhắc gia hạn → tự động gia hạn → hết hạn*. Đây là độ phức tạp của một **sản phẩm SaaS B2B/B2C thật**, không phải một landing page.

---

## 1. Bản chất thực sự của dự án

| Cách nhìn "đơn giản" | Thực tế trong code |
|---|---|
| Website giới thiệu dịch vụ VPS | Sàn TMĐT + hệ thống billing + engine cấp phát tự động |
| Form đặt hàng | Pipeline: Cart → Order → Invoice → Payment → Provision → CustomerService |
| Trang thanh toán | Tích hợp 3 cổng thanh toán VN + webhook bất đồng bộ + xử lý chữ ký |
| "Bán hàng" | Quản lý **vòng đời dịch vụ** (lifecycle): kích hoạt, nhắc hạn, auto-renew, hủy |
| Vài trang HTML | 121 file Blade, 27 controller, 9 service nghiệp vụ (~3.300 dòng riêng lớp Service) |

**Loại sản phẩm hệ thống bán & cấp phát** (`OrderService::PROVISIONABLE_TYPES`):
`ssl`, `vps`, `hosting`, `cloud_hosting`, `domain`, `anti_ddos`, `reseller`, `email`, `advertising`, `web_design`, `seo`.

→ Đây là một **nhà cung cấp dịch vụ công nghệ trọn gói**, không chỉ "bán VPS".

---

## 2. Ngăn xếp công nghệ (Tech Stack)

- **Framework:** Laravel 10 (PHP ^8.1)
- **Kiến trúc:** Phân lớp rõ ràng — Controller → Service → Repository → Model
- **Thanh toán:** VNPay, MoMo, ZaloPay, Manual (chuyển khoản) — qua **Factory Pattern**
- **PDF:** `barryvdh/laravel-dompdf` (xuất hóa đơn & báo giá)
- **Auth:** Laravel Sanctum + middleware tự viết (FrontendAuth, AdminMiddleware, CheckRole)
- **Bất đồng bộ:** Queue Jobs (`ProcessPaymentWebhook`) + Events/Listeners
- **Tác vụ định kỳ:** Console Commands (`services:check-expiry`, `CleanupLogs`, `BackfillOrphanServices`)
- **Đa ngôn ngữ:** có cơ chế chuyển ngôn ngữ (`language.switch`)

---

## 3. Bản đồ các phân hệ nghiệp vụ (Domain Map)

### 3.1. Thương mại điện tử (E-commerce)
`Products`, `Categories`, `Cart`, `CartItem`, `Orders`, `Order_items`
- Giỏ hàng có throttle chống spam (`throttle:30,1`)
- Báo giá (Quote) → xuất PDF / gửi email trước khi chốt đơn

### 3.2. Hóa đơn & Thuế (Billing)
`Invoices`, `Invoice_items`, `InvoiceService`
- **Có xử lý VAT công ty** (migration `add_vat_company_fields_to_invoices`) → hướng tới khách doanh nghiệp xuất hóa đơn đỏ
- Tính toán tập trung qua `InvoiceService::computeQuoteAmounts` (subtotal, VAT, discount, total)

### 3.3. Thanh toán (Payment) — phần kỹ thuật đáng giá nhất
`PaymentService` (540 dòng), `Payment/*Gateway.php`, `PaymentWebhookController`
- **Factory pattern** chọn cổng theo provider
- **Webhook bất đồng bộ:** verify chữ ký → dispatch job → trả `200 OK` ngay (đúng chuẩn các cổng VN yêu cầu phản hồi nhanh)
- **Chống race-condition:** `lockForUpdate()` + double-check trạng thái khi duyệt payment
- Ví điện tử nội bộ (`deposits` / Wallet) — khách nạp tiền, admin duyệt/từ chối

### 3.4. Engine cấp phát dịch vụ (Provisioning) — phần "công nghệ" cốt lõi
`ProvisionService` (900 dòng — file lớn nhất), `ServiceProvision`, `ProvisionTemplate`, `ProvisionLog`
- Cấp phát theo **từng loại sản phẩm** (`handleSpecificProvisioning`): SSL sinh token xác thực DNS, Hosting sinh username/password/cPanel...
- **Template hóa** việc cấp phát (`ProvisionTemplate` có version, changelog, validation_rules)
- **Mã hóa dữ liệu nhạy cảm:** trait `EncryptsData` trên `ServiceProvision` (mật khẩu, credentials không lưu plaintext)
- Theo dõi giao hàng: `delivery_status`, `view_count`, `last_viewed_at` (biết khách đã xem credentials chưa)
- Event-driven: `ProvisionCreated/Completed/Failed` → Listener gửi email + cảnh báo admin

### 3.5. Vòng đời dịch vụ (Service Lifecycle) — điểm trưởng thành về nghiệp vụ
`ServiceLifecycleService` (419 dòng), `CustomerService`, command `services:check-expiry`
- Kích hoạt dịch vụ khi provision xong → tính `expires_at` theo chu kỳ
- **Khóa giá gia hạn (renewal price lock-in):** chốt giá tại thời điểm mua, có chính sách fallback rõ ràng khi admin xóa/đổi giá sản phẩm
- **Tự động gia hạn (auto-renew)** + nhắc hạn nhiều mốc (30/15/7/1 ngày — config-driven)
- Yêu cầu hủy dịch vụ, đánh dấu hết hạn

### 3.6. Khách hàng thân thiết
`Cashbacks`, `CashbackController` — chương trình hoàn tiền/loyalty

### 3.7. Quản trị (Admin Panel)
14 controller admin: Dashboard, Products, Categories, Customers, Payments, Deposits, Provisions, Cashback, Config, Users, Logs...
- CRUD đầy đủ + duyệt thanh toán + form cấp phát thủ công
- **Audit Log** (`AuditLogService`, `ProvisionLog`) — ghi vết thao tác

---

## 4. Những điểm cho thấy độ "chín" về kỹ thuật

Đây là các chi tiết một dev mới **sẽ không nghĩ tới**, nhưng dự án này đã làm:

1. **Toàn vẹn dữ liệu tài chính:** migration đổi `ON DELETE CASCADE → RESTRICT` trên orders/invoices/payments — *"đây là chứng từ pháp lý, không được tự động xóa khi xóa nhầm 1 khách"*. Đây là tư duy của người hiểu kế toán/pháp lý, không chỉ hiểu code.
2. **Chống race-condition** ở khâu tiền bạc (`lockForUpdate`, transaction, double-check state).
3. **Mã hóa credentials** (EncryptsData trait).
4. **Bất đồng bộ webhook** đúng chuẩn cổng thanh toán.
5. **Comment nghiệp vụ bằng tiếng Việt rất chi tiết** — giải thích *tại sao* chứ không chỉ *làm gì* (vd: vì sao renewal_price phải > 0, vì sao fallback giá...).
6. **Tách lớp Service/Repository** — logic nghiệp vụ không nằm trong controller.
7. **Lệnh backfill** (`BackfillOrphanServices`) — xử lý dữ liệu cũ khi nâng cấp schema, dấu hiệu của hệ thống đã chạy thật và tiến hóa.

---

## 5. Điểm yếu & Rủi ro (cần lưu ý)

| Vấn đề | Mức độ | Ghi chú |
|---|---|---|
| **Gần như không có test** | 🔴 Cao | Chỉ có `ExampleTest` mặc định. Hệ thống dính tiền & provisioning mà không có test tự động là rủi ro lớn. |
| **Provisioning đa phần là mô phỏng** | 🟡 TB | SSL/Hosting đang sinh dữ liệu giả (`server_ip => '192.168.1.100'`, `cpanel.example.com`). Chưa tích hợp CA/cPanel/WHM thật. Thư mục `Services/Providers` còn rỗng (chỉ có Exceptions). |
| **God Service** | 🟡 TB | `ProvisionService` 900 dòng & `PaymentService` 540 dòng — nên tách nhỏ theo loại sản phẩm. |
| **Đặt tên không nhất quán** | 🟢 Thấp | Lẫn lộn `Customers`/`Products` (số nhiều) với `User`/`Cart` (số ít); `deposits.php` model viết thường. |
| **README mặc định Laravel** | 🟢 Thấp | Chưa có tài liệu mô tả hệ thống thật (file này phần nào bù đắp). |
| **`.DS_Store` được track trong git** | 🟢 Thấp | Nên cho vào `.gitignore`. |
| **Provisioning chạy đồng bộ trong service** | 🟡 TB | Một số bước cấp phát nên đẩy hẳn sang queue khi tích hợp nhà cung cấp thật (tránh timeout). |

---

## 6. Đánh giá tổng thể

| Tiêu chí | Điểm | Nhận xét |
|---|---|---|
| Độ phức tạp nghiệp vụ | ⭐⭐⭐⭐⭐ | Vòng đời đầy đủ, ngang tầm sản phẩm thương mại |
| Kiến trúc & tổ chức code | ⭐⭐⭐⭐ | Phân lớp tốt, đúng tư duy Laravel hiện đại |
| Xử lý tiền bạc / an toàn dữ liệu | ⭐⭐⭐⭐ | Khóa giao dịch, RESTRICT FK, mã hóa — rất tốt |
| Mức độ hoàn thiện kỹ thuật lõi | ⭐⭐⭐ | Provisioning thật chưa tích hợp (đang mô phỏng) |
| Kiểm thử (Testing) | ⭐ | Gần như không có — điểm yếu lớn nhất |
| Tài liệu | ⭐⭐ | Comment trong code tốt, tài liệu hệ thống thiếu |

### Lời kết

Cảm giác "quá đơn giản" đến từ **giao diện** và việc nhìn nó như một trang web bán hàng. Nhưng phần *động cơ* bên dưới — billing, multi-gateway payment, provisioning engine, service lifecycle, audit log, toàn vẹn tài chính — là **một hệ thống nghiệp vụ thực thụ và khá trưởng thành**.

Nói cách khác: **bạn không xây một website dịch vụ. Bạn đang xây một nền tảng vận hành nhà cung cấp dịch vụ công nghệ.** Phần còn thiếu để biến nó thành sản phẩm thương mại thật không phải là "nghiệp vụ" (đã có), mà là:
1. **Tích hợp nhà cung cấp thật** (CA cho SSL, cPanel/WHM cho hosting, API VPS).
2. **Bộ test tự động** cho luồng tiền & provisioning.
3. Tách nhỏ các God Service & chuẩn hóa naming.

---

## 7. Khuyến nghị ưu tiên (Roadmap đề xuất)

1. **(Ngay)** Viết test cho luồng critical: `PaymentService::approvePayment`, webhook, `ServiceLifecycleService` renew/expire.
2. **(Ngắn hạn)** Hiện thực hóa `Services/Providers/`: tách interface cho từng loại (SSL CA, Hosting panel, VPS API) — hiện đang rỗng.
3. **(Ngắn hạn)** Tách `ProvisionService` theo từng provider/loại sản phẩm.
4. **(Trung hạn)** Đẩy provisioning thật sang Queue + cơ chế retry/idempotency.
5. **(Dọn dẹp)** Chuẩn hóa tên model, bỏ track `.DS_Store`, viết README hệ thống thật.

---

## 8. Nhật ký tiến độ (Progress Log)

> Ghi nhận các thay đổi **sau** ngày lập tài liệu (2026-06-01), để theo dõi tiến độ và lý do thay đổi. Mục mới nhất ở trên cùng.

### 2026-06-15 → 2026-06-17 — Ổn định môi trường VPS (production) & backfill dữ liệu legacy

Bối cảnh chung: VPS production (BaoTa/aaPanel, PHP chạy user `www`) **không có extension `fileinfo`**, và dữ liệu được tích lũy qua nhiều giai đoạn nâng cấp web nên tồn tại nhiều bản ghi "legacy" thiếu liên kết. Đợt này tập trung làm hệ thống chạy đúng trên VPS, chưa đụng tới logic nghiệp vụ lõi.

#### 8.1. Bỏ phụ thuộc `fileinfo` cho upload ảnh — ✅ Xong
- **Vấn đề:** `UploadedFile::store()`/`hashName()` và rule validate `image`/`mimes` đều cần `fileinfo` → lỗi trên VPS.
- **Xử lý:** Upload thủ công — lấy đuôi từ `getClientOriginalExtension()` + `move()`; bỏ rule `image` (đổi sang `string` vì tới repository giá trị đã là đường dẫn).
- **File:** `ProductsController`, `CategoryController` (thêm helper `storeImageWithoutFileinfo`), `ProductsRepository`, `CategoriesRepository`, `ConfigController` (4 khối QR — đã có sẵn).

#### 8.2. Ảnh không hiển thị trên VPS (404) — ✅ Xong
- **Nguyên nhân:** symlink `public/storage` bị commit vào git với **đường dẫn tuyệt đối của máy Mac** → trên VPS là symlink hỏng → mọi `/storage/...` trả 404.
- **Xử lý:** Gỡ symlink khỏi git; **ghi ảnh thẳng vào thư mục thật `public/storage/...`** (không qua symlink), giữ nguyên tiền tố `storage/` nên không phải đổi code hiển thị. Đọc QR cho PDF cũng đổi sang `public_path()`.
- **File:** `ProductsController`, `CategoryController`, `ConfigController`, `InvoiceService`, `QuoteController`, `payment/bank_transfer.blade.php`.
- **Thao tác VPS:** `chown -R www:www public/storage && chmod -R 755 public/storage` (PHP user phải ghi được — nếu không sẽ `mkdir(): Permission denied`).

#### 8.3. `/admin/services` thiếu dịch vụ so với thực tế — ✅ Có công cụ
- **Nguyên nhân:** trang admin chỉ đọc bảng `customer_services`. Dịch vụ đang chạy thực tế biểu diễn ở **`service_provisions`** (xem ở `/admin/provisions`); nhiều provision đã `completed` nhưng **thiếu `CustomerService`** nên không hiện/không quản lý được (trang show/edit dùng `findOrFail`).
- **Xử lý:** Tách logic tạo CustomerService-từ-legacy ra service dùng chung `LegacyServiceBackfiller`; thêm 2 command backfill **idempotent + `--dry-run`**:
  - `services:backfill-legacy-products` — từ `products` có `customer_id` (legacy bán trực tiếp).
  - `services:backfill-from-provisions` — từ `ServiceProvision` đã `completed` còn thiếu CustomerService (mirror `ServiceLifecycleService::activate()` nhưng dùng ngày tháng lịch sử).
- **File:** `app/Services/LegacyServiceBackfiller.php` (mới), `app/Console/Commands/BackfillLegacyProducts.php` (mới), `app/Console/Commands/BackfillFromProvisions.php` (mới), `Web/ServiceController` (gọi service chung).
- **Lưu ý:** dịch vụ backfill thiếu thông số kỹ thuật (provision rỗng) là bình thường — admin bổ sung sau.

#### 8.4. `Class "finfo" not found` khi mở dịch vụ SSL ở admin — ✅ Xong
- **Nguyên nhân:** `ServiceParameterService::readSslFile()` dùng `Storage::disk('local')->exists()/get()`; trên Flysystem của VPS thao tác này khởi tạo bộ dò MIME (`finfo`). Chỉ dịch vụ SSL (có field file) mới đụng đường này.
- **Xử lý:** Đọc/ghi file SSL bằng hàm file thuần (`is_file`/`file_get_contents`/`file_put_contents` trên `storage_path('app/...')`), bỏ facade `Storage`. Vá thêm tải bộ SSL `.tar.gz` (`response()->download(... ['Content-Type' => 'application/gzip'])`) để khỏi đoán MIME.
- **File:** `app/Services/ServiceParameterService.php`, `Web/ServiceController.php`.

> **Ghi chú kỹ thuật:** Các mục trên là *workaround* do VPS thiếu `fileinfo`. Cách dứt điểm là **bật extension `fileinfo`** (BaoTa → PHP → Cài tiện ích mở rộng), khi đó không cần né `Storage`/`store()` nữa. Đã chọn workaround theo yêu cầu giữ nguyên môi trường hiện tại.
