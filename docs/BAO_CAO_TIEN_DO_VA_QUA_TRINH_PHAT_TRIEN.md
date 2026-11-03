# BÁO CÁO TIẾN ĐỘ THỰC TẾ VÀ QUÁ TRÌNH PHÁT TRIỂN DỰ ÁN
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
- **Tiến độ tổng thể toàn dự án:** **~82%** (Giai đoạn hoàn thiện Construction, chuẩn bị bước vào Transition)

---

## 1. BẢNG PHÂN CÔNG NHÂN SỰ VÀ TIẾN ĐỘ THỰC TẾ

| Thành Viên | Vai Trò | Phân Hệ / Trách Nhiệm Kỹ Thuật | Tiến Độ | Trạng Thái Thực Tế |
| :--- | :--- | :--- | :---: | :--- |
| **Võ Trịnh Quốc Huy** | Trưởng nhóm / Full-stack | FR01 (Auth JWT/OTP), FR15 (Privacy Export/Closure), Dark Mode, Mobile Responsive, Merge Main. | **85%** | Đang hoàn thiện test SMTP & tải |
| **Võ Văn Khôi Nguyên** | Backend / Data Analyst | FR02 (Ví tài sản), FR07 (Mục tiêu tiết kiệm), FR08 (Vàng tích lũy), FR10 (Dashboard Net Worth). | **80%** | Đang bổ sung Visual E2E & test tải |
| **Nguyễn Thành Trung** | Kiến trúc / Lead QA | Kiến trúc BCE+D & 2-tier Monolith, mô hình hóa UML (AD/SQ/CD), FR13, FR14 (Admin), Khung test E2E. | **88%** | Đã test E2E FR01/12/13, mở rộng các FR còn lại |
| **Phan Nguyễn Tuấn Kiệt** | Core Engine / Logic | FR03 (Sổ cái kép ACID), FR04 (Đa tệ snapshot), FR05 (Ngân sách), FR06 (Định kỳ), Planning Workbook. | **82%** | Cần stress test Concurrency & Cron OS |

*Tiến độ bình quân toàn đội: **~84%**.*

---

## 2. BẢNG ĐÁNH GIÁ TIẾN ĐỘ THEO PHA VÀ SPRINT (UNIFIED PROCESS)

| Pha / Sprint | Thời Gian | Mục Tiêu Trọng Tâm | Sản Phẩm Bàn Giao | Tiến Độ | Trạng Thái |
| :--- | :--- | :--- | :--- | :---: | :---: |
| **Pha Inception** | 06/10 – 08/10/2026 | Khảo sát ý tưởng, tính khả thi, Business-Modelling, tổ chức thư mục dự án. | `Business-Modelling.md`, `NhomVozSRS.md`, cấu trúc UP 4 pha. | **100%** | Hoàn thành |
| **Pha Elaboration** | 09/10 – 17/10/2026 | Đặc tả SRS v2.1 (13 Use Cases, 15 FRs, 18 BRs), UML (11 AD, 8 SQ, 12 CD), CSDL 28 bảng, refactor 2-tier Monolith. | `SRS.md` v2.1, CSDL 28 bảng, API specs, BCE+D Class Diagrams. | **100%** | Hoàn thành |
| **Construction Sprint 1** | 14/10 – 19/10/2026 | Hiện thực động cơ sổ kép ACID (FR03), Đa tiền tệ snapshot (FR04), Ngân sách (FR05), Lập lịch định kỳ (FR06), Ví tiền (FR02), Auth JWT/OTP (FR01). | `source/backend/src/Modules/*` (Core Ledger, Wallets, Budgets, Recurring, Auth). | **95%** | Đạt mục tiêu Sprint |
| **Construction Sprint 2** | 20/10 – 26/10/2026 | Mục tiêu tiết kiệm (FR07), Vàng & Net Worth (FR08, FR10), Nợ vi mô (FR09), Admin danh mục mẫu & Broadcast (FR13), Admin thống kê & khóa user (FR14). | Phân hệ Goals, Wealth, Loans, Admin FR13/FR14, bộ test visual PyTest FR12/FR13. | **85%** | Cần mở rộng test E2E |
| **Construction Sprint 3 & Tích hợp** | 27/10 – 03/11/2026 | Giao diện (Dark Mode, Responsive), Phân hệ Quyền riêng tư theo NĐ 13 (Export JSON, Close Account), merge dev_trung vào main, tinh gọn tài liệu. | Dark mode, Mobile layout, Privacy Module, 170 test cases Unit/Integration/E2E. | **80%** | Đang hoàn thiện kiểm thử |
| **Pha Transition (Dự kiến)** | 04/11 – 15/11/2026 | Kiểm thử chấp nhận người dùng (UAT), stress test đồng thời, triển khai môi trường staging MySQL thật, đóng gói nghiệm thu. | Biên bản nghiệm thu UAT, gói cài đặt 1-click production, hướng dẫn vận hành. | **15%** | Đang chuẩn bị môi trường |

