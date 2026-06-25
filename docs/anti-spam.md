# Chống spam đơn hàng — Echbay Quick Buy

Tài liệu gợi ý các hướng giảm spam đơn hàng qua popup **Mua ngay**. Dựa trên luồng hiện tại: form gửi AJAX tới `admin-ajax.php` → `EQB_Ajax::create_order()` → `EQB_Order::create()`.

---

## Hiện trạng bảo vệ

| Lớp                           | Có / Không | Ghi chú                                                                     |
| ----------------------------- | ---------- | --------------------------------------------------------------------------- |
| WordPress nonce               | Có         | `check_ajax_referer( 'eqb_quick_buy', 'nonce' )` trong `class-eqb-ajax.php` |
| Validate server-side          | Có         | Họ tên, SĐT, tỉnh/xã, sản phẩm, tồn kho… trong `class-eqb-order.php`        |
| Validate client-side          | Có         | HTML5 + JS trong `quick-buy.js`                                             |
| Rate limit                    | **Không**  | Bot có thể gửi hàng loạt request                                            |
| CAPTCHA                       | **Không**  | Không phân biệt người / bot                                                 |
| Honeypot                      | **Không**  | Form không có trường bẫy                                                    |
| Giới hạn theo SĐT / IP        | **Không**  | Cùng SĐT có thể tạo nhiều đơn liên tiếp                                     |
| Thời gian tối thiểu điền form | **Không**  | Bot submit ngay sau khi mở popup                                            |

**Điểm yếu chính:** Nonce chỉ chống CSRF cơ bản, không chống bot. Nonce nằm trong `eqb_vars` trên trang sản phẩm — script có thể lấy nonce rồi gọi thẳng `eqb_create_order` mà không cần mở popup.

---

## Các hướng chống spam (từ nhẹ đến mạnh)

### 1. Rate limiting theo IP (khuyến nghị — ưu tiên cao)

**Ý tưởng:** Giới hạn số lần tạo đơn từ một IP trong khoảng thời gian (ví dụ: tối đa 3 đơn / 15 phút, hoặc 10 đơn / giờ).

**Cách làm trong plugin:**

- Dùng WordPress Transient API: key `eqb_rate_{md5(IP)}`, tăng counter mỗi lần `create_order` thành công (hoặc mỗi lần gọi).
- Chặn trước khi gọi `EQB_Order::create()` trong `class-eqb-ajax.php`.
- Trả `429` hoặc thông báo thân thiện: _"Bạn đã đặt quá nhiều đơn, vui lòng thử lại sau."_

**Ưu điểm:** Triển khai nhanh, không ảnh hưởng UX người dùng thật.  
**Nhược điểm:** IP động / NAT chung (công ty, quán net) có thể bị ảnh hưởng; bot dùng proxy vẫn qua được.

**Độ khó:** Thấp  
**Hiệu quả:** Trung bình–cao với spam script đơn giản

---

### 2. Rate limiting theo số điện thoại

**Ý tưởng:** Không cho cùng một SĐT tạo quá N đơn trong X phút (ví dụ: 1 đơn / 5 phút, hoặc 3 đơn / ngày).

**Cách làm:**

- Chuẩn hóa SĐT (bỏ khoảng trắng, `+84` → `0`) rồi lưu transient `eqb_phone_{hash}`.
- Hoặc query đơn WooCommerce gần đây: `billing_phone` + `date_created` trong 24h.

**Ưu điểm:** Chặn spam cùng một số giả lặp lại.  
**Nhược điểm:** Spammer đổi SĐT ngẫu nhiên vẫn qua; cần cân nhắc khách đặt nhiều đơn thật.

**Độ khó:** Thấp  
**Hiệu quả:** Trung bình (kết hợp IP tốt hơn)

---

### 3. Honeypot (trường bẫy)

**Ý tưởng:** Thêm input ẩn (CSS `display:none` hoặc off-screen). Người dùng không thấy; bot thường điền hết mọi field.

**Cách làm:**

- Thêm field ví dụ `website` / `company` trong `templates/popup.php`.
- JS không gửi field này (hoặc luôn để trống).
- Server: nếu `$_POST['website']` không rỗng → từ chối im lặng hoặc trả lỗi chung.

**Ưu điểm:** Không cần dịch vụ bên thứ ba, UX không đổi.  
**Nhược điểm:** Bot tinh vi bỏ qua field ẩn; không đủ một mình.

**Độ khó:** Thấp  
**Hiệu quả:** Thấp–trung bình (nên dùng kèm rate limit)

---

### 4. Thời gian tối thiểu (time-based token)

