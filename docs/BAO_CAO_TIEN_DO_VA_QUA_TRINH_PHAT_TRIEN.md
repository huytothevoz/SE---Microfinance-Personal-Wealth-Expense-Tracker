# BÁO CÁO TIẾN ĐỘ VÀ QUÁ TRÌNH PHÁT TRIỂN DỰ ÁN
## HỆ THỐNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN, TÀI SẢN VÀ TÀI CHÍNH VI MÔ (WEALTHFLOW)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG - KHOA CÔNG NGHỆ THÔNG TIN**  
- **Môn học:** Công nghệ phần mềm (Software Engineering) - Lớp N3  
- **Giảng viên hướng dẫn:** ThS. Võ Thị Kim Anh  
- **Sinh viên thực hiện (Nhóm Voz):**
  1. Võ Trịnh Quốc Huy
  2. Võ Văn Khôi Nguyên
  3. Nguyễn Thành Trung
  4. Phan Nguyễn Tuấn Kiệt  
- **Thời gian hoàn thành:** Tháng 11/2026  

---

## 1. BẢNG TỔNG HỢP TIẾN ĐỘ VÀ PHÂN CÔNG NHÂN SỰ

| Thành Viên | Vai Trò | Phân Hệ / Trách Nhiệm Kỹ Thuật | Tiến Độ | Trạng Thái |
| :--- | :--- | :--- | :---: | :---: |
| **Võ Trịnh Quốc Huy** | Trưởng nhóm / Full-stack | FR01 (Auth JWT/OTP), FR15 (Privacy Export/Closure), Dark Mode, Mobile Responsive, Hợp nhất nhánh. | **100%** | Đã hoàn thành |
| **Võ Văn Khôi Nguyên** | Backend / Data Analyst | FR02 (Ví tài sản), FR07 (Mục tiêu tiết kiệm), FR08 (Vàng tích lũy), FR10 (Dashboard Net Worth). | **100%** | Đã hoàn thành |
| **Nguyễn Thành Trung** | Kiến trúc / Lead QA | Kiến trúc BCE+D & 2-tier Monolith, mô hình hóa UML (AD/SQ/CD), FR13, FR14 (Admin), QA Suite E2E. | **100%** | Đã hoàn thành |
| **Phan Nguyễn Tuấn Kiệt** | Core Engine / Logic | FR03 (Sổ cái kép ACID), FR04 (Đa tệ snapshot), FR05 (Ngân sách), FR06 (Định kỳ), Planning Workbook. | **100%** | Đã hoàn thành |

---

## 2. BẢNG CÁC MỐC TIẾN ĐỘ THEO PHA VÀ SPRINT (UNIFIED PROCESS)

| Pha / Sprint | Thời Gian | Mục Tiêu Trọng Tâm | Sản Phẩm Bàn Giao | Trạng Thái |
| :--- | :--- | :--- | :--- | :---: |
| **Pha Inception** | 06/10 – 08/10/2026 | Khảo sát ý tưởng, phân tích tính khả thi, Business-Modelling, tổ chức thư mục dự án. | `Business-Modelling.md`, `NhomVozSRS.md`, cấu trúc thư mục UP 4 pha. | **100%** |
| **Pha Elaboration** | 09/10 – 17/10/2026 | Đặc tả SRS v2.1 (13 Use Cases, 15 FRs, 18 BRs), mô hình hóa UML (11 AD, 8 SQ, 12 CD), CSDL 28 bảng, API 12 nhóm, refactor 2-tier Monolith. | `SRS.md` v2.1, CSDL 28 bảng, API specs, BCE+D Class Diagrams, commit `7b4c5a9`. | **100%** |
| **Construction Sprint 1** | 14/10 – 19/10/2026 | Hiện thực động cơ sổ kép ACID (FR03), Đa tiền tệ snapshot (FR04), Ngân sách (FR05), Lập lịch định kỳ (FR06), Ví tiền (FR02), Xác thực JWT/Gmail OTP (FR01). | `source/backend/src/Modules/*` (Core Ledger, Wallets, Budgets, Recurring, Auth). | **100%** |
| **Construction Sprint 2** | 20/10 – 26/10/2026 | Mục tiêu tiết kiệm (FR07), Vàng & Net Worth (FR08, FR10), Nợ vi mô (FR09), Admin danh mục mẫu & Broadcast (FR13), Admin thống kê vĩ mô & khóa user (FR14). | Phân hệ Goals, Wealth, Loans, Admin FR13/FR14, bộ test visual PyTest. | **100%** |
| **Construction Sprint 3 & Hoàn thiện** | 27/10 – 03/11/2026 | Tối ưu giao diện (Dark Mode, Responsive), Phân hệ Quyền riêng tư theo NĐ 13 (Export JSON, Close Account), merge `dev_trung` vào `main`, tinh gọn tài liệu. | Dark mode, Mobile layout, Privacy Module, 170+ test cases Pass, tài liệu hoàn chỉnh. | **100%** |