*Tiến độ lũy kế toàn dự án theo tiến trình Unified Process: **~82%**.*

---

## 3. BẢNG ĐÁNH GIÁ MỨC ĐỘ HOÀN THÀNH THỰC TẾ 15 NHÓM CHỨC NĂNG (FR01 – FR15)

| Mã FR | Phân Hệ Chức Năng | Code | Tình Trạng Kiểm Thử & Khoảng Trống Thực Tế | Tiến Độ | Trạng Thái Thực Tế |
| :---: | :--- | :---: | :--- | :---: | :--- |
| **FR01** | Định danh & Phiên JWT/OTP | 95% | Đã test JWT & mock mail (.eml), API contract. Chưa test gửi Gmail SMTP thật ngoài mạng và rate-limit tải cao. | **80%** | Đang test SMTP thật & tải |
| **FR02** | Quản lý ví tài sản | 90% | 24 tests backend SQLite trong run_tests.php. Chưa có kịch bản Visual E2E tự động; chưa test quy đổi ngoại tệ đầu kỳ. | **80%** | Cần bổ sung Visual E2E |
| **FR03** | Giao dịch & Sổ kép ACID | 95% | 16 tests backend cân bằng Debit=Credit. Chưa stress test Concurrency (nhiều giao dịch đồng thời) trên MySQL thật. | **82%** | Cần stress test Concurrency |
| **FR04** | Đa tiền tệ & Snapshot tỷ giá | 80% | 4 tests backend snapshot tỷ giá. Chưa test tự động sync live API Vietcombank khi mất mạng/timeout và cron production. | **70%** | Chưa test live API ngân hàng |
| **FR05** | Kiểm soát ngân sách | 90% | 14 tests backend kiểm tra ngưỡng 80% và 100%. Chưa test gửi cảnh báo Email/Push Notification ra ngoài (mới in-app). | **80%** | Cần test cảnh báo đa kênh |
| **FR06** | Giao dịch định kỳ tự động | 85% | 9 tests backend với worker thủ công, kẹp ngày 28-31. Chưa test daemon chạy ngầm liên tục qua Task Scheduler/Cron OS. | **75%** | Chưa test Cron Daemon OS |
| **FR07** | Mục tiêu tích lũy (Earmark) | 85% | 5 tests domain trong wealth_goals_test.php. Chưa có kịch bản Visual E2E; chưa test biên đóng băng ví nguồn. | **75%** | Thiếu Visual E2E & test biên |
| **FR08** | Vàng & Giá vốn WAC | 85% | 12 tests backend tính WAC và lãi lỗ. Chưa kết nối feed giá vàng thời gian thực (nhập giá thị trường thủ công). | **75%** | Chưa kết nối live price feed |
| **FR09** | Khoản vay & Nợ vi mô | 85% | 9 tests backend phân rã gốc/lãi. Chưa kiểm thử tình huống trễ hạn phạt nợ (Penalty), gia hạn hoặc cơ cấu lại nợ. | **75%** | Chưa test trễ hạn & cơ cấu nợ |
| **FR10** | Dashboard Net Worth | 90% | 4 tests backend cân đối tài sản - nợ. Chưa kiểm thử hiệu năng render biểu đồ trên tập dữ liệu lịch sử lớn (>10.000 tx). | **80%** | Cần test tải dữ liệu lớn |
| **FR11** | Dòng tiền & Saving Rate | 85% | Được test gián tiếp trong nhóm regression 5. Chưa xây dựng bộ test suite riêng biệt; chưa có Visual E2E. | **72%** | Thiếu bộ test riêng & Visual E2E |
| **FR12** | Đối soát & Xuất CSV | 98% | 18-24 tests trong bộ Visual E2E PyTest (test_fr12_suite.py) chạy thật trên trình duyệt (Trial Balance, CSV BOM, chống injection). | **92%** | Hoàn thiện cao (Có Visual E2E) |
| **FR13** | Cấu hình & Broadcast Admin | 98% | 15-21 tests trong bộ Visual E2E PyTest (test_fr13_suite.py) chạy thật trên trình duyệt (danh mục mẫu, broadcast popup). | **92%** | Hoàn thiện cao (Có Visual E2E) |
| **FR14** | Quản trị người dùng Admin | 92% | 11 tests backend (thống kê vĩ mô BR11, khóa user >=10 ký tự, revoke session). Chưa viết kịch bản Visual E2E tự động. | **80%** | Cần bổ sung Visual E2E |
| **FR15** | Bảo mật & Quyền riêng tư | 90% | 2 tests trong privacy_test.php (JSON export, soft closure). Chưa kiểm thử quy trình thu hồi và xóa cứng theo Retention Policy. | **78%** | Chưa test Retention Policy |