**Ý tưởng:** Ghi nhận thời điểm popup được load (`eqb_load_popup`). Khi submit, nếu thời gian < 3–5 giây → coi là bot.

**Cách làm:**

- Khi load popup thành công, server trả thêm `form_token` + `loaded_at` (hoặc HMAC).
- Client gửi lại khi `eqb_create_order`.
- Server kiểm tra: `now - loaded_at >= 3` và token hợp lệ.

**Ưu điểm:** Chặn bot bắn request liên tục không qua UI.  
**Nhược điểm:** Người dùng submit quá nhanh (đã lưu localStorage) có thể bị chặn nhẹ — cần threshold hợp lý (2–3 giây).

**Độ khó:** Trung bình  
**Hiệu quả:** Trung bình

---

### 5. CAPTCHA (reCAPTCHA v3 / hCaptcha / Cloudflare Turnstile)

**Ý tưởng:** Xác minh người dùng trước khi tạo đơn.

| Loại                  | UX                           | Chống bot |
| --------------------- | ---------------------------- | --------- |
| reCAPTCHA v3          | Gần như vô hình (điểm score) | Cao       |
| Turnstile             | Nhẹ, ít phiền                | Cao       |
| reCAPTCHA v2 checkbox | Rõ ràng, hơi phiền           | Rất cao   |

**Cách làm:**

- Cài plugin hoặc tích hợp trực tiếp: site key / secret key trong `EQB_Settings`.
- Frontend: lấy token trước khi `$.post( eqb_create_order )`.
- Backend: verify token với API Google / Cloudflare trước `EQB_Order::create()`.

**Ưu điểm:** Hiệu quả cao nhất với bot tự động.  
**Nhược điểm:** Phụ thuộc dịch vụ ngoài; cần đăng ký key; có thể ảnh hưởng nhẹ tốc độ submit.

**Độ khó:** Trung bình–cao  
**Hiệu quả:** Cao

---

### 6. Nonce theo phiên / theo popup (cải thiện nonce hiện tại)

**Ý tưởng:** Nonce hiện tại tạo một lần khi load trang, dùng được nhiều lần. Có thể:

- Tạo nonce mới mỗi lần `eqb_load_popup` và chỉ chấp nhận nonce đó cho **một** lần `eqb_create_order` (one-time nonce).
- Hoặc nonce gắn `product_id` + timestamp.

**Ưu điểm:** Giảm replay attack, bot khó spam hàng loạt chỉ với một nonce trang.  
**Nhược điểm:** Cần đổi flow JS (lấy nonce từ response load popup, không dùng `eqb_vars.nonce` cố định).

**Độ khó:** Trung bình  
**Hiệu quả:** Trung bình (bổ trợ, không thay CAPTCHA)

---

### 7. Giới hạn tần suất ở tầng server / CDN

**Ý tưởng:** Không sửa code plugin nhiều — cấu hình ở hosting:

- **Cloudflare Rate Limiting** cho `*/admin-ajax.php` + body chứa `eqb_create_order`
- **ModSecurity**, **fail2ban**, **nginx limit_req** theo IP

**Ưu điểm:** Chặn sớm, giảm tải PHP/DB.  
**Nhược điểm:** Cần quyền server/CDN; rule sai có thể chặn AJAX hợp lệ khác.

**Độ khó:** Phụ thuộc hạ tầng  
**Hiệu quả:** Cao nếu cấu hình đúng

---

### 8. Phát hiện pattern dữ liệu giả

**Ý tưởng:** Heuristic trên dữ liệu đơn spam thường gặp:

- Tên random (`asdf`, `test`, chuỗi lặp)
- SĐT không tồn tại / cùng pattern
- Địa chỉ quá ngắn hoặc giống nhau hàng loạt
- Cùng IP + nhiều SĐT khác nhau trong thời gian ngắn

**Cách làm:** Thêm lớp `EQB_Spam_Check::validate( $data )` trước khi tạo đơn; log và từ chối hoặc đưa đơn vào trạng thái `pending` / `on-hold` để duyệt tay.

**Ưu điểm:** Bắt spam “rác” không cần CAPTCHA.  
**Nhược điểm:** Dễ false positive; cần tune rule theo thực tế shop.

**Độ khó:** Trung bình  
**Hiệu quả:** Trung bình (theo chất lượng spam)

---

### 9. Tích hợp plugin chống spam có sẵn

Nếu site đã dùng:

- **Wordfence** — rate limit, firewall
- **CleanTalk** — anti-spam form
- **WP Armour (Honeypot)** — honeypot toàn site

