# PHÂN TÍCH CHUYÊN SÂU: LUỒNG THANH TOÁN

> Bổ trợ cho [DANH_GIA.MD](./DANH_GIA.MD) — *Ngày lập: 2026-06-01*

Phân tích dựa trên đọc trực tiếp: `PaymentService`, `InvoiceController::proceedToPayment`, `PaymentWebhookController`, `ProcessPaymentWebhook` (Job), `PaymentStatusController`, `GatewayFactory` + 4 Gateway, `WalletController`.

---

## 1. Tổng quan: có 4 phương thức, 2 cơ chế xác nhận

| Phương thức | Provider | Xác nhận bởi | Trạng thái khởi tạo | Hiện trạng |
|---|---|---|---|---|
| **Ví nội bộ** | `wallet` | Hệ thống (tức thì) | `completed` ngay | ✅ Hoạt động |
| **Chuyển khoản** | `bank` / `manual` | **Admin duyệt tay** | `pending` | ✅ Hoạt động |
| **VNPay** | `vnpay` | Webhook (auto) | `pending` | ⚠️ Khung sẵn, chưa cắm API |
| **MoMo** | `momo` | Webhook (auto) | `pending` | ⚠️ Khung sẵn, chưa cắm API |
| **ZaloPay** | `zalopay` | Webhook (auto) | `pending` | ⚠️ Khung sẵn, chưa cắm API |

**Điểm mấu chốt:** dù phương thức nào, tất cả đều hội tụ về cùng một "đích" — đưa `Payment.status = completed`, `Order.status = processing`, `Invoice.status = paid`, rồi **sinh ra các `ServiceProvision` pending** để cấp phát. Đây là thiết kế tốt: một điểm hợp nhất (convergence point).

---

## 2. Sơ đồ luồng tổng thể

```
                         ┌─────────────────────────────┐
                         │  Cart (giỏ hàng)            │
                         └──────────────┬──────────────┘
                                        │ proceedToPayment()
                         ┌──────────────▼──────────────┐
                         │  DB::transaction:           │
                         │  lock cart → createFromCart │  ← chống double-click/F5
                         │  → tạo Order + Invoice      │
                         │  → xóa cart items           │
                         └──────────────┬──────────────┘
                                        │
              ┌─────────────────────────┼─────────────────────────┐
              ▼                         ▼                         ▼
      ┌───────────────┐        ┌────────────────┐        ┌─────────────────┐
      │ WALLET        │        │ BANK / MANUAL  │        │ GATEWAY (VN)    │
      │ đủ số dư?     │        │                │        │ vnpay/momo/zalo │
      └───────┬───────┘        └───────┬────────┘        └────────┬────────┘
              │ lockForUpdate          │ status=pending           │ redirect URL
              │ trừ balance            │ hiện STK ngân hàng        │ (Phase 3)
              │ payment=completed      │                          │
              ▼                        ▼                          ▼
   ┌──────────────────┐   ┌──────────────────────┐   ┌──────────────────────┐
   │ XÁC NHẬN NGAY    │   │ Admin duyệt tay       │   │ Webhook từ provider   │
   │ (đồng bộ)        │   │ approvePayment()      │   │ → verify chữ ký       │
   │                  │   │                       │   │ → dispatch Job        │
   │                  │   │                       │   │ → confirmPayment...() │
   └────────┬─────────┘   └──────────┬───────────┘   └──────────┬───────────┘
            │                        │                          │
            └────────────────────────┼──────────────────────────┘
                                     ▼
                   ┌─────────────────────────────────────┐
                   │  ĐIỂM HỘI TỤ:                       │
                   │  Order = processing                 │
                   │  Invoice = paid                     │
                   │  Sinh ServiceProvision (pending)    │
                   │  → đẩy sang Provisioning engine     │
                   └─────────────────────────────────────┘
```

---

## 3. Mổ xẻ từng nhánh

### 3.1. Khởi tạo Order (chung cho mọi phương thức)
`InvoiceController::proceedToPayment` — `InvoiceController.php:66`

