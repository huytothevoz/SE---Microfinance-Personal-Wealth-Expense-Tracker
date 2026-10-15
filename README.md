# WealthFlow — hướng dẫn chạy và kiểm thử

Tài liệu này hướng dẫn thành viên nhóm cài WealthFlow trên Windows/XAMPP, chạy ứng dụng và kiểm tra các luồng chính từ đầu đến cuối.

## 1. Yêu cầu

- Windows 10/11.
- XAMPP có Apache, PHP 8.2 trở lên và MySQL/MariaDB. Ví dụ bên dưới giả sử XAMPP nằm ở `F:\xampp`.
- Trình duyệt hiện đại.
- Kết nối Internet ở lần cài dependencies đầu tiên.
- Composer: có thể cài trên máy hoặc tải Composer PHAR như ở bước 3.

Script setup phải được chạy từ thư mục gốc repository (thư mục chứa file này). Có thể để project ở ổ C dù XAMPP ở ổ F.

## 2. Cảnh báo về dữ liệu

`setup-run-version-windows.ps1` là script **cài mới**, không phải lệnh chạy ứng dụng thường ngày. Khi chạy, script:

1. Sao lưu rồi thay thế **toàn bộ** thư mục `XAMPP\htdocs`.
2. Đặt lại database `wealthflow`.
3. Giữ bản backup trong `XAMPP\wealthflow-backups`.

Sao lưu website/database cần giữ trước khi chạy. Không chạy lại setup chỉ để bật ứng dụng hoặc thử đăng nhập.

## 3. Cài mới lần đầu

### 3.1. Khởi động MySQL

1. Mở XAMPP Control Panel.
2. Nhấn **Start** ở dòng **MySQL**.
3. Chờ đến khi trạng thái MySQL hiện **Running**.
4. Để cửa sổ XAMPP mở trong lúc cài và kiểm thử.

Không cần cài Apache dưới dạng Windows Service. Setup sẽ khởi động Apache của XAMPP sau khi cài.

### 3.2. Tải Composer PHAR nếu chưa cài Composer

Mở PowerShell tại thư mục gốc repository. Nếu XAMPP ở `F:\xampp`, chạy:

```powershell
$Php = 'F:\xampp\php\php.exe'
$ComposerPhar = Join-Path $env:TEMP 'composer.phar'
Invoke-WebRequest 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $ComposerPhar
& $Php $ComposerPhar --version
```

Nếu XAMPP ở nơi khác, sửa `$Php` thành đường dẫn tới `php.exe` của XAMPP. Lệnh cuối cần in ra phiên bản Composer.

### 3.3. Chạy setup

Trong cùng PowerShell, từ thư mục gốc repository, chạy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ".\setup-run-version-windows.ps1" -XamppDir "F:\xampp" -ApachePort 8080 -ComposerPhar "$env:TEMP\composer.phar"
```

Thay `F:\xampp` nếu XAMPP ở đường dẫn khác. Nếu Composer đã cài và nhận diện được bằng lệnh `composer`, có thể bỏ `-ComposerPhar "$env:TEMP\composer.phar"`.

Nếu cổng 8080 đang được chương trình khác sử dụng, chọn cổng trống khác, ví dụ `-ApachePort 8081`. Sau khi cài, dùng đúng cổng mà bạn đã chọn.

Setup thành công khi dòng cuối là:

```text
Fresh install and API health check completed.
```

Cảnh báo `No installed service named "Apache2.4"` có thể xuất hiện với XAMPP không chạy Apache dạng Windows Service. Hãy xem kết quả cuối cùng của setup và health check để xác nhận thành công.

### 3.4. Mở ứng dụng và xác nhận API

Mở trình duyệt:

```text
http://localhost:8080/
```

Không mở `source\index.html` trực tiếp bằng `file://`.

Trong PowerShell, xác nhận API và database:

```powershell
Invoke-RestMethod 'http://localhost:8080/api/health' | ConvertTo-Json -Depth 5
```

Kết quả mong đợi có `success: true` và `databaseConnected: true`.

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

## 6. Kiểm tra nhanh sau khi khởi động lại máy

Sau khi đã setup thành công, **không chạy lại setup**:

1. Mở XAMPP Control Panel.
2. Start MySQL nếu chưa Running.
3. Mở PowerShell và chạy Apache bằng cấu hình XAMPP:

   ```powershell
   Start-Process -FilePath 'F:\xampp\apache\bin\httpd.exe' -ArgumentList @('-d','F:/xampp/apache','-f','conf/httpd.conf') -WorkingDirectory 'F:\xampp\apache\bin'
   ```

4. Kiểm tra `http://localhost:8080/` và lệnh health trong mục 3.4.

Nếu Apache đã chạy, không khởi động thêm bản Apache thứ hai. Trong XAMPP Control Panel cũng có thể nhấn **Start** ở dòng Apache nếu cấu hình XAMPP đang trỏ đúng cổng.

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
