# WealthFlow — Hướng dẫn chạy và kiểm thử

## 1. Khởi động và quản lý hệ thống (TUI Control Center)

Đọc [HUONG_DAN.txt](./HUONG_DAN.txt) để xem thông tin chi tiết. Hệ thống cung cấp **Menu điều khiển trực quan trên Terminal (TUI)** giúp khởi động, dừng, kiểm tra, reset CSDL chỉ với 1 lệnh duy nhất:

### Trên Windows:
Double-click `run_wealthflow.bat`, hoặc mở terminal chạy:
```powershell
.\run_wealthflow.bat
```

### Trên Linux / macOS:
Mở terminal và chạy:
```bash
chmod +x run_wealthflow.sh
./run_wealthflow.sh
```

Menu điều khiển sẽ hiển thị các tùy chọn:
- `[1]`: Khởi động Web Server (cổng `8080`) & MySQL SE độc lập (cổng `3307`), tự động nạp 28 migrations và mở trình duyệt.
- `[2]`: Dừng toàn bộ hệ thống sạch sẽ, không ảnh hưởng đến dữ liệu.
- `[3]`: Kiểm tra trạng thái và kết quả `/api/health`.
- `[4]`: Cập nhật CSDL Migrations (28 file SQL).
- `[5]`: Reset CSDL & nạp lại dữ liệu mẫu (`user1@gmail.com` / `User1234`).
- `[6]`: Chạy toàn bộ test suites tự động (36 unit tests + 7 mail smoke tests).
- `[7]`: Xem log và email OTP cục bộ.
- `[0]`: Thoát menu.

> **Lưu ý an toàn:** Runner tự động nhận diện thư mục cài đặt XAMPP (`C:\xampp`, `D:\xampp`, `E:\xampp`...). Runner sử dụng phiên MySQL riêng biệt trên cổng `3307`, hoàn toàn **KHÔNG** can thiệp hay thay đổi cấu hình Apache cổng 80/8000 và MySQL cổng 3306 của XAMPP.

## 2. Kiểm thử thủ công đầu-cuối

Tạo một tài khoản thử riêng cho mỗi thành viên, dùng email có thể truy cập. Không dùng tài khoản/mật khẩu dùng chung được ghi trong chat hoặc commit trong README.

### 2.1. Giao diện và điều hướng

1. Mở `http://localhost:8080/`.
2. Kiểm tra trang chủ tải được, logo, stylesheet, ảnh và JavaScript hiển thị bình thường.
3. Thử mở form đăng nhập và đăng ký, đóng form, chuyển qua lại giữa hai chế độ.
4. Thu nhỏ cửa sổ hoặc mở bằng điện thoại; kiểm tra bố cục responsive và không có thanh cuộn ngang ngoài ý muốn.

### 2.2. Đăng ký, đăng nhập và phiên

1. Mở form đăng ký và nhập tên, email thử nghiệm, mật khẩu hợp lệ cùng xác nhận mật khẩu.
2. Gửi form; mong đợi thông báo tạo tài khoản thành công.
3. Đăng nhập với email và mật khẩu vừa đăng ký; mong đợi ứng dụng mở được.
4. Tải lại trang ứng dụng; phiên hợp lệ cần tiếp tục hoạt động.
5. Đăng xuất; đăng nhập lại với thông tin đúng.
6. Thử đăng nhập với mật khẩu sai; mong đợi bị từ chối và không vào được trang ứng dụng.
7. Thử đăng ký lại cùng email; mong đợi hệ thống không tạo tài khoản trùng.

### 2.3. Đặt lại mật khẩu và OTP

Runner local ghi email vào file `.eml` trong `source\backend\storage` thay vì gửi SMTP thật. Mở file email mới nhất để lấy OTP.

1. Trên trang đăng nhập, chọn **Quên mật khẩu**.
2. Nhập email tài khoản thử nghiệm và gửi yêu cầu.
3. Mở file `.eml` mới nhất trong `source\backend\storage`; OTP có hiệu lực 10 phút, tối đa 5 lần nhập.
4. Nhập OTP và mật khẩu mới hợp lệ, xác nhận mật khẩu.
5. Đăng nhập bằng mật khẩu mới; xác nhận mật khẩu cũ không còn dùng được.

### 2.4. Kiểm tra màn hình tài chính

