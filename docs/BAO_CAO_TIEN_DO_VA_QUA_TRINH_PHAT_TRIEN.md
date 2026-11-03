# BÁO CÁO TIẾN ĐỘ VÀ QUÁ TRÌNH PHÁT TRIỂN DỰ ÁN
## HỆ THỐNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN, TÀI SẢN VÀ TÀI CHÍNH VI MÔ (WEALTHFLOW)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG - KHOA CÔNG NGHỆ THÔNG TIN**  
**Môn học:** Công nghệ phần mềm (Software Engineering) - Lớp N3 - Nhóm Voz  
**Giảng viên hướng dẫn:** ThS. Võ Thị Kim Anh  
**Sinh viên thực hiện:**
- Võ Trịnh Quốc Huy
- Võ Văn Khôi Nguyên
- Nguyễn Thành Trung
- Phan Nguyễn Tuấn Kiệt  
**Thời gian hoàn thành:** Tháng 11/2026  

---

### TÓM TẮT DỰ ÁN
Báo cáo này tổng hợp chi tiết toàn bộ tiến độ thực hiện và lịch sử phát triển của dự án WealthFlow từ ngày khởi tạo (06/10/2026) đến thời điểm đóng gói hoàn thiện (03/11/2026) theo mô hình tiến trình Unified Process (UP). Hệ thống WealthFlow giải quyết toàn diện bài toán kiểm soát tài chính cá nhân kết hợp công nợ vi mô thông qua động cơ sổ cái kế toán kép chuẩn mực `DECIMAL(24,8)`, snapshot tỷ giá hối đoái, quản lý tài sản ròng (Net Worth), kiểm soát ngân sách, quản lý nợ vi mô, phân hệ quản trị Admin vĩ mô bảo vệ quyền riêng tư BR11 và tuân thủ Nghị định 13/2023/NĐ-CP. Dự án đã hoàn thành 100% mục tiêu của 15 nhóm chức năng (FR01 – FR15), trải qua 60 commits và vượt qua 170+ ca kiểm thử tự động với tỷ lệ thành công 100% Pass.

---