Có thể hook `eqb_order_created` hoặc filter trước `create_order` để tái sử dụng logic có sẵn thay vì viết mới.

**Ưu điểm:** Ít code custom.  
**Nhược điểm:** Phụ thuộc plugin khác; không phải lúc nào cũng hỗ trợ AJAX custom.

---

### 10. Xử lý hậu kỳ (không chặn được lúc tạo nhưng giảm thiệt hại)

- Đơn từ Quick Buy đặt trạng thái **`on-hold`** / **`pending`** thay vì `processing` — admin xác nhận trước khi xử lý.
- Gửi thông báo Telegram/Zalo khi có đơn mới (phát hiện spam sớm).
- Báo cáo đơn theo nguồn (đã có ghi chú _"Đơn hàng từ plugin Echbay Quick Buy"_) để lọc và xóa hàng loạt.
- Tắt tạm Quick Buy trong **Cài đặt** khi bị tấn công.

**Ưu điểm:** Giảm rủi ro vận hành dù vẫn bị spam.  
**Nhược điểm:** Không ngăn request; thêm việc cho admin.

---

## Gợi ý triển khai theo giai đoạn

### Giai đoạn 1 — Nhanh, ít rủi ro UX (1–2 ngày)

1. **Rate limit IP** trên `eqb_create_order`
2. **Rate limit SĐT** (ví dụ 1 đơn / 10 phút / SĐT)
3. **Honeypot** trong form popup
4. **Giữ nút submit disabled** lâu hơn sau khi thành công (tránh double-click; bot replay)

### Giai đoạn 2 — Chống bot mạnh hơn (3–5 ngày)

5. **Time-based token** (popup load → submit tối thiểu 3s)
6. **One-time nonce** gắn với `eqb_load_popup`
7. Tùy chọn trong admin: bật **Cloudflare Turnstile** hoặc **reCAPTCHA v3**

### Giai đoạn 3 — Hạ tầng & vận hành

8. Rate limit **Cloudflare** cho `admin-ajax.php`
9. Heuristic spam + log IP/SĐT
10. Workflow duyệt đơn `on-hold` nếu cần

---

## So sánh nhanh

| Hướng               | Độ khó | UX        | Chống bot script | Chống bot tinh vi |
| ------------------- | ------ | --------- | ---------------- | ----------------- |
| Rate limit IP       | Thấp   | Tốt       | Cao              | Thấp              |
| Rate limit SĐT      | Thấp   | Tốt       | Trung bình       | Thấp              |
| Honeypot            | Thấp   | Tốt       | Trung bình       | Thấp              |
| Time token          | TB     | Khá       | Trung bình       | Thấp              |
| CAPTCHA / Turnstile | TB–Cao | Khá–TB    | Cao              | Cao               |
| One-time nonce      | TB     | Tốt       | Trung bình       | Thấp              |
| CDN rate limit      | TB\*   | Tốt       | Cao              | Trung bình        |
| Heuristic data      | TB     | Rủi ro FP | Trung bình       | Trung bình        |

\* Phụ thuộc hosting

---

## Gợi ý cấu hình mặc định (tham khảo)

```text
IP:     tối đa 5 request eqb_create_order / 15 phút (kể cả thất bại)
SĐT:    tối đa 1 đơn thành công / 10 phút
Honeypot: từ chối nếu field ẩn có giá trị
Thời gian: tối thiểu 3 giây từ load popup đến submit
CAPTCHA: bật khi vẫn spam sau giai đoạn 1
```

Các ngưỡng nên đưa vào **Cài đặt plugin** để shop tự chỉnh.

---

## File liên quan trong plugin

| File                              | Vai trò                                 |
| --------------------------------- | --------------------------------------- |
| `includes/class-eqb-ajax.php`     | Điểm chặn spam trước `create_order`     |
| `includes/class-eqb-order.php`    | Validate nghiệp vụ đơn hàng             |
| `assets/js/quick-buy.js`          | Submit AJAX, có thể gửi CAPTCHA / token |
| `templates/popup.php`             | Thêm honeypot, widget CAPTCHA           |
| `includes/class-eqb-settings.php` | Lưu cấu hình chống spam                 |
| `includes/class-eqb-frontend.php` | `wp_localize_script` — nonce, i18n      |

---

## Bước tiếp theo

Nếu muốn triển khai trong code, nên bắt đầu **Giai đoạn 1** (rate limit IP + SĐT + honeypot) vì chi phí thấp và phù hợp đa số shop WooCommerce Việt Nam. CAPTCHA chỉ bật khi spam vẫn cao sau các lớp trên.
