# MODULE TÊN MIỀN (DOMAIN RESELLER) — TÀI LIỆU BÀN GIAO

> Cho session/người tiếp theo. Mô tả toàn bộ module bán & quản lý tên miền đã xây trên base Laravel của dự án.
> Cập nhật: 2026-06-02.

---

## 1. Mục tiêu nghiệp vụ

Chủ dự án mua tên miền hộ khách (chủ yếu qua **nhanhoa.com**) và muốn làm **trung gian bán lại có phụ phí (markup)**. Mô hình đã chốt:

- **Fulfill THỦ CÔNG** (không nối API Nhân Hòa): khách đặt trên web → admin tự mua trên Nhân Hòa → nhập NS/auth/hạn vào panel.
- **Bán cả đuôi quốc tế (.com/.net…) lẫn .vn/.com.vn** (.vn cần thông tin chủ thể: CCCD cá nhân / MST doanh nghiệp — từ 01/07/2025 MST cá nhân = CCCD 12 số).
- **Check khả dụng bằng RDAP/WHOIS miễn phí** (gTLD); .vn kiểm tra thủ công.
- **Giá = giá gốc + markup**, lưu giá gốc để **tự tính lãi** (vd cost 300k + 70k = bán 370k, lãi 70k).
- Hỗ trợ **import domain đã mua sẵn** từ Nhân Hòa vào hệ thống.

Tham khảo chuẩn ngành: WHMCS (giá tách register/renew/transfer + markup %/amount + rounding; đổi giá chỉ áp domain mới — khớp cơ chế `renewal_price_locked_at` sẵn có của dự án).

---

## 2. Trạng thái các Phase

| Phase | Nội dung | Trạng thái |
|---|---|---|
| 1 | Nền dữ liệu: bảng `domain_tlds`, `domains`; markup tự tính; mã hóa chủ thể; import | ✅ |
| 2 | Admin: CRUD danh mục đuôi (TLD) + form import + kho domain + báo cáo lãi | ✅ |
| 3 | Check khả dụng RDAP (gTLD) / thủ công (.vn) + endpoint + nút trên form | ✅ |
| 4 | Khách tự đặt mua: trang `/domains` search + check + cấu hình + add-to-cart (tái dùng engine cart/order/payment) | ✅ |
| 5 | Admin fulfill domain khách đặt → tạo `Domain` (active) + `CustomerService` (lifecycle); đồng bộ hạn khi gia hạn/hết hạn | ✅ |
| 6 | (chưa làm) xem mục §11 | ⏳ |

**Tổng test domain: 27 test / 88 assertions, đều xanh** (chạy riêng, dùng SQLite in-memory).

---

## 3. Chiến lược kiến trúc (QUAN TRỌNG)

**Nguyên tắc vàng: KHÔNG sửa code tiền (PaymentService/OrderService).** Domain tái dùng nguyên engine `cart → order → invoice → payment → provision → lifecycle`. Chỉ thêm phần *đặc thù domain* và **hook** vào các điểm mở rộng sẵn có.

Cách nối vào engine:
- Mỗi đơn domain dùng **1 Product "neo"** (`sku=DOMAIN-ANCHOR`, type=`domain`, inactive) làm `product_id` cho `order_items`/`service_provisions` (vì các bảng này yêu cầu product_id). Giá thực **không** lấy từ product — tính từ `domain_tlds`. Anchor được tạo lazy & idempotent qua `DomainCatalogService::ensureAnchorProduct()`.
- Khi khách thanh toán xong: vì `order_item.options.service_type='domain'`, engine **tự sinh** `ServiceProvision(type=domain, pending)` (y như mọi dịch vụ). Không cần code thêm ở khâu thanh toán.
- Khi admin **hoàn tất provision** (form domain sẵn có): `ProvisionService::markProvisionCompleted()` → `ServiceLifecycleService::activateFromProvision()` → **đã được hook** để nhận diện domain và chuyển sang `DomainProvisioningService::activate()` (tạo `Domain` + `CustomerService` đúng kiểu domain).

---

## 4. Mô hình dữ liệu

### Bảng `domain_tlds` (danh mục đuôi + giá)
Nguồn sự thật DUY NHẤT về giá. Lưu **giá gốc + markup**, giá bán/lãi là **accessor** (không lưu cột → không lệch).
- `tld` (unique: 'com','vn','com.vn'), `is_vn`
- `register_cost`, `renew_cost`, `transfer_cost?`
- `markup_type` ('amount'|'percent'), `markup_value`, `round_to?` (làm tròn bội số)
- `min_years`, `max_years`, `product_id?`, `is_active`, `sort_order`
- Accessor: `register_price`, `renew_price`, `transfer_price`, `register_profit` (xem `App\Models\DomainTld::computePrice()`).

