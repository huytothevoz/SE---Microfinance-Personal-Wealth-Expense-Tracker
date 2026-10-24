# Hướng Dẫn Chạy Kiểm Thử (Visual E2E Testing)

## 1. Chuẩn bị môi trường
Trước khi kiểm thử, hãy đảm bảo:
1. Mở **XAMPP Control Panel** ➔ Bấm **Start** dịch vụ **MySQL**.
2. Chạy file `run_wealthflow.bat` tại thư mục gốc để khởi động máy chủ Web (hoặc đảm bảo hệ thống đang chạy tại `http://localhost:8080`).

---

## 2. Cách chạy kiểm thử

Tại thư mục gốc dự án, mở CMD/PowerShell và chạy:

```cmd
.\run_tests.bat
```

### Quy trình thao tác:
1. **Bước 1**: Màn hình hiện danh sách các phân hệ (FR) ➔ **Nhập số của FR cần test** (Ví dụ: gõ `12` để chọn FR12).
2. **Bước 2**: Hệ thống sẽ mở tiếp danh sách các kịch bản test của FR đó ➔ **Chọn số kịch bản muốn chạy** (hoặc chọn chạy toàn bộ).
3. **Bước 3**: Trình duyệt Chrome sẽ tự động mở lên kèm con trỏ chuột ảo để bạn theo dõi trực tiếp từng thao tác kiểm thử.

---

### Danh sách kịch bản mẫu của từng Fr thì vào TEST_SCENARIOS.md để xem