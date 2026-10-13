# SQ08: Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo

> Tham chiếu: [SRS.md](../../SRS.md) v2.1 | [sequence_diagram.md](../sequence_diagram.md) | [class_diagram.md](../class_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ08** |
| **Tên Kịch bản** | **Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo Toàn hệ thống** |
| **Use Cases bao phủ** | `UC12` (Quản trị người dùng & Thống kê vĩ mô), `UC13` (Cấu hình hệ thống & Phát thông báo) |
| **Quy tắc Nghiệp vụ (BR)** | `BR11` (Bảo mật phiên & Quyền riêng tư người dùng), `BR13` (Quản trị cấu hình & Kiểm toán hành chính), `BR14` (Kiểm toán bảo mật) |
| **Yêu cầu Chức năng (FR)** | `FR12.01` - `FR12.04`, `FR13.01` - `FR13.04` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa quy trình quản trị người dùng, khóa tài khoản vi phạm an toàn (kiểm tra không cho phép tự khóa Admin duy nhất còn lại, thu hồi ngay lập tức toàn bộ Refresh Token, ghi nhật ký Admin Audit Log, trả về 403 Forbidden cho các request tiếp theo của User), và phát hành thông báo toàn hệ thống (Broadcast Announcement) đến hòm thư thông báo của tất cả người dùng Active. |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **Admin** | Actor | Quản trị viên hệ thống (Role = ADMIN, Actor A02). |
| **AdminController** | Boundary | Endpoint tiếp nhận yêu cầu quản trị `/api/admin/users/...` và `/api/admin/announcements`. |
| **AdminService** | Control | Xử lý logic kiểm tra điều kiện Admin cuối cùng, thu hồi phiên và điều phối phát thông báo. |
| **NotificationService** | Control | Quản lý việc phân phối thông báo đến người dùng (fanout to active users). |
| **AdminRepository** | Entity / Persistence | Truy vấn và cập nhật trạng thái User, bảng RefreshToken, lưu AdminAuditLog và SystemAnnouncement. |
| **PostgreSQL Database** | Storage | Cơ sở dữ liệu quan hệ lưu trữ dữ liệu và thực thi Transaction nguyên tử. |
| **EndUser** | Actor | Người dùng thông thường bị ảnh hưởng khi tài khoản bị khóa hoặc khi nhận thông báo. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Quản trị viên (Admin A02)
    participant AC as AdminController
    participant AS as AdminService
    participant NS as NotificationService
    participant AR as AdminRepository
    participant DB as PostgreSQL Database
    actor EndUser as Người dùng bị ảnh hưởng

    %% Luồng Khóa tài khoản
    rect rgb(254, 226, 226)
        note over Admin, EndUser: 1. KỊCH BẢN KHÓA TÀI KHOẢN VI PHẠM (ACCOUNT LOCKOUT)
        Admin ->> AC: PUT /api/admin/users/{id}/lock (reason='Vi phạm điều khoản')
        activate AC
        AC ->> AS: lockUserAccount(adminId, targetUserId, reason)
        activate AS

        alt Lý do khóa để trống
            AS -->> AC: Ném lỗi UnprocessableEntityException("Lý do khóa tài khoản là bắt buộc")
            AC -->> Admin: 422 Unprocessable Entity
        else Kiểm tra tài khoản bị khóa có phải là Admin cuối cùng
            AS ->> AR: checkIsLastAdmin(targetUserId)
            activate AR
            AR ->> DB: SELECT COUNT(*) FROM users WHERE role = 'ADMIN' AND status = 'ACTIVE' AND id != ?
            DB -->> AR: remainingActiveAdminsCount
            AR -->> AS: remainingActiveAdminsCount
            deactivate AR

            alt remainingActiveAdminsCount == 0 (Là Admin duy nhất còn lại)
                AS -->> AC: Ném lỗi BadRequestException("Không thể khóa tài khoản Quản trị viên duy nhất còn lại")
                AC -->> Admin: 400 Bad Request
            else Hợp lệ -> Thực thi khóa nguyên tử
                AS ->> AR: executeLockUserAndRevokeSessions(adminId, targetUserId, reason)
                activate AR
                AR ->> DB: BEGIN TRANSACTION
                AR ->> DB: UPDATE users SET status = 'LOCKED', locked_reason = reason WHERE id = targetUserId
                AR ->> DB: DELETE FROM refresh_tokens WHERE user_id = targetUserId (thu hồi phiên)
                AR ->> DB: INSERT INTO admin_audit_logs (admin_id, action='LOCK_USER', target_id=targetUserId, note=reason)
                AR ->> DB: COMMIT TRANSACTION
                DB -->> AR: Success
                AR -->> AS: lockSuccess
                deactivate AR

                AS -->> AC: LockResponse(targetUserId, status='LOCKED')
                deactivate AS
                AC -->> Admin: 200 OK (Khóa tài khoản thành công)
                deactivate AC

                %% Tác động tức thì lên Người dùng bị khóa
                EndUser -) AC: Yêu cầu API tiếp theo kèm JWT cũ
                activate AC
                AC -->> EndUser: 403 Forbidden (Tài khoản đã bị khóa kèm lý do)
                deactivate AC
            end
        end
    end

    %% Luồng Phát thông báo Broadcast
    rect rgb(240, 253, 244)
        note over Admin, EndUser: 2. KỊCH BẢN PHÁT THÔNG BÁO TOÀN HỆ THỐNG (BROADCAST ANNOUNCEMENT)
        Admin ->> AC: POST /api/admin/announcements (title, content, priority='MAINTENANCE')
        activate AC
        AC ->> AS: broadcastAnnouncement(adminId, dto)
        activate AS

        alt Tiêu đề hoặc nội dung để trống
            AS -->> AC: Ném lỗi UnprocessableEntityException("Tiêu đề và nội dung không được để trống")
            AC -->> Admin: 422 Unprocessable Entity
        else Hợp lệ -> Phát hành thông báo
            AS ->> AR: saveSystemAnnouncement(adminId, dto)
            activate AR
            AR ->> DB: INSERT INTO system_announcements (admin_id, title, content, priority) RETURNING id
            DB -->> AR: announcementId
            AR -->> AS: announcementId
            deactivate AR

            AS ->> NS: fanoutNotificationToActiveUsers(announcementId, dto)
            activate NS
            NS ->> DB: INSERT INTO notifications (user_id, title, content, priority) SELECT id, title, content, priority FROM users WHERE status = 'ACTIVE'
            DB -->> NS: insertedCount
            NS -->> EndUser: Đẩy thông báo chuông (Notification Center sáng lên)
            deactivate NS

            AS -->> AC: BroadcastResult(announcementId, sentUsersCount)
            deactivate AS
            AC -->> Admin: 201 Created (Phát thông báo thành công cho toàn bộ người dùng)
            deactivate AC
        end
    end