### Bảng `domains` (tài sản tên miền)
Mỗi domain khách đặt HOẶC admin import. Giá **snapshot** lúc bán (đổi giá TLD sau không ảnh hưởng lãi đã ghi).
- Liên kết: `customer_id?`, `order_item_id?`, `customer_service_id?` (lifecycle), `tld_id?`
- `domain_name` (unique), `sld`, `tld`, `status` (pending/active/expired/cancelled/transferred), `years`, `registered_at?`, `expires_at?`
- Tài chính: `cost_price`, `sell_price`, `profit`
- **Mã hóa** (`EncryptsData`): `registrant` (JSON: name,email,phone,address,id_type,id_number), `auth_code`
- `registrar` (mặc định 'nhanhoa'), `nameservers` (JSON), `auto_renew`, `source` ('customer_order'|'admin_import'), `notes`

Migrations: `2026_06_02_000001_create_domain_tlds_table.php`, `2026_06_02_000002_create_domains_table.php`.

---

## 5. Bản đồ file

**Models:** `app/Models/DomainTld.php`, `app/Models/Domain.php` (encrypt registrant/auth_code qua mutator).

**Services:**
- `app/Services/DomainCatalogService.php` — `splitDomain()`, `importExisting()` (Phase 2), `ensureDomainCategory()/ensureAnchorProduct()/buildCartLine()` (Phase 4).
- `app/Services/DomainAvailabilityService.php` — check RDAP (Phase 3).
- `app/Services/DomainProvisioningService.php` — `activate()` (tạo Domain+CS khi fulfill), `syncFromService()` (đồng bộ hạn), `isDomainProvision()` (Phase 5).

**Controllers:**
- `app/Http/Controllers/Admin/DomainTldController.php` — CRUD danh mục đuôi.
- `app/Http/Controllers/Admin/DomainController.php` — kho domain, import, báo cáo lãi, `checkAvailability`.
- `app/Http/Controllers/Web/DomainController.php` — khách: `search`, `check` (public), `addToCart` (auth).

**Views:**
- Admin: `resources/views/source/admin/domain_tlds/index.blade.php`, `…/domains/{index,create,show}.blade.php`.
- Web: `resources/views/source/web/domains/search.blade.php`.
- Menu: thêm trong `layouts/admin/layouts/sidebar.blade.php` (mục "Tên miền") và `layouts/web/nav.blade.php` (link "Tên miền").

**Routes:**
- `routes/admin.php` — nhóm `admin/domain-tlds/*` và `admin/domains/*` (gồm `admin/domains/check`).
- `routes/web.php` — `GET /domains` (search), `GET /domains/check` (throttle 30/p), `POST /domains/add-to-cart` (auth, throttle).

**Seeder:** `database/seeders/DomainTldSeeder.php` — 4 đuôi mẫu (.com/.net/.vn/.com.vn), idempotent.

**Hook (sửa file sẵn có):** `app/Services/ServiceLifecycleService.php` — `activateFromProvision()` rẽ nhánh domain; `renew()`, `completeRenewalFromOrder()`, `markExpired()` gọi `syncFromService()` (defensive, không ném lỗi).

---

## 6. Các luồng end-to-end

**A. Khách đặt mua (Phase 4):**
`/domains` → gõ tên + chọn đuôi + năm → [Kiểm tra] (RDAP) → khai chủ thể → Add to cart (`DomainController@addToCart`: guard khớp đuôi, .vn bắt buộc giấy tờ, chặn domain đã đăng ký với gTLD, chặn trùng giỏ; `buildCartLine` nhúng domain/registrant/cost/sell vào options) → checkout/thanh toán **engine cũ** → tự sinh `ServiceProvision(domain, pending)`.

**B. Admin fulfill (Phase 5):**
Admin mở provision domain (form `provisions/forms/domain.blade.php` sẵn có) → nhập domain_name/registrar/expiry_date/nameservers/auto_renewal → Complete → `markProvisionCompleted` → `activateFromProvision` → **hook** → `DomainProvisioningService::activate()`:
- Tạo `CustomerService` (status active, `billing_cycle='yearly'`, hạn = `expiry_date` admin nhập HOẶC now+years, `renewal_price = renew_price × năm`, lock giá).
- Tạo `Domain` (active, snapshot cost/sell/profit, registrant, NS, nguồn `customer_order`), link `customer_service_id`.
- Idempotent theo `order_item_id` (re-complete không tạo trùng).