---

## 4. BẢNG NHẬT KÝ COMMITS TIÊU BIỂU DỰ ÁN (GIT COMMIT HISTORY)

| Mã Commit | Ngày | Tác Giả | Nội Dung Kỹ Thuật Thực Hiện | Pha / Sprint |
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
| `8d33e4f` | 03/11/2026 | Thành Trung | Cập nhật báo cáo tiến độ tinh gọn bảng biểu theo chuẩn Template_VN. | Construction S3 |

---

## 5. BẢNG ĐÁNH GIÁ KHOẢNG TRỐNG KIỂM THỬ VÀ CHẤT LƯỢNG (QA METRICS)

| Tệp Test Suite | Loại Hình | Phạm Vi Kiểm Thử | Tổng | Pass | Fail | Đạt | Khoảng Trống Cần Bổ Sung (Gap) |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: | :--- |
| **run_tests.php** | Backend Unit & Integration | Môi trường giả lập SQLite in-memory (FR02–FR06, FR14, Ledger kép) | 87 | 87 | 0 | 100% | Chưa test trên môi trường MySQL thật 3307 |
| **wealth_goals_test.php** | Domain Integration Test | Môi trường SQLite (FR07 Goals, FR08 Gold, FR09 Loans, FR10 Net Worth) | 32 | 32 | 0 | 100% | Chưa có kịch bản Visual E2E trên trình duyệt |
| **privacy_test.php** | Security & Legal Test | Xuất dữ liệu cá nhân JSON, Đóng tài khoản Soft Closure (NĐ 13) | 2 | 2 | 0 | 100% | Chưa test thu hồi quyền xóa cứng theo Retention |
| **contract-smoke-test.mjs** | API Contract Smoke Test | Kiểm tra hợp đồng giao tiếp giữa Frontend và Backend REST API (offline) | 10 | 10 | 0 | 100% | Mới kiểm tra cấu trúc DTO, chưa test tải mạng |
| **test_fr12_suite.py** | PyTest Visual E2E | Bộ lọc giao dịch, đối soát sổ cái, xuất file CSV chống formula injection | 18 | 18 | 0 | 100% | Đã test tự động trên trình duyệt Chrome thật |
| **test_fr13_suite.py** | PyTest Visual E2E | Quản lý danh mục mẫu hệ thống, thông báo khẩn cấp Broadcast Admin | 21 | 21 | 0 | 100% | Đã test tự động trên trình duyệt Chrome thật |
| **TỔNG KIỂM THỬ HIỆN CÓ** | **Tập test tự động hiện tại** | **Toàn bộ ca test tự động đã được lập trình sẵn trong mã nguồn** | **170** | **170** | **0** | **100%** | **Bao phủ logic Backend; Visual E2E mới đạt 3/15 FR** |
| **ĐÁNH GIÁ ĐỘ PHỦ HỆ THỐNG** | **Độ phủ kiểm thử thực tế** | **Bao gồm cả Unit test, Integration, Visual E2E và Stress test** | **-** | **-** | **-** | **~68%** | **12 phân hệ còn lại chưa có Visual E2E (mới test tay)** |
