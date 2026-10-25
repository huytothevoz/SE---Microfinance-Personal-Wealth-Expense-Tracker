# KỊCH BẢN KIỂM THỬ TRỰC QUAN & TÍCH HỢP: FR13.01
## Phân hệ: FR13 - Cấu hình hệ thống & Quản trị danh mục mặc định

---

### 1. Mục tiêu kiểm thử
Xác minh toàn diện các yêu cầu nghiệp vụ và kỹ thuật của chức năng **FR13.01: Quản lý danh mục hệ thống mặc định (Default Categories Management)**:
1. **Phân quyền truy cập (Role-based Access Control)**:
   - Chỉ tài khoản có vai trò `role = 'ADMIN'` mới được phép xem, thêm, sửa, đổi trạng thái và xóa danh mục hệ thống mặc định.
   - Tài khoản người dùng cá nhân thường (`role = 'USER'`) bị chặn với mã lỗi `403 Forbidden` cả ở tầng REST API và giao diện SPA.
2. **Xác thực dữ liệu & Ràng buộc chống trùng lặp**:
   - Tên danh mục bắt buộc nhập (1 - 100 ký tự), loại danh mục thuộc `INCOME` hoặc `EXPENSE`.
   - Nếu tạo trùng cặp `(name, category_type)` (không phân biệt hoa/thường và khoảng trắng thừa), hệ thống trả về mã `409 Conflict`.
3. **Quy tắc kế thừa danh mục mẫu & Bảo toàn dữ liệu người dùng**:
   - Khi người dùng mới đăng ký tài khoản, hệ thống tự động sao chép các danh mục mẫu đang hoạt động (`is_active = 1`) sang danh mục cá nhân.
   - Thay đổi ở danh mục mẫu chỉ áp dụng cho tài khoản đăng ký mới sau thời điểm đó; không bao giờ làm biến đổi hay xóa danh mục và lịch sử giao dịch cá nhân của các tài khoản đã tồn tại.
4. **Nhật ký kiểm toán quản trị (Admin Audit Logging)**:
   - Toàn bộ thao tác thêm, cập nhật, xóa danh mục mẫu đều được ghi vết bất biến vào bảng `admin_audit_logs`.

---

### 2. Danh mục kịch bản kiểm thử trực quan trên trình duyệt (Visual E2E)

| STT | Mã kịch bản | Tên kịch bản | Mục tiêu quan sát trực quan |
|---|---|---|---|
| 1 | **TC-UI-01** | Quan sát giao diện Quản trị danh mục hệ thống | Đăng nhập Admin (`admin@wealthflow.local`), điều hướng tới `app.html#admin-categories`, quan sát tiêu đề, nút "Thêm danh mục mẫu", các thẻ danh mục hiển thị badge loại (Thu nhập/Chi tiêu) và trạng thái (Áp dụng/Tạm ngưng) được nạp trực tiếp từ backend API. |
| 2 | **TC-UI-02** | Thêm danh mục mẫu mới qua Modal Dialog | Bấm nút "Thêm danh mục mẫu", nhập tên "Thưởng kinh doanh", chọn loại "Thu nhập (INCOME)", bấm lưu. Quan sát thẻ mới xuất hiện ngay lập tức trên lưới danh mục. |
| 3 | **TC-UI-03** | Kiểm tra ràng buộc chống trùng lặp (409 Conflict) | Cố tình tạo danh mục đã tồn tại ("Ăn uống" - Chi tiêu). Quan sát thông báo lỗi xung đột màu đỏ hiển thị trực tiếp trên modal, không cho phép lưu trùng. |
| 4 | **TC-UI-04** | Điều chỉnh danh mục & Đổi trạng thái Áp dụng / Tạm ngưng | Bấm "Điều chỉnh" trên thẻ danh mục, đổi trạng thái sang "Tạm ngưng". Quan sát badge chuyển sang "Tạm ngưng". Nhấp nút chuyển nhanh "Kích hoạt" để phục hồi trạng thái. |
| 5 | **TC-UI-05** | Bảo mật phân quyền: Chặn tài khoản người dùng thường | Đăng nhập bằng tài khoản `user1@gmail.com` (role USER), cố tình vào URL `app.html#admin-categories`. Quan sát giao diện từ chối truy cập với thông báo bảo mật "Không có quyền". |

---

### 3. Hướng dẫn chạy kiểm thử

#### Cách 1: Chạy từ Master Test Center (Menu tập trung toàn bộ FR)
```bat
./run_tests.bat
```
- Nhập số `13` và nhấn Enter để mở trực tiếp menu kiểm thử phân hệ FR13.

#### Cách 2: Chạy trực tiếp bộ kiểm thử trực quan FR13
```bat
source/tests/fr13_system_config_broadcast/run_fr13_visual_tests.bat
```

#### Cách 3: Chạy bộ kiểm thử tự động đa tầng BCE+D (Automated Pytest)
```bash
pytest source/tests/fr13_system_config_broadcast/test_fr13_suite.py -v
```