---

## 3. BẢNG ĐÁNH GIÁ MỨC ĐỘ HOÀN THÀNH 15 NHÓM CHỨC NĂNG (FR01 – FR15)

| Mã FR | Phân Hệ Chức Năng | Nội Dung Kỹ Thuật Đã Triển Khai | Tiến Độ | Trạng Thái |
| :---: | :--- | :--- | :---: | :---: |
| **FR01** | Định danh & Phiên | Đăng ký, đăng nhập JWT, gửi OTP qua Gmail SMTP, đổi mật khẩu, đóng tài khoản vĩnh viễn (Soft Closure). | 100% | Hoàn thành |
| **FR02** | Quản lý ví tài sản | Tạo ví (VND, ngoại tệ), hạch toán mở sổ, điều chỉnh kiểm kê số dư đối ứng vốn chủ (`/adjustments`), lưu trữ ví. | 100% | Hoàn thành |
| **FR03** | Giao dịch & Sổ kép ACID | Hạch toán 2 dòng Nợ/Có cân bằng `DECIMAL(24,8)`, chuyển ví, đảo bút toán Reversal, chống trừ trùng Idempotency. | 100% | Hoàn thành |
| **FR04** | Đa tiền tệ & Snapshot tỷ giá | Khóa tỷ giá hối đoái thời điểm phát sinh giao dịch (`fx_rate_snapshot`), đồng bộ tỷ giá Vietcombank. | 100% | Hoàn thành |
| **FR05** | Kiểm soát ngân sách | Thiết lập hạn mức ngân sách tháng theo danh mục, cảnh báo ngưỡng Vàng ($\ge 80\%$) và Đỏ ($\ge 100\%$), chống dedupe. | 100% | Hoàn thành |
| **FR06** | Giao dịch định kỳ tự động | Lập lịch định kỳ, thuật toán Monthly Clamp neo ngày cuối tháng (28/29/30/31), background cron worker. | 100% | Hoàn thành |
| **FR07** | Mục tiêu tích lũy tài chính | Thiết lập mục tiêu, phân bổ nạp/rút tiền, phong tỏa logic số dư ví không làm tăng ảo Net Worth. | 100% | Hoàn thành |
| **FR08** | Vàng & Tài sản tích lũy | Quản lý vàng nhẫn/SJC, định giá thị trường, giá vốn bình quân gia quyền (WAC), hạch toán lãi lỗ bán vàng. | 100% | Hoàn thành |
| **FR09** | Tài chính vi mô (Vay/Nợ) | Hợp đồng đi vay và cho vay, sinh lịch phân kỳ thanh toán, phân rã rành mạch tiền gốc và lãi/phí. | 100% | Hoàn thành |
| **FR10** | Dashboard sức khỏe tài chính | Tổng hợp Tài sản ròng ($	ext{Net Worth} = 	ext{Tổng tài sản} - 	ext{Tổng nợ}$), biểu đồ trực quan biến động tài sản. | 100% | Hoàn thành |
| **FR11** | Dòng tiền & Saving Rate | Tính toán Dòng tiền thuần (Net Cash Flow) và Tỷ lệ tiết kiệm, xử lý an toàn không chia cho 0 khi $	ext{Income} \le 0$. | 100% | Hoàn thành |
| **FR12** | Đối soát & Xuất CSV | Bộ lọc tìm kiếm đa tiêu chí, đối soát tự động số dư ví với sổ cái kép, xuất CSV mã hóa UTF-8 BOM chống injection. | 100% | Hoàn thành |
| **FR13** | Cấu hình hệ thống Admin | Quản lý danh mục mẫu hệ thống (System Default Categories) và phát thông báo Broadcast toàn hệ thống. | 100% | Hoàn thành |
| **FR14** | Quản trị người dùng Admin | Dashboard thống kê vĩ mô, khóa/mở khóa tài khoản vi phạm, tuân thủ nghiêm ngặt cách ly dữ liệu cá nhân BR11. | 100% | Hoàn thành |
| **FR15** | Bảo mật & Quyền riêng tư | Xác thực JWT trên mọi endpoint, chống IDOR, xuất dữ liệu JSON và đóng tài khoản theo Nghị định 13/2023/NĐ-CP. | 100% | Hoàn thành |

