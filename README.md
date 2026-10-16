# WealthFlow — hướng dẫn chạy và kiểm thử

Tài liệu này hướng dẫn thành viên nhóm cài WealthFlow trên Windows/XAMPP, chạy ứng dụng và kiểm tra các luồng chính từ đầu đến cuối.

## 1. Yêu cầu

- Windows 10/11.
- XAMPP có Apache, PHP 8.2 trở lên và MySQL/MariaDB. Ví dụ bên dưới giả sử XAMPP nằm ở `F:\xampp`.
- Trình duyệt hiện đại.
- Kết nối Internet ở lần cài dependencies đầu tiên.
- Composer: có thể cài trên máy hoặc tải Composer PHAR như ở bước 3.

Script setup phải được chạy từ thư mục gốc repository (thư mục chứa file này). Có thể để project ở ổ C dù XAMPP ở ổ F.

## 2. Cách chạy ứng dụng (Khuyến nghị 1-Click)

Hệ thống đã hỗ trợ script tự động kiểm tra môi trường, bật MySQL/Apache và mở ứng dụng.

### Trên Windows
Từ thư mục gốc repository, bạn chỉ cần:
- Nhấp đúp chuột vào file `run_wealthflow.bat`
- Hoặc mở PowerShell/CMD gõ:
  ```cmd
  .\run_wealthflow.bat
  ```

### Trên Linux (Ubuntu/Debian)
Từ thư mục gốc repository, chạy:
```bash
chmod +x run_wealthflow.sh
./run_wealthflow.sh
```

> **Script sẽ tự động:**
> 1. Dò tìm thư mục cài đặt XAMPP (`C:\xampp`, `D:\xampp`, hoặc `/opt/lampp`).
> 2. Kiểm tra và khởi động MySQL nếu chưa chạy.
> 3. Tự động tải Composer dependencies và đồng bộ mã nguồn nếu là lần đầu chạy.
> 4. Khởi tạo cơ sở dữ liệu `wealthflow` và các bảng dữ liệu mẫu nếu chưa có.
> 5. Cấu hình và khởi động Apache chạy trên cổng **8080** mà không gây xung đột hệ thống.
> 6. Tự động mở trình duyệt đến trang chủ `http://localhost:8080/`.

---

## 3. Thông tin đăng nhập mẫu (Test Account)

