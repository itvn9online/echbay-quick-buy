# SQL địa chỉ hành chính VN (34 tỉnh / phường-xã)

Đặt 2 file sau vào thư mục này:

- `vn_tinh_thanh34.sql` — tạo bảng + 34 tỉnh/thành (chạy **trước**)
- `vn_phuong_xa34.sql` — tạo bảng + phường/xã + FK (chạy **sau**)

Plugin tự import khi **activate** nếu bảng `vn_tinh_thanh34` chưa có hoặc chưa đủ 34 dòng.

Bảng nằm trong **cùng database WordPress**, **không** thêm prefix `wp_`.