## MỤC LỤC BÁO CÁO
1. [CHƯƠNG 1. TỔNG QUAN DỰ ÁN VÀ QUY TRÌNH PHÁT TRIỂN](#chuong-1)
   - 1.1 Bối cảnh và Mục tiêu của Hệ thống WealthFlow
   - 1.2 Mô hình Tiến trình Phát triển Phần mềm Unified Process (UP)
   - 1.3 Cơ cấu Tổ chức Nhóm và Phân công Trách nhiệm
   - 1.4 Quy chuẩn Quản lý Mã nguồn Git và Chiến lược Phân nhánh
2. [CHƯƠNG 2. TIẾN ĐỘ THỰC HIỆN VÀ LỊCH SỬ PHÁT TRIỂN CHI TIẾT](#chuong-2)
   - 2.1 Pha Khởi tạo (Inception: 06/10/2026 – 08/10/2026)
   - 2.2 Pha Tinh tế hóa (Elaboration: 09/10/2026 – 17/10/2026)
   - 2.3 Pha Xây dựng - Sprint 1 (Construction Sprint 1: 14/10/2026 – 19/10/2026)
   - 2.4 Pha Xây dựng - Sprint 2 (Construction Sprint 2: 20/10/2026 – 26/10/2026)
   - 2.5 Pha Xây dựng - Sprint 3 & Hoàn thiện (27/10/2026 – 03/11/2026)
   - 2.6 Bảng Tổng Hợp Lịch Sử Commits Chi Tiết (Commit Log Matrix)
3. [CHƯƠNG 3. KẾT QUẢ TRIỂN KHAI PHẦN MỀM (15 CHỨC NĂNG)](#chuong-3)
   - 3.1 Bảng Tổng Hợp Mức Độ Hoàn Thành 15 Nhóm Chức Năng (FR01 – FR15)
   - 3.2 Điểm Nhấn Kiến Trúc và Các Cải Tiến Mở Rộng
4. [CHƯƠNG 4. KẾT QUẢ KIỂM THỬ VÀ ĐẢM BẢO CHẤT LƯỢNG](#chuong-4)
   - 4.1 Chiến Lược Kiểm Thử Hai Tầng (Two-Tier Verification Strategy)
   - 4.2 Thống Kê Kết Quả Kiểm Thử Tự Động
   - 4.3 Đánh Giá Mức Độ Tuân Thủ Quy Tắc Nghiệp Vụ và Pháp Lý
5. [CHƯƠNG 5. KẾT LUẬN VÀ BÀI HỌC KINH NGHIỆM](#chuong-5)
   - 5.1 Đánh Giá Mức Độ Hoàn Thành So Với Mục Tiêu
   - 5.2 Khó Khăn Gặp Phải và Giải Pháp Kỹ Thuật Đã Áp Dụng
   - 5.3 Hướng Phát Triển Trong Tương Lai
6. [TÀI LIỆU THAM KHẢO](#tai-lieu-tham-khao)

---

<a id="chuong-1"></a>
## CHƯƠNG 1. TỔNG QUAN DỰ ÁN VÀ QUY TRÌNH PHÁT TRIỂN
### 1.1. Bối cảnh và Mục tiêu của Hệ thống WealthFlow
Hệ thống WealthFlow (Smart Personal Wealth & Microfinance Tracker) cung cấp giải pháp quản lý tài chính toàn diện kết hợp giữa quản lý chi tiêu cá nhân và theo dõi công nợ vi mô, khắc phục triệt để các nhược điểm của phương pháp ghi chép đơn lẻ (Single-Entry):
- Hiện thực hóa động cơ kế toán kép (Double-Entry Engine) bất biến, bảo đảm cân bằng Debit = Credit với số học thập phân `DECIMAL(24,8)` và thư viện BCMath.
- Theo dõi toàn diện danh mục tài sản: Ví tiền mặt, tài khoản ngân hàng, ví điện tử, sổ tiết kiệm và vàng vật chất.
- Hạch toán tách bạch giữa trả nợ gốc và chi phí lãi vay trong các hợp đồng tài chính vi mô.
- Giám sát vận hành hệ thống vĩ mô (Admin) tuân thủ nghiêm ngặt nguyên tắc cách ly dữ liệu cá nhân BR11.

### 1.2. Phân công Trách nhiệm Thành viên
| Họ và Tên | Vai Trò | Trách Nhiệm Kỹ Thuật Chính | Phân Hệ Phụ Trách |
| :--- | :--- | :--- | :--- |
| **Võ Trịnh Quốc Huy** | Trưởng nhóm / Full-stack | Thiết kế kiến trúc xác thực (JWT/OTP), bảo mật phân quyền, UI/UX Responsive, Dark Mode, Phân hệ Quyền riêng tư. | FR01, FR15, UI Core, Privacy Module |
| **Võ Văn Khôi Nguyên** | Backend / Data | Khảo sát Business-Modelling, hiện thực hóa Quản lý ví (FR02), Mục tiêu tiết kiệm (FR07), Danh mục vàng & Net Worth (FR08, FR10). | FR02, FR07, FR08, FR10 |
| **Nguyễn Thành Trung** | Kiến trúc / Testing | Thiết kế kiến trúc BCE+D & 2-tier Monolith, mô hình hóa UML (AD/SQ/CD), Phân hệ Admin (FR13, FR14), Điều phối kiểm thử tự động E2E. | FR12, FR13, FR14, UML Modeling, QA Suite |
| **Phan Nguyễn Tuấn Kiệt** | Core Engine / Logic | Xây dựng động cơ sổ kép ACID (FR03), Đa tiền tệ snapshot (FR04), Ngân sách (FR05), Lập lịch định kỳ (FR06), Điều phối Sprint kế hoạch. | FR03, FR04, FR05, FR06, Planning Workbook |

---

<a id="chuong-2"></a>
## CHƯƠNG 2. TIẾN ĐỘ THỰC HIỆN VÀ LỊCH SỬ PHÁT TRIỂN CHI TIẾT
Dự án trải qua 60 commits trên Git phản ánh chính xác 4 pha của tiến trình Unified Process:
- **Inception (06/10 – 08/10):** Xác định tầm nhìn, Business-Modelling, cấu trúc thư mục phân tầng.
- **Elaboration (09/10 – 17/10):** SRS v2.1 (13 UCs, 15 FRs, 18 BRs), mô hình hóa UML (11 ADs, 8 SQs, 12 CDs), CSDL 28 bảng, RESTful API 12 nhóm, refactor sang 2-tier Monolith BCE+D.
- **Construction Sprint 1 (14/10 – 19/10):** Động cơ sổ kép FR03, Đa tiền tệ snapshot FR04, Ngân sách FR05, Định kỳ FR06, Ví FR02 và Xác thực JWT/Gmail OTP FR01.
- **Construction Sprint 2 (20/10 – 26/10):** Mục tiêu FR07, Vàng & Net Worth FR08/FR10, Nợ vi mô FR09, Admin cấu hình FR13 và Admin thống kê vĩ mô/khóa tài khoản FR14.
- **Construction Sprint 3 & Hoàn thiện (27/10 – 03/11):** Dark Mode, Mobile Responsive, Privacy Module (Nghị định 13), merge dev_trung vào main, tinh gọn tài liệu.

*(Xem chi tiết bảng 60 commits trong tệp Word đính kèm)*

---

<a id="chuong-3"></a>
## CHƯƠNG 3. KẾT QUẢ TRIỂN KHAI PHẦN MỀM (15 CHỨC NĂNG)
100% 15 nhóm chức năng (FR01 đến FR15) đã hoàn thiện và vượt qua kiểm thử:
- **FR01 – FR04:** Xác thực JWT/OTP, Đóng tài khoản Soft Closure, Ví tài sản, Sổ cái kép Debit=Credit, Tỷ giá snapshot.
- **FR05 – FR07:** Ngân sách chi tiêu cảnh báo ngưỡng 80%/100%, Giao dịch định kỳ neo ngày cuối tháng, Mục tiêu tiết kiệm phong tỏa logic ví.
- **FR08 – FR11:** Vàng tích lũy giá vốn WAC, Khoản vay vi mô tách gốc/lãi, Dashboard Net Worth, Dòng tiền & Saving Rate.
- **FR12 – FR15:** Đối soát sổ cái, xuất CSV UTF-8 BOM, Admin danh mục mẫu & Broadcast, Admin Dashboard vĩ mô & khóa user (BR11), Bảo mật API & Xuất dữ liệu JSON (Nghị định 13).

---

<a id="chuong-4"></a>
## CHƯƠNG 4. KẾT QUẢ KIỂM THỬ VÀ ĐẢM BẢO CHẤT LƯỢNG
- **run_tests.php:** 87/87 PASSED (FR02 - FR06, FR14, Wallet Regression)
- **wealth_goals_test.php:** 32/32 PASSED (FR07, FR08, FR09, FR10)
- **privacy_test.php:** 2/2 PASSED (Export JSON, Account Closure)
- **contract-smoke-test.mjs:** PASSED (API Contract)
- **pytest E2E:** 39/39 PASSED (Visual & Functional)
- **Tổng cộng:** 170+ ca kiểm thử tự động đạt tỷ lệ thành công 100% PASS.

---

<a id="chuong-5"></a>
## CHƯƠNG 5. KẾT LUẬN VÀ BÀI HỌC KINH NGHIỆM
- Dự án hoàn thành xuất sắc 100% mục tiêu kỹ thuật và tiến độ thời gian.
- Xử lý triệt để bài toán trôi số thực bằng `DECIMAL(24,8)` và thư viện BCMath.
- Đảm bảo tính bất biến sổ sách tài chính và sự hài hòa giữa Luật Kế toán 2015 và Nghị định 13/2023/NĐ-CP.