```

---

## 4. Đặc tả Chi tiết Các Khối Tương tác

### 4.1 Khóa Tài khoản Vi phạm & Thu hồi Phiên (Account Lockout)
1. **Kiểm tra nghiệp vụ tiền điều kiện:**
   - Trường lý do khóa (`locked_reason`) là bắt buộc để phục vụ công tác kiểm toán nội bộ và giải trình khi người dùng khiếu nại.
   - Hệ thống đếm số lượng tài khoản quản trị viên đang hoạt động (`role = 'ADMIN' AND status = 'ACTIVE' AND id != targetUserId`). Nếu kết quả bằng `0`, hệ thống từ chối hành động bằng mã `400 Bad Request` nhằm ngăn chặn trường hợp hệ thống mất toàn bộ quyền quản trị cấp cao.
2. **Thực thi giao dịch nguyên tử (ACID Transaction):**
   - Đổi trạng thái `users.status = 'LOCKED'`.
   - Xóa ngay lập tức mọi bản ghi trong `refresh_tokens` thuộc `targetUserId`. Khi đó, người dùng không thể xin cấp lại Access Token mới.
   - Khi Access Token hiện tại hết hạn (tối đa 15 phút) hoặc ngay tại lần truy vấn tiếp theo khi Middleware kiểm tra cờ trạng thái tài khoản, hệ thống sẽ lập tức từ chối với mã phản hồi `403 Forbidden`.
   - Ghi bản ghi vào `admin_audit_logs` để lưu vết người thực hiện, thời điểm và lý do.

### 4.2 Phát hành Thông báo Toàn hệ thống (Broadcast Announcement)
1. **Lưu trữ thông báo gốc:** Lưu bản ghi vào bảng `system_announcements` do Quản trị viên khởi tạo, kèm mức độ ưu tiên (`INFO`, `WARNING`, `MAINTENANCE`).
2. **Kỹ thuật Fan-out:** `NotificationService` phân phối thông báo hàng loạt vào bảng `notifications` cho tất cả người dùng có trạng thái `ACTIVE` thông qua câu lệnh SQL tối ưu (`INSERT INTO ... SELECT ...`), đảm bảo hiệu năng cao kể cả với quy mô hàng chục nghìn người dùng.

---

## 5. Mã Nguồn PlantUML Bổ trợ

<details>
<summary><b>Nhấn để mở mã nguồn PlantUML (dùng để xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ08_Quan_Tri_Nguoi_Dung_Va_Thong_Bao
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Quản trị viên (Admin)" as Admin
participant "AdminController" as AC
participant "AdminService" as AS
participant "NotificationService" as NS
participant "AdminRepository" as AR
database "PostgreSQL Database" as DB
actor "Người dùng (End-User)" as EndUser

== Khóa tài khoản vi phạm ==
Admin -> AC: PUT /api/admin/users/{id}/lock (reason)
activate AC
AC -> AS: lockUserAccount(adminId, targetId, reason)
activate AS
AS -> AR: checkIsLastAdmin(targetId)
activate AR
AR -> DB: SELECT COUNT(*) FROM users WHERE role='ADMIN' AND status='ACTIVE'
DB --> AR: count
AR --> AS: count
deactivate AR

alt Là Admin cuối cùng hoặc thiếu lý do
  AS --> AC: Ném lỗi thao tác không hợp lệ
  AC --> Admin: 400 Bad Request / 422 Unprocessable Entity
else Hợp lệ
  AS -> AR: executeLockUserAndRevokeSessions(...)
  activate AR
  AR -> DB: BEGIN TRANSACTION
  AR -> DB: UPDATE users SET status = 'LOCKED' WHERE id = targetId
  AR -> DB: DELETE FROM refresh_tokens WHERE user_id = targetId
  AR -> DB: INSERT INTO admin_audit_logs (...)
  AR -> DB: COMMIT TRANSACTION
  DB --> AR: Success
  AR --> AS: Success
  deactivate AR
  AS --> AC: LockResponse
  deactivate AS
  AC --> Admin: 200 OK (Khóa tài khoản thành công)
  deactivate AC
  EndUser -> AC: Yêu cầu API tiếp theo
  AC --> EndUser: 403 Forbidden (Tài khoản đã bị tạm khóa)
end

== Phát thông báo toàn hệ thống ==
Admin -> AC: POST /api/admin/announcements (title, content, priority)
activate AC
AC -> AS: broadcastAnnouncement(adminId, dto)
activate AS
AS -> AR: saveSystemAnnouncement(...)
activate AR
AR -> DB: INSERT INTO system_announcements (...)
DB --> AR: announcementId
AR --> AS: announcementId
deactivate AR

AS -> NS: fanoutNotificationToActiveUsers(...)
activate NS
NS -> DB: INSERT INTO notifications (...)
NS --> EndUser: Đẩy thông báo chuông cho toàn bộ người dùng Active
deactivate NS
AS --> AC: BroadcastResult
deactivate AS
AC --> Admin: 201 Created (Phát thông báo thành công)
deactivate AC
@enduml
```
</details>

---

## 6. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ07: Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn](./sq07_reconciliation_csv.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