Đăng nhập rồi mở mục Ví, Giao dịch, Danh mục, Ngân sách và Định kỳ để kiểm tra giao diện, điều hướng và hiển thị trên desktop/mobile.

**Giới hạn hiện tại:** một số phân hệ trong dashboard là giao diện demo phía frontend, chưa nối với API lưu dữ liệu thật. Không xem thao tác trên các màn hình đó là bằng chứng dữ liệu đã được ghi vào MySQL. Dùng bộ kiểm thử backend ở mục 3 để kiểm tra logic FR03–FR06.

## 3. Chạy kiểm thử backend FR03–FR06

Có thể chạy trực tiếp từ Menu Control Center (chọn `[6]`), hoặc chạy trong PowerShell:

```powershell
# Chạy với PHP của XAMPP:
php .\source\backend\tests\run_tests.php
# Hoặc chỉ định rõ đường dẫn:
& 'D:\xampp\php\php.exe' '.\source\backend\tests\run_tests.php'
```

Chạy từ thư mục gốc repository. Bộ test dùng SQLite in-memory và không cần sửa database MySQL của ứng dụng.

Kết quả mong đợi có dòng tổng kết `36 PASSED, 0 FAILED` (số lượng có thể thay đổi khi nhóm bổ sung test) và tiến trình kết thúc không có lỗi.

## 4. Chạy lại sau khi khởi động lại máy

1. Double-click `run_wealthflow.bat`, chọn `[1]` để khởi động lại server và MySQL riêng của SE.
2. Mở `http://localhost:8080/`; dùng [hướng dẫn setup](./HUONG_DAN.txt) nếu cần xem log khi có lỗi.

## 5. Xử lý lỗi thường gặp

| Lỗi | Cách kiểm tra/xử lý |
|---|---|
| Port 8080 hoặc 3307 đang bận | Đóng ứng dụng đang dùng cổng đó hoặc kiểm tra hướng dẫn trong [HUONG_DAN.txt](./HUONG_DAN.txt). Runner không tự dừng tiến trình khác. |
| MySQL riêng của SE không khởi động | Xem `C:\xampp\se-instance\mysql-error.log`; không xóa thư mục dữ liệu. |
| PHP server hoặc API lỗi | Xem `C:\xampp\se-instance\php-8080-error.log` và kiểm tra `/api/health`. |
| Không thấy email OTP | Runner ghi email local dạng `.eml` trong `source\backend\storage`, không gửi SMTP thật. |

## 6. Báo lỗi để nhóm xử lý

Khi một bước thất bại, ghi lại:

1. Hệ điều hành, phiên bản PHP và đường dẫn XAMPP (không gửi bí mật).
2. Bước đang làm và thao tác vừa thực hiện.
3. Thông báo lỗi đầy đủ hoặc ảnh chụp đã che email/token.
4. Kết quả `/api/health` nếu server đã chạy.
5. Dòng log liên quan trong `C:\xampp\se-instance\` hoặc `source\backend\storage`, sau khi rà soát và che thông tin riêng tư.

Không gửi `config.local.php`, mật khẩu Gmail/App Password, OTP, cookie đăng nhập hoặc token.


## Phân chia code frontend (cập nhật)

- `source/frontend/js/landing/main.js`: trang giới thiệu và xác thực.
- `source/frontend/js/services/auth-api.js`: dịch vụ xác thực.
- `source/frontend/js/app/main.js`: khởi động ứng dụng, điều hướng và sự kiện.
- `source/frontend/js/app/pages.js`: các màn hình giao diện.
- `source/frontend/js/app/forms.js`: trường biểu mẫu cho các thao tác chưa nối API.
- `source/frontend/js/app/ui-core.js`: thành phần UI dùng chung.
- `source/frontend/js/data/fixtures.js`: dữ liệu tĩnh trong màn hình còn chưa kết nối API; **không phải số dư thật**.
- `source/backend/`: backend PHP và API đã có. Không chuyển logic nghiệp vụ sang frontend.
- `source/database/`: schema và migrations MySQL.

Khi triển khai module tài chính: kết nối từng màn hình đến API đã có, bỏ dữ liệu `fixtures.js` tương ứng, kiểm thử với database riêng trước khi đưa vào môi trường thật. Các nút thuộc chức năng chưa làm không ghi dữ liệu hoặc trả thông báo thành công giả.