Điểm tốt:
- **Bọc trong `DB::transaction` + `lockForUpdate` trên cart** → double-click hoặc F5 không tạo 2 order trùng. Đây là lỗi kinh điển mà nhiều hệ thống TMĐT mắc phải, ở đây đã xử lý.
- **Validate VAT có regex mã số thuế** (`10–14 ký tự số`) — đúng chuẩn MST Việt Nam.
- Xóa cart items + reset tổng tiền ngay trong transaction → trạng thái nhất quán.

### 3.2. Nhánh VÍ (Wallet) — `processWalletPayment()` — `PaymentService.php`
```
lockForUpdate() trên Customer  →  check balance  →  decrement('balance')
   →  tạo Payment status=completed  →  Invoice=paid
   →  nếu là đơn gia hạn: completeRenewalFromOrder()
   →  nếu mua mới: OrderService::processCompletedOrder() (sinh provision)
```
**Điểm xuất sắc:** khóa row khách hàng (`lockForUpdate`) để **check-và-trừ tiền nguyên tử (atomic)**. Hai request song song không thể trừ âm số dư (double-spend). Đây là tư duy đúng về tiền.

### 3.3. Nhánh CHUYỂN KHOẢN — `createBankTransferPayment()` + `approvePayment()`
- Tạo `Payment` status `pending`, hiển thị số tài khoản công ty (lấy từ `Config::current()`).
- Khách chuyển khoản → **admin vào panel duyệt tay**.
- `approvePayment()` có: `lockForUpdate` + **double-check `status !== 'pending'`** → chống việc 2 admin duyệt cùng lúc (double-provision).

### 3.4. Nhánh GATEWAY (VNPay/MoMo/ZaloPay) — bất đồng bộ qua Webhook
Đây là phần kiến trúc đáng giá nhất, dù implementation gateway còn là TODO:

```
Provider POST /webhook/payment/{provider}
   │
   ▼  PaymentWebhookController::handle()
   ├─ GatewayFactory::make(provider)        → 400 nếu provider lạ
   ├─ gateway->verifyWebhook(request)       → 400 nếu chữ ký sai  ★ bảo mật
   ├─ extractTransactionId() theo provider  → mỗi cổng field khác nhau
   │     vnpay→vnp_TxnRef, momo→orderId, zalopay→app_trans_id
   ├─ ProcessPaymentWebhook::dispatch()     → đẩy sang queue
   └─ return 200 OK NGAY LẬP TỨC            ★ provider cần phản hồi nhanh
                    │
                    ▼  (async) Job: tries=3, backoff=30s
        PaymentService::confirmPaymentFromGateway()
```

**Ba quyết định thiết kế đúng:**
1. **Trả `200 OK` ngay, xử lý sau qua queue** — đúng yêu cầu của các cổng VN (nếu chậm, provider sẽ retry webhook gây trùng lặp).
2. **Verify chữ ký trước khi làm bất cứ gì** — chống giả mạo webhook.
3. **Phân biệt lỗi nghiệp vụ vs lỗi mạng** trong Job: `no_retry=true` (payment không tồn tại / sai trạng thái) thì `fail()` luôn, không phí 3 lần retry; lỗi khác thì `throw` để retry.

---

## 4. Tính idempotency (chống xử lý trùng) — phân tích quan trọng

Webhook **luôn có thể đến nhiều lần** (provider retry). Code xử lý:

`confirmPaymentFromGateway()`:
```php
if ($payment->status === 'completed') {
    return ['success' => true, 'already_processed' => true]; // idempotent ✅
}
if ($payment->status !== 'pending') {
    return ['no_retry' => true]; // ✅
}
```
→ Webhook đến lần 2 sẽ thấy `completed` và bỏ qua. **Tốt.**

⚠️ **Nhưng có một lỗ hổng race-condition tinh tế:** khác với `approvePayment()` và `processWalletPayment()`, hàm `confirmPaymentFromGateway()` **KHÔNG dùng `lockForUpdate()`** khi đọc payment. Việc check `status` nằm *ngoài* transaction. Nếu 2 webhook của cùng giao dịch đến gần như đồng thời (race), cả hai có thể cùng đọc `status='pending'` trước khi một trong hai kịp update → **tạo provision 2 lần**.

Xác suất thấp (queue worker + backoff giúp giảm), nhưng đây là điểm cần vá để đồng nhất với 2 nhánh kia.

