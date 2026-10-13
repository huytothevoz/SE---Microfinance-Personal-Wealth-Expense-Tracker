# Database – Bản phác thảo Construction

Tệp `001_initial_schema.sql` dựng **khung** các thực thể được tham chiếu trong SRS Elaboration cho MySQL 8.0+/InnoDB. Không có dữ liệu tài chính giả để import; giao diện demo lấy mẫu từ `frontend/js/demo-data.js`.

Lệnh khởi tạo trên máy đã cài MySQL (tùy chọn, không cần để demo):

```cmd
mysql -u root -p < database\001_initial_schema.sql
```

**Cảnh báo:** Chưa có implementation đảm bảo kiểm tra ledger Nợ/Có, idempotency, concurrency lock, quyền sở hữu, bất biến giao dịch, refresh token/JWT hay đúng tất cả invariants BR01–BR18. Đây không phải schema được nghiệm thu đưa vào production. Nhóm cần mở rộng bằng các migration mới và integration tests trước khi làm API ghi sổ thật.