**C. Admin import domain mua sẵn (Phase 2):**
`Tên miền → Import` → nhập domain + giá gốc (+ giá bán tự tính theo markup nếu bỏ trống) + hạn + chủ thể → `DomainCatalogService::importExisting()` tạo `Domain(source=admin_import)`, snapshot lãi.

**D. Gia hạn / hết hạn:**
Dùng lại `ServiceLifecycleService` (command `services:check-expiry` nhắc hạn nhiều mốc 30/15/7/1 ngày, auto-renew nếu đủ ví). Vì product anchor giá 0, `currentRenewalPrice()` fallback về `renewal_price` snapshot trên CS (= renew_price). Sau renew/expire, `syncFromService()` cập nhật `domains.expires_at`/`status`.

---

## 7. Cách chạy / setup

```bash
php artisan migrate                              # tạo domain_tlds, domains (chỉ thêm bảng, an toàn)
php artisan db:seed --class=DomainTldSeeder      # nạp 4 đuôi mẫu (sửa giá trong admin)
```
Admin: **Tên miền → Danh mục đuôi** (đặt giá gốc + markup), **Import tên miền**, **Kho tên miền** (xem lãi).
Khách: menu **Tên miền** (`/domains`).

---

## 8. Cách test (PATTERN QUAN TRỌNG)

Tất cả test domain dùng **SQLite `:memory:` với schema dựng tay trong `setUp()`** — KHÔNG dùng `RefreshDatabase`. Lý do:
1. DB dev là **MySQL** (`portfolio`); `RefreshDatabase` sẽ **xoá sạch dữ liệu dev**.
2. Migration `service_provisions` dùng kiểu cột `set` (MySQL-only) → **không chạy được trên SQLite**.
→ Mỗi test tự `config(['database.default'=>'sqlite', ...':memory:'])`, `DB::purge('sqlite')`, rồi `Schema::create(...)` các bảng cần (enum/set → string).

Chạy:
```bash
php vendor/bin/phpunit tests/Feature/DomainCatalogTest.php \
  tests/Feature/DomainAvailabilityTest.php \
  tests/Feature/DomainCartLineTest.php \
  tests/Feature/DomainFulfillmentTest.php
```
(`DomainAvailabilityTest` dùng `Http::fake()` thay vì DB.)

---

## 9. Bảo mật & tính đúng đắn
- `registrant` + `auth_code` **mã hóa tại nghỉ** (test xác minh DB thô không lộ plaintext).
- Guard phía server ở add-to-cart (không tin client).
- `syncFromService()` bọc try/catch — không bao giờ rollback luồng tiền gọi nó.
- Idempotent: import & activate không tạo domain trùng theo `order_item_id`.

---

## 10. Quyết định & "gotcha" cần biết
- **Giá bán/lãi là accessor**, không lưu cột → sửa markup là giá đổi ngay, không cần đồng bộ.
- **Anchor product** thay vì product-per-TLD: tránh quản lý giá 2 nơi.
- **`.vn` không RDAP**: cố tình trả `unknown` (kiểm tra thủ công) thay vì đoán sai.
- **Lỗi mạng RDAP → unknown**, không khẳng định "trống" để tránh bán nhầm.
- Bài học từ trước (ngoài domain): cột phải nằm trong `$fillable` MỚI được mass-assign; thêm fillable phải kèm migration tạo cột (xem `verified_by/verified_at` trên `payments`).

---

## 11. Việc còn lại (Phase 6+ gợi ý)
- **Dashboard lãi nâng cao**: gộp cả domain khách đặt (hiện báo cáo `admin/domains` tính theo bảng `domains`; domain khách đặt chỉ có row sau khi admin fulfill — đúng vì lãi ghi nhận khi active).
- **Trang "Tên miền của tôi"** cho khách (xem NS/auth/hạn, bấm gia hạn) — hiện khách thấy qua portal CustomerService chung; có thể làm view domain riêng.
- **Auth/EPP code** chưa có ô trong form provision domain admin (chỉ import mới nhập) — thêm nếu cần chuyển nhượng.
- **Transfer & redemption/grace** (chuyển về, chuộc domain hết hạn) — chưa làm.
- **Nối API Nhân Hòa** (nếu sau này có tài khoản đại lý) để tự động check/đăng ký — hiện thủ công.
- **Đồng bộ Domain.expires_at** đã có khi renew/expire qua CS; nếu thêm luồng gia hạn khác nhớ gọi `syncFromService()`.

---

## 12. Tài liệu liên quan
- `DANH_GIA.MD` — đánh giá tổng thể dự án.
- `LUONG_THANH_TOAN.MD` — phân tích luồng thanh toán (engine domain tái dùng).