---

## 5. Bảng so sánh xử lý đồng thời (concurrency) giữa 3 nhánh

| Hàm | `lockForUpdate` | Double-check status | Trong transaction | Idempotent |
|---|:---:|:---:|:---:|:---:|
| `processWalletPayment` | ✅ (Customer) | — | ✅ | N/A (đồng bộ) |
| `approvePayment` | ✅ (Payment) | ✅ | ✅ | ✅ |
| `rejectPayment` | ✅ (Payment) | ✅ | ✅ | ✅ |
| `confirmPaymentFromGateway` | ❌ **thiếu** | ✅ (ngoài txn) | ✅ (phần update) | ⚠️ một phần |

→ **3/4 nhánh xử lý concurrency rất tốt. Nhánh webhook là mắt xích yếu nhất** — đáng chú ý vì đây lại là nhánh dễ bị gọi trùng nhất.

---

## 6. Theo dõi trạng thái phía khách (UX) — `PaymentStatusController`

- Trang `pending` có **AJAX polling** gọi `checkStatus` → trả JSON `{status, redirect_url}`.
- Khi webhook xác nhận xong (đổi `completed`), lần poll kế tiếp nhận `redirect_url` → tự chuyển sang trang success.
- **Phân quyền chặt:** `getPaymentForUser` chặn xem payment của người khác (`abort(403)`).

Đây là pattern chuẩn cho thanh toán bất đồng bộ — khách không phải F5 thủ công.

---

## 7. Phát hiện code lặp (cần refactor)

Logic **"sinh ServiceProvision từ order items"** bị lặp ở **3 nơi** gần như giống hệt:
- `approvePayment()` (vòng lặp `foreach ($order->items)`)
- `confirmPaymentFromGateway()` (lặp lại y hệt)
- `OrderService::processCompletedOrder()` (gọi từ wallet)

→ Nên gom về **một** method dùng chung (vd `OrderService::createProvisionsForOrder()`). Hiện nay sửa logic provision phải sửa 3 chỗ — rủi ro phân kỳ (đã thấy comment cảnh báo "priority phải đồng bộ giữa OrderService và PaymentService", chính là triệu chứng của việc này).

---

## 8. Tổng kết luồng thanh toán

### Điểm mạnh ⭐⭐⭐⭐
- Kiến trúc multi-gateway sạch (Factory + Interface), dễ thêm cổng mới.
- Webhook bất đồng bộ đúng chuẩn: verify chữ ký → 200 ngay → queue + retry có phân loại lỗi.
- Xử lý tiền cẩn thận: atomic check-and-decrement ví, khóa row, double-check chống double-approve.
- Một điểm hội tụ duy nhất cho mọi phương thức → dễ bảo trì.
- Idempotency cơ bản cho webhook; UX polling tốt; phân quyền chặt.

### Điểm cần vá 🔧
| Ưu tiên | Vấn đề | Đề xuất |
|---|---|---|
| 🔴 Cao | `confirmPaymentFromGateway` thiếu `lockForUpdate` → race tạo provision trùng | Bọc `Payments::lockForUpdate()` trong transaction, re-check status bên trong |
| 🟡 TB | Logic sinh provision lặp 3 nơi | Gom về 1 method dùng chung |
| 🟡 TB | Gateway VNPay/MoMo/ZaloPay mới là TODO (throw exception) | Hiện thực ký/verify khi có API key (Phase 3) |
| 🟢 Thấp | Chưa kiểm tra `amount` webhook khớp `payment.amount` | So khớp số tiền provider trả về với số tiền đơn (chống sai lệch) |
| 🔴 Cao | Không có test cho luồng tiền | Test: wallet đủ/thiếu số dư, double webhook, approve song song |

### Nhận định
Luồng thanh toán ở đây **không hề "đơn giản"**. Nó xử lý đúng những bài toán khó của thanh toán thật: tính nguyên tử của tiền, chống trùng đơn, webhook bất đồng bộ, idempotency, phân quyền. Phần còn thiếu chủ yếu là **cắm API gateway thật** và **bịt nốt race-condition ở nhánh webhook** — chứ bộ khung tư duy đã ở mức của một hệ thống thương mại.