Trong cơ sở dữ liệu đã có sẵn tài khoản mẫu để kiểm thử:
- **Email:** `user1@gmail.com`
- **Mật khẩu:** `User1234`
- **Tên hiển thị:** `nguyen`
- **Trang chủ:** [http://localhost:8080/](http://localhost:8080/)
- **Trang Dashboard ứng dụng:** [http://localhost:8080/app.html](http://localhost:8080/app.html)
- **API Health Check:** [http://localhost:8080/api/health](http://localhost:8080/api/health)

*(Bạn cũng có thể tự tạo tài khoản mới bằng nút **Đăng ký** trên giao diện).*

Khi khởi động, script sẽ hiển thị thông báo thành công cùng trạng thái kết nối Database và API Health Check (`status: ok`, `databaseConnected: true`).

## 4. Kiểm thử thủ công đầu-cuối

Tạo một tài khoản thử riêng cho mỗi thành viên, dùng email có thể truy cập. Không dùng tài khoản/mật khẩu dùng chung được ghi trong chat hoặc commit trong README.

### 4.1. Giao diện và điều hướng

1. Mở `http://localhost:8080/`.
2. Kiểm tra trang chủ tải được, logo, stylesheet, ảnh và JavaScript hiển thị bình thường.
3. Thử mở form đăng nhập và đăng ký, đóng form, chuyển qua lại giữa hai chế độ.
4. Thu nhỏ cửa sổ hoặc mở bằng điện thoại; kiểm tra bố cục responsive và không có thanh cuộn ngang ngoài ý muốn.

### 4.2. Đăng ký, đăng nhập và phiên

1. Mở form đăng ký và nhập tên, email thử nghiệm, mật khẩu hợp lệ cùng xác nhận mật khẩu.
2. Gửi form; mong đợi thông báo tạo tài khoản thành công.
3. Đăng nhập với email và mật khẩu vừa đăng ký; mong đợi ứng dụng mở được.
4. Tải lại trang ứng dụng; phiên hợp lệ cần tiếp tục hoạt động.
5. Đăng xuất; đăng nhập lại với thông tin đúng.
6. Thử đăng nhập với mật khẩu sai; mong đợi bị từ chối và không vào được trang ứng dụng.
7. Thử đăng ký lại cùng email; mong đợi hệ thống không tạo tài khoản trùng.

### 4.3. Đặt lại mật khẩu và OTP

OTP được gửi **đến email người dùng yêu cầu đặt lại mật khẩu**. Tài khoản gửi thư mặc định là `wealthflow.voz@gmail.com`.

1. Cấu hình SMTP theo mục [4.4](#44-cấu-hình-email-otp-tùy-chọn-nhưng-cần-để-test-email).
2. Trên trang đăng nhập, chọn **Quên mật khẩu**.
3. Nhập email tài khoản thử nghiệm và gửi yêu cầu.
4. Kiểm tra hộp thư đến và Spam/Junk; OTP có hiệu lực 10 phút, tối đa 5 lần nhập.
5. Nhập OTP và mật khẩu mới hợp lệ, xác nhận mật khẩu.
6. Đăng nhập bằng mật khẩu mới; xác nhận mật khẩu cũ không còn dùng được.

### 4.4. Cấu hình email OTP (tùy chọn, nhưng cần để test email)

1. Tài khoản gửi `wealthflow.voz@gmail.com` cần bật Google 2-Step Verification và tạo **Google App Password**.
2. Mở file cấu hình cục bộ sau khi setup:

   ```text
   F:\xampp\htdocs\wealthflow\backend\config.local.php
   ```

3. Đặt App Password vào khóa `MAIL_PASSWORD`:

   ```php
   'MAIL_PASSWORD' => 'APP_PASSWORD',
   ```

   Nếu XAMPP nằm ở ổ/thư mục khác, thay đường dẫn. Không dùng mật khẩu đăng nhập Google thông thường. Không commit hoặc gửi App Password qua chat.
4. Lưu file. Apache không cần khởi động lại.
5. Tại thư mục `backend` đang chạy trong XAMPP, kiểm tra cấu hình trước khi thử giao diện:

   ```cmd
   "F:\xampp\php\php.exe" bin\check-mail.php
   "F:\xampp\php\php.exe" bin\check-mail.php --to=your-email@example.com
   ```

   Dòng đầu không gửi thư; dòng thứ hai gửi một email test. Nếu báo SMTP authentication failed, hãy tạo lại Google App Password. **Không gửi App Password hoặc `config.local.php` cho người khác.**
6. Thử chức năng quên mật khẩu từ giao diện. Nếu chưa nhận OTP, kiểm tra Spam và có thể gửi lại sau 60 giây.

> Nếu source ZIP mới không có `backend/config.local.php`, đó là chủ ý để không làm lộ bí mật. Giữ lại file của bản đang triển khai, hoặc tạo file bằng `php bin/setup.php` mà **không chạy lại script cài mới/reset database**. Chế độ `MAIL_TRANSPORT=log` chỉ dùng để test local, xem file `.eml` trong `backend/storage` (không gửi Gmail).

### 4.5. Kiểm tra màn hình tài chính

Đăng nhập rồi mở mục Ví, Giao dịch, Danh mục, Ngân sách và Định kỳ để kiểm tra giao diện, điều hướng và hiển thị trên desktop/mobile.

**Giới hạn hiện tại:** một số phân hệ trong dashboard là giao diện demo phía frontend, chưa nối với API lưu dữ liệu thật. Không xem thao tác trên các màn hình đó là bằng chứng dữ liệu đã được ghi vào MySQL. Dùng bộ kiểm thử backend ở mục 5 để kiểm tra logic FR03–FR06.

## 5. Chạy kiểm thử backend FR03–FR06

Sau khi cài dependencies thành công, chạy trong PowerShell:

```powershell
& 'F:\xampp\php\php.exe' '.\source\backend\tests\run_tests.php'
```

Chạy từ thư mục gốc repository. Nếu XAMPP ở đường dẫn khác, đổi đường dẫn `php.exe`. Bộ test dùng SQLite in-memory và không cần sửa database MySQL của ứng dụng.

Kết quả mong đợi có dòng tổng kết `36 PASSED, 0 FAILED` (số lượng có thể thay đổi khi nhóm bổ sung test) và tiến trình kết thúc không có lỗi.

## 6. Khởi động ứng dụng hàng ngày

Khi khởi động lại máy hoặc muốn mở lại ứng dụng:
- **Windows:** Chỉ cần chạy lại file `run_wealthflow.bat` (hoặc nhấp đúp chuột).
- **Linux:** Chạy `./run_wealthflow.sh`.

Script sẽ tự kiểm tra trạng thái: nếu MySQL/Apache đang chạy thì giữ nguyên và mở trình duyệt, nếu dịch vụ nào chưa bật thì sẽ tự động khởi chạy an toàn. Bạn không cần phải nhớ cấu hình lệnh thủ công.

## 7. Xử lý lỗi thường gặp

| Lỗi | Cách kiểm tra/xử lý |
|---|---|
| `composer` không được nhận diện | Dùng Composer PHAR theo mục 3.2 và truyền `-ComposerPhar "$env:TEMP\composer.phar"` khi chạy setup. |
| Thiếu PHP ZIP | Script bật `php\ext\php_zip.dll` tạm cho Composer. Nếu báo DLL thiếu, kiểm tra lại cài đặt XAMPP/PHP. |
| Cổng 8080 đang bận | Chọn cổng khác khi chạy setup, ví dụ 8081. Đóng ứng dụng đang dùng cổng trước khi setup. |
| MySQL không kết nối được | Xác nhận MySQL ở XAMPP Control Panel đang Running và kiểm tra `DB_HOST`, `DB_PORT` trong `backend\config.local.php`. |
| Trang không mở / connection refused | Xác nhận Apache đang chạy, kiểm tra cổng trong `httpd.conf`, rồi xem `F:\xampp\apache\logs\error.log`. |
| API báo dịch vụ chưa sẵn sàng | Chạy health endpoint; kiểm tra MySQL và `F:\xampp\apache\logs\error.log`. |
| OTP không tới hộp thư | Kiểm tra `MAIL_PASSWORD` có App Password đúng, xem Spam/Junk, và kiểm tra log Apache. Không gửi thông tin bí mật cho người khác. |

## 8. Báo lỗi để nhóm xử lý

Khi một bước thất bại, ghi lại:

1. Hệ điều hành, phiên bản PHP và đường dẫn XAMPP (không gửi bí mật).
2. Bước đang làm và thao tác vừa thực hiện.
3. Thông báo lỗi đầy đủ hoặc ảnh chụp đã che email/token.
4. Kết quả `/api/health` nếu server đã chạy.
5. Dòng log liên quan trong `XAMPP\apache\logs\error.log`, sau khi rà soát và che thông tin riêng tư.

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
