# WealthFlow - Ghi chú chỉnh sửa giao diện

## Dọn giao diện và tái tổ chức mã

- Bỏ nhãn phiên bản Construction/Demo, các mã FR/UC/BR/SYS/NFR hiển thị cho người dùng, banner kỹ thuật trên đầu trang và các dòng trạng thái mô phỏng.
- Giữ nguyên danh sách phân hệ và các route backend hiện có.
- Giao diện quản trị chỉ hiển thị cho phiên đăng nhập có `role = ADMIN`; thao tác quản trị chưa có API sẽ không lưu dữ liệu.
- Phân chia JS thành `app/` (`main.js`, `pages.js`, `forms.js`, `ui-core.js`), `landing/`, `services/`, `data/`. Không sửa logic xác thực phía máy chủ.
- Các trang chưa nối API **vẫn đọc dữ liệu tĩnh** từ `fixtures.js`. Các số liệu này không phải thông tin tài chính thực. Xem `frontend/README.md` trước khi triển khai lên production.