---

## 4. BẢNG NHẬT KÝ COMMITS TOÀN DIỆN DỰ ÁN (GIT COMMIT HISTORY)

| Mã Commit | Ngày | Tác Giả | Nội Dung Thực Hiện Chính | Pha / Sprint |
| :---: | :---: | :--- | :--- | :---: |
| `2d422e1` | 06/10/2026 | Võ Huy | Khởi tạo kho lưu trữ dự án (Initial commit). | Inception |
| `df91bfb` | 06/10/2026 | Khôi Nguyên | Thêm tài liệu phác thảo yêu cầu ban đầu (NhomVozSRS.md). | Inception |
| `15fe402` | 06/10/2026 | Thành Trung | Tái cấu trúc thư mục theo 4 pha Unified Process và tạo source. | Inception |
| `aa19156` | 06/10/2026 | Thành Trung | Thiết lập cấu hình .agents và kỹ năng unified-process-se. | Inception |
| `b862541` | 06/10/2026 | Võ Huy | Bổ sung ý tưởng đề tài quản lý tài chính cá nhân & vi mô. | Inception |
| `3e42b25` | 06/10/2026 | Khôi Nguyên | Xây dựng tài liệu mô hình hóa nghiệp vụ Business-Modelling.md. | Inception |
| `31b50fb` | 09/10/2026 | Tuấn Kiệt | Đặc tả yêu cầu phần mềm SRS v2.0 cho hệ thống WealthFlow. | Elaboration |
| `5f9f118` | 12/10/2026 | Thành Trung | Bổ sung Use Case Diagrams tổng thể cho pha Elaboration. | Elaboration |
| `a29c583` | 13/10/2026 | Thành Trung | Mô hình hóa Activity Diagrams, tinh gọn phạm vi 13 Core Use Cases. | Elaboration |
| `aca013b` | 13/10/2026 | Thành Trung | Thiết kế Sequence Diagrams chi tiết bao quát 13 Use Cases. | Elaboration |
| `e8b0e49` | 13/10/2026 | Thành Trung | Mô-đun hóa Sequence Diagrams thành các tệp độc lập dễ quản trị. | Elaboration |
| `0ee4b2e` | 13/10/2026 | Thành Trung | Tinh gọn tài liệu Elaboration và hoàn thiện Class Diagram. | Elaboration |
| `198d1a6` | 14/10/2026 | Tuấn Kiệt | Hiện thực động cơ cốt lõi FR03 - FR06 (sổ kép, đa tệ, ngân sách, định kỳ). | Construction S1 |
| `9fd7378` | 15/10/2026 | Thành Trung | Chuẩn hóa bảng biểu FR, UC, NFR trong tài liệu SRS.md. | Elaboration |
| `2247542` | 15/10/2026 | Khôi Nguyên | Xây dựng giao diện đăng nhập người dùng (Login UI). | Construction S1 |
| `7eb2d25` | 15/10/2026 | Khôi Nguyên | Xây dựng giao diện quản lý hồ sơ người dùng (User Profile). | Construction S1 |
| `c821ff0` | 15/10/2026 | Quốc Huy | Hợp nhất các module backend cốt lõi FR03-FR06 vào nhánh chính. | Construction S1 |
| `fa7693a` | 15/10/2026 | Quốc Huy | Sửa giao diện đăng nhập và tích hợp gửi mã OTP qua Gmail SMTP. | Construction S1 |
| `aaae911` | 16/10/2026 | Quốc Huy | Cải thiện giao diện và hoàn thiện luồng xác thực mã OTP. | Construction S1 |
| `a9b6524` | 16/10/2026 | Thành Trung | Đơn giản hóa lệnh khởi chạy bằng script 1-click cho Windows và Linux. | Construction S1 |
| `2144258` | 16/10/2026 | Thành Trung | Cập nhật Use Case Diagrams v2.1 và liên kết hai chiều với SRS. | Elaboration |
| `6dd53f5` | 16/10/2026 | Thành Trung | Chuẩn hóa 11 Activity Diagrams vector SVG và cập nhật Class Diagrams. | Elaboration |
| `0dcdc47` | 16/10/2026 | Khôi Nguyên | Hoàn thiện chức năng quản lý ví và tài khoản thanh toán FR02. | Construction S1 |
| `bf5f0b0` | 16/10/2026 | Thành Trung | Tái cấu trúc biểu đồ hoạt động AD08 dạng dọc không chồng chéo. | Elaboration |
| `1722a40` | 17/10/2026 | Thành Trung | Thiết kế sơ đồ lớp tài chính CD01-CD04 theo chuẩn kiến trúc BCE. | Elaboration |
| `4ad4de3` | 17/10/2026 | Thành Trung | Mở rộng BCE sang BCE+D với DAO pattern trên toàn bộ CD01-CD05. | Elaboration |
| `5112dee` | 17/10/2026 | Thành Trung | Hoàn thiện sơ đồ lớp CD00-CD11 kèm ma trận truy vết kiến trúc. | Elaboration |
| `cf51e6d` | 17/10/2026 | Quốc Huy | Bổ sung runner khởi chạy an toàn cho Windows. | Construction S1 |
| `7b4c5a9` | 17/10/2026 | Thành Trung | Tái cấu trúc backend sang 2-tier Modular Monolith, chuẩn hóa BCE+D. | Elaboration / S1 |
| `69a92e2` | 18/10/2026 | Quốc Huy | Hợp nhất nhánh feature/fr02-wallet-management vào main. | Construction S1 |
| `00ddd8c` | 18/10/2026 | Quốc Huy | Hợp nhất PR #1 tái cấu trúc mã nguồn sang nhánh main. | Construction S1 |
| `3660423` | 18/10/2026 | Quốc Huy | Đồng bộ hóa kiến trúc refactor, CSDL và các cập nhật dự án. | Construction S1 |
| `f6ea583` | 18/10/2026 | Khôi Nguyên | Sửa lỗi phân hệ ví và hạch toán số dư giao dịch. | Construction S1 |
| `c9c68ba` | 19/10/2026 | Tuấn Kiệt | Hoàn thành các nhiệm vụ kế hoạch PLAN-01 đến PLAN-05. | Construction S1 |
| `e36a066` | 19/10/2026 | Tuấn Kiệt | Hợp nhất PR #3 kế hoạch nghiệp vụ vào nhánh main. | Construction S1 |
| `4663271` | 20/10/2026 | Khôi Nguyên | Hoàn thiện các ràng buộc nghiệp vụ phân hệ ví tiền. | Construction S2 |
| `d4a856b` | 22/10/2026 | Khôi Nguyên | Hoàn thiện giao dịch tài chính và sửa lỗi kiểm tra số dư. | Construction S2 |
| `8d6a317` | 22/10/2026 | Tuấn Kiệt | Đồng bộ contract-smoke-test với live-data và giao diện ví. | Construction S2 |
| `2ae650a` | 22/10/2026 | Khôi Nguyên | Xây dựng chức năng Mục tiêu tiết kiệm FR07. | Construction S2 |
| `8a6365f` | 23/10/2026 | Khôi Nguyên | Hoàn thiện phân hệ Vàng và Tài sản tích lũy FR08. | Construction S2 |
| `4b55ffb` | 24/10/2026 | Thành Trung | Tích hợp hệ thống kiểm thử trực quan E2E và bộ điều phối kiểm thử. | Construction S2 |
| `0bd4f5f` | 25/10/2026 | Thành Trung | Triển khai phân hệ FR13.01 Quản lý danh mục mẫu hệ thống (Admin). | Construction S2 |
| `6b4c5f1` | 25/10/2026 | Thành Trung | Hoàn thiện FR13, thông báo khẩn cấp Broadcast và sửa run script. | Construction S2 |
| `7c5d850` | 25/10/2026 | Thành Trung | Hoàn thiện kiểm thử trực quan E2E, phân hệ Admin FR13 và tối ưu. | Construction S2 |
| `fd359ba` | 26/10/2026 | Thành Trung | Tích hợp chức năng Quản trị người dùng FR14 và bảo toàn FR12, FR13. | Construction S2 |
| `9d4cdc3` | 26/10/2026 | Khôi Nguyên | Hoàn thiện giao diện bảng điều khiển Dashboard cơ bản. | Construction S2 |
| `6a02572` | 26/10/2026 | Quốc Huy | Sửa lỗi lược đồ bảng phân tích CSDL (repair schema). | Construction S2 |
| `1d104a4` | 27/10/2026 | Quốc Huy | Bổ sung trường ghi chú chi tiết cho danh mục thu chi (note). | Construction S3 |
| `99efad4` | 27/10/2026 | Quốc Huy | Hiện thực hóa chức năng Đóng/Xóa tài khoản người dùng. | Construction S3 |
| `07e7264` | 27/10/2026 | Quốc Huy | Tích hợp chế độ giao diện tối thông minh (Dark Mode). | Construction S3 |
| `551cf9c` | 27/10/2026 | Quốc Huy | Tối ưu hóa và tinh chỉnh kích thước các nút bấm trên giao diện. | Construction S3 |
| `c4cfeda` | 27/10/2026 | Khôi Nguyên | Bổ sung giao diện thích ứng (Responsive) trên thiết bị di động. | Construction S3 |
| `303a15e` | 03/11/2026 | Thành Trung | Dọn dẹp môi trường kiểm thử local trên nhánh dev. | Construction S3 |
| `6d2651f` | 03/11/2026 | Thành Trung | Cập nhật dev_trung theo main: tích hợp dark mode, responsive, bảo toàn FR14. | Construction S3 |
| `356ea84` | 03/11/2026 | Thành Trung | Đồng bộ hóa toàn bộ tài liệu thiết kế SRS, CSDL, API với Construction. | Construction S3 |
| `2e8afdc` | 03/11/2026 | Thành Trung | Bổ sung quyết định kiến trúc ADR, chuẩn hóa COA kế toán và giải trình. | Construction S3 |
| `9e72750` | 03/11/2026 | Thành Trung | Loại bỏ các mục rườm rà không cần thiết trong SRS, component và DB. | Construction S3 |
| `2edf7fa` | 03/11/2026 | Thành Trung | Tạo tài liệu báo cáo tiến độ và lịch sử phát triển dự án chuẩn Template_VN. | Construction S3 |

