# HUIT Schedule Hub

Ứng dụng PHP/MySQL quản lý lịch giảng dạy định kỳ theo giảng viên và cơ sở. Quản trị viên thêm, sửa, xóa và lọc lịch; giảng viên đăng nhập để xem lịch cá nhân theo tuần hoặc tháng. Hệ thống ngăn trùng tiết của giảng viên và phòng, gợi ý giảng viên theo từ khóa chuyên môn, thời gian rảnh, cơ sở và khoảng trống giữa các tiết.

## Cài đặt trên Laragon

1. Đặt thư mục dự án tại `C:\laragon\www\huit-schedule`, bật Apache và MySQL trong Laragon. Bật Auto Virtual Hosts và chọn Reload trong Laragon để dùng `http://huit-schedule.test`; hoặc truy cập `http://localhost/huit-schedule/`.
2. Mở Terminal của Laragon tại thư mục dự án, chạy `composer install`. Cần PHP 8.1 trở lên với các extension `pdo_mysql` và `mbstring`.
3. Sao chép `.env.example` thành `.env`, sửa `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` theo MySQL. SQL mẫu tạo database `huit_db`; nếu đổi tên database hãy sửa cả file SQL trước khi import.
4. Import `database/database.sql` bằng HeidiSQL hoặc chạy `mysql -u root < database/database.sql` trong terminal hỗ trợ chuyển hướng. Tài khoản cần quyền tạo database.
5. Chạy `php scripts/migrate.php`, sau đó chạy `php scripts/seed.php` để tạo dữ liệu mẫu và `php scripts/backfill.php` để tạo danh mục từ lịch mẫu. Script tạo dữ liệu mẫu chỉ chạy khi các bảng liên quan còn trống.

## Tài khoản kiểm thử

Các tài khoản sau được tạo khi chạy thành công `php scripts/seed.php` trên cơ sở dữ liệu trống:

| Vai trò khi đăng nhập | Tên đăng nhập / Email | Mật khẩu mẫu |
| --- | --- | --- |
| Phòng đào tạo | `admin` | `demo1234` |
| Giảng viên | `giangvien1@huit.edu.vn` | `demo1234` |
| Giảng viên | `giangvien2@huit.edu.vn` | `demo1234` |
| Giảng viên | `giangvien3@huit.edu.vn` | `demo1234` |
| Giảng viên | `giangvien4@huit.edu.vn` | `demo1234` |
| Giảng viên | `giangvien5@huit.edu.vn` | `demo1234` |

Đây là tài khoản mẫu cho môi trường phát triển. Nếu đã đổi mật khẩu hoặc cơ sở dữ liệu đã có dữ liệu thì tài khoản thực tế có thể khác; script không ghi đè dữ liệu cũ. Đổi mật khẩu trước khi triển khai thực tế và không đưa `.env` hoặc `vendor/` lên Git.

## Kiểm tra

Chạy `php -l index.php`, `php -l suggestions.php`, `php -l scripts/seed.php`. GitHub Actions tự kiểm tra cú pháp PHP khi push hoặc tạo pull request. Mã nguồn sử dụng giấy phép MIT.

## Phiên bản giao diện và danh mục mới

Sau khi import SQL và chạy `composer install`, chạy `php scripts/migrate.php` để tạo các bảng và cột bổ sung. Với dữ liệu cũ, chạy tiếp `php scripts/backfill.php` để tạo danh mục từ lịch hiện có; script không xóa lịch. Nếu cài dữ liệu mẫu mới, chạy `php scripts/seed.php` trước `php scripts/backfill.php`. Các mã lớp và tên học kỳ sinh từ lịch cũ chỉ là dữ liệu tạm, cần sửa lại cho đúng.

Đăng nhập quản trị, vào **Danh mục** để thiết lập giảng viên, cơ sở, phòng, học phần, học kỳ, chuyên môn theo học phần và thời gian di chuyển hai chiều. Sau đó phân lịch trên trang chính. Gợi ý dựa trên chuyên môn đã khai báo, lịch trống, cơ sở và số tiết trống. Thời gian di chuyển hiện được so với số tiết trống nhân 50 phút; đây là ước tính, chưa có cấu hình giờ thực tế từng tiết. Chưa cấu hình thời gian giữa hai cơ sở sẽ không cho phân hai lịch cùng ngày cho cùng giảng viên ở hai cơ sở đó.

Thời khóa biểu tuần hiển thị từng buổi, cho phép quản trị đánh dấu một buổi nghỉ và khôi phục. Lịch bù có thể nhập như lịch riêng; hệ thống chưa tự động tạo lịch bù hay gửi thông báo. Màn hình tháng là danh sách lịch định kỳ, không liệt kê từng buổi nghỉ. Thao tác kiểm tra trùng hiện chưa có cơ chế khóa cho nhiều người phân lịch đồng thời. Trước khi triển khai trực tuyến cần bổ sung khóa giao dịch, bảo mật và triển khai máy chủ phù hợp.

Trên cấu hình Laragon hiện tại, Apache chạy cổng 8080: truy cập `http://localhost:8080/huit-schedule/`.

## Cấu trúc dự án

- `index.php`, `manage.php`, `suggestions.php`: các điểm truy cập giữ nguyên đường dẫn Laragon.
- `app/bootstrap.php`: kết nối cơ sở dữ liệu, phiên đăng nhập và các hàm dùng chung.
- `app/controllers/`: xử lý yêu cầu và chuẩn bị dữ liệu cho từng trang.
- `app/views/`: giao diện PHP của lịch và danh mục.
- `assets/css/`, `assets/js/`: giao diện và tương tác trên trình duyệt.
- `database/`: cấu trúc cơ sở dữ liệu ban đầu.
- `scripts/`: cập nhật cấu trúc, tạo dữ liệu mẫu và chuyển dữ liệu cũ; chỉ chạy qua dòng lệnh.

Các thư mục `app/` và `database/` được chặn truy cập trực tiếp bằng cấu hình Apache `.htaccess`.
