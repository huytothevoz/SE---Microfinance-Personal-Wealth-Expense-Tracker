# WealthFlow frontend

## Tổ chức mã nguồn

- `css/`: `base.css` dùng chung, `landing.css` cho trang giới thiệu, `app.css` cho ứng dụng, `responsive.css` cho các breakpoint.
- `js/landing/main.js`: đăng nhập, đăng ký, quên/đặt lại mật khẩu qua API thật.
- `js/app/main.js`: điều hướng, xác định trang, xử lý sự kiện và biểu mẫu bảo mật.
- `js/app/pages.js`: các màn hình chính và layout theo phân hệ.
- `js/app/forms.js`: cấu hình biểu mẫu cho tính năng chờ kết nối API.
- `js/app/ui-core.js`: menu điều hướng, icon, escape HTML và định dạng số.
- `js/services/auth-api.js`: client xác thực dùng chung.
- `js/data/fixtures.js`: số liệu tĩnh cũ chưa kết nối API; dùng tạm cho layout, **không đại diện dữ liệu của tài khoản**.
- `assets/favicon.png`: biểu tượng website.

## Lưu ý khi tiếp tục triển khai

Một số thẻ số liệu, biểu đồ, danh sách và biểu mẫu chưa được nối với cơ sở dữ liệu: phần frontend này vẫn dùng fixture. Trước khi đưa ra môi trường thật phải thay dữ liệu fixture bằng dữ liệu API theo người dùng đã đăng nhập; không trình bày fixture là số dư thật.

Các tính năng đăng nhập, đăng ký, đăng xuất, đổi mật khẩu và reset mật khẩu đã gọi backend. Các biểu mẫu nghiệp vụ không có API xử lý chỉ hiển thị nhắc nhở 'Tính năng lưu chưa khả dụng', không báo lưu thành công.

**Khi tách tiếp:** tạo module trong `js/services/` cho các API ví, giao dịch, ngân sách và định kỳ; tách dần `js/app/pages.js` thành các module nhỏ hơn theo từng nghiệp vụ khi đã kết nối API, tránh chuyển đổi hàng loạt trong lúc đang phát triển. Chỉ thay đổi từng module sau khi có test smoke tránh làm hỏng điều hướng.
