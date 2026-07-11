-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 11, 2026 lúc 01:28 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanly_lichhop`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_thamgia`
--

CREATE TABLE `chitiet_thamgia` (
  `cuochop_id` int(11) NOT NULL,
  `nhanvien_id` int(11) NOT NULL,
  `trangthai_phanhoi` varchar(20) DEFAULT 'Chờ xác nhận'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chitiet_thamgia`
--

INSERT INTO `chitiet_thamgia` (`cuochop_id`, `nhanvien_id`, `trangthai_phanhoi`) VALUES
(1, 2, 'Chờ xác nhận'),
(1, 3, 'Chờ xác nhận'),
(2, 1, 'Chờ xác nhận'),
(3, 1, 'Chờ xác nhận'),
(3, 2, 'Chờ xác nhận'),
(3, 3, 'Chờ xác nhận'),
(3, 4, 'Chờ xác nhận'),
(4, 1, 'Chờ xác nhận'),
(4, 2, 'Chờ xác nhận'),
(4, 3, 'Chờ xác nhận'),
(4, 4, 'Chờ xác nhận'),
(5, 1, 'Chờ xác nhận'),
(5, 2, 'Chờ xác nhận'),
(6, 1, 'Chờ xác nhận'),
(6, 3, 'Chờ xác nhận'),
(7, 2, 'Từ chối'),
(7, 3, 'Đồng ý'),
(8, 2, 'Từ chối'),
(8, 3, 'Đồng ý'),
(9, 4, 'Chờ xác nhận'),
(10, 2, 'Chờ xác nhận'),
(10, 3, 'Chờ xác nhận');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cuochop`
--

CREATE TABLE `cuochop` (
  `id` int(11) NOT NULL,
  `tieude` varchar(200) NOT NULL,
  `noidung` text DEFAULT NULL,
  `thoigian_batdau` datetime NOT NULL,
  `thoigian_ketthuc` datetime NOT NULL,
  `nguoitao_id` int(11) NOT NULL,
  `phong_id` int(11) NOT NULL,
  `trangthai` varchar(20) DEFAULT 'Sắp diễn ra'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cuochop`
--

INSERT INTO `cuochop` (`id`, `tieude`, `noidung`, `thoigian_batdau`, `thoigian_ketthuc`, `nguoitao_id`, `phong_id`, `trangthai`) VALUES
(1, 'Theo dõi tiến độ', '', '2026-07-08 09:00:00', '2026-07-08 11:00:00', 1, 1, 'Đã hủy'),
(2, 'họp 2', '', '2026-07-10 20:52:00', '2026-07-10 22:52:00', 1, 1, 'Đã hủy'),
(3, 'họp 2', '', '2026-07-13 14:54:00', '2026-07-13 16:54:00', 1, 2, 'Đã hủy'),
(4, 'Review code', '', '2026-07-06 22:30:00', '2026-07-06 23:30:00', 1, 1, 'Đã hủy'),
(5, 'data 1', '', '2026-07-06 22:11:00', '2026-07-06 23:11:00', 1, 1, 'Đã hủy'),
(6, 'data 2', '', '2026-07-06 12:12:00', '2026-07-06 14:12:00', 1, 1, 'Đã hủy'),
(7, 'teest', '', '2026-07-11 16:37:00', '2026-07-11 17:40:00', 1, 1, 'Đã hủy'),
(8, 'Review code 2', '', '2026-07-11 16:49:00', '2026-07-11 17:49:00', 1, 1, 'Sắp diễn ra'),
(9, 'abc', '', '2024-01-11 17:40:00', '2024-01-11 17:45:00', 1, 1, 'Sắp diễn ra'),
(10, 'xyz', '', '2026-07-12 17:40:00', '2026-07-12 18:40:00', 1, 1, 'Sắp diễn ra');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvien`
--

CREATE TABLE `nhanvien` (
  `id` int(11) NOT NULL,
  `manv` varchar(20) NOT NULL,
  `tennv` varchar(100) NOT NULL,
  `phongban` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvien`
--

INSERT INTO `nhanvien` (`id`, `manv`, `tennv`, `phongban`, `email`, `password`, `role`) VALUES
(1, 'NV001', 'Huỳnh Bảo Huy', 'Phát triển Phần mềm', 'baohuy@company.com', '123456', 'employee'),
(2, 'NV002', 'Trương Gia Huy', 'Thiết kế UI/UX', 'giahuy@company.com', '123456', 'employee'),
(3, 'NV003', 'Nguyễn Minh Huy', 'Kiểm thử Phần mềm', 'minhhuy@company.com', '123456', 'employee'),
(4, 'QL001', 'Nguyễn Văn A', 'Ban Giám Đốc', 'vanA@company.com', '123456', 'meeting_manager'),
(5, 'NV004', 'Trần Quốc Anh', 'Phòng Kinh doanh', 'quocanh@company.com', '123456', 'employee'),
(6, 'NV005', 'Lê Minh Khang', 'Phòng Nhân sự', 'minhkhang@company.com', '123456', 'employee'),
(7, 'NV006', 'Phạm Ngọc Linh', 'Phòng Kế toán', 'ngoclinh@company.com', '123456', 'employee'),
(8, 'NV007', 'Võ Hoàng Nam', 'Phòng Marketing', 'hoangnam@company.com', '123456', 'employee'),
(9, 'NV008', 'Đặng Thu Trang', 'Chăm sóc khách hàng', 'thutrang@company.com', '123456', 'employee'),
(10, 'NV009', 'Nguyễn Đức Long', 'Phát triển Phần mềm', 'duclong@company.com', '123456', 'employee'),
(11, 'NV010', 'Trần Thảo Vy', 'Thiết kế UI/UX', 'thaovy@company.com', '123456', 'employee'),
(12, 'NV011', 'Lý Gia Bảo', 'Kiểm thử Phần mềm', 'giabao@company.com', '123456', 'employee'),
(13, 'NV012', 'Bùi Khánh An', 'Vận hành hệ thống', 'khanhan@company.com', '123456', 'employee'),
(14, 'NV013', 'Phan Tuấn Kiệt', 'Phòng Kinh doanh', 'tuankiet@company.com', '123456', 'employee'),
(15, 'NV014', 'Hồ Ngọc Mai', 'Phòng Hành chính', 'ngocmai@company.com', '123456', 'employee'),
(16, 'NV015', 'Đỗ Thanh Tùng', 'Phân tích nghiệp vụ', 'thanhtung@company.com', '123456', 'employee');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phong`
--

CREATE TABLE `phong` (
  `id` int(11) NOT NULL,
  `tenphong` varchar(50) NOT NULL,
  `succhua` int(11) NOT NULL,
  `thietbi` text DEFAULT NULL,
  `trangthai` varchar(20) DEFAULT 'Trong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phong`
--

INSERT INTO `phong` (`id`, `tenphong`, `succhua`, `thietbi`, `trangthai`) VALUES
(1, 'Phòng họp A', 20, 'Máy chiếu, Camera, Hệ thống Micro không dây', 'Trong'),
(2, 'Phòng họp B', 10, 'Tivi thông minh, Bảng viết cố định', 'Trong'),
(3, 'Phòng họp C', 5, 'Bảng viết nhỏ', 'Trong'),
(4, 'Phòng họp D', 8, 'Tivi thông minh, Camera, Micro', 'Trong'),
(5, 'Phòng họp E', 12, 'Máy chiếu, Bảng trắng, Loa', 'Trong'),
(6, 'Phòng họp F', 30, 'Máy chiếu, Camera, Hệ thống Micro không dây', 'Trong'),
(7, 'Phòng họp G', 6, 'Tivi 55 inch, Bảng viết', 'Trong'),
(8, 'Phòng họp H', 15, 'Máy chiếu, Camera, Loa hội nghị', 'Trong'),
(9, 'Phòng họp I', 20, 'Tivi 75 inch, Camera, Micro, Bảng trắng', 'Trong'),
(10, 'Phòng họp J', 50, 'Máy chiếu, Hệ thống âm thanh, Camera hội nghị', 'Trong');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chitiet_thamgia`
--
ALTER TABLE `chitiet_thamgia`
  ADD PRIMARY KEY (`cuochop_id`,`nhanvien_id`),
  ADD KEY `nhanvien_id` (`nhanvien_id`);

--
-- Chỉ mục cho bảng `cuochop`
--
ALTER TABLE `cuochop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nguoitao_id` (`nguoitao_id`),
  ADD KEY `phong_id` (`phong_id`);

--
-- Chỉ mục cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `manv` (`manv`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `phong`
--
ALTER TABLE `phong`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenphong` (`tenphong`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cuochop`
--
ALTER TABLE `cuochop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `phong`
--
ALTER TABLE `phong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitiet_thamgia`
--
ALTER TABLE `chitiet_thamgia`
  ADD CONSTRAINT `chitiet_thamgia_ibfk_1` FOREIGN KEY (`cuochop_id`) REFERENCES `cuochop` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chitiet_thamgia_ibfk_2` FOREIGN KEY (`nhanvien_id`) REFERENCES `nhanvien` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cuochop`
--
ALTER TABLE `cuochop`
  ADD CONSTRAINT `cuochop_ibfk_1` FOREIGN KEY (`nguoitao_id`) REFERENCES `nhanvien` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cuochop_ibfk_2` FOREIGN KEY (`phong_id`) REFERENCES `phong` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
