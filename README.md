# Hệ thống quản lý lịch họp — Module Employee

## Cài đặt nhanh trên XAMPP

1. Chép thư mục này vào `xampp/htdocs/quanly_lichhop2`.
2. Khởi động Apache và MySQL.
3. Mở phpMyAdmin, chọn **Import**, rồi nhập file `database/quanly_lichhop.sql`.
4. Kiểm tra `db_connect.php`:
   - Database: `quanly_lichhop`
   - User mặc định XAMPP: `root`
   - Password mặc định: để trống
5. Truy cập:
   `http://localhost:8080/quanly_lichhop2/chon_tai_khoan.php`

## Tài khoản demo Employee

- Huỳnh Bảo Huy
- Trương Gia Huy
- Nguyễn Minh Huy

Trang chọn tài khoản chỉ dùng để mô phỏng đăng nhập khi trình bày. Mỗi tài khoản nên mở ở một trình duyệt hoặc cửa sổ ẩn danh khác nhau để thử chức năng Đồng ý/Từ chối.

## Chức năng chính

- Tạo cuộc họp, chọn phòng, thời gian và nhiều người tham gia.
- Không cho tự mời chính mình.
- Kiểm tra trùng lịch phòng, người tổ chức và người tham gia.
- Xem lịch theo ngày, tuần hoặc tháng; chuyển kỳ trước/Hôm nay/kỳ sau.
- Lọc lịch theo người tổ chức, phòng họp và khoảng ngày giờ.
- Xem chi tiết, danh sách người tham gia và trạng thái phản hồi.
- Người được mời có thể Đồng ý hoặc Từ chối.
- Người tổ chức có thể sửa hoặc hủy trước giờ bắt đầu.
- Dashboard lấy số liệu trực tiếp từ cơ sở dữ liệu.