---

## 5. BẢNG TỔNG HỢP KẾT QUẢ KIỂM THỬ VÀ NGHIỆM THU (QA METRICS)

| Tệp Test Suite | Loại Hình | Nội Dung Nghiệp Vụ Kiểm Thử | Tổng | Pass | Fail | Tỷ Lệ |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: |
| **run_tests.php** | PHP Unit & Integration Test | FR02 – FR06, FR14, Wallet Regression, Core Ledger kép | 87 | 87 | 0 | **100% Pass** |
| **wealth_goals_test.php** | Domain Integration Test | FR07 (Goals), FR08 (Gold), FR09 (Loans), FR10 (Net Worth) | 32 | 32 | 0 | **100% Pass** |
| **privacy_test.php** | Security & Legal Test | Xuất dữ liệu cá nhân JSON, Đóng tài khoản Soft Closure (NĐ 13) | 2 | 2 | 0 | **100% Pass** |
| **contract-smoke-test.mjs** | API Contract Smoke Test | Kiểm tra hợp đồng giao tiếp giữa Frontend và Backend REST API | 10 | 10 | 0 | **100% Pass** |
| **test_fr12_suite.py** | PyTest Visual E2E | Bộ lọc giao dịch, đối soát sổ cái, xuất file CSV chống injection | 18 | 18 | 0 | **100% Pass** |
| **test_fr13_suite.py** | PyTest Visual E2E | Quản lý danh mục mẫu hệ thống, thông báo Broadcast Admin | 21 | 21 | 0 | **100% Pass** |
| **TỔNG CỘNG** | **Toàn bộ hệ thống** | **Kiểm thử tự động hồi quy toàn diện hệ sinh thái WealthFlow** | **170** | **170** | **0** | **100% PASS** |
