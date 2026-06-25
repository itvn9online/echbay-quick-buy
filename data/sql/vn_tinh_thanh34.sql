-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th6 23, 2026 lúc 01:08 AM
-- Phiên bản máy phục vụ: 8.0.35
-- Phiên bản PHP: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `user26254_022118`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vn_tinh_thanh34`
--

CREATE TABLE `vn_tinh_thanh34` (
  `ma_tinh` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã tỉnh/TP (vd: 01, 79)',
  `ten_tinh` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên tỉnh/thành phố',
  `loai` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tỉnh | Thành phố',
  `stt` decimal(9,0) DEFAULT NULL COMMENT 'Thứ tự hiển thị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục 34 tỉnh/thành phố VN';

--
-- Đang đổ dữ liệu cho bảng `vn_tinh_thanh34`
--

INSERT INTO `vn_tinh_thanh34` (`ma_tinh`, `ten_tinh`, `loai`, `stt`) VALUES
('01', 'Hà Nội', 'Thành phố', 1),
('04', 'Cao Bằng', 'Tỉnh', 7),
('08', 'Tuyên Quang', 'Tỉnh', 8),
('11', 'Điện Biên', 'Tỉnh', 13),
('12', 'Lai Châu', 'Tỉnh', 14),
('14', 'Sơn La', 'Tỉnh', 15),
('15', 'Lào Cai', 'Tỉnh', 9),
('19', 'Thái Nguyên', 'Tỉnh', 10),
('20', 'Lạng Sơn', 'Tỉnh', 11),
('22', 'Quảng Ninh', 'Tỉnh', 3),
('24', 'Bắc Ninh', 'Tỉnh', 2),
('25', 'Phú Thọ', 'Tỉnh', 12),
('31', 'Hải Phòng', 'Thành phố', 4),
('33', 'Hưng Yên', 'Tỉnh', 5),
('37', 'Ninh Bình', 'Tỉnh', 6),
('38', 'Thanh Hóa', 'Tỉnh', 16),
('40', 'Nghệ An', 'Tỉnh', 17),
('42', 'Hà Tĩnh', 'Tỉnh', 18),
('44', 'Quảng Trị', 'Tỉnh', 19),
('46', 'Huế', 'Thành phố', 20),
('48', 'Đà Nẵng', 'Thành phố', 21),
('51', 'Quảng Ngãi', 'Tỉnh', 22),
('52', 'Gia Lai', 'Tỉnh', 24),
('56', 'Khánh Hòa', 'Tỉnh', 23),
('66', 'Đắk Lắk', 'Tỉnh', 25),
('68', 'Lâm Đồng', 'Tỉnh', 26),
('75', 'Đồng Nai', 'Tỉnh', 28),
('79', 'TP. Hồ Chí Minh', 'Thành phố', 29),
('80', 'Tây Ninh', 'Tỉnh', 27),
('82', 'Đồng Tháp', 'Tỉnh', 31),
('86', 'Vĩnh Long', 'Tỉnh', 30),
('91', 'An Giang', 'Tỉnh', 32),
('92', 'Cần Thơ', 'Thành phố', 33),
('96', 'Cà Mau', 'Tỉnh', 34);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `vn_tinh_thanh34`
--
ALTER TABLE `vn_tinh_thanh34`
  ADD PRIMARY KEY (`ma_tinh`),
  ADD KEY `idx_vn_tinh_stt` (`stt`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
