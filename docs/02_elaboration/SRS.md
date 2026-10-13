# ĐẶC TẢ YÊU CẦU PHẦN MỀM (SRS) - PHA ELABORATION
## HỆ THỐNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN, TÀI SẢN VÀ TÀI CHÍNH VI MÔ (WEALTHFLOW)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG**  
**KHOA CÔNG NGHỆ THÔNG TIN**  

- **Đề tài:** Microfinance Personal Wealth & Expense Tracker (WealthFlow)
- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Nhóm / Lớp:** Voz / N3
- **Giảng viên hướng dẫn:** ThS. Võ Thị Kim Anh
- **Thành viên nhóm:**
  - Võ Trịnh Quốc Huy
  - Võ Văn Khôi Nguyên
  - Nguyễn Thành Trung
  - Phan Nguyễn Tuấn Kiệt
- **Phiên bản:** 2.1 (Elaboration Refined Baseline - 13 Core Use Cases)
- **Ngày hoàn thiện:** Tháng 10/2026

---

### Mục lục tài liệu

| Phần | Tiêu đề | Nội dung chính |
| :---: | :--- | :--- |
| **[Phần 1](#phan-1)** | **Giới thiệu, Tầm nhìn và Ranh giới hệ thống** | Tầm nhìn sản phẩm, ranh giới In-Scope vs Out-of-Scope (Non-Goals), đối tượng người dùng, danh mục User Requirements (UR) và thuật ngữ chuyên ngành. |
| **[Phần 2](#phan-2)** | **Bối cảnh và Kiến trúc nền tảng** | Ranh giới hệ thống, tác nhân, kiến trúc tham chiếu Layered + Modular Monolith, chính sách đa tiền tệ và 18 quy tắc nghiệp vụ cốt lõi (BR). |
| **[Phần 3](#phan-3)** | **Đặc tả Yêu cầu chức năng (FR)** | Đặc tả chi tiết 15 nhóm chức năng (FR01 - FR15) theo chuẩn Ian Sommerville §4.3.2, bao quát Microfinance (vay/nợ), Personal Wealth (vàng/Net Worth/Saving Rate), Expense Tracker và Quản trị hệ thống (Admin). |
| **[Phần 4](#phan-4)** | **Mô hình tương tác và Use Case** | Danh mục 13 Use Cases cốt lõi (UC01 - UC13) đặc tả đầy đủ luồng chính, luồng phụ và luồng ngoại lệ tách biệt; 3 kịch bản xử lý nền (SYS01 - SYS03) và ma trận hạch toán kế toán kép. |
| **[Phần 5](#phan-5)** | **Yêu cầu phi chức năng (NFR)** | Lượng hóa 7 nhóm NFR theo tiêu chuẩn quốc tế ISO/IEC 25010:2023 trên môi trường kiểm thử chuẩn E1/D1/L1. |
| **[Phần 6](#phan-6)** | **Ma trận truy vết và Nghiệm thu** | Ma trận truy vết hai chiều (UR - FR - UC - BR - NFR - Test Suite), ca kiểm thử mẫu phân rã theo luồng và tiêu chí nghiệm thu pha Elaboration. |

---

<a id="phan-1"></a>

## 1. Giới thiệu, Tầm nhìn và Ranh giới hệ thống

### 1.1 Mục đích và tầm nhìn sản phẩm
Tài liệu Đặc tả Yêu cầu Phần mềm (Software Requirements Specification - SRS) này định nghĩa toàn diện các yêu cầu chức năng, yêu cầu phi chức năng, quy tắc nghiệp vụ và khung kiến trúc nền tảng cho hệ thống **WealthFlow** trong pha **Elaboration** theo tiến trình Unified Process (UP/RUP).

Tầm nhìn cốt lõi của **WealthFlow** là hỗ trợ người dùng chuyển dịch căn bản trong tư duy tài chính cá nhân:
$$\text{Từ: "Passive Expense Tracking" (Ghi chép thu chi thụ động)}$$
$$\text{Sang: "Active Personal Wealth Management" (Chủ động quản lý tài sản, kiểm soát nợ và tích lũy bền vững)}$$

Thay vì chỉ dừng lại ở việc trả lời câu hỏi *"Tôi đã tiêu bao nhiêu tiền?"*, WealthFlow giúp người dùng làm chủ tài chính thông qua việc trả lời 3 câu hỏi cốt tử:
1. **Tôi đang có bao nhiêu tài sản thực tế?** (Tổng hợp tiền mặt, ngân hàng, ví điện tử, sổ tiết kiệm, tài sản tích trữ (vàng) và trừ đi tổng nghĩa vụ nợ để xác định **Tài sản ròng - Net Worth**).
2. **Dòng tiền của tôi đang đi đâu?** (Phân loại thu nhập, chi phí sinh hoạt, dòng tiền luân chuyển, hạn mức ngân sách và thói quen tiêu dùng).
3. **Tình hình tài chính của tôi đang tốt lên hay xấu đi?** (Theo dõi xu hướng Net Worth, Cash Flow, Tỷ lệ tiết kiệm - Saving Rate, mức độ gánh nặng nợ và tiến độ đạt các mục tiêu tài chính cá nhân theo thời gian).

### 1.2 Ranh giới hệ thống: Những gì hệ thống LÀM GÌ và KHÔNG THỂ LÀM GÌ (In-Scope vs Out-of-Scope)
Để bảo đảm tính khả thi cho nhóm phát triển 4 sinh viên trong môn Công nghệ phần mềm và loại bỏ rủi ro mở rộng phạm vi (Scope Creep), hệ thống xác lập rõ ranh giới nghiệp vụ:

| Hạng mục nghiệp vụ | Hệ thống CẦN LÀM (In-Scope) | Hệ thống KHÔNG THỂ LÀM (Out-of-Scope / Non-Goals) |
| :--- | :--- | :--- |
| **1. Quản lý tiền & Ví** | Ghi chép, phân loại và theo dõi số dư nhiều ví/tài khoản (Tiền mặt, Ngân hàng, Ví điện tử MoMo/ZaloPay, Tiết kiệm). | **KHÔNG chuyển tiền hay thanh toán thực tế:** Ứng dụng là sổ theo dõi, không kết nối Napas/VietQR hay API ngân hàng để chuyển tiền thật ngoài đời. |
| **2. Quản lý Vay & Nợ vi mô (Microfinance)** | Theo dõi danh sách khoản vay/trả góp cá nhân, đối tác vay, lịch trả nợ, bóc tách nợ gốc vs lãi/phí, cập nhật dư nợ giảm dần. | **KHÔNG xét duyệt hay cấp khoản vay:** Ứng dụng không phải sàn P2P lending hay tổ chức tín dụng; không thực hiện giải ngân tiền mặt thực tế. |
| **3. Tài sản tích lũy (Personal Wealth)** | Theo dõi vàng tích trữ (nhẫn tròn trơn, vàng miếng SJC) theo số lượng chỉ/lượng và giá trị quy đổi thị trường. | **KHÔNG định giá bất động sản, xe cộ hay tài sản khấu hao phức tạp:** Tránh việc tính toán hao mòn xe máy hoặc thẩm định giá nhà đất thiếu chính xác. |
| **4. Ngân sách & Cảnh báo** | Thiết lập hạn mức chi tiêu tháng theo danh mục, tính % sử dụng, hiển thị cảnh báo tiệm cận (80%) và vượt mức (100%). | **KHÔNG chặn chi tiêu thực tế:** Cảnh báo chỉ hiển thị trong ứng dụng để nhắc nhở người dùng, không can thiệp khóa thẻ hay tài khoản bên ngoài. |
| **5. Nhập liệu & Đồng bộ** | Người dùng chủ động nhập liệu giao dịch, hỗ trợ giao dịch định kỳ lặp lại tự động hàng tháng. | **KHÔNG tự động cào dữ liệu ngân hàng (No Open Banking Sync):** Không tích hợp cào dữ liệu tài khoản ngân hàng ngầm do rào cản bảo mật và pháp lý. |
| **6. Quản trị hệ thống (Admin)** | Quản lý tài khoản người dùng (khóa/mở khóa), xem dashboard thống kê vĩ mô, cấu hình danh mục mặc định và phát thông báo broadcast. | **KHÔNG sao lưu/khôi phục DB trực tiếp trên Web UI:** Thao tác sao lưu/phục hồi DB thuộc về DevOps/DBA quản trị hạ tầng máy chủ, không làm web UI. |

### 1.3 Đối tượng người dùng mục tiêu (Target Users)
Hệ thống đặc biệt tối ưu cho các nhóm đối tượng có nguồn tài chính ban đầu chưa lớn nhưng cần kiểm soát chặt chẽ để tích lũy:
* **Học sinh, sinh viên:** Quản lý tiền trợ cấp từ gia đình, tiền làm thêm, học phí, chi phí sinh hoạt hàng tháng và bắt đầu thói quen tích lũy từ những khoản tiền nhỏ.
* **Người mới đi làm (Young Professionals):** Quản lý lương, kiểm soát chi tiêu ngoài kế hoạch, lập quỹ khẩn cấp và trả các khoản vay học tập/mua sắm ban đầu.
* **Người có thu nhập thấp đến trung bình, Freelancer:** Dòng tiền không cố định, cần cân đối thu chi hàng ngày, quản lý các khoản vay mua trả góp (laptop, xe máy) và tránh rơi vào bẫy nợ tín dụng.
* **Cá nhân và hộ kinh doanh vi mô:** Theo dõi rõ ràng tiền ở nhiều tài khoản/ví, tách bạch tài sản và nợ, giám sát dòng tiền thuần (Net Cash Flow) và xây dựng kế hoạch dự phòng.

### 1.4 Danh mục Yêu cầu người dùng (User Requirements - UR)

| Mã UR | Nhu cầu người dùng | Lý do nghiệp vụ | Ánh xạ FR | Ánh xạ UC |
| :---: | :--- | :--- | :---: | :---: |
| **UR01** | Tạo tài khoản an toàn, bảo vệ quyền riêng tư dữ liệu tài chính. | Đảm bảo chỉ chủ tài khoản mới được truy cập và sửa đổi số liệu nhạy cảm. | FR01, FR15 | UC01 |
| **UR02** | Quản lý nhiều ví/nguồn tiền và ghi nhận thu/chi/chuyển tiền chuẩn xác. | Nắm giữ tiền ở tiền mặt, ngân hàng, ví điện tử mà không bị thất thoát, có dấu vết kiểm toán khi sửa đổi. | FR02, FR03, FR04 | UC02, UC03 |
| **UR03** | Thiết lập hạn mức ngân sách và nhận cảnh báo sớm trước khi bội chi. | Ngăn chặn hành vi chi tiêu quá tay theo từng nhóm nhu cầu (Ăn uống, Giải trí, Mua sắm...). | FR05 | UC05 |
| **UR04** | Tự động hóa ghi nhận các khoản thu/chi định kỳ hàng tháng. | Tránh quên thanh toán hóa đơn điện/nước/internet, tiền thuê nhà hoặc dịch vụ đăng ký. | FR06 | UC04 |
| **UR05** | Thiết lập các mục tiêu tích lũy và tính toán kế hoạch tiết kiệm khả thi. | Giúp người dùng biết cần tiết kiệm bao nhiêu mỗi tháng và bao lâu sẽ đạt mục tiêu (mua máy tính, quỹ khẩn cấp...). | FR07 | UC09 |
| **UR06** | Theo dõi toàn diện danh mục tài sản (tiền, tiết kiệm, vàng), các khoản nợ và tính toán tài sản ròng (Net Worth). | Người dùng biết rõ giá trị thực sự mình sở hữu sau khi trừ hết các khoản nợ, theo dõi sự tăng trưởng tài sản. | FR08, FR10 | UC08, UC10 |
| **UR07** | Quản lý chi tiết từng khoản vay, khoản nợ nhỏ và theo dõi lịch thanh toán (Microfinance). | Nắm rõ nợ ai, nợ bao nhiêu, lịch trả nợ, số tiền đã trả và phân định rõ tiền gốc so với tiền lãi/phí. | FR09 | UC06, UC07 |
| **UR08** | Xem Dashboard trực quan về sức khỏe tài chính: Net Worth, Cash Flow, Saving Rate. | Cung cấp góc nhìn toàn cảnh về tình hình tài chính trong một màn hình, biết tài chính đang tốt lên hay xấu đi. | FR10, FR11 | UC10 |
| **UR09** | Tra cứu lịch sử, đối soát sai lệch số dư và xuất báo cáo tài chính sang tệp CSV. | Cho phép người dùng kiểm tra lại chi tiết từng giao dịch và xuất ra CSV để tự lưu trữ hoặc phân tích sâu. | FR12 | UC11 |
| **UR10** | Quản trị viên quản lý tài khoản người dùng, xem thống kê vĩ mô và phát thông báo hệ thống. | Đảm bảo tính an toàn hệ thống, khóa tài khoản vi phạm, gửi thông báo vận hành/bảo trì cho người dùng. | FR13, FR14 | UC12, UC13 |
| **UR11** | Vận hành hệ thống ổn định, kiểm soát truy cập và bảo mật dữ liệu đa người dùng. | Đảm bảo tính sẵn sàng cao, cô lập dữ liệu tuyệt đối giữa các người dùng cá nhân. | FR15 | Toàn bộ UC |
| **UR12** | Giao diện hiện đại, tốc độ phản hồi nhanh, bảo mật thông tin trên mọi thiết bị. | Đem lại trải nghiệm liền mạch, tiện dụng và an tâm cho người dùng. | Toàn bộ NFR | Toàn bộ UC |

### 1.5 Thuật ngữ và từ viết tắt (Glossary)
* **Net Worth (Tài sản ròng):** Giá trị tài sản còn lại của cá nhân sau khi lấy Tổng tài sản (Total Assets) trừ đi Tổng nợ phải trả (Total Liabilities).
* **Cash Flow (Dòng tiền thuần):** Chênh lệch giữa tổng thu nhập thực nhận và tổng chi phí thực tế phát sinh trong một kỳ ($NetCashFlow = TotalIncome - TotalExpense$).
* **Saving Rate (Tỷ lệ tiết kiệm):** Phần trăm thu nhập được giữ lại sau khi chi tiêu: $SavingRate = \frac{Income - Expense}{Income} \times 100\%$.
* **Microfinance (Tài chính vi mô):** Phân hệ quản lý các khoản vay quy mô nhỏ, khoản trả góp, công nợ bạn bè/người thân và các dòng tiền nhỏ lẻ thường ngày.
* **Double-Entry Bookkeeping (Kế toán kép):** Nguyên lý ghi sổ mọi nghiệp vụ tài chính dưới dạng ít nhất một dòng Nợ (Debit) và một dòng Có (Credit) với tổng giá trị tiền cơ sở luôn bằng nhau.
* **Idempotency Key (Khóa bất biến / chống trùng lặp):** Khóa duy nhất do máy khách gửi kèm để bảo đảm một yêu cầu khi gửi nhiều lần do sự cố đường truyền mạng chỉ được xử lý đúng một lần duy nhất.
* **Base Currency (Tiền cơ sở):** Đồng tiền chuẩn do người dùng chọn khi khởi tạo tài khoản (ví dụ VND), dùng để quy đổi và lập báo cáo tài chính hợp nhất.
* **Snapshot Exchange Rate (Tỷ giá chụp tại thời điểm):** Bản ghi tỷ giá hối đoái có hiệu lực tại thời điểm phát sinh giao dịch, được lưu bất biến cùng giao dịch.
* **Broadcast Announcement (Thông báo phát thanh):** Thông báo từ Quản trị viên gửi đến toàn bộ người dùng đang hoạt động trong hệ thống.


---

<a id="phan-2"></a>

## 2. Bối cảnh và Kiến trúc nền tảng (Architectural Baseline)

### 2.1 Ranh giới hệ thống và Tác nhân (System Boundaries & Actors)

```
       ┌────────────────────────┐
       │   A01: Người dùng      │
       │ (Cá nhân, Sinh viên,   │
       │   Hộ kinh doanh nhỏ)   │
       └───────────┬────────────┘
                   │ HTTPS / Web Responsive (IF01)
                   ▼
┌────────────────────────────────────────────────────────┐
│                   HỆ THỐNG WEALTHFLOW                  │
│                                                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 1. Core Ledger & Double-Entry Transaction Engine │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 2. Expense & Multi-Category Budgeting Engine     │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 3. Microfinance & Debt / Loan Tracking Engine    │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 4. Personal Wealth & Net Worth Calculation Engine│  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 5. Financial Goal & Saving Accumulation Engine   │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 6. Analytics, Cash Flow & Reporting Service      │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ 7. System Administration & Broadcast Service     │  │
│  └──────────────────────────────────────────────────┘  │
└───────▲────────────────────────────────────────▲───────┘
        │                                        │
        │ IF02                                   │ Vận hành / Giám sát (IF01)
        ▼                                        ▼
┌──────────────┐                         ┌───────────────┐
│ A03: Dịch vụ │                         │ A02: Quản trị │
│ Tỷ giá ngoài │                         │  viên (Admin) │
│  (Rates API) │                         └───────────────┘
└──────────────┘
```

#### Bảng định nghĩa Tác nhân (Actors)
* **A01 - Người dùng (User):** Người dùng cuối (khách vãng lai khi đăng ký/đăng nhập; người dùng đã xác thực khi thao tác dữ liệu cá nhân). Có toàn quyền quản lý tài sản, nợ, ví, giao dịch của bản thân; tuyệt đối không thể xem hay sửa dữ liệu của người dùng khác.
* **A02 - Quản trị viên (System Administrator):** Chịu trách nhiệm bảo trì hệ thống, quản lý khóa/mở khóa tài khoản người dùng vi phạm, theo dõi Dashboard thống kê vĩ mô, cấu hình danh mục/tham số mặc định và phát thông báo hệ thống (Broadcast Announcements). Tuyệt đối không có quyền xem số liệu sổ sách tài chính cá nhân của người dùng (theo BR11).
* **A03 - Nhà cung cấp tỷ giá (Exchange Rate Provider):** Hệ thống API bên ngoài cung cấp bảng tỷ giá tiền tệ theo thời gian thực hoặc định kỳ hàng ngày thông qua giao tiếp IF02.
* **SYS - Bộ lập lịch nền (System Scheduler):** Tiến trình nội bộ chạy ngầm kích hoạt các tác vụ định kỳ tự động (SYS01 - SYS03).

### 2.2 Kiến trúc tham chiếu nền tảng (Layered Architecture + Modular Monolith)
Nhằm bảo đảm tính cô lập cao, khả năng bảo trì tốt (Maintainability) và kiểm thử triệt để (Testability) cho nhóm 4 người trong pha Elaboration, WealthFlow áp dụng mô hình kiến trúc phân tầng kết hợp đơn khối module hóa:

1. **Presentation Layer (Tầng trình diễn):** Giao diện Web Responsive hiện đại, tối ưu hiển thị trên màn hình Desktop, Tablet và Mobile. Giao tiếp với tầng ứng dụng hoàn toàn thông qua giao thức RESTful JSON API chuẩn hóa.
2. **Application Layer (Tầng ứng dụng):** Bao gồm các bộ điều phối dịch vụ (Application Services): `AuthService`, `WalletService`, `TransactionService`, `BudgetService`, `WealthService`, `LoanService`, `GoalService`, `ReportService`, `AdminService`, `NotificationService`. Tầng này điều phối luồng nghiệp vụ, kiểm soát xác thực và phân quyền truy cập.
3. **Business / Domain Layer (Tầng quy tắc nghiệp vụ):** Trung tâm của hệ thống, chứa các quy tắc nghiệp vụ cốt lõi không phụ thuộc công nghệ:
   * Thuật toán kiểm tra cân bằng sổ kế toán kép (Double-Entry Balance: $\sum \text{Debit} = \sum \text{Credit}$).
   * Công thức tính toán tài sản ròng: $\text{Net Worth} = \text{Total Assets} - \text{Total Liabilities}$.
   * Công thức tính dòng tiền thuần: $\text{Net Cash Flow} = \text{Income} - \text{Expense}$.
   * Công thức tính tỷ lệ tiết kiệm: $\text{Saving Rate} = \frac{\text{Income} - \text{Expense}}{\text{Income}} \times 100\%$.
   * Quy tắc khấu trừ nợ gốc và nợ lãi trong các khoản vay vi mô (Microfinance Loan Amortization).
   * Thuật toán kiểm tra hạn mức ngân sách và phân loại ngưỡng cảnh báo (80% Vàng, 100% Đỏ).
4. **Data Access Layer (Tầng truy cập dữ liệu):** Áp dụng mẫu thiết kế Repository / DAO, bảo đảm mọi tương tác với cơ sở dữ liệu đều nằm trong phạm vi giao dịch ACID bền vững.
5. **Database Layer (Tầng cơ sở dữ liệu):** Hệ quản trị cơ sở dữ liệu quan hệ (PostgreSQL / MySQL) hỗ trợ khóa mức dòng (Row-level Locking) và cô lập giao dịch Serializable/Repeatable Read nhằm triệt tiêu tranh chấp dữ liệu đồng thời.

### 2.3 Môi trường và giao diện tích hợp (Interfaces)
* **IF01 - Giao diện Web / REST API:** Giao tiếp thông qua HTTPS/TLS 1.3, định dạng payload JSON. Mọi phản hồi API đều có cấu trúc chuẩn gồm `success`, `data`, `error` (chứa `code`, `message`, `fieldErrors`) và `requestId`. Mã lỗi tuân thủ chuẩn HTTP: 401 (Chưa xác thực), 403 (Sai quyền), 404 (Không tồn tại/Không sở hữu), 409 (Xung đột khóa), 422 (Dữ liệu không hợp lệ), 500 (Lỗi nội bộ hệ thống).
* **IF02 - Tỷ giá hối đoái:** Tiếp nhận cặp tiền, tỷ giá quy đổi ($rate > 0$), thời điểm hiệu lực `effectiveAt` và nguồn cấp. Dữ liệu tỷ giá được lưu trữ lịch sử theo phiên bản; sự cố từ nhà cung cấp bên ngoài không được làm sai lệch tỷ giá lịch sử đã lưu.

### 2.4 Chính sách tiền tệ và hạch toán đa tiền
* Phiên bản phát hành hỗ trợ tiền cơ sở mặc định là VND (Việt Nam Đồng), hỗ trợ các ngoại tệ giao dịch USD (Đô la Mỹ) và EUR (Euro).
* Tiền cơ sở (`baseCurrency`) được người dùng lựa chọn khi tạo tài khoản và bị **khóa vĩnh viễn** sau khi phát sinh bút toán ghi sổ đầu tiên.
* Các số dư tài khoản ngoại tệ được duy trì song song: giá trị nguyên tệ (Original Currency) và giá trị quy đổi tiền cơ sở (Base Currency) theo phương pháp giá trị ghi sổ bình quân (Book Value Base) quy định tại BR15.

### 2.5 Danh mục Quy tắc nghiệp vụ cốt lõi (Business Rules - BR)

| Mã BR | Tên quy tắc | Nội dung chi tiết |
| :---: | :--- | :--- |
| **BR01** | Cân bằng sổ kế toán kép | Mỗi giao dịch ghi sổ (Posted) phải có ít nhất một dòng Nợ (Debit) và một dòng Có (Credit). Tổng giá trị Nợ quy đổi tiền cơ sở phải bằng chính xác tổng giá trị Có quy đổi tiền cơ sở: $\sum \text{DebitBase} = \sum \text{CreditBase}$. Tuyệt đối không cộng gộp trực tiếp các loại tiền tệ khác nhau. |
| **BR02** | Phương trình kế toán mở rộng | Hệ thống quản lý đủ 5 loại tài khoản: Tài sản (Asset), Nợ phải trả (Liability), Vốn chủ (Equity), Thu nhập (Income), Chi phí (Expense). Số dư Tài sản = Nợ - Có; Số dư Nợ/Vốn/Thu = Có - Nợ; Số dư Chi phí = Nợ - Có. Phương trình kế toán luôn thỏa mãn: $\text{Asset} = \text{Liability} + \text{Equity} + (\text{Income} - \text{Expense})$. |
| **BR03** | Độ chính xác số học thập phân | Toàn bộ các phép tính tiền tệ phải sử dụng số thập phân chính xác tuyệt đối (Decimal/BigDecimal), cấm sử dụng số thực dấu phẩy động (Floating-point) nhị phân. VND có 0 chữ số lẻ; USD/EUR có 2 chữ số lẻ; tỷ giá ngoại tệ lưu tối đa 8 chữ số lẻ. Quy tắc làm tròn số: Half-Up về đơn vị tiền nhỏ nhất; sai số làm tròn được ghi vào dòng tài khoản chênh lệch làm tròn riêng biệt. |
| **BR04** | Tỷ giá chụp bất biến | Tỷ giá quy đổi giao dịch sử dụng bản ghi hợp lệ mới nhất có $effectiveAt \le \text{thời điểm giao dịch}$ và tuổi dữ liệu không vượt quá 24 giờ. Snapshot tỷ giá gắn liền với giao dịch và trở thành bất biến sau khi commit; không tự động tính lại giá trị giao dịch lịch sử khi có tỷ giá mới. |
| **BR05** | Phân loại hạch toán cơ bản | - Thu nhập: Ghi Nợ Ví tài sản / Ghi Có Tài khoản Thu nhập.<br>- Chi tiêu: Ghi Nợ Tài khoản Chi phí / Ghi Có Ví tài sản (hoặc Ví nợ).<br>- Chuyển tiền nội bộ: Ghi Nợ Ví đích / Ghi Có Ví nguồn (không tính vào Thu nhập hay Chi phí). Cả 2 ví phải thuộc cùng một chủ sở hữu; cấm chuyển tiền vào chính ví nguồn. |
| **BR06** | Tính bất biến của sổ và Đảo giao dịch | Giao dịch đã ghi sổ (Posted) cấm sửa đổi hoặc xóa vật lý. Để điều chỉnh sai sót, hệ thống bắt buộc tạo bút toán Đảo (Reversal Transaction) với các dòng Nợ/Có đối ứng ngược dấu và giữ nguyên tỷ giá snapshot gốc. Nghiệp vụ sửa đổi thực chất là chuỗi nguyên tử: Đảo bản ghi cũ + Ghi nhận bản ghi mới thay thế có liên kết `originalId`. Bản nháp (Draft) được phép sửa/xóa trực tiếp. |
| **BR07** | Quản lý hạn mức ngân sách | Ngân sách được thiết lập theo từng danh mục chi phí (Expense Category) cho một tháng xác định theo múi giờ người dùng. Hạn mức ngân sách phải lớn hơn 0 và tính bằng tiền cơ sở. Chi tiêu thực tế được tính bằng tổng chi ròng (chi tiêu trừ đi các khoản hoàn/đảo) trong tháng; chuyển tiền nội bộ và số dư đầu kỳ không được tính vào ngân sách. Mỗi danh mục chỉ có duy nhất một ngân sách hoạt động trong một tháng. |
| **BR08** | Ngưỡng và chống trùng cảnh báo ngân sách | Mức sử dụng ngân sách: $Usage = \frac{\text{Chi tiêu ròng}}{\text{Hạn mức ngân sách}} \times 100\%$. Phân loại mức cảnh báo: $< 80\%$ (Bình thường - Xanh); từ $80\%$ đến $< 100\%$ (Cảnh báo - Vàng); $\ge 100\%$ (Vượt mức - Đỏ). Hệ thống chỉ phát duy nhất 1 thông báo cho mỗi mức/ngân sách/tháng. Nếu một giao dịch đẩy mức dùng từ $< 80\%$ lên $\ge 100\%$, hệ thống chỉ phát thông báo Đỏ và đánh dấu mức Vàng đã được kích hoạt. Đảo giao dịch cập nhật trạng thái nhưng không xóa thông báo lịch sử. |
| **BR09** | Xử lý giao dịch định kỳ | Mỗi chu kỳ định kỳ (ngày đến hạn `dueDate`) chỉ sinh tối đa 1 giao dịch hoặc bản nháp với khóa duy nhất `(subscriptionId, dueDate)`. Chế độ tự động ghi sổ phải được người dùng bật chủ động; mặc định hệ thống chỉ tạo bản nháp và phát thông báo nhắc nhở. Đối với các ngày đến hạn 29, 30, 31 trong các tháng không đủ ngày, hệ thống tự động lùi về ngày cuối cùng của tháng đó và giữ nguyên ngày neo gốc cho chu kỳ kế tiếp. |
| **BR10** | Nguyên tắc quản lý mục tiêu tài chính | Mục tiêu tài chính (Financial Goal) là công cụ phân bổ theo dõi logic, không tự động sinh ra tiền và không làm tăng tổng tài sản của người dùng. $CurrentAmount$ là tổng các khoản phân bổ còn hiệu lực; $TargetAmount > 0$. Tiến độ: $Progress = \frac{CurrentAmount}{TargetAmount} \times 100\%$. Thanh tiến độ trực quan hiển thị tối đa 100%, số tiền vượt định mức vẫn hiển thị đầy đủ. Mức tích lũy cần thiết hàng tháng: $MonthlySavingTarget = \frac{TargetAmount - CurrentAmount}{\text{Số tháng còn lại}}$. |
| **BR11** | Cô lập dữ liệu đa người dùng & Quyền riêng tư Admin | Toàn bộ bản ghi nghiệp vụ (tài khoản, ví, tài sản, nợ, giao dịch, ngân sách, mục tiêu) đều phải thuộc về một `userId` duy nhất. Máy chủ phải thẩm tra quyền sở hữu đối với 100% yêu cầu API. Quyền Quản trị viên (Admin) tuyệt đối không cho phép xem dữ liệu sổ sách tài chính cá nhân của người dùng; Admin chỉ quản trị tài khoản, tham số cấu hình và Dashboard vĩ mô. |
| **BR12** | Tính nguyên tử trong giao dịch (ACID) | Toàn bộ các thao tác ghi nhận giao dịch, sinh dòng sổ Nợ/Có, cập nhật số dư bộ đệm và phát sự kiện xử lý nền phải được thực thi trong một Database Transaction nguyên tử duy nhất. Nếu có bất kỳ bước nào thất bại, toàn bộ thao tác phải được Rollback về trạng thái ban đầu; không bao giờ được lưu trạng thái dở dang. |
| **BR13** | Cơ chế Idempotency chống trùng lặp | Mọi yêu cầu ghi nhận/đảo giao dịch gửi kèm `Idempotency-Key`: nếu cùng `userId` + cùng `key` + cùng nội dung thì hệ thống trả lại kết quả đã xử lý trước đó mà không thực hiện lại; nếu cùng `key` nhưng khác nội dung thì hệ thống từ chối với mã lỗi 409 Conflict. Khóa mức dòng (Row-level lock) phải được áp dụng để ngăn ngừa triệt để tình trạng ghi đè hoặc chi vượt số dư ví tài sản. |
| **BR14** | Tách biệt trạng thái giao dịch | Giao dịch ở trạng thái Nháp (Draft) không được tính vào số dư ví, không tính vào ngân sách và không xuất hiện trên báo cáo tài chính. Báo cáo tài chính chỉ tổng hợp các giao dịch đã ghi sổ (Posted) và các dòng đảo tương ứng; không được loại trừ bản ghi gốc rồi lại trừ tiếp dòng đảo làm sai lệch số liệu. |
| **BR15** | Phương pháp giá trị sổ ngoại tệ | Khi xuất ngoại tệ khỏi ví: giá trị quy đổi tiền cơ sở xuất sổ được tính theo phương pháp Bình quân gia quyền: $UnitRate = \frac{BookBaseBalance}{OriginalBalance}$. Khi xuất toàn bộ số dư nguyên tệ, toàn bộ giá trị $BookBaseBalance$ phải được xuất hết để triệt tiêu sai số làm tròn. Chênh lệch giữa giá trị xuất sổ và giá trị nhận được theo tỷ giá giao dịch được hạch toán vào tài khoản Lãi/Lỗ chênh lệch tỷ giá. |
| **BR16** | Xác định Tài sản ròng (Net Worth) | $\text{Tài sản ròng (Net Worth)} = \text{Tổng tài sản (Total Assets)} - \text{Tổng nợ (Total Liabilities)}$.<br>- Tổng tài sản bao gồm: Toàn bộ số dư các ví tài sản (tiền mặt, ngân hàng, ví điện tử), tài khoản tiết kiệm và giá trị định giá của tài sản tích lũy ngoài ví (vàng) đã ghi nhận.<br>- Tổng nợ bao gồm: Toàn bộ số dư các ví nợ (thẻ tín dụng, hạn mức thấu chi) và dư nợ gốc còn lại của các khoản vay vi mô/trả góp.<br>- Tiền đã tồn tại trong danh sách ví chỉ được tính đúng 1 lần vào Tổng tài sản. |
| **BR17** | Nguyên tắc quản lý khoản vay và nợ vi mô | - Nhận tiền vay: Tăng số dư ví tài sản nhận tiền và Tăng khoản nợ phải trả (Liability); cấm hạch toán số tiền vay nhận được vào Thu nhập (Income).<br>- Trả nợ định kỳ: Số tiền thanh toán được phân rã thành:<br>  + Tiền gốc (Principal Paid): Làm giảm dư nợ gốc khoản vay và giảm số dư ví chi trả.<br>  + Tiền lãi và phí (Interest/Fee Paid): Ghi nhận vào Chi phí tài chính (Expense) và giảm số dư ví chi trả.<br>- Cập nhật dư nợ: Dư nợ gốc còn lại = Số tiền vay ban đầu - Tổng tiền gốc đã trả. Khoản nợ chuyển sang trạng thái "Đã thanh toán" (Paid Off) khi dư nợ gốc về 0. |
| **BR18** | Xác định Dòng tiền và Tỷ lệ tiết kiệm | - Dòng tiền thuần: $\text{Net Cash Flow} = \text{Total Income} - \text{Total Expense}$ trong kỳ khảo sát.<br>- Tỷ lệ tiết kiệm: $\text{Saving Rate} = \frac{\text{Total Income} - \text{Total Expense}}{\text{Total Income}} \times 100\%$.<br>- Xử lý ngoại lệ: Nếu $\text{Total Income} \le 0$, không thực hiện phép chia; hệ thống hiển thị "Chưa xác định (N/A)" cho Saving Rate nhưng vẫn thể hiện đầy đủ giá trị chi tiêu và mức thâm hụt dòng tiền. Nếu $\text{Total Expense} > \text{Total Income}$, Saving Rate hiển thị giá trị âm màu đỏ cảnh báo. |

### 2.6 Mô hình dữ liệu khái niệm và Thực thể logic (Domain Model)

```
┌─────────────────┐       1..* ┌─────────────────┐       1..* ┌─────────────────┐
│      User       ├───────────►│  Wallet/Account ├───────────►│   LedgerEntry   │
│ (Hồ sơ, Tiền cs)│            │(Ví TS, Ví Nợ, TK)│            │(Nợ/Có, amountBase│
└────────┬────────┘            └─────────────────┘            └────────▲────────┘
         │ 1..*                                                        │ 2..*
         ├────────────────────►┌─────────────────┐                     │
         │                     │   Transaction   ├─────────────────────┘
         │ 1..*                │ (Posted, Draft) │
         ├────────────────────►└─────────────────┘
         │                     ┌─────────────────┐
         │ 1..*                │ Asset (Khác ví) │
         ├────────────────────►│ (Vàng tích trữ) │
         │                     └─────────────────┘
         │ 1..*                ┌─────────────────┐       1..* ┌─────────────────┐
         ├────────────────────►│  Loan / Debt    ├───────────►│ LoanRepayment   │
         │                     │(Gốc, Lãi, Kỳ hạn│            │(Gốc, Lãi, Ngày) │
         │ 1..*                └─────────────────┘            └─────────────────┘
         ├────────────────────►┌─────────────────┐
         │                     │ Budget & Alerts │
         │ 1..*                └─────────────────┘
         ├────────────────────►┌─────────────────┐       1..* ┌─────────────────┐
         │                     │ Financial Goal  ├───────────►│  Contribution   │
         │ 1..*                │(Mục tiêu, Hạn)  │            │(Phân bổ tiền)   │
         │ 1..*                └─────────────────┘            └─────────────────┘
         ├────────────────────►┌─────────────────┐
         │                     │  Subscription   │
         │ 1..*                │(Định kỳ, Chu kỳ)│
         ├────────────────────►└─────────────────┘
         │                     ┌─────────────────┐
         │                     │  Announcement   │
         │                     │(Thông báo Admin)│
         └────────────────────►└─────────────────┘
```

#### Mô tả các thực thể dữ liệu chính:
* **User (Người dùng):** `id`, `email`, `passwordHash`, `fullName`, `role` (User/Admin), `baseCurrency` (VND/USD/EUR), `timeZone`, `status` (Active/Locked), `createdAt`, `version`.
* **Wallet / LedgerAccount (Ví / Tài khoản sổ cái):** `id`, `userId`, `name`, `type` (Asset: Cash/Bank/E-Wallet/Saving; Liability: Credit/Overdraft), `currency`, `balanceOriginal`, `balanceBase`, `isArchived`, `createdAt`.
* **Asset (Tài sản tích lũy ngoài ví - Vàng):** `id`, `userId`, `name`, `assetType` (GoldRing - Nhẫn trơn, GoldBar - Vàng miếng SJC), `quantity` (Số chỉ), `estimatedValue`, `valuationDate`, `description`, `createdAt`.
* **Loan / Debt (Khoản vay & Nợ vi mô):** `id`, `userId`, `title`, `type` (Borrow - Đi vay / Lend - Cho vay), `lenderOrBorrowerName`, `initialPrincipal`, `remainingPrincipal`, `interestRate`, `startDate`, `dueDate`, `status` (Active, PaidOff, Overdue), `notes`.
* **LoanRepayment (Thanh toán nợ):** `id`, `loanId`, `transactionId`, `repaymentDate`, `principalAmount`, `interestAmount`, `feeAmount`, `paymentWalletId`.
* **Transaction (Giao dịch):** `id`, `userId`, `type` (Income, Expense, Transfer, LoanDisbursement, LoanRepayment), `effectiveDate`, `description`, `status` (Draft, Posted, Reversed), `idempotencyKey`, `originalId`, `createdAt`.
* **LedgerEntry (Bút toán sổ kép):** `id`, `transactionId`, `accountId`, `direction` (Debit/Credit), `amountOriginal`, `currency`, `amountBase`, `exchangeRateSnapshot`.
* **Budget (Ngân sách):** `id`, `userId`, `categoryId`, `monthYear` (YYYY-MM), `limitAmountBase`, `isArchived`.
* **FinancialGoal (Mục tiêu tài chính):** `id`, `userId`, `title`, `targetAmountBase`, `currentAmountBase`, `deadlineDate`, `status` (InProgress, Achieved, Overdue, Cancelled).
* **GoalContribution (Phân bổ tích lũy mục tiêu):** `id`, `goalId`, `amountBase`, `contributionDate`, `sourceWalletId`, `notes`.
* **Subscription (Khoản định kỳ):** `id`, `userId`, `name`, `amount`, `currency`, `cycle` (Monthly/Yearly), `anchorDay`, `nextDueDate`, `walletId`, `categoryId`, `autoPost`, `status` (Active, Paused, Cancelled).
* **SystemAnnouncement (Thông báo hệ thống từ Admin):** `id`, `adminId`, `title`, `content`, `priority` (Info, Warning, Maintenance), `effectiveDate`, `expireDate`, `createdAt`.


---

<a id="phan-3"></a>

## 3. Đặc tả Yêu cầu chức năng (Functional Requirements Specification - FR)

Cấu trúc đặc tả tuân thủ phương pháp kỹ nghệ yêu cầu của Ian Sommerville (§4.3.2): mỗi nhóm yêu cầu định rõ thuộc tính chuẩn hóa, đầu vào/nguồn, đầu ra/đích, tiền điều kiện, các câu mệnh lệnh bắt buộc (`FRnn.xx`), xử lý ngoại lệ, hậu điều kiện, tác động phụ và tiêu chí kiểm thử nghiệm thu (`TC-Fnn`).

### FR01 - Định danh, xác thực và quản lý phiên truy cập
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR01, BR11 | Use Case: UC01 | Kiểm thử: TC-F01
- **Đầu vào / Nguồn:** Email, mật khẩu, họ tên, tiền cơ sở từ người dùng đăng ký; thông tin đăng nhập/đổi mật khẩu từ chủ tài khoản.
- **Đầu ra / Đích nhận:** Kết quả đăng ký tài khoản hoặc JWT/phiên xác thực trả về máy khách; thông tin hồ sơ lưu trữ an toàn trong kho dữ liệu.
- **Tiền điều kiện:** Đăng ký: email chưa tồn tại trong hệ thống. Đăng nhập: tài khoản tồn tại và trạng thái Active. Đổi mật khẩu: phiên đăng nhập hợp lệ.

**FR01.01** Hệ thống phải chuẩn hóa email (xóa khoảng trắng thừa, chuyển về chữ thường) và bảo đảm tính duy nhất tuyệt đối của email trên toàn hệ thống.  
**FR01.02** Hệ thống phải chỉ cấp phiên truy cập (Token) khi thông tin đăng nhập chính xác và tài khoản ở trạng thái Active; khi đăng nhập thất bại, hệ thống phải trả thông báo lỗi chung, không được tiết lộ email có tồn tại hay không nhằm chống tấn công thu thập tài khoản.  
**FR01.03** Hệ thống phải vô hiệu hóa phiên truy cập ngay khi người dùng chọn Đăng xuất; mọi phiên truy cập hết hạn hoặc bị thu hồi phải bị từ chối truy cập tài nguyên bảo vệ.  
**FR01.04** Hệ thống phải yêu cầu xác thực mật khẩu hiện tại trước khi thực hiện đổi mật khẩu mới và tự động hủy bỏ mọi phiên đăng nhập cũ trên các thiết bị khác sau khi đổi mật khẩu thành công.  

- **Xử lý ngoại lệ:** Email sai định dạng hoặc mật khẩu không đạt chuẩn chính sách NFR02: từ chối, không tạo tài khoản dở dang. Tài khoản bị khóa (Locked): từ chối đăng nhập với thông báo tài khoản tạm ngưng.
- **Hậu điều kiện:** Tài khoản mới được khởi tạo hoặc phiên hợp lệ được thiết lập; các lần đăng nhập thất bại không làm biến động số liệu tài chính.
- **Tác động phụ:** Ghi nhật ký kiểm toán bảo mật (Security Audit Log), không ghi mật khẩu thô hay token vào tệp nhật ký.
- **Tiêu chí kiểm chứng TC-F01:** Đăng ký email trùng hoa/thường; đăng nhập đúng/sai mật khẩu; kiểm tra tài khoản bị khóa; kiểm tra token hết hạn; kiểm tra đăng xuất và thu hồi phiên.

---

### FR02 - Quản lý tài khoản thanh toán và ví tiền
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR02, BR01, BR02, BR03, BR11 | Use Case: UC02 | Kiểm thử: TC-F02
- **Đầu vào / Nguồn:** Tên ví, loại ví (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm), tiền tệ, số dư mở sổ ban đầu từ người dùng.
- **Đầu ra / Đích nhận:** Bản ghi ví mới được tạo, số dư khởi tạo được hạch toán cân bằng, danh sách các ví đang hoạt động.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; tên ví không trùng lặp trong cùng chủ sở hữu; số dư mở sổ $\ge 0$.

**FR02.01** Hệ thống phải cho phép tạo các loại ví: Tiền mặt, Tài khoản ngân hàng, Ví điện tử, Tài khoản tiết kiệm; số dư đầu kỳ lớn hơn 0 phải được hạch toán đối ứng với tài khoản Vốn chủ sở hữu (Equity) trong một thao tác nguyên tử.  
**FR02.02** Hệ thống phải cho phép người dùng xem số dư chi tiết của từng ví và tổng số dư quy đổi tiền cơ sở của toàn bộ các ví đang quản lý.  
**FR02.03** Hệ thống phải cho phép lưu trữ (Archive) ví có số dư bằng 0 và không còn giao dịch định kỳ hoạt động; ví đã lưu trữ không được phép phát sinh giao dịch mới nhưng vẫn hiển thị trong lịch sử báo cáo.  
**FR02.04** Hệ thống phải cập nhật số dư ví tức thì và đồng bộ khi có phát sinh giao dịch thu, chi hoặc chuyển tiền nội bộ.  

- **Xử lý ngoại lệ:** Số dư ban đầu $< 0$: từ chối và báo lỗi. Lưu trữ ví còn số dư khác 0: cảnh báo người dùng phải chuyển hết số dư trước khi lưu trữ.
- **Hậu điều kiện:** Số dư ví được quản lý nhất quán; không bao giờ để ví tài sản bị âm ngoài quy định.
- **Tác động phụ:** Cập nhật Total Assets và Net Worth trên Dashboard.
- **Tiêu chí kiểm chứng TC-F02:** Mở ví tiền mặt 2.000.000 VND và ví tiết kiệm 10.000.000 VND: kiểm tra số dư ban đầu, hạch toán đối ứng Vốn chủ đạt chuẩn 100%.

---

### FR03 - Quản lý giao dịch tài chính và sổ kế toán kép
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR02, BR01 - BR06, BR12, BR13, BR15 | Use Case: UC03 | Kiểm thử: TC-F03
- **Đầu vào / Nguồn:** Loại giao dịch (Thu, Chi, Chuyển nội bộ), ví nguồn, ví đích, danh mục thu/chi, số tiền, ngày phát sinh, mô tả và `Idempotency-Key`.
- **Đầu ra / Đích nhận:** Bản ghi giao dịch Posted, các dòng bút toán sổ Nợ/Có cân bằng và số dư ví được cập nhật.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; ví và danh mục thuộc quyền sở hữu; ví nguồn đủ số dư khi chi/chuyển; ngày giao dịch không ở tương lai.

**FR03.01** Hệ thống phải tự động tạo các dòng Nợ và Có tương ứng với loại giao dịch và bắt buộc kiểm tra tính cân bằng tuyệt đối: $\sum 	ext{DebitBase} = \sum 	ext{CreditBase}$ trước khi commit dữ liệu theo chuẩn BR01.  
**FR03.02** Hệ thống phải xử lý giao dịch chuyển tiền giữa 2 ví khác nhau của cùng một chủ sở hữu; chuyển khác tiền tệ bắt buộc cung cấp số tiền nguồn và số tiền đích để chốt snapshot quy đổi theo BR04 và BR15. Chuyển tiền nội bộ tuyệt đối không tính vào Thu nhập hay Chi phí.  
**FR03.03** Hệ thống phải từ chối xóa hoặc sửa vật lý đối với giao dịch đã ghi sổ (Posted); thao tác sửa giao dịch bắt buộc phải thực thi nguyên tử: tạo bút toán Đảo (Reversal) bản gốc và ghi nhận giao dịch thay thế mới có liên kết `originalId` theo BR06.  
**FR03.04** Hệ thống phải áp dụng cơ chế chống trùng lặp Idempotency theo BR13 cho 100% các yêu cầu tạo giao dịch, đảo và thay thế giao dịch.  
**FR03.05** Hệ thống phải cho phép người dùng tự tạo mới, đổi tên và lưu trữ danh mục thu nhập/chi tiêu cá nhân.  

- **Xử lý ngoại lệ:** Thiếu tỷ giá quy đổi: từ chối ghi sổ giao dịch ngoại tệ. Số tiền $\le 0$, ví không đủ số dư khả dụng, chuyển tiền vào chính ví nguồn: từ chối và nêu rõ thông báo lỗi.
- **Hậu điều kiện:** Giao dịch được lưu trữ nhất quán; các bút toán Nợ/Có bảo đảm tính bất biến và toàn vẹn sổ sách.
- **Tác động phụ:** Kích hoạt cập nhật ngân sách và dashboard tài chính.
- **Tiêu chí kiểm chứng TC-F03:** Ghi nhận khoản thu 5.000.000 VND; chi 1.000.000 VND; chuyển tiền nội bộ 2.000.000 VND; đảo khoản chi 1.000.000 VND: kiểm tra số dư và tính cân bằng sổ cái sau mỗi bước.

---

### FR04 - Đa tiền tệ và tỷ giá hối đoái snapshot
- **Thuộc tính yêu cầu:** Mức ưu tiên: P2 | Truy vết: UR02, BR03, BR04, BR15 | Use Case: UC02, UC03 | Tác vụ: SYS01 | Kiểm thử: TC-F04
- **Đầu vào / Nguồn:** Bảng tỷ giá hối đoái từ nhà cung cấp IF02; cặp tiền giao dịch và số tiền nguyên tệ từ người dùng.
- **Đầu ra / Đích nhận:** Giá trị quy đổi tiền cơ sở chính xác, bản ghi snapshot tỷ giá gắn liền với giao dịch.
- **Tiền điều kiện:** Cặp tiền được hệ thống hỗ trợ (VND, USD, EUR); tỷ giá hối đoái $> 0$ và còn hiệu lực trong vòng 24 giờ.

**FR04.01** Hệ thống phải tự động tiếp nhận hoặc cập nhật bảng tỷ giá định kỳ thông qua IF02 và lưu trữ lịch sử theo phiên bản thời gian hiệu lực.  
**FR04.02** Hệ thống phải chụp snapshot tỷ giá tại thời điểm commit giao dịch ngoại tệ và gắn bất biến vào bản ghi bút toán; tuyệt đối không tự động tính lại giá trị giao dịch lịch sử khi tỷ giá mới xuất hiện.  
**FR04.03** Hệ thống phải áp dụng phương pháp bình quân gia quyền theo BR15 khi xuất ngoại tệ khỏi ví và ghi nhận chênh lệch vào tài khoản Lãi/Lỗ tỷ giá.  

- **Xử lý ngoại lệ:** Không có tỷ giá hợp lệ: từ chối giao dịch ngoại tệ và hướng dẫn người dùng thử lại sau.
- **Hậu điều kiện:** Toàn bộ báo cáo tài chính hợp nhất đều quy về tiền cơ sở một cách chuẩn xác.
- **Tác động phụ:** Lưu bản ghi snapshot tỷ giá.
- **Tiêu chí kiểm chứng TC-F04:** Mua 100 USD lúc tỷ giá 25.000; bán 100 USD lúc tỷ giá 25.500: kiểm tra lãi tỷ giá 50.000 VND được hạch toán chuẩn xác.

---

### FR05 - Thiết lập và kiểm soát ngân sách chi tiêu
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR03, BR07, BR08 | Use Case: UC05 | Kiểm thử: TC-F05
- **Đầu vào / Nguồn:** Danh mục chi tiêu, hạn mức ngân sách tháng (`limitAmount > 0`), tháng áp dụng (YYYY-MM) từ người dùng.
- **Đầu ra / Đích nhận:** Bản ghi ngân sách, phần trăm sử dụng ngân sách thời gian thực và cảnh báo vượt ngưỡng.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; danh mục thuộc quyền sở hữu; hạn mức ngân sách $> 0$.

**FR05.01** Hệ thống phải cho phép người dùng tạo, xem, chỉnh sửa hạn mức và lưu trữ ngân sách chi tiêu theo từng danh mục cho một tháng xác định.  
**FR05.02** Hệ thống phải tự động tính toán tổng chi tiêu ròng trong tháng của danh mục tương ứng và tính tỷ lệ sử dụng ngân sách theo BR07.  
**FR05.03** Hệ thống phải tự động kích hoạt cảnh báo theo 2 ngưỡng: Cảnh báo Vàng khi mức sử dụng $\ge 80\%$ và Cảnh báo Đỏ khi mức sử dụng $\ge 100\%$ theo quy tắc chống trùng lặp thông báo BR08.  

- **Xử lý ngoại lệ:** Hạn mức $\le 0$: từ chối và báo lỗi. Trùng ngân sách cùng danh mục trong cùng tháng: yêu cầu sửa hạn mức thay vì tạo mới.
- **Hậu điều kiện:** Ngân sách được giám sát chủ động; không tự ý chặn quyền chi tiêu của người dùng.
- **Tác động phụ:** Gửi thông báo cảnh báo đến Notification Center của người dùng.
- **Tiêu chí kiểm chứng TC-F05:** Đặt ngân sách ăn uống 2.000.000 VND. Chi 1.600.000 VND: kiểm tra cảnh báo Vàng (80%). Chi thêm 500.000 VND: kiểm tra cảnh báo Đỏ (105%).

---

### FR06 - Xử lý giao dịch định kỳ
- **Thuộc tính yêu cầu:** Mức ưu tiên: P2 | Truy vết: UR04, BR09 | Use Case: UC04 | Tác vụ: SYS02 | Kiểm thử: TC-F06
- **Đầu vào / Nguồn:** Tên khoản định kỳ, số tiền, loại (Thu/Chi), ví, danh mục, chu kỳ (Hàng tháng), ngày neo đến hạn (`anchorDay`), chế độ tự động ghi sổ từ người dùng.
- **Đầu ra / Đích nhận:** Bản ghi khoản định kỳ, các bản nháp hoặc giao dịch tự động sinh ra đúng chu kỳ.
- **Tiền điều kiện:** Người dùng đã xác thực; số tiền $> 0$; ngày neo từ 1 đến 31.

**FR06.01** Hệ thống phải cho phép người dùng thiết lập, xem danh sách, tạm dừng, kích hoạt lại và xóa các khoản giao dịch định kỳ hàng tháng.  
**FR06.02** Hệ thống phải hỗ trợ 2 chế độ: Nhắc nhở tạo bản nháp (mặc định) và Tự động ghi sổ (khi người dùng bật chủ động).  
**FR06.03** Tiến trình nền SYS02 phải tự động quét các khoản định kỳ đến hạn, tự động lùi về ngày cuối tháng đối với các tháng thiếu ngày theo BR09 và áp dụng khóa duy nhất `(subscriptionId, dueDate)` để chống trùng lặp.  

- **Xử lý ngoại lệ:** Ví chi trả không đủ số dư khi tự động ghi sổ: chuyển trạng thái sang bản nháp chờ xử lý và phát thông báo nhắc nhở người dùng nạp tiền.
- **Hậu điều kiện:** Không bao giờ sinh trùng 2 giao dịch trong cùng một kỳ đến hạn.
- **Tác động phụ:** Phát thông báo nhắc nhở đến người dùng.
- **Tiêu chí kiểm chứng TC-F06:** Thiết lập tiền nhà 3.000.000 VND ngày 31 hàng tháng. Kiểm tra tháng 2 (28 ngày): hệ thống sinh giao dịch đúng ngày 28 và giữ ngày neo 31 cho tháng 3.

---

### FR07 - Quản lý mục tiêu tích lũy tài chính
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR05, BR10 | Use Case: UC09 | Kiểm thử: TC-F07
- **Đầu vào / Nguồn:** Tên mục tiêu, số tiền đích (`targetAmount`), ngày thời hạn (`deadlineDate`), các khoản phân bổ tiền tích lũy từ người dùng.
- **Đầu ra / Đích nhận:** Bản ghi mục tiêu, tổng tiền tích lũy, phần trăm tiến độ, số tiền còn thiếu và gợi ý số tiền cần dành mỗi tháng.
- **Tiền điều kiện:** Số tiền đích $> 0$; ngày thời hạn không ở quá khứ.

**FR07.01** Hệ thống phải cho phép người dùng tạo mới, xem danh sách, chỉnh sửa thông tin, hủy bỏ và hoàn thành mục tiêu tài chính.  
**FR07.02** Hệ thống phải cho phép người dùng ghi nhận các khoản phân bổ tiền tích lũy gắn với mục tiêu mà không làm tăng tổng tài sản của người dùng theo BR10.  
**FR07.03** Hệ thống phải tự động tính toán tiến độ hoàn thành ($Progress = rac{Current}{Target} 	imes 100\%$) và gợi ý số tiền cần tích lũy bình quân mỗi tháng: $MonthlySaving = rac{Target - Current}{	ext{Số tháng còn lại}}$ (tránh chia 0).  

- **Xử lý ngoại lệ:** Số tiền đích $\le 0$, thời hạn ở quá khứ: từ chối và báo lỗi.
- **Hậu điều kiện:** Tiến độ mục tiêu được theo dõi rõ ràng, hỗ trợ người dùng xây dựng thói quen tích lũy.
- **Tác động phụ:** Cập nhật tiến độ trên Dashboard.
- **Tiêu chí kiểm chứng TC-F07:** Mục tiêu mua laptop 20.000.000 VND trong 10 tháng. Đã dành 5.000.000 VND: kiểm tra tiến độ 25%, số tiền cần dành mỗi tháng là 1.500.000 VND.

---

### FR08 - Quản lý tài sản tích lũy ngoài ví (Vàng) và Tài sản ròng (Personal Wealth & Net Worth)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR06, UR08, BR02, BR16 | Use Case: UC08, UC10 | Kiểm thử: TC-F08
- **Đầu vào / Nguồn:** Thông tin tài sản vàng tích lũy (Tên tài sản, loại vàng: Nhẫn tròn trơn 9999, Vàng miếng SJC; Số lượng chỉ, Giá trị quy đổi hoặc thị giá cập nhật, Ngày định giá, Mô tả) từ người dùng.
- **Đầu ra / Đích nhận:** Danh mục tài sản tích lũy, Tổng tài sản (Total Assets), Tổng nợ (Total Liabilities), Tài sản ròng (Net Worth) và biểu đồ xu hướng Net Worth.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; số lượng chỉ và giá trị định giá $\ge 0$.

**FR08.01** Hệ thống phải cho phép người dùng thêm mới, cập nhật thông tin, cập nhật thị giá định giá lại (Revaluation) và xóa/thanh lý tài sản vàng tích lũy ngoài ví.  
**FR08.02** Hệ thống phải tự động tổng hợp Tổng tài sản (Total Assets) bao gồm: tổng số dư quy đổi tiền cơ sở của toàn bộ ví tài sản (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm) cộng với tổng giá trị định giá của toàn bộ tài sản vàng tích lũy đang theo dõi.  
**FR08.03** Hệ thống phải tự động tổng hợp Tổng nợ (Total Liabilities) bao gồm: tổng số dư các ví nợ (Thẻ tín dụng) cộng với tổng dư nợ gốc còn lại của toàn bộ các khoản vay/nợ vi mô đang hoạt động.  
**FR08.04** Hệ thống phải tính toán chính xác Tài sản ròng tại mọi thời điểm theo công thức: $	ext{Net Worth} = 	ext{Total Assets} - 	ext{Total Liabilities}$ theo BR16 và lưu trữ snapshot lịch sử để vẽ biểu đồ biến động theo thời gian.  
**FR08.05** Hệ thống phải bảo đảm nguyên tắc không tính trùng lặp: tiền trong ví chỉ tính đúng 1 lần vào Tổng tài sản; chuyển tiền nội bộ tuyệt đối không làm thay đổi Net Worth.  

- **Xử lý ngoại lệ:** Giá trị hoặc số lượng $< 0$: từ chối và báo lỗi.
- **Hậu điều kiện:** Net Worth phản ánh trung thực toàn bộ tiềm lực tài chính cá nhân của người dùng.
- **Tác động phụ:** Cập nhật các chỉ số trọng yếu trên Dashboard.
- **Tiêu chí kiểm chứng TC-F08:** Ví NH 20tr, Tiết kiệm 50tr, 1 chỉ vàng nhẫn 8.5tr, Khoản vay còn nợ 15tr, Thẻ tín dụng nợ 5tr. Kiểm tra: Total Assets = 78.5tr; Total Liabilities = 20tr; Net Worth = 58.5tr.

---

### FR09 - Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance Tracking)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR07, BR05, BR17 | Use Case: UC06, UC07 | Tác vụ: SYS03 | Kiểm thử: TC-F09
- **Đầu vào / Nguồn:** Thông tin khoản vay: Tên khoản vay, loại (Đi vay / Cho vay), đối tác vay/mượn, số tiền gốc ban đầu, lãi suất (nếu có), ngày bắt đầu, ngày đến hạn; Thông tin trả nợ: ngày trả, tiền gốc, tiền lãi/phí, ví thanh toán.
- **Đầu ra / Đích nhận:** Hồ sơ khoản vay, dư nợ gốc còn lại, lịch sử các đợt trả nợ, trạng thái khoản vay (Active, PaidOff, Overdue) và thông báo nhắc nợ.
- **Tiền điều kiện:** Người dùng đã xác thực; số tiền vay $> 0$; ngày đến hạn $\ge$ ngày bắt đầu; ví thanh toán đủ số dư khi trả nợ.

**FR09.01** Hệ thống phải cho phép người dùng tạo, xem chi tiết, chỉnh sửa thông tin và theo dõi các khoản vay mượn vi mô (bao gồm cả khoản đi vay và khoản cho vay người khác).  
**FR09.02** Khi tạo khoản đi vay mới có nhận tiền thực tế vào ví, hệ thống phải tự động sinh giao dịch: Tăng số dư ví nhận tiền và Tăng nợ phải trả tương ứng; tuyệt đối cấm ghi nhận tiền vay nhận được vào Thu nhập (theo BR17).  
**FR09.03** Hệ thống phải cho phép ghi nhận các đợt thanh toán trả nợ từng kỳ; hệ thống bắt buộc phân rã số tiền thanh toán thành: Tiền trả nợ gốc (làm giảm trực tiếp dư nợ gốc khoản vay và giảm số dư ví chi trả) và Tiền lãi/phí phát sinh (hạch toán vào Chi phí tài chính và giảm số dư ví chi trả) theo chuẩn kế toán.  
**FR09.04** Hệ thống phải tự động tính toán dư nợ gốc còn lại sau mỗi lần trả nợ: $	ext{Dư nợ còn lại} = 	ext{Gốc ban đầu} - \sum 	ext{Gốc đã trả}$; tự động chuyển trạng thái sang "Đã thanh toán" (PaidOff) khi dư nợ gốc về 0.  
**FR09.05** Tiến trình nền SYS03 phải tự động quét các khoản vay sắp đến hạn trong vòng 3 ngày và gửi thông báo nhắc nhở người dùng.  

- **Xử lý ngoại lệ:** Số tiền trả gốc vượt quá dư nợ còn lại: từ chối và báo lỗi. Ví chi trả không đủ tiền: từ chối ghi nhận trả nợ.
- **Hậu điều kiện:** Dư nợ gốc của khoản vay và số dư ví tiền được cập nhật đồng bộ.
- **Tác động phụ:** Cập nhật Total Liabilities và Net Worth của người dùng.
- **Tiêu chí kiểm chứng TC-F09:** Khoản vay 10tr. Trả kỳ 1: 2tr gốc + 150k lãi từ ví NH: kiểm tra ví NH giảm 2.15tr, dư nợ còn 8tr, chi phí ghi nhận 150k (thu chi không bị trừ 2tr gốc).

---

### FR10 - Dashboard sức khỏe tài chính và Tổng quan tài sản ròng
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR08, BR02, BR14, BR16, BR18 | Use Case: UC10 | Kiểm thử: TC-F10
- **Đầu vào / Nguồn:** Khoảng thời gian lọc (Mặc định tháng hiện tại), bộ lọc tài khoản từ người dùng.
- **Đầu ra / Đích nhận:** Màn hình Dashboard trực quan hiển thị: Tổng tài sản, Tổng nợ, Net Worth, Thu nhập, Chi phí, Dòng tiền thuần, Tỷ lệ tiết kiệm và biểu đồ xu hướng.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.

**FR10.01** Hệ thống phải tổng hợp và hiển thị đồng thời các chỉ số sức khỏe tài chính trọng yếu trong một màn hình duy nhất theo thời gian thực.  
**FR10.02** Hệ thống phải vẽ biểu đồ biến động Net Worth theo các mốc thời gian lịch sử và biểu đồ cơ cấu phân bổ tài sản.  
**FR10.03** Hệ thống phải hiển thị tiến độ tổng hợp của các mục tiêu tích lũy tài chính và mức độ sử dụng của các ngân sách đang hoạt động.  

- **Xử lý ngoại lệ:** Người dùng mới chưa có dữ liệu: hiển thị các chỉ số bằng 0 kèm thông điệp hướng dẫn bắt đầu tạo ví và ghi chép.
- **Hậu điều kiện:** Cung cấp góc nhìn toàn cảnh giúp người dùng đưa ra quyết định tài chính đúng đắn.
- **Tác động phụ:** Không làm biến động dữ liệu sổ sách.
- **Tiêu chí kiểm chứng TC-F10:** Kiểm tra tính khớp đúng 100% giữa số liệu trên Dashboard và số dư thực tế của các ví, tài sản vàng và khoản vay.

---

### FR11 - Phân tích dòng tiền và Tỷ lệ tiết kiệm
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR08, BR18 | Use Case: UC10 | Kiểm thử: TC-F11
- **Đầu vào / Nguồn:** Khoảng thời gian khảo sát (Tuần, Tháng, Năm hoặc tùy chọn).
- **Đầu ra / Đích nhận:** Dòng tiền thuần (Net Cash Flow), Tỷ lệ tiết kiệm (Saving Rate), biểu đồ cơ cấu thu/chi theo danh mục.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.

**FR11.01** Hệ thống phải tính toán dòng tiền thuần theo công thức chuẩn: $	ext{Net Cash Flow} = 	ext{Total Income} - 	ext{Total Expense}$ trong kỳ khảo sát.  
**FR11.02** Hệ thống phải tính toán tỷ lệ tiết kiệm theo công thức: $	ext{Saving Rate} = rac{	ext{Total Income} - 	ext{Total Expense}}{	ext{Total Income}} 	imes 100\%$.  
**FR11.03** Hệ thống phải xử lý an toàn ngoại lệ theo BR18: nếu $	ext{Total Income} \le 0$, không thực hiện phép chia; hiển thị "N/A" an toàn mà không làm sập ứng dụng; nếu $	ext{Expense} > 	ext{Income}$, Saving Rate hiển thị giá trị âm màu đỏ cảnh báo.  

- **Xử lý ngoại lệ:** Không có giao dịch trong kỳ: hiển thị dòng tiền bằng 0 và tỷ lệ tiết kiệm "N/A".
- **Hậu điều kiện:** Dữ liệu phân tích phản ánh chính xác thói quen tiêu dùng của người dùng.
- **Tác động phụ:** Không có tác động phụ lên cơ sở dữ liệu.
- **Tiêu chí kiểm chứng TC-F11:** Thu nhập 10tr, Chi tiêu 6tr: Cash Flow = 4tr, Saving Rate = 40%. Thu nhập 0tr, Chi tiêu 2tr: Cash Flow = -2tr, Saving Rate = "N/A" an toàn.

---

### FR12 - Tra cứu lịch sử, đối soát và xuất tệp CSV
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR09, BR01, BR11, BR14 | Use Case: UC11 | Kiểm thử: TC-F12
- **Đầu vào / Nguồn:** Bộ lọc tìm kiếm: khoảng ngày, loại giao dịch, ví, danh mục, từ khóa; Lệnh xuất tệp CSV từ người dùng.
- **Đầu ra / Đích nhận:** Danh sách giao dịch phân trang, chi tiết bút toán Nợ/Có và tệp CSV tải về.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.

**FR12.01** Hệ thống phải cho phép tìm kiếm, lọc phân trang lịch sử giao dịch và xem chi tiết từng giao dịch bao gồm: nguyên tệ, tiền cơ sở, tỷ giá snapshot, các dòng sổ Nợ/Có và liên kết đảo/thay thế.  
**FR12.02** Hệ thống phải cung cấp chức năng đối soát số dư: so sánh số dư bộ đệm của ví với tổng lũy kế lịch sử các dòng sổ cái để bảo đảm tính toàn vẹn 100%.  
**FR12.03** Hệ thống phải cho phép xuất danh sách giao dịch đã lọc sang tệp định dạng CSV mã hóa UTF-8 có kèm BOM để tương thích hoàn toàn với Microsoft Excel.  
**FR12.04** Hệ thống phải tự động khử ký tự nguy hiểm (Neutralize characters `=, +, -, @`) trong các trường văn bản xuất sang CSV để phòng chống triệt để lỗ hổng CSV Formula Injection.  

- **Xử lý ngoại lệ:** Không tìm thấy giao dịch thỏa mãn: hiển thị bảng trống kèm thông báo phù hợp. Lỗi tạo file CSV: báo lỗi và cho phép thử lại.
- **Hậu điều kiện:** Tệp CSV được tải về an toàn, dữ liệu khớp đúng với kết quả tra cứu.
- **Tác động phụ:** Ghi log thao tác xuất dữ liệu.
- **Tiêu chí kiểm chứng TC-F12:** Tạo giao dịch có mô tả chứa `=SUM(...)`, xuất CSV: kiểm tra tệp mở bằng Excel hiển thị đúng chuỗi ký tự mà không tự thực thi mã lệnh.

---

### FR13 - Cấu hình hệ thống và Phát thông báo người dùng (Admin)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P2 | Truy vết: UR10, BR07, BR08, BR11 | Use Case: UC13 | Kiểm thử: TC-F13
- **Đầu vào / Nguồn:** Danh mục hệ thống mặc định, tham số vận hành (ngưỡng cảnh báo ngân sách 80%, thời gian timeout phiên), nội dung thông báo phát thanh (tiêu đề, nội dung, mức độ ưu tiên) từ Quản trị viên.
- **Đầu ra / Đích nhận:** Bộ danh mục mẫu cập nhật, tham số hệ thống mới, bản ghi thông báo broadcast được gửi đến toàn bộ người dùng.
- **Tiền điều kiện:** Đăng nhập với tài khoản có vai trò `Admin`.

**FR13.01** Hệ thống phải cho phép Quản trị viên quản lý danh mục thu/chi mặc định của hệ thống để tự động gán cho người dùng mới khi đăng ký tài khoản.  
**FR13.02** Hệ thống phải cho phép Quản trị viên cấu hình các tham số vận hành chung (ngưỡng cảnh báo ngân sách mặc định, hạn ngạch tải tệp).  
**FR13.03** Hệ thống phải cho phép Quản trị viên soạn và phát thông báo hệ thống (Broadcast Announcement) tới toàn bộ người dùng; thông báo tự động xuất hiện tại Notification Center của người dùng khi đăng nhập.  

- **Xử lý ngoại lệ:** Nội dung thông báo để trống: từ chối và báo lỗi trường. Giá trị tham số nằm ngoài khoảng an toàn: từ chối lưu.
- **Hậu điều kiện:** Thông báo được gửi đến người dùng; các cấu hình mới có hiệu lực cho toàn hệ thống.
- **Tác động phụ:** Ghi nhật ký kiểm toán hành chính Admin Audit Log.
- **Tiêu chí kiểm chứng TC-F13:** Admin phát thông báo "Bảo trì hệ thống lúc 23:00": kiểm tra toàn bộ tài khoản người dùng đăng nhập đều nhận được thông báo này trên chuông thông báo.

---

### FR14 - Quản trị tài khoản người dùng và Dashboard thống kê vĩ mô (Admin)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR10, BR11 | Use Case: UC12 | Kiểm thử: TC-F14
- **Đầu vào / Nguồn:** Yêu cầu xem danh sách người dùng, lệnh khóa/mở khóa tài khoản kèm lý do bắt buộc từ Quản trị viên.
- **Đầu ra / Đích nhận:** Màn hình Dashboard thống kê vĩ mô, danh sách người dùng phân trang, trạng thái tài khoản cập nhật.
- **Tiền điều kiện:** Đăng nhập với quyền `Admin`.

**FR14.01** Hệ thống phải cung cấp Dashboard thống kê vĩ mô cho Admin: hiển thị tổng số lượng người dùng, số lượng người dùng mới đăng ký theo tháng, số lượng tài khoản đang hoạt động (`Active`) và phân bố loại tiền cơ sở.  
**FR14.02** Hệ thống phải bảo đảm nguyên tắc bảo mật quyền riêng tư BR11: Dashboard của Admin tuyệt đối không hiển thị chi tiết số dư tài khoản, ví tiền hay danh sách giao dịch tài chính cá nhân của người dùng.  
**FR14.03** Hệ thống phải cho phép Quản trị viên tìm kiếm, xem danh sách người dùng và thực hiện khóa tài khoản (`Locked`) kèm lý do bắt buộc; hệ thống tự động thu hồi toàn bộ token phiên đăng nhập của người dùng đó ngay lập tức.  
**FR14.04** Hệ thống phải cho phép Quản trị viên mở khóa tài khoản (`Active`) cho người dùng đã giải trình vi phạm.  
**FR14.05** Hệ thống tuyệt đối cấm Quản trị viên tự vô hiệu hóa tài khoản Admin duy nhất còn lại của hệ thống.  

- **Xử lý ngoại lệ:** Khóa tài khoản không nhập lý do: từ chối thao tác. Thao tác trên Admin cuối cùng: từ chối và báo lỗi an toàn.
- **Hậu điều kiện:** Tài khoản bị khóa mất quyền truy cập API ngay lập tức; dữ liệu sổ sách của người dùng được bảo toàn nguyên vẹn.
- **Tác động phụ:** Ghi Audit Log hành chính.
- **Tiêu chí kiểm chứng TC-F14:** Admin khóa tài khoản UserA: kiểm tra UserA đang đăng nhập bị từ chối API 401/403 ngay lập tức; mở khóa: UserA đăng nhập lại bình thường.

---

### FR15 - Kiểm soát truy cập API và bảo mật dữ liệu đa người dùng
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR01, UR11, UR12, BR11 | Áp dụng: Toàn bộ UC bảo vệ | Kiểm thử: TC-F15
- **Đầu vào / Nguồn:** Token xác thực (JWT), định danh vai trò (Role), định danh tài nguyên cần thao tác từ mọi yêu cầu HTTP gửi đến API.
- **Đầu ra / Đích nhận:** Cho phép yêu cầu tiếp tục xử lý hoặc từ chối truy cập kèm mã lỗi HTTP chuẩn hóa (401, 403, 404).
- **Tiền điều kiện:** Đường dẫn công khai duy nhất là Đăng ký, Đăng nhập và tài nguyên tĩnh; toàn bộ API còn lại đều được bảo vệ nghiêm ngặt.

**FR15.01** Hệ thống phải kiểm chứng tính hợp lệ và thời hạn của Token xác thực trước khi xử lý nghiệp vụ; danh tính người dùng bắt buộc trích xuất từ Token ký số an toàn, tuyệt đối không tin tưởng tham số `userId` do máy khách truyền lên trong phần thân yêu cầu.  
**FR15.02** Hệ thống phải kiểm tra quyền sở hữu đối với 100% tài nguyên dữ liệu (ví, tài sản, khoản nợ, giao dịch, ngân sách, mục tiêu); nếu người dùng yêu cầu tài nguyên không thuộc quyền sở hữu của mình, hệ thống phải trả về mã lỗi 404 Not Found (nhằm che giấu sự tồn tại của dữ liệu) thay vì 403.  
**FR15.03** Hệ thống phải ngăn chặn hoàn toàn việc Quản trị viên truy vấn dữ liệu chi tiết sổ sách tài chính cá nhân của người dùng thông qua API theo BR11.  

- **Xử lý ngoại lệ:** Token không hợp lệ hoặc hết hạn: trả về 401 Unauthorized; sai vai trò quản trị: trả về 403 Forbidden; tài khoản bị khóa: từ chối truy cập.
- **Hậu điều kiện:** Dữ liệu tài chính giữa các người dùng được cô lập tuyệt đối.
- **Tác động phụ:** Ghi nhật ký cảnh báo bảo mật khi phát hiện dấu hiệu dò quét tài nguyên trái phép.
- **Tiêu chí kiểm chứng TC-F15:** UserA dùng ID thật của giao dịch thuộc UserB để gửi yêu cầu xem chi tiết: hệ thống trả về 404 Not Found, không làm lộ bất kỳ dữ liệu nào của UserB.


---

<a id="phan-4"></a>

## 4. Mô hình tương tác, Use Cases và Kịch bản xử lý nền

### 4.1 Danh mục Use Case theo tác nhân (13 Core Use Cases)

```
                               ┌────────────────────────────────────────────────────────┐
                               │                    WEALTHFLOW SYSTEM                   │
                               │                                                        │
                               │  [Phân hệ 1: Xác thực & Ví tiền]                       │
                               │  ├── UC01: Đăng ký & Xác thực người dùng               │
                               │  └── UC02: Quản lý tài khoản thanh toán và ví tiền     │
                               │                                                        │
                               │  [Phân hệ 2: Expense Tracker (Thu - Chi - Ngân sách)]  │
                               │  ├── UC03: Ghi nhận & Quản lý giao dịch tài chính      │
                               │  ├── UC04: Thiết lập & Xử lý giao dịch định kỳ         │
                               │  └── UC05: Thiết lập & Kiểm soát ngân sách chi tiêu    │
                               │                                                        │
      ┌──────────────────┐     │  [Phân hệ 3: Microfinance (Vay & Nợ vi mô)]            │
      │                  │     │  ├── UC06: Quản lý khoản vay và nghĩa vụ nợ vi mô      │
      │                  ├────►│  └── UC07: Ghi nhận thanh toán trả nợ vay              │
      │                  │     │                                                        │
      │  A01: Người dùng │     │  [Phân hệ 4: Personal Wealth (Tài sản & Tích lũy)]     │
      │                  │     │  ├── UC08: Quản lý tài sản tích lũy ngoài ví (Vàng)    │
      │                  ├────►│  └── UC09: Quản lý mục tiêu tích lũy tài chính         │
      │                  │     │                                                        │
      │                  │     │  [Phân hệ 5: Báo cáo, Đối soát & Dashboard]            │
      │                  ├────►│  ├── UC10: Xem Dashboard sức khỏe tài chính & Net Worth│
      │                  │     │  └── UC11: Báo cáo thống kê & Xuất dữ liệu CSV         │
      └──────────────────┘     └────────────────────────────────────────────────────────┘
                               ┌────────────────────────────────────────────────────────┐
      ┌──────────────────┐     │  [Phân hệ 6: Quản trị Hệ thống (Admin)]                │
      │ A02: Quản trị    ├────►│  ├── UC12: Quản trị người dùng & Dashboard vĩ mô       │
      │      viên (Admin)│     │  └── UC13: Cấu hình hệ thống & Phát thông báo người dùng│
      └──────────────────┘     └────────────────────────────────────────────────────────┘
```

#### Bảng tổng hợp ánh xạ 13 Use Cases

| Mã UC | Tên Use Case | Phân hệ (Subsystem) | Tác nhân chính | Ánh xạ FR |
| :---: | :--- | :--- | :---: | :--- |
| **UC01** | Đăng ký & Xác thực người dùng | 1. Xác thực & Hồ sơ | A01-G, A01, A02 | FR01, FR15 |
| **UC02** | Quản lý tài khoản thanh toán và ví tiền | 1. Xác thực & Hồ sơ | A01: Người dùng | FR02, FR04, FR15 |
| **UC03** | Ghi nhận & Quản lý giao dịch tài chính | 2. Expense Tracker | A01: Người dùng | FR03, FR04, FR15 |
| **UC04** | Thiết lập & Xử lý giao dịch định kỳ | 2. Expense Tracker | A01, SYS | FR06, SYS02 |
| **UC05** | Thiết lập & Kiểm soát ngân sách chi tiêu | 2. Expense Tracker | A01, SYS | FR05, SYS03 |
| **UC06** | Quản lý khoản vay và nghĩa vụ nợ vi mô | 3. Microfinance | A01: Người dùng | FR09, FR15 |
| **UC07** | Ghi nhận thanh toán trả nợ vay | 3. Microfinance | A01: Người dùng | FR03, FR09, FR15 |
| **UC08** | Quản lý tài sản tích lũy ngoài ví (Vàng) | 4. Personal Wealth | A01: Người dùng | FR08, FR15 |
| **UC09** | Quản lý mục tiêu tích lũy tài chính | 4. Personal Wealth | A01: Người dùng | FR07, FR15 |
| **UC10** | Xem Dashboard sức khỏe tài chính & Net Worth | 5. Báo cáo & Dashboard | A01: Người dùng | FR08, FR10, FR11 |
| **UC11** | Báo cáo thống kê & Xuất dữ liệu CSV | 5. Báo cáo & Dashboard | A01: Người dùng | FR12, FR15 |
| **UC12** | Quản trị người dùng & Dashboard thống kê vĩ mô | 6. Quản trị Hệ thống | A02: Admin | FR14, FR15 |
| **UC13** | Cấu hình hệ thống & Phát thông báo người dùng | 6. Quản trị Hệ thống | A02: Admin | FR13, FR15 |

---

### 4.2 Đặc tả chi tiết các Use Case hệ thống

---

#### UC01 - Đăng ký & Xác thực người dùng
- **Tác nhân:** A01-G (Khách vãng lai), A01 (Người dùng đã xác thực), A02 (Quản trị viên).
- **Tiền điều kiện:** Đăng ký: địa chỉ email chưa từng tồn tại trong hệ thống. Đăng nhập: tài khoản tồn tại và trạng thái `Active`. Đổi mật khẩu: phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Đăng ký: Họ tên, Email, Mật khẩu, Tiền tệ cơ sở (`baseCurrency`); Đăng nhập: Email, Mật khẩu; Đổi mật khẩu: Mật khẩu cũ, Mật khẩu mới.
- **Dữ liệu đầu ra:** Thông báo thành công, Token JWT phiên truy cập, hoặc xác nhận đổi mật khẩu.

**Luồng sự kiện chính (Main Flow - Đăng ký & Đăng nhập):**
1. Khách truy cập WealthFlow và chọn "Đăng ký".
2. Hệ thống hiển thị biểu mẫu đăng ký kèm danh sách tiền cơ sở hỗ trợ (mặc định VND).
3. Khách nhập họ tên, email, mật khẩu và chọn tiền cơ sở, nhấn "Đăng ký tài khoản".
4. Hệ thống chuẩn hóa email, kiểm tra định dạng và tính duy nhất của email.
5. Hệ thống băm mật khẩu bằng thuật toán an toàn (Argon2id/bcrypt) kèm Salt ngẫu nhiên.
6. Hệ thống tạo tài khoản mới ở trạng thái `Active`, tự động gán bộ danh mục thu/chi mặc định của hệ thống.
7. Hệ thống chuyển hướng người dùng sang màn hình đăng nhập.
8. Người dùng nhập email và mật khẩu, nhấn "Đăng nhập".
9. Hệ thống xác thực thông tin, cấp cặp Token JWT (Access Token & Refresh Token) và lưu vết phiên.
10. Hệ thống chuyển hướng người dùng vào màn hình Dashboard chính.

**Luồng phụ (Alternative Flows):**
* *8a. Người dùng đổi mật khẩu:* Người dùng vào trang cá nhân, nhập mật khẩu cũ và mật khẩu mới; hệ thống kiểm tra mật khẩu cũ chính xác, cập nhật mật khẩu mới và tự động hủy toàn bộ phiên đăng nhập cũ trên các thiết bị khác theo FR01.04.
* *8b. Người dùng đăng xuất:* Người dùng chọn "Đăng xuất"; hệ thống vô hiệu hóa Token và kết thúc phiên làm việc ngay lập tức.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Email đã tồn tại hoặc mật khẩu yếu:* Hệ thống báo lỗi trường cụ thể và giữ nguyên biểu mẫu để người dùng sửa.
* *9e1. Sai thông tin đăng nhập:* Hệ thống trả thông báo lỗi chung "Email hoặc mật khẩu không chính xác", không tiết lộ sự tồn tại của email.
* *9e2. Tài khoản đang bị khóa (Locked):* Hệ thống từ chối đăng nhập và hiển thị thông báo "Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên kèm lý do".

- **Hậu điều kiện:** Người dùng có tài khoản hợp lệ; phiên làm việc được quản lý bảo mật.

---

#### UC02 - Quản lý tài khoản thanh toán và ví tiền
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tên ví, loại ví (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm), tiền tệ (VND/USD/EUR), số dư mở sổ ban đầu.
- **Dữ liệu đầu ra:** Bản ghi ví mới được tạo, số dư cập nhật, danh sách các ví đang hoạt động.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập mục "Quản lý Ví" và nhấn "Tạo ví mới".
2. Hệ thống hiển thị biểu mẫu tạo ví.
3. Người dùng nhập tên ví (ví dụ: "Tài khoản Vietcombank"), chọn loại ví ("Ngân hàng"), chọn loại tiền tệ và nhập số dư đầu kỳ (ví dụ: 10.000.000 VND).
4. Hệ thống kiểm tra số dư $\ge 0$ và tên ví không trùng lặp.
5. Hệ thống tạo bản ghi ví trong cơ sở dữ liệu.
6. Nếu số dư đầu kỳ $> 0$, hệ thống tự động sinh bút toán đối ứng Vốn chủ sở hữu (Equity) theo nguyên lý kế toán kép BR01 & BR02 trong một giao dịch nguyên tử.
7. Hệ thống cập nhật danh sách ví và hiển thị thông báo tạo ví thành công.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng chỉnh sửa thông tin ví:* Người dùng chọn đổi tên ví hoặc màu sắc/biểu tượng hiển thị; hệ thống lưu thay đổi và giữ nguyên số dư và lịch sử giao dịch.
* *1b. Người dùng lưu trữ (Archive) ví:* Người dùng chọn lưu trữ ví khi không còn sử dụng; nếu số dư bằng 0, hệ thống chuyển trạng thái sang `Archived` (ẩn khỏi danh sách chọn khi tạo giao dịch nhưng giữ nguyên trong lịch sử báo cáo).

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số dư đầu kỳ $< 0$:* Hệ thống báo lỗi "Số dư ban đầu không được âm".
* *1be1. Lưu trữ ví còn số dư khác 0:* Hệ thống từ chối lưu trữ và cảnh báo "Ví vẫn còn số dư. Vui lòng chuyển hết tiền trước khi lưu trữ ví".

- **Hậu điều kiện:** Ví mới sẵn sàng thực hiện các giao dịch thu, chi và chuyển tiền.

---

#### UC03 - Ghi nhận & Quản lý giao dịch tài chính
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; có ít nhất một ví tài sản đang hoạt động.
- **Dữ liệu đầu vào:** Loại giao dịch (Thu nhập / Chi tiêu / Chuyển tiền nội bộ), ví nguồn, ví đích (nếu chuyển tiền), danh mục, số tiền, ngày giao dịch, mô tả, `Idempotency-Key`.
- **Dữ liệu đầu ra:** Giao dịch ghi sổ (Posted), bút toán Nợ/Có cân bằng, số dư ví cập nhật.

**Luồng sự kiện chính (Main Flow - Ghi nhận Chi tiêu):**
1. Người dùng nhấn nút "Thêm giao dịch" và chọn loại "Chi tiêu".
2. Người dùng chọn ví thanh toán, chọn danh mục (ví dụ: Ăn uống), nhập số tiền (ví dụ: 50.000 VND), ngày phát sinh và mô tả.
3. Người dùng nhấn "Lưu giao dịch".
4. Hệ thống kiểm tra tính hợp lệ: số tiền $> 0$, ngày giao dịch không ở tương lai, ví thanh toán đủ số dư khả dụng.
5. Hệ thống áp dụng cơ chế Idempotency theo BR13 để chống trùng lặp yêu cầu mạng.
6. Hệ thống thực thi Database Transaction nguyên tử:
   - Tạo bản ghi Giao dịch ở trạng thái `Posted`.
   - Sinh dòng Nợ: Tài khoản Chi phí (`Expense Account`) và dòng Có: Ví thanh toán (`Asset Wallet`).
   - Cập nhật giảm số dư bộ đệm của ví thanh toán.
7. Hệ thống kích hoạt tác vụ kiểm tra hạn mức ngân sách SYS03.
8. Hệ thống hiển thị thông báo thành công và cập nhật số dư tức thì.

**Luồng phụ (Alternative Flows):**
* *1a. Ghi nhận Thu nhập:* Người dùng chọn loại "Thu nhập"; hệ thống ghi Nợ Ví tài sản và Có Tài khoản Thu nhập.
* *1b. Chuyển tiền giữa các ví nội bộ:* Người dùng chọn ví nguồn và ví đích (cùng chủ sở hữu); hệ thống ghi Nợ Ví đích và Có Ví nguồn; tuyệt đối không tính vào doanh số Thu nhập hay Chi phí theo BR05.
* *1c. Đảo / Hoàn tác giao dịch (Reversal):* Người dùng chọn một giao dịch Posted đã ghi sổ và nhấn "Hoàn tác"; hệ thống tạo bút toán Đảo đối ứng ngược chiều nguyên tử theo BR06 mà không xóa vật lý bản ghi cũ.
* *1d. Quản lý danh mục thu/chi:* Người dùng tự tạo thêm danh mục cá nhân mới (ví dụ: "Tiền cà phê") hoặc đổi tên danh mục chưa phát sinh giao dịch.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số dư ví không đủ:* (Chi tiêu vượt quá số dư ví tiền mặt/ngân hàng). Hệ thống từ chối ghi sổ và thông báo "Số dư ví không đủ để thực hiện giao dịch".
* *4e2. Chuyển tiền vào chính ví nguồn:* Hệ thống từ chối và yêu cầu chọn ví đích khác ví nguồn.
* *5e1. Trùng khóa Idempotency khác nội dung:* Hệ thống từ chối với mã lỗi 409 Conflict.

- **Hậu điều kiện:** Sổ kế toán kép được duy trì cân bằng tuyệt đối; số dư ví cập nhật chính xác.

---

#### UC04 - Thiết lập & Xử lý giao dịch định kỳ
- **Tác nhân:** A01 (Người dùng đã xác thực), SYS (Tiến trình lập lịch nền).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; có sẵn ví và danh mục tương ứng.
- **Dữ liệu đầu vào:** Tên khoản định kỳ, loại (Thu/Chi), số tiền, ví liên quan, danh mục, chu kỳ (Hàng tháng), ngày neo (`anchorDay`: 1 - 31), tùy chọn "Tự động ghi sổ".
- **Dữ liệu đầu ra:** Bản ghi khoản định kỳ, các giao dịch bản nháp hoặc đã ghi sổ tự động theo chu kỳ.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập mục "Giao dịch định kỳ" và nhấn "Tạo khoản định kỳ mới".
2. Người dùng nhập tên khoản (ví dụ: "Tiền thuê phòng trọ"), số tiền (3.000.000 VND), chọn loại Chi tiêu, ví thanh toán, ngày đến hạn hàng tháng (ví dụ: ngày 05) và chọn chế độ tự động ghi sổ.
3. Người dùng nhấn "Lưu".
4. Hệ thống kiểm tra dữ liệu hợp lệ và lưu bản ghi `Subscription` với ngày đến hạn kế tiếp `nextDueDate`.
5. Hệ thống hiển thị thông báo thiết lập thành công.
6. Đến chu kỳ định kỳ, tiến trình nền SYS02 quét bản ghi đến hạn:
   - Nếu bật "Tự động ghi sổ" và ví đủ tiền: tự động tạo giao dịch `Posted`, cập nhật số dư ví và gửi thông báo đã ghi sổ thành công.
   - Nếu tắt tự động ghi sổ: tự động tạo giao dịch ở trạng thái Nháp (`Draft`) và gửi thông báo nhắc nhở người dùng vào duyệt.
7. Hệ thống tự động tính và cập nhật ngày đến hạn của chu kỳ tiếp theo theo quy tắc BR09.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng tạm dừng / kích hoạt lại khoản định kỳ:* Người dùng chuyển trạng thái sang `Paused` (tiến trình nền tạm bỏ qua) hoặc `Active`.
* *1b. Xử lý tháng thiếu ngày:* Nếu ngày neo là 29, 30 hoặc 31 nhưng tháng khảo sát không đủ ngày (ví dụ tháng 2 có 28 ngày), hệ thống tự động lùi về ngày 28 và giữ nguyên ngày neo gốc cho các tháng tiếp theo theo BR09.

**Luồng ngoại lệ (Exception Flows):**
* *6e1. Ví không đủ tiền khi tự động ghi sổ:* Tiến trình nền chuyển giao dịch sang trạng thái `Draft` và gửi thông báo khẩn "Ví không đủ số dư để tự động thanh toán khoản định kỳ".

- **Hậu điều kiện:** Không bao giờ phát sinh trùng lặp giao dịch trong cùng một kỳ đến hạn.

---

#### UC05 - Thiết lập & Kiểm soát ngân sách chi tiêu
- **Tác nhân:** A01 (Người dùng đã xác thực), SYS (Tiến trình xử lý sự kiện nền).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Danh mục chi tiêu, hạn mức ngân sách tháng (`limitAmountBase > 0`), tháng áp dụng (YYYY-MM).
- **Dữ liệu đầu ra:** Bản ghi ngân sách, thanh tiến độ tỷ lệ sử dụng trực quan và thông báo cảnh báo.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập mục "Quản lý Ngân sách" và nhấn "Thiết lập ngân sách".
2. Hệ thống hiển thị biểu mẫu thiết lập.
3. Người dùng chọn danh mục chi tiêu (ví dụ: "Ăn uống"), nhập hạn mức tháng (ví dụ: "3.000.000 VND") cho tháng hiện tại và nhấn "Lưu".
4. Hệ thống kiểm tra hạn mức $> 0$ và chưa có ngân sách hoạt động cho danh mục này trong tháng.
5. Hệ thống lưu bản ghi ngân sách và tự động tính tổng chi tiêu ròng lũy kế của danh mục trong tháng:
   $$Usage = \frac{\text{Chi tiêu ròng}}{\text{Hạn mức}} \times 100\%$$
6. Khi người dùng phát sinh chi tiêu mới thuộc danh mục này, tiến trình SYS03 tính lại tỷ lệ $Usage$:
   - Nếu $80\% \le Usage < 100\%$: Hệ thống đổi màu thanh tiến độ sang Vàng và phát Thông báo Cảnh báo Vàng.
   - Nếu $Usage \ge 100\%$: Hệ thống đổi màu thanh tiến độ sang Đỏ và phát Thông báo Cảnh báo Đỏ (Vượt ngân sách).
7. Thông báo hiển thị trên thanh chuông Notification của người dùng.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng điều chỉnh hạn mức ngân sách:* Người dùng tăng/giảm hạn mức trong tháng; hệ thống tính lại ngay lập tức phần trăm sử dụng và cập nhật lại màu sắc cảnh báo tương ứng.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Hạn mức ngân sách $\le 0$:* Hệ thống báo lỗi "Hạn mức ngân sách phải lớn hơn 0".
* *4e2. Danh mục đã có ngân sách trong tháng:* Hệ thống gợi ý chỉnh sửa hạn mức của ngân sách hiện tại thay vì tạo bản ghi trùng lặp.

- **Hậu điều kiện:** Người dùng luôn nắm rõ tốc độ tiêu tiền trong tháng; hệ thống không tự ý chặn quyền chi tiêu.

---

#### UC06 - Quản lý khoản vay và nghĩa vụ nợ vi mô
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tiêu đề khoản nợ, phân loại (Đi vay / Cho vay), tên đối tác liên quan, số tiền gốc ban đầu, lãi suất (%/năm, nếu có), ngày vay, ngày đến hạn, ghi chú; tùy chọn ví nhận tiền nếu có giải ngân tiền thực tế.
- **Dữ liệu đầu ra:** Hồ sơ khoản vay `Active`, dư nợ gốc được theo dõi, Total Liabilities và Net Worth được cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập phân hệ "Microfinance" và nhấn "Tạo khoản vay / nợ mới".
2. Người dùng nhập tên khoản vay (ví dụ: "Vay mua laptop"), chọn loại "Đi vay", nhập bên cho vay ("Ngân hàng / Người thân"), số tiền gốc (15.000.000 VND), ngày bắt đầu và ngày đáo hạn.
3. Người dùng tích chọn "Nhận tiền giải ngân vào ví" và chọn ví nhận tiền ("Ví Ngân hàng").
4. Hệ thống kiểm tra số tiền gốc $> 0$ và ngày đến hạn $\ge$ ngày bắt đầu.
5. Hệ thống lưu bản ghi khoản nợ với `remainingPrincipal = 15.000.000 VND`.
6. Hệ thống tự động sinh giao dịch tài chính đối ứng: Tăng số dư Ví Ngân hàng 15.000.000 VND và Tăng khoản Nợ phải trả (Liability) 15.000.000 VND; tuyệt đối không hạch toán vào Thu nhập theo BR17.
7. Hệ thống cập nhật Total Liabilities (+15tr), Tổng tài sản (+15tr), Net Worth không đổi.
8. Hệ thống hiển thị thông báo tạo khoản nợ thành công.

**Luồng phụ (Alternative Flows):**
* *3a. Nhập khoản vay đã có từ trước (không nhận tiền mới):* Người dùng không tích chọn nhận tiền giải ngân; hệ thống chỉ ghi nhận nghĩa vụ nợ vào Total Liabilities mà không làm tăng số dư ví tiền.
* *1a. Ghi nhận khoản cho người khác vay (Lend):* Người dùng cho bạn bè vay tiền; hệ thống ghi nhận khoản phải thu, làm giảm số dư ví xuất tiền và tăng tài sản Phải thu.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số tiền gốc $\le 0$:* Hệ thống báo lỗi trường số tiền.
* *4e2. Ngày đến hạn trước ngày bắt đầu:* Hệ thống báo lỗi ngày không hợp lệ.

- **Hậu điều kiện:** Khoản nợ được quản lý chặt chẽ với đầy đủ thông tin kỳ hạn và lịch sử trả nợ tiếp nối.

---

#### UC07 - Ghi nhận thanh toán trả nợ vay
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; khoản nợ đang ở trạng thái `Active`; ví thanh toán đủ số dư.
- **Dữ liệu đầu vào:** Khoản nợ cần trả, ngày thanh toán, số tiền trả nợ gốc, số tiền lãi/phí, ví chi trả, ghi chú.
- **Dữ liệu đầu ra:** Bản ghi trả nợ, dư nợ gốc giảm dần, số dư ví giảm, trạng thái khoản vay cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn khoản nợ cần thanh toán trong danh sách nợ và nhấn "Trả nợ".
2. Hệ thống hiển thị biểu mẫu thanh toán nợ kèm dư nợ gốc hiện tại.
3. Người dùng nhập số tiền trả gốc (ví dụ: 3.000.000 VND), số tiền lãi (ví dụ: 200.000 VND), chọn ví thanh toán ("Ví Ngân hàng") và ngày trả.
4. Hệ thống kiểm tra: Tiền gốc trả $\le$ Dư nợ gốc hiện tại; Tổng tiền thanh toán (Gốc + Lãi = 3.200.000 VND) $\le$ Số dư ví chi trả.
5. Hệ thống thực thi giao dịch nguyên tử:
   - Giảm số dư Ví Ngân hàng: 3.200.000 VND.
   - Giảm dư nợ gốc khoản vay: 3.000.000 VND.
   - Hạch toán Chi phí tài chính (Expense): 200.000 VND (doanh số chi tiêu chỉ tăng 200k, không tăng 3tr tiền gốc theo BR17).
   - Lưu bản ghi lịch sử `LoanRepayment`.
6. Hệ thống tính lại dư nợ gốc còn lại: nếu dư nợ gốc $= 0$, hệ thống tự động chuyển trạng thái khoản vay sang "Đã tất toán" (`PaidOff`).
7. Hệ thống cập nhật Total Liabilities, chi phí trong kỳ và Net Worth.
8. Hệ thống hiển thị thông báo thanh toán thành công.

**Luồng phụ (Alternative Flows):**
* *3a. Tất toán toàn bộ trước hạn:* Người dùng chọn "Tất toán toàn bộ"; hệ thống tự động điền toàn bộ dư nợ gốc còn lại vào ô tiền gốc.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số tiền trả gốc vượt quá dư nợ còn lại:* Hệ thống báo lỗi "Tiền trả gốc không được vượt quá số dư nợ còn lại".
* *4e2. Ví không đủ số dư:* Hệ thống báo lỗi "Ví thanh toán không đủ số dư để trả nợ".

- **Hậu điều kiện:** Dư nợ gốc giảm chính xác; báo cáo chi phí không bị đội ảo tiền gốc.

---

#### UC08 - Quản lý tài sản tích lũy ngoài ví (Vàng)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tên tài sản, loại vàng (Nhẫn tròn trơn 9999 / Vàng miếng SJC), số lượng (chỉ), giá trị định giá quy đổi (VND), ngày định giá, mô tả.
- **Dữ liệu đầu ra:** Bản ghi tài sản vàng tích lũy, Tổng tài sản và Net Worth được cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập phân hệ "Personal Wealth" và chọn "Tài sản tích lũy (Vàng)".
2. Người dùng nhấn "Thêm tài sản tích lũy".
3. Người dùng nhập tên (ví dụ: "Nhẫn tròn trơn 9999"), chọn loại vàng, nhập số lượng (ví dụ: "2 chỉ"), nhập giá trị định giá quy đổi ("17.000.000 VND"), ngày định giá và nhấn "Lưu".
4. Hệ thống kiểm tra số lượng và giá trị định giá $\ge 0$ và ngày định giá không ở tương lai.
5. Hệ thống lưu bản ghi tài sản vàng vào cơ sở dữ liệu.
6. Hệ thống cập nhật Tổng tài sản (+17tr) và tính lại Net Worth theo BR16.
7. Hệ thống hiển thị thông báo thành công và danh mục tài sản cập nhật.

**Luồng phụ (Alternative Flows):**
* *1a. Cập nhật thị giá vàng (Revaluation):* Người dùng chọn tài sản vàng hiện có và cập nhật giá trị định giá mới theo biến động thị trường (ví dụ vàng tăng lên 18.000.000 VND); hệ thống ghi nhận lịch sử định giá mới và cập nhật Net Worth.
* *1b. Xóa tài sản khi bán vàng:* Người dùng chọn xóa tài sản vàng đã bán; hệ thống xác nhận và xóa khỏi danh mục theo dõi, trừ giá trị khỏi Total Assets.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Giá trị hoặc số lượng $< 0$:* Hệ thống báo lỗi trường nhập liệu.

- **Hậu điều kiện:** Giá trị vàng tích lũy đóng góp trực tiếp vào tiềm lực tài chính và Net Worth của người dùng mà không cần cấu hình phức tạp.

---

#### UC09 - Quản lý mục tiêu tích lũy tài chính
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tên mục tiêu, số tiền đích (`targetAmountBase > 0`), ngày thời hạn mong muốn, số tiền phân bổ tích lũy.
- **Dữ liệu đầu ra:** Bản ghi mục tiêu, thanh tiến độ %, số tiền còn thiếu và kế hoạch tiết kiệm bình quân mỗi tháng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Mục tiêu tài chính" và nhấn "Tạo mục tiêu mới".
2. Người dùng nhập tên mục tiêu (ví dụ: "Quỹ khẩn cấp 6 tháng"), số tiền cần đạt (30.000.000 VND), ngày hạn chót (sau 10 tháng) và nhấn "Lưu".
3. Hệ thống kiểm tra số tiền đích $> 0$ và ngày hạn chót ở tương lai.
4. Hệ thống tạo bản ghi mục tiêu ở trạng thái `InProgress` với số tiền hiện có ban đầu $= 0$.
5. Người dùng nhấn "Phân bổ tiền tích lũy", nhập số tiền (ví dụ: 6.000.000 VND) và nhấn "Xác nhận".
6. Hệ thống cộng số tiền vào mục tiêu (`currentAmount = 6.000.000 VND`) mà không làm tăng tổng tài sản theo BR10.
7. Hệ thống tính toán và hiển thị:
   - Tiến độ: $Progress = \frac{6.000.000}{30.000.000} \times 100\% = 20\%$.
   - Số tiền còn thiếu: $24.000.000$ VND.
   - Gợi ý mức tiết kiệm bình quân: $24.000.000 / 10 = 2.400.000$ VND/tháng.
8. Hệ thống hiển thị thông báo cập nhật thành công.

**Luồng phụ (Alternative Flows):**
* *5a. Hủy một khoản phân bổ nhập nhầm:* Người dùng chọn hủy khoản phân bổ; hệ thống trừ lại số tiền tích lũy của mục tiêu mà không ảnh hưởng đến số dư ví.
* *6a. Đạt mục tiêu sớm:* Khi số tiền tích lũy $\ge$ số tiền đích, hệ thống tự động đổi trạng thái mục tiêu sang `Achieved` (Đã hoàn thành) kèm lời chúc mừng.

**Luồng ngoại lệ (Exception Flows):**
* *3e1. Số tiền đích $\le 0$:* Hệ thống báo lỗi số tiền.
* *3e2. Thời hạn ở quá khứ:* Hệ thống báo lỗi ngày hết hạn.

- **Hậu điều kiện:** Mục tiêu tích lũy được theo dõi liên tục, tạo động lực tài chính cho người dùng.

---

#### UC10 - Xem Dashboard sức khỏe tài chính & Net Worth
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Khoảng thời gian xem (Mặc định tháng hiện tại), tùy chọn bộ lọc.
- **Dữ liệu đầu ra:** Màn hình Dashboard trực quan toàn diện gồm các chỉ số tài chính, biểu đồ xu hướng và cảnh báo.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng đăng nhập hoặc nhấn vào mục "Dashboard".
2. Hệ thống truy vấn đồng thời dữ liệu tài chính thời gian thực của người dùng:
   - Tổng tài sản (Total Assets): Tổng số dư các ví tài sản + Giá trị vàng tích lũy.
   - Tổng nợ (Total Liabilities): Tổng số dư ví nợ + Dư nợ gốc các khoản vay vi mô.
   - Tài sản ròng (Net Worth): $\text{Total Assets} - \text{Total Liabilities}$.
   - Dòng tiền trong tháng: Tổng thu nhập, Tổng chi phí, Net Cash Flow.
   - Tỷ lệ tiết kiệm (Saving Rate) theo BR18.
3. Hệ thống vẽ biểu đồ đường biến động Net Worth theo thời gian và biểu đồ tròn cơ cấu tài sản/chi tiêu.
4. Hệ thống hiển thị danh sách các ngân sách chi tiêu gần chạm ngưỡng và các khoản nợ sắp đến hạn.
5. Người dùng xem toàn bộ sức khỏe tài chính của mình trên một giao diện thống nhất.

**Luồng phụ (Alternative Flows):**
* *2a. Người dùng thay đổi kỳ khảo sát sang Tháng trước hoặc Năm:* Hệ thống tính toán lại các chỉ số dòng tiền và tỷ lệ tiết kiệm theo kỳ được chọn.

**Luồng ngoại lệ (Exception Flows):**
* *2e1. Kỳ khảo sát không có thu nhập (Thu nhập $\le 0$):* Hệ thống hiển thị Saving Rate là "N/A" an toàn theo BR18, không phát sinh lỗi chia cho 0.

- **Hậu điều kiện:** Dữ liệu hiển thị trung thực tiềm lực tài chính cá nhân; không làm thay đổi trạng thái cơ sở dữ liệu.

---

#### UC11 - Báo cáo thống kê & Xuất dữ liệu CSV
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tiêu chí lọc: khoảng ngày (Từ ngày - Đến ngày), ví tài khoản, danh mục thu/chi, loại giao dịch; Lệnh xuất tệp CSV.
- **Dữ liệu đầu ra:** Danh sách giao dịch phân trang, thông tin đối soát số dư và tệp CSV tải về.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập mục "Báo cáo & Sổ cái".
2. Người dùng chọn khoảng thời gian cần tra cứu (ví dụ: từ 01/10/2026 đến 31/10/2026) và nhấn "Tìm kiếm".
3. Hệ thống hiển thị danh sách các giao dịch thỏa mãn kèm phân trang, tổng thu, tổng chi và dòng tiền thuần của kỳ.
4. Người dùng có thể nhấn vào một giao dịch để xem chi tiết các dòng bút toán Nợ/Có đối ứng và snapshot tỷ giá.
5. Người dùng nhấn nút "Xuất file CSV".
6. Hệ thống trích xuất dữ liệu, thực hiện khử ký tự công thức nguy hiểm để chống CSV Injection theo FR12.04.
7. Hệ thống tạo tệp CSV mã hóa UTF-8 kèm ký tự BOM và truyền tải tệp về trình duyệt của người dùng.
8. Người dùng mở tệp bằng Microsoft Excel và xem dữ liệu hiển thị tiếng Việt chuẩn xác.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng thực hiện đối soát số dư:* Người dùng nhấn "Đối soát sổ cái"; hệ thống tự động so sánh số dư đệm của ví với tổng lũy kế các dòng sổ cái từ đầu đến hiện tại; hiển thị thông báo "Số dư sổ cái hoàn toàn khớp đúng 100%".

**Luồng ngoại lệ (Exception Flows):**
* *2e1. Khoảng ngày không hợp lệ (Từ ngày > Đến ngày):* Hệ thống báo lỗi và yêu cầu chọn lại.
* *3e1. Không có dữ liệu trong khoảng thời gian:* Hệ thống hiển thị bảng trống kèm thông báo phù hợp.

- **Hậu điều kiện:** Tệp CSV được trích xuất an toàn và minh bạch.

---

#### UC12 - Quản trị người dùng & Dashboard thống kê vĩ mô (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với tài khoản có vai trò `Admin`.
- **Dữ liệu đầu vào:** Yêu cầu xem thống kê, từ khóa tìm kiếm người dùng, mã người dùng cần khóa/mở khóa, lý do bắt buộc.
- **Dữ liệu đầu ra:** Màn hình Dashboard vĩ mô, danh sách người dùng phân trang, trạng thái tài khoản cập nhật, nhật ký kiểm toán hành chính.

**Luồng sự kiện chính (Main Flow):**
1. Quản trị viên truy cập trang quản trị Admin.
2. Hệ thống hiển thị **Dashboard thống kê vĩ mô**:
   - Tổng số lượng người dùng đã đăng ký.
   - Số lượng người dùng mới trong tháng hiện tại.
   - Số lượng tài khoản đang hoạt động (`Active`) và đang bị khóa (`Locked`).
   - Tỷ lệ phân bố đơn vị tiền cơ sở (`VND`, `USD`, `EUR`).
   - *Lưu ý:* Tuyệt đối không hiển thị số dư, ví tiền hay giao dịch cá nhân của bất kỳ người dùng nào theo BR11.
3. Quản trị viên chuyển sang tab "Danh sách người dùng".
4. Quản trị viên tìm kiếm một tài khoản vi phạm chính sách và chọn thao tác "Khóa tài khoản".
5. Hệ thống hiển thị hộp thoại yêu cầu nhập lý do khóa bắt buộc.
6. Quản trị viên nhập lý do và nhấn "Xác nhận khóa".
7. Hệ thống kiểm tra tài khoản bị khóa không phải là tài khoản Admin duy nhất của hệ thống theo FR14.05.
8. Hệ thống cập nhật trạng thái sang `Locked` và thu hồi toàn bộ token phiên đăng nhập của người dùng đó.
9. Hệ thống ghi nhật ký Admin Audit Log (thời điểm, mã Admin, mã User bị khóa, lý do).
10. Hệ thống hiển thị thông báo khóa thành công.

**Luồng phụ (Alternative Flows):**
* *4a. Quản trị viên mở khóa tài khoản:* Quản trị viên chọn tài khoản đang bị khóa và nhấn "Mở khóa"; hệ thống chuyển trạng thái sang `Active` và cho phép người dùng đăng nhập lại bình thường.

**Luồng ngoại lệ (Exception Flows):**
* *6e1. Không nhập lý do khóa:* Hệ thống chặn thao tác và yêu cầu nhập lý do bắt buộc.
* *7e1. Cố gắng khóa Admin cuối cùng:* Hệ thống từ chối với thông báo "Không thể vô hiệu hóa tài khoản Quản trị viên duy nhất còn lại".

- **Hậu điều kiện:** Tài khoản vi phạm bị chặn truy cập ngay lập tức; quyền riêng tư dữ liệu cá nhân của người dùng được bảo vệ tuyệt đối.

---

#### UC13 - Cấu hình hệ thống & Phát thông báo người dùng (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với quyền `Admin`.
- **Dữ liệu đầu vào:** Danh mục thu/chi mặc định, tham số hệ thống (ngưỡng cảnh báo ngân sách 80%), nội dung thông báo phát thanh (tiêu đề, nội dung, mức độ ưu tiên: Info / Warning / Maintenance).
- **Dữ liệu đầu ra:** Cấu hình mới có hiệu lực, thông báo broadcast được gửi đến toàn bộ người dùng.

**Luồng sự kiện chính (Main Flow - Phát thông báo):**
1. Quản trị viên truy cập mục "Thông báo hệ thống & Cấu hình".
2. Quản trị viên nhấn nút "Tạo thông báo phát thanh mới".
3. Quản trị viên nhập Tiêu đề (ví dụ: "Bảo trì hệ thống định kỳ"), Nội dung (ví dụ: "Hệ thống sẽ nâng cấp máy chủ lúc 00:00 - 01:00 đêm nay"), chọn mức độ ưu tiên ("Bảo trì / Maintenance").
4. Quản trị viên nhấn "Phát thông báo cho toàn bộ người dùng".
5. Hệ thống kiểm tra dữ liệu đầu vào không để trống.
6. Hệ thống tạo bản ghi `SystemAnnouncement` và đẩy thông báo vào hàng đợi Notification của toàn bộ người dùng đang hoạt động.
7. Khi người dùng đăng nhập hoặc mở ứng dụng, chuông thông báo (Notification Center) sáng lên hiển thị thông báo mới từ Quản trị viên.
8. Hệ thống hiển thị thông báo cho Admin xác nhận đã gửi thành công.

**Luồng phụ (Alternative Flows):**
* *1a. Cấu hình danh mục thu/chi mặc định:* Quản trị viên thêm/sửa bộ danh mục hệ thống chuẩn (Ăn uống, Đi lại, Lương...); khi người dùng mới đăng ký qua UC01, hệ thống tự động sao chép bộ danh mục này cho người dùng.
* *1b. Cấu hình tham số vận hành:* Quản trị viên cập nhật ngưỡng cảnh báo mặc định hoặc thời gian hết hạn phiên token; hệ thống áp dụng cấu hình mới cho các yêu cầu tiếp theo.

**Luồng ngoại lệ (Exception Flows):**
* *5e1. Tiêu đề hoặc nội dung thông báo để trống:* Hệ thống báo lỗi trường bắt buộc.

- **Hậu điều kiện:** Toàn bộ người dùng nhận được thông báo vận hành; các tham số hệ thống được quản trị tập trung.

---

### 4.3 Kịch bản xử lý nền (Background Tasks / Scheduled Services)

| Mã tác vụ | Kích hoạt & Tần suất | Luồng xử lý nghiệp vụ | Kết quả kỳ vọng & Xử lý lỗi |
| :---: | :--- | :--- | :--- |
| **SYS01** | Lịch tự động: 00:05 hàng ngày hoặc khi khởi động. | Gửi yêu cầu đến IF02 lấy tỷ giá mới nhất; kiểm tra giá trị $> 0$, thời điểm hợp lệ; lưu phiên bản tỷ giá mới. Không làm thay đổi snapshot của giao dịch cũ. | Thành công có tỷ giá mới. Thất bại: thử lại sau 1, 5, 15 phút rồi chuyển sang mỗi giờ; quá 24h cảnh báo Admin và chặn ghi sổ ngoại tệ mới. |
| **SYS02** | Lịch tự động: mỗi 5 phút quét một lần. | Quét các lịch định kỳ hoạt động có $nextDueDate \le \text{thời điểm hiện tại}$ chưa xử lý. Nhận khóa `(subscriptionId, dueDate)`; tạo bản nháp hoặc tự ghi sổ nếu người dùng bật; cập nhật ngày đến hạn kế tiếp theo BR09. | Mỗi kỳ đến hạn xử lý đúng một lần. Lỗi số dư: giữ bản ghi chờ và thông báo người dùng; lỗi tạm thời: thử lại với cùng khóa Idempotency. |
| **SYS03** | Lịch tự động: 08:00 sáng hàng ngày & Hướng sự kiện giao dịch. | Quét danh mục các khoản vay/nợ vi mô đang `Active` có ngày đến hạn trả nợ trong vòng 3 ngày tới để gửi thông báo nhắc nợ; đồng thời lúc 23:59:59 chụp snapshot Net Worth phục vụ vẽ biểu đồ xu hướng. | Người dùng nhận nhắc nợ kịp thời; biểu đồ Net Worth có đầy đủ điểm dữ liệu lịch sử liên tục. |

---

### 4.4 Ma trận hạch toán kế toán kép minh họa (Ledger Posting Matrix)

| Nghiệp vụ tài chính phát sinh | Dòng ghi Nợ (Debit) | Dòng ghi Có (Credit) | Tác động Tài sản ròng (Net Worth) | Ghi chú nghiệp vụ |
| :--- | :--- | :--- | :---: | :--- |
| **1. Mở ví tiền mặt 2.000.000 VND** | Ví tiền mặt: 2.000.000 | Vốn chủ đầu kỳ: 2.000.000 | Tăng 2.000.000 VND | Thu/Chi = 0; vốn đối ứng ban đầu. |
| **2. Nhận lương 10.000.000 VND vào NH** | Ví Ngân hàng: 10.000.000 | Thu nhập lương: 10.000.000 | Tăng 10.000.000 VND | Doanh số thu nhập tăng 10.000.000 VND. |
| **3. Chi tiền ăn uống 100.000 VND từ ví** | Chi phí ăn uống: 100.000 | Ví tiền mặt: 100.000 | Giảm 100.000 VND | Doanh số chi tiêu tăng 100.000 VND. |
| **4. Chuyển khoản 1.000.000 VND sang ví MoMo** | Ví MoMo: 1.000.000 | Ví Ngân hàng: 1.000.000 | Không đổi (0 VND) | Chuyển nội bộ; Thu/Chi = 0. |
| **5. Nhận tiền vay 10.000.000 VND vào NH** | Ví Ngân hàng: 10.000.000 | Nợ phải trả (Vay): 10.000.000 | Không đổi (0 VND) | Tài sản tăng 10tr, Nợ tăng 10tr; Thu = 0. |
| **6. Trả nợ vay: 2.000.000 gốc + 100.000 lãi** | Nợ phải trả: 2.000.000<br>Chi phí lãi vay: 100.000 | Ví Ngân hàng: 2.100.000 | Giảm 100.000 VND | Tài sản giảm 2,1tr; Nợ giảm 2tr; Chi = 100k. |
| **7. Mua 1 chỉ vàng 8.500.000 VND từ ví NH** | Tài sản tích lũy (Vàng): 8.500.000 | Ví Ngân hàng: 8.500.000 | Không đổi (0 VND) | Hoán đổi cấu trúc tài sản (Tiền mặt sang Vàng); Thu/Chi = 0. |
| **8. Đảo khoản chi ăn uống 100.000 VND** | Ví tiền mặt: 100.000 | Chi phí ăn uống: 100.000 | Tăng 100.000 VND | Bút toán đảo đối ứng; giảm chi ròng. |
| **9. Đổi 100 USD (giá sổ 2.500.000) lấy 2.480.000 VND** | Ví VND: 2.480.000<br>Lỗ tỷ giá: 20.000 | Ví USD: 100 USD (Quy đổi Base: 2.500.000) | Giảm 20.000 VND | Cân bằng tiền cơ sở; ví USD hết sạch số dư. |


---

<a id="phan-5"></a>

## 5. Yêu cầu phi chức năng (Non-Functional Requirements - NFR)

Toàn bộ các yêu cầu phi chức năng được chuẩn hóa và lượng hóa theo tiêu chuẩn quốc tế **ISO/IEC 25010:2023** (Systems and software Quality Requirements and Evaluation - SQRE) trên môi trường kiểm thử tiêu chuẩn (E1: Mạng giả lập trễ 100ms; D1: Cơ sở dữ liệu mẫu 50.000 dòng; L1: 50 người dùng đồng thời).

### NFR01 - Hiệu năng và Thời gian đáp ứng (Performance Efficiency)
- **NFR01.01 (Thời gian phản hồi API):** $95\%$ các yêu cầu API đọc (Xem số dư ví, tra cứu danh mục, xem Dashboard) phải hoàn tất xử lý trong thời gian **$T_{resp} \le 200\text{ ms}$**; các yêu cầu ghi sổ kép có khóa giao dịch và kiểm tra Idempotency phải hoàn tất trong **$T_{write} \le 500\text{ ms}$** trên môi trường D1/L1.
- **NFR01.02 (Tải dữ liệu ban đầu):** Thời gian hiển thị nội dung có ý nghĩa đầu tiên (First Contentful Paint - FCP) của giao diện Web trên máy khách không vượt quá **$1.5\text{ giây}$** qua kết nối băng thông tiêu chuẩn.
- **NFR01.03 (Khả năng chịu tải đồng thời):** Hệ thống phải duy trì tỷ lệ lỗi nhỏ hơn **$0.1\%$** khi chịu tải 50 yêu cầu đồng thời (50 concurrent users) thực hiện ghi nhận giao dịch liên tục.

### NFR02 - Bảo mật và Toàn vẹn thông tin (Security)
- **NFR02.01 (Mã hóa đường truyền và lưu trữ):** 100% kết nối giữa máy khách và máy chủ bắt buộc thực thi qua giao thức HTTPS (TLS 1.3). Mật khẩu người dùng bắt buộc được băm bằng thuật toán Argon2id hoặc bcrypt với Cost Factor $\ge 12$ kèm Salt ngẫu nhiên tối thiểu 16 bytes.
- **NFR02.02 (Bảo vệ phiên truy cập):** Phiên truy cập sử dụng Token JWT có chữ ký số HMAC-SHA256 hoặc RSA-256; Access Token có thời hạn tối đa 60 phút; Refresh Token được lưu trữ an toàn trong HttpOnly, Secure, SameSite Cookie.
- **NFR02.03 (Phòng chống lỗ hổng OWASP Top 10):** Hệ thống phải triệt tiêu 100% các nguy cơ: SQL Injection (bắt buộc sử dụng Parameterized Query / ORM), XSS (thoát mã ký tự HTML/JS trên giao diện), CSRF (kiểm tra token CSRF hoặc cơ chế SameSite) và CSV Injection (FR12.04).
- **NFR02.04 (Bảo vệ quyền riêng tư theo BR11):** Cơ chế phân quyền RBAC phải bảo đảm cách ly 100% dữ liệu tài chính của người dùng; Quản trị viên không thể xem chi tiết số dư hay giao dịch cá nhân.

### NFR03 - Độ tin cậy và Tính toàn vẹn dữ liệu (Reliability & Consistency)
- **NFR03.01 (Toàn vẹn kế toán kép):** Tính cân bằng sổ kế toán kép $\sum \text{DebitBase} = \sum \text{CreditBase}$ phải đạt tỷ lệ chính xác tuyệt đối **$100.000\%$**. Bất kỳ phát hiện mất cân bằng nào phải kích hoạt Rollback tự động ngay lập tức.
- **NFR03.02 (Tính khả dụng):** Độ sẵn sàng của hệ thống (Availability) đạt tối thiểu **$99.5\%$** trong thời gian vận hành kiểm thử.
- **NFR03.03 (Khôi phục sau sự cố RPO & RTO):** Điểm phục hồi dữ liệu mục tiêu (RPO) $\le 24$ giờ; Thời gian phục hồi dịch vụ mục tiêu (RTO) $\le 60$ phút khi khôi phục cơ sở dữ liệu.

### NFR04 - Khả năng bảo trì và Tiến hóa (Maintainability)
- **NFR04.01 (Độ bao phủ kiểm thử):** Tỷ lệ bao phủ mã nguồn (Code Coverage) của bộ kiểm thử tự động đạt tối thiểu **$80\%$** đối với Tầng Nghiệp vụ (Domain/Business Layer) và Tầng Ứng dụng (Application Services).
- **NFR04.02 (Định dạng nhật ký chuẩn hóa):** Nhật ký hệ thống (Application Logs) phải được ghi nhận dưới định dạng JSON có cấu trúc chuẩn gồm: `timestamp`, `level`, `traceId`, `userId`, `action`, `message`, `executionTimeMs` nhằm hỗ trợ điều tra sự cố nhanh chóng.

### NFR05 - Khả năng tương thích và Đa nền tảng (Compatibility)
- **NFR05.01 (Tương thích trình duyệt):** Giao diện Web phải hiển thị đúng thiết kế và hoạt động ổn định trên 100% các trình duyệt hiện đại: Google Chrome (bản $\ge 115$), Mozilla Firefox (bản $\ge 115$), Microsoft Edge (bản $\ge 115$) và Apple Safari (bản $\ge 16$).
- **NFR05.02 (Thiết kế đáp ứng Responsive):** Giao diện tự động tối ưu hiển thị trên các kích thước màn hình phổ biến: Mobile (375px - 430px), Tablet (768px - 1024px) và Desktop (1280px - 1920px).

### NFR06 - Tính khả dụng và Trải nghiệm người dùng (Usability)
- **NFR06.01 (Tối ưu hóa thao tác nhập liệu):** Số thao tác nhấn (clicks) để ghi nhận một giao dịch chi tiêu thông thường không vượt quá **4 bước**.
- **NFR06.02 (Thông báo lỗi thân thiện):** Toàn bộ thông báo lỗi trả về cho người dùng phải bằng tiếng Việt rõ ràng, dễ hiểu, chỉ rõ trường bị sai và hướng dẫn cách khắc phục cụ thể; không hiển thị vết ngăn xếp kỹ thuật (Stack Trace) ra giao diện người dùng.

### NFR07 - Ràng buộc pháp lý và Chuẩn mực kế toán (Compliance)
- **NFR07.01 (Bảo mật thông tin cá nhân):** Tuân thủ Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân: người dùng có toàn quyền kiểm tra, chỉnh sửa và yêu cầu xóa thông tin tài khoản cá nhân.
- **NFR07.02 (Chuẩn mực tiền tệ Việt Nam):** Đồng tiền cơ sở VND hiển thị dấu phân cách hàng nghìn bằng dấu chấm (ví dụ: `1.000.000 đ`) và không có phần thập phân theo tập quán kế toán Việt Nam.

---

<a id="phan-6"></a>

## 6. Ma trận truy vết và Tiêu chí nghiệm thu (Traceability & Acceptance)

### 6.1 Ma trận truy vết hai chiều (Bidirectional Traceability Matrix)

| Yêu cầu người dùng (UR) | Yêu cầu chức năng (FR) | Use Case / Tác vụ nền | Quy tắc nghiệp vụ (BR) | Yêu cầu phi chức năng (NFR) | Mã ca kiểm thử |
| :---: | :---: | :---: | :---: | :---: | :---: |
| **UR01** | FR01, FR15 | UC01 | BR11 | NFR02, NFR06 | TC-F01, TC-U01 |
| **UR02** | FR02, FR03, FR04 | UC02, UC03 | BR01, BR02, BR03, BR04, BR05, BR06, BR12, BR13, BR15 | NFR01, NFR03, NFR07 | TC-F02, TC-F03, TC-U02, TC-U03 |
| **UR03** | FR05 | UC05, SYS03 | BR07, BR08 | NFR01, NFR06 | TC-F05, TC-U05, TC-S03 |
| **UR04** | FR06 | UC04, SYS02 | BR09, BR12, BR13 | NFR03, NFR04, NFR07 | TC-F06, TC-U04, TC-S02 |
| **UR05** | FR07 | UC09 | BR10 | NFR02, NFR06 | TC-F07, TC-U09 |
| **UR06** | FR08, FR10 | UC08, UC10 | BR02, BR16 | NFR01, NFR03, NFR06 | TC-F08, TC-U08, TC-U10 |
| **UR07** | FR09 | UC06, UC07, SYS03 | BR05, BR17 | NFR02, NFR03, NFR06 | TC-F09, TC-U06, TC-U07 |
| **UR08** | FR10, FR11 | UC10 | BR14, BR16, BR18 | NFR01, NFR06 | TC-F10, TC-F11, TC-U10 |
| **UR09** | FR12 | UC11 | BR01, BR11, BR14 | NFR01, NFR02, NFR06 | TC-F12, TC-U11 |
| **UR10** | FR13, FR14 | UC12, UC13 | BR07, BR08, BR11 | NFR02, NFR04, NFR06 | TC-F13, TC-F14, TC-U12, TC-U13 |
| **UR11** | FR15, Toàn bộ | Toàn bộ UC bảo vệ | BR11, BR13 | NFR02, NFR03, NFR07 | TC-F15, TC-N02, TC-N03 |
| **UR12** | Toàn bộ FR | Toàn bộ UC | Toàn bộ BR | NFR01 - NFR07 | Toàn bộ Test Cases |

---

### 6.2 Ca kiểm thử mẫu tiêu biểu (Representative Test Cases)

| Mã ca kiểm thử | Loại kịch bản | Mục đích kiểm thử | Dữ liệu đầu vào & Kích thích | Kết quả kỳ vọng |
| :---: | :---: | :--- | :--- | :--- |
| **TC-F01.01-P** | Luồng chính | Kiểm tra đăng ký tài khoản mới kèm tiền cơ sở | Đăng ký với email hợp lệ, mật khẩu mạnh, tiền cơ sở VND. | Hệ thống tạo tài khoản `Active`, gán danh mục mặc định, khóa tiền cơ sở ban đầu. |
| **TC-F03.01-P** | Luồng chính | Kiểm tra cân bằng sổ kế toán kép (Double-Entry) | Ghi nhận chi tiêu 150.000 VND từ ví tiền mặt cho Ăn uống. | Sinh đúng dòng Nợ Chi phí 150k và Có Ví 150k; $\sum 	ext{Debit} = \sum 	ext{Credit} = 150.000$ VND; số dư ví trừ 150k. |
| **TC-F03.04-E** | Luồng ngoại lệ | Kiểm tra chống trùng lặp Idempotency | Gửi 5 yêu cầu HTTP POST cùng `Idempotency-Key` tạo chi tiêu 200.000 VND. | Chỉ tạo đúng 1 bản ghi giao dịch; ví chỉ bị trừ 200.000 VND đúng 1 lần; 4 yêu cầu sau nhận mã 200 kèm kết quả cũ. |
| **TC-F03.02-E** | Luồng ngoại lệ | Kiểm tra tính nguyên tử (Rollback khi có lỗi) | Giả lập lỗi ngắt kết nối DB sau khi tạo dòng Nợ chi phí nhưng trước khi tạo dòng Có ví. | Toàn bộ giao dịch bị Rollback sạch sẽ; không có bút toán mồ côi; số dư ví không đổi; ghi log lỗi kèm `requestId`. |
| **TC-F05.03-P** | Luồng chính | Kiểm tra cảnh báo vượt ngưỡng ngân sách chi tiêu | Ngân sách Ăn uống 2tr. Đã chi 1,5tr. Chi thêm 200k (tổng 1,7tr = 85%). | Hệ thống gửi thông báo Cảnh báo Vàng (85%) đến chuông thông báo của người dùng; chi thêm vượt 2tr phát thông báo Đỏ. |
| **TC-F08.04-P** | Luồng chính | Kiểm tra tính toán Tài sản ròng (Net Worth) | Người dùng có Ví NH 15tr, Tiết kiệm 20tr, 1 chỉ vàng nhẫn 8.5tr, Khoản nợ vay còn 10tr. | Hệ thống tính chính xác: Total Assets = 43.5tr; Total Liabilities = 10tr; Net Worth = 33.5tr. |
| **TC-F09.03-P** | Luồng chính | Kiểm tra phân rã trả nợ vi mô (Gốc và Lãi) | Khoản vay 10tr. Trả kỳ 1: 2tr gốc + 150k lãi từ Ví ngân hàng số dư 5tr. | Ví NH còn 2.850.000 VND; Dư nợ khoản vay còn 8.000.000 VND; Chi phí ghi nhận 150.000 VND; Chi tiêu không bị trừ 2tr gốc. |
| **TC-F11.03-E** | Luồng ngoại lệ | Kiểm tra tính Saving Rate khi không có thu nhập | Kỳ khảo sát có Thu nhập = 0 VND, Chi tiêu = 1.500.000 VND. | Cash Flow = -1.500.000 VND; Saving Rate hiển thị "N/A" an toàn (không bị lỗi chia cho 0 `DivideByZeroException`). |
| **TC-F12.04-E** | Luồng ngoại lệ | Kiểm tra chống CSV Formula Injection | Tạo giao dịch có mô tả: `=cmd|' /C calc'!A0`. Thực hiện xuất báo cáo CSV. | Tệp CSV tải về có trường mô tả được trung hòa ký tự (tiền tố dấu nháy đơn `'=cmd...`), mở bằng Excel không tự kích hoạt lệnh. |
| **TC-F14.03-P** | Luồng chính | Kiểm tra Quản trị viên khóa tài khoản vi phạm | Admin chọn tài khoản UserA, nhập lý do "Spam giao dịch rác" và nhấn Khóa. | Trạng thái UserA chuyển sang `Locked`; toàn bộ token phiên của UserA bị thu hồi; yêu cầu API tiếp theo của UserA bị từ chối 401. |

---

### 6.3 Tiêu chí nghiệm thu sản phẩm pha Elaboration (Acceptance Criteria)

1. **Hoàn thiện đặc tả yêu cầu:** $100\%$ trong số 13 Use Cases cốt lõi và 15 Yêu cầu chức năng (FR01 - FR15) đã được mô hình hóa và phê duyệt đầy đủ; loại bỏ hoàn toàn các chức năng ngoài phạm vi (OCR, Backup web UI).
2. **Khung kiến trúc thực thi (Architectural Baseline):**
   - Đã hiện thực hóa khung mã nguồn kết nối thông suốt từ Presentation Layer (Web UI) qua Application/Domain Layer đến Database Layer.
   - Thử nghiệm thành công kịch bản cốt lõi: Đăng ký $\rightarrow$ Mở ví $\rightarrow$ Ghi thu chi kép $\rightarrow$ Tính Net Worth $\rightarrow$ Cập nhật Dashboard mà không phát sinh lỗi kiến trúc.
3. **Triệt tiêu rủi ro trọng yếu (Risk Retirement):**
   - Rủi ro sai lệch số dư đã được triệt tiêu hoàn toàn thông qua cơ chế giao dịch ACID và khóa Idempotency.
   - Rủi ro đa tiền tệ và làm tròn đã được kiểm chứng thông qua thuật toán quy đổi snapshot tỷ giá và kiểm tra cân bằng sổ kép.
   - Rủi ro bảo mật dữ liệu đa người dùng và quyền riêng tư Admin đã được kiểm chứng thông qua ma trận kiểm thử phân quyền 100%.
4. **Chất lượng kiểm thử:** Bộ kiểm thử tự động (Unit Test & Integration Test) bao phủ tối thiểu $80\%$ mã nguồn nghiệp vụ cốt lõi; $100\%$ các ca kiểm thử sổ kép, rollback và bảo mật đều đạt kết quả Pass.

---

### 6.4 Danh mục quyết định kiến trúc và kỹ thuật đã chốt (Architectural Decisions)

| Hạng mục quyết định | Phương án lựa chọn pha Elaboration | Cơ sở lý luận & Ràng buộc kỹ thuật |
| :--- | :--- | :--- |
| **Phong cách kiến trúc** | Layered Architecture + Modular Monolith | Phù hợp quy mô đồ án 4 sinh viên, dễ triển khai, dễ kiểm thử toàn diện, tránh sự phức tạp phân tán không cần thiết của Microservices. |
| **Hệ quản trị CSDL** | PostgreSQL (hoặc MySQL 8.0+) | Hỗ trợ chuẩn giao dịch ACID mạnh mẽ, khóa mức dòng (Row-level lock), kiểu dữ liệu `DECIMAL` chính xác cho tiền tệ và hỗ trợ lưu trữ JSON. |
| **Chiến lược tính số dư** | Hybrid: Sổ cái bất biến + Số dư bộ đệm đối soát | Giao dịch ghi vào sổ cái Nợ/Có bất biến; duy trì số dư lưu đệm trên bảng Ví để phản hồi tức thì dưới 200ms; đối soát định kỳ để bảo đảm tính toàn vẹn. |
| **Cơ chế chống trùng lặp** | Idempotency Key trên Header HTTP | Bắt buộc client gửi khóa UUID cho mỗi yêu cầu ghi sổ; lưu trạng thái khóa trong cùng transaction của giao dịch. |
| **Quy định tỷ giá ngoại tệ** | Snapshot tại thời điểm giao dịch | Khóa tỷ giá quy đổi tại thời điểm commit; không đánh giá lại sổ sách tự động theo giá thị trường hàng ngày; tách riêng lãi/lỗ tỷ giá khi tất toán. |
| **Phân định nợ vi mô** | Phân rã dòng tiền Trả nợ gốc vs Lãi/phí | Bảo đảm tuân thủ nguyên lý kế toán: tiền trả nợ gốc không được tính vào chi phí chi tiêu cá nhân để tránh làm sai lệch báo cáo dòng tiền và ngân sách. |
| **Phạm vi tài sản ngoài ví** | Giới hạn theo dõi Vàng tích trữ | Loại bỏ bất động sản và xe cộ để tránh bài toán khấu hao và định giá bất khả thi; giữ lại vàng nhẫn/SJC có thị giá rõ ràng và thanh khoản cao. |
| **Phạm vi quản trị Admin** | Quản lý người dùng, Dashboard vĩ mô & Broadcast | Cắt giảm hoàn toàn tính năng Backup/Restore trên Web UI (chuyển sang DevOps script); tập trung vào quản lý tài khoản và phát thông báo vận hành. |

---
**KẾT THÚC TÀI LIỆU ĐẶC TẢ SRS PHA ELABORATION - NHÓM VOZ**
