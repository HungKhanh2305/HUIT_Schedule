# HUIT Schedule Hub

Ứng dụng PHP/MySQL quản lý lịch giảng dạy định kỳ theo giảng viên và cơ sở. Quản trị viên thêm, sửa, xóa và lọc lịch; giảng viên đăng nhập để xem lịch cá nhân theo tuần hoặc tháng. Hệ thống ngăn trùng tiết của giảng viên và phòng, gợi ý giảng viên theo từ khóa chuyên môn, thời gian rảnh, cơ sở và khoảng trống giữa các tiết.

## Cài đặt trên Laragon

1. Đặt thư mục dự án tại `C:\laragon\www\huit-schedule`, bật Apache và MySQL trong Laragon. Bật Auto Virtual Hosts và chọn Reload trong Laragon để dùng `http://huit-schedule.test`; hoặc truy cập `http://localhost/huit-schedule/`.
2. Mở Terminal của Laragon tại thư mục dự án, chạy `composer install`. Cần PHP 8.1 trở lên với các extension `pdo_mysql` và `mbstring`.
3. Sao chép `.env.example` thành `.env`, sửa `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` theo MySQL. SQL mẫu tạo database `huit_db`; nếu đổi tên database hãy sửa cả file SQL trước khi import.
4. Import `database/database.sql` bằng HeidiSQL hoặc chạy `mysql -u root < database/database.sql` trong terminal hỗ trợ chuyển hướng. Tài khoản cần quyền tạo database.
5. Chạy `php scripts/seed.php` để tạo dữ liệu mẫu; script chỉ chạy trên các bảng trống. Đăng nhập quản trị bằng `admin` / `demo1234`, hoặc giảng viên bằng `giangvien1@huit.edu.vn` / `demo1234`. Các giảng viên mẫu khác dùng email `giangvien2@huit.edu.vn` đến `giangvien5@huit.edu.vn` và cùng mật khẩu.

Đây là tài khoản mẫu cho môi trường phát triển: đổi mật khẩu trong cơ sở dữ liệu trước khi triển khai thực tế. Không đưa `.env` hoặc `vendor` lên Git. Gợi ý chuyên môn dựa trên từ khóa chung từ ba ký tự trở lên, điểm ưu tiên lịch cùng cơ sở và giảm khoảng trống; đây là gợi ý, không tính thời gian di chuyển thực tế. Lịch lặp hàng tuần trong khoảng ngày bắt đầu–kết thúc và bộ lọc tuần/tháng hiển thị các lịch có khoảng áp dụng giao với kỳ được chọn.

## Kiểm tra

Chạy `php -l index.php`, `php -l suggestions.php`, `php -l scripts/seed.php`. GitHub Actions tự kiểm tra cú pháp PHP khi push hoặc tạo pull request. Mã nguồn sử dụng giấy phép MIT.

## Phiên bản giao diện và danh mục mới

Sau khi import SQL và chạy `composer install`, chạy `php scripts/migrate.php` để tạo các bảng và cột bổ sung. Với dữ liệu cũ, chạy tiếp `php scripts/backfill.php` để tạo danh mục từ lịch hiện có; script không xóa lịch. Nếu cài dữ liệu mẫu mới, chạy `php scripts/seed.php` trước `php scripts/backfill.php`. Các mã lớp và tên học kỳ sinh từ lịch cũ chỉ là dữ liệu tạm, cần sửa lại cho đúng.

Đăng nhập quản trị, vào **Danh mục** để thiết lập giảng viên, cơ sở, phòng, học phần, học kỳ, chuyên môn theo học phần và thời gian di chuyển hai chiều. Sau đó phân lịch trên trang chính. Gợi ý dựa trên chuyên môn đã khai báo, lịch trống, cơ sở và số tiết trống. Thời gian di chuyển hiện được so với số tiết trống nhân 50 phút; đây là ước tính, chưa có cấu hình giờ thực tế từng tiết. Chưa cấu hình thời gian giữa hai cơ sở sẽ không cho phân hai lịch cùng ngày cho cùng giảng viên ở hai cơ sở đó.

Thời khóa biểu tuần hiển thị từng buổi, cho phép quản trị đánh dấu một buổi nghỉ và khôi phục. Lịch bù có thể nhập như lịch riêng; hệ thống chưa tự động tạo lịch bù hay gửi thông báo. Màn hình tháng là danh sách lịch định kỳ, không liệt kê từng buổi nghỉ. Thao tác kiểm tra trùng hiện chưa có cơ chế khóa cho nhiều người phân lịch đồng thời. Trước khi triển khai trực tuyến cần bổ sung khóa giao dịch, bảo mật và triển khai máy chủ phù hợp.

Trên cấu hình Laragon hiện tại, Apache chạy cổng 8080: truy cập `http://localhost:8080/huit-schedule/`.

## C?u tr?c d? ?n

- `index.php`, `manage.php`, `suggestions.php`: c?c ?i?m truy c?p gi? nguy?n ???ng d?n Laragon.
- `app/bootstrap.php`: k?t n?i c? s? d? li?u, phi?n ??ng nh?p v? c?c h?m d?ng chung.
- `app/controllers/`: x? l? y?u c?u v? chu?n b? d? li?u cho t?ng trang.
- `app/views/`: giao di?n PHP c?a l?ch v? danh m?c.
- `assets/css/`, `assets/js/`: giao di?n v? t??ng t?c tr?n tr?nh duy?t.
- `database/`: c?u tr?c c? s? d? li?u ban ??u.
- `scripts/`: c?p nh?t c?u tr?c, t?o d? li?u m?u v? chuy?n d? li?u c?; ch? ch?y qua d?ng l?nh.

C?c th? m?c `app/` v? `database/` ???c ch?n truy c?p tr?c ti?p b?ng c?u h?nh Apache `.htaccess`.
