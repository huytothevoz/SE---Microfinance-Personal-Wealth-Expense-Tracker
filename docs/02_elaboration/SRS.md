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
| <a id="ur01"></a>**UR01** | Tạo tài khoản an toàn, bảo vệ quyền riêng tư dữ liệu tài chính. | Đảm bảo chỉ chủ tài khoản mới được truy cập và sửa đổi số liệu nhạy cảm. | FR01, FR15 | UC01 |
| <a id="ur02"></a>**UR02** | Ghi nhận & theo dõi tiền ở nhiều ví / tài khoản nguồn tiền (tiền mặt, tài khoản ngân hàng, ví điện tử...). | Nắm giữ tiền ở tiền mặt, ngân hàng, ví điện tử mà không bị thất thoát, có dấu vết kiểm toán khi sửa đổi. | FR02, FR03, FR04 | UC02, UC03 |
| <a id="ur03"></a>**UR03** | Thiết lập hạn mức ngân sách và nhận cảnh báo sớm trước khi bội chi. | Ngăn chặn hành vi chi tiêu quá tay theo từng nhóm nhu cầu (Ăn uống, Giải trí, Mua sắm...). Nên có danh mục ưu tiên chi tiêu. | FR05 | UC05 |
| <a id="ur04"></a>**UR04** | Tự động hóa ghi nhận các khoản thu/chi định kỳ hàng tháng. | Tránh quên thanh toán hóa đơn điện/nước/internet, tiền thuê nhà hoặc dịch vụ đăng ký. Thông báo trước cho người dùng khi sắp tới các khoản thường niên. | FR06 | UC04 |
| <a id="ur05"></a>**UR05** | Thiết lập các mục tiêu tích lũy và tính toán kế hoạch tiết kiệm khả thi. | Giúp người dùng biết cần tiết kiệm bao nhiêu mỗi tháng và bao lâu sẽ đạt mục tiêu (mua máy tính, quỹ khẩn cấp...). | FR07 | UC09 |
| <a id="ur06"></a>**UR06** | Theo dõi toàn diện danh mục tài sản (tiền, tiết kiệm, vàng), các khoản nợ và tính toán tài sản ròng (Net Worth). | Người dùng biết rõ giá trị thực sự mình sở hữu sau khi trừ hết các khoản nợ, theo dõi sự tăng trưởng tài sản. | FR08, FR10 | UC08, UC10 |
| <a id="ur07"></a>**UR07** | Quản lý chi tiết từng khoản vay, khoản nợ nhỏ và theo dõi lịch thanh toán (Microfinance). | Nắm rõ nợ ai, nợ bao nhiêu, lịch trả nợ, số tiền đã trả và phân định rõ tiền gốc so với tiền lãi/phí. Người dùng có thể theo dõi được lãi gộp sau khi hoàn thành tất toán. | FR09 | UC06, UC07 |
| <a id="ur08"></a>**UR08** | Xem Dashboard trực quan về sức khỏe tài chính: Net Worth, Cash Flow, Saving Rate. | Cung cấp góc nhìn toàn cảnh về tình hình tài chính trong một màn hình, biết tài chính đang tốt lên hay xấu đi. | FR10, FR11 | UC10 |
| <a id="ur09"></a>**UR09** | Tra cứu lịch sử, đối soát sai lệch số dư và xuất báo cáo tài chính sang tệp CSV. | Cho phép người dùng kiểm tra lại chi tiết từng giao dịch và xuất ra CSV để tự lưu trữ hoặc phân tích sâu. | FR12 | UC11 |
| <a id="ur10"></a>**UR10** | Quản trị viên quản lý tài khoản người dùng, xem thống kê vĩ mô và phát thông báo hệ thống. | Đảm bảo tính an toàn hệ thống, khóa tài khoản vi phạm, gửi thông báo vận hành/bảo trì cho người dùng. <br>TUYỆT ĐỐI KHÔNG ĐƯỢC PHÉP TRUY CẬP THÔNG TIN TÀI CHÍNH NGƯỜI DÙNG. | FR13, FR14 | UC12, UC13 |
| <a id="ur11"></a>**UR11** | Vận hành hệ thống ổn định, kiểm soát truy cập và bảo mật dữ liệu đa người dùng. | Đảm bảo tính sẵn sàng cao, cô lập dữ liệu tuyệt đối giữa các người dùng cá nhân. | FR15 | Toàn bộ UC |
| <a id="ur12"></a>**UR12** | Giao diện hiện đại, tốc độ phản hồi nhanh, bảo mật thông tin trên mọi thiết bị. | Đem lại trải nghiệm liền mạch, tiện dụng và an tâm cho người dùng. | Toàn bộ NFR | Toàn bộ UC |

### 1.5 Thuật ngữ và từ viết tắt (Glossary)
* **Net Worth (Tài sản ròng):** Giá trị tài sản còn lại của cá nhân sau khi lấy Tổng tài sản (Total Assets) trừ đi Tổng nợ phải trả (Total Liabilities).
* **Cash Flow (Dòng tiền thuần):** Chênh lệch giữa tổng thu nhập thực nhận và tổng chi phí thực tế phát sinh trong một kỳ ($NetCashFlow = TotalIncome - TotalExpense$).
* **Saving Rate (Tỷ lệ tiết kiệm):** Phần trăm thu nhập được giữ lại sau khi chi tiêu: $SavingRate = \frac{Income - Expense}{Income} \times 100\%$.
* **Microfinance (Tài chính vi mô):** Phân hệ quản lý các khoản vay quy mô nhỏ, khoản trả góp, công nợ bạn bè/người thân và các dòng tiền nhỏ lẻ thường ngày.
* **Double-Entry Bookkeeping (Kế toán kép):** Nguyên lý ghi sổ mọi nghiệp vụ tài chính dưới dạng ít nhất một dòng Nợ (Debit) và một dòng Có (Credit) với tổng giá trị tiền cơ sở luôn bằng nhau.
* **Idempotency Key (Khóa bất biến / chống trùng lặp):** Khóa duy nhất do máy khách gửi kèm để bảo đảm một yêu cầu khi gửi nhiều lần do sự cố đường truyền mạng chỉ được xử lý đúng một lần duy nhất.
* **Base Currency (Tiền cơ sở):** Đồng tiền chuẩn do người dùng chọn khi khởi tạo tài khoản (ví dụ VND), dùng để quy đổi và lập báo cáo tài chính hợp nhất.
* **Snapshot Exchange Rate (Lát cắt tỷ giá tại thời điểm):** Bản ghi tỷ giá hối đoái có hiệu lực tại thời điểm phát sinh giao dịch, được lưu bất biến cùng giao dịch để bảo toàn số liệu lịch sử.
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
* **IF01 - Giao diện Web / REST API:** Giao tiếp thông qua HTTPS/TLS 1.3, định dạng payload JSON chuẩn hóa xác thực với JWT Bearer Token (định hướng nghiên cứu mở rộng cơ chế mã hóa payload JSON qua JWE trong tương lai). Mọi phản hồi API đều có cấu trúc chuẩn gồm `success`, `data`, `error` (chứa `code`, `message`, `fieldErrors`) và `requestId`. Mã lỗi tuân thủ chuẩn HTTP: 401 (Chưa xác thực), 403 (Sai quyền), 404 (Không tồn tại/Không sở hữu), 409 (Xung đột khóa), 422 (Dữ liệu không hợp lệ), 500 (Lỗi nội bộ hệ thống).
* **IF02 - Tỷ giá hối đoái:** Tiếp nhận cặp tiền, tỷ giá quy đổi ($rate > 0$), thời điểm hiệu lực `effectiveAt` và nguồn cấp. Dữ liệu tỷ giá được lưu trữ lịch sử theo phiên bản; sự cố từ nhà cung cấp bên ngoài không được làm sai lệch tỷ giá lịch sử đã lưu.

### 2.4 Chính sách tiền tệ và hạch toán đa tiền
* Phiên bản phát hành hỗ trợ tiền cơ sở mặc định là VND (Việt Nam Đồng), hỗ trợ các ngoại tệ giao dịch USD (Đô la Mỹ) và EUR (Euro), cần thông qua API để quy đổi.
* Tiền cơ sở (`baseCurrency`) được người dùng lựa chọn khi tạo tài khoản và bị **khóa vĩnh viễn** sau khi phát sinh bút toán ghi sổ đầu tiên.
* Các số dư tài khoản ngoại tệ được duy trì song song: giá trị nguyên tệ (Original Currency) và giá trị quy đổi tiền cơ sở (Base Currency) theo phương pháp giá trị ghi sổ bình quân (Book Value Base) quy định tại BR15.

### 2.5 Danh mục Quy tắc nghiệp vụ cốt lõi (Business Rules - BR)

| Mã BR | Tên quy tắc | Nội dung chi tiết |
| :---: | :--- | :--- |
| <a id="br01"></a>**BR01** | Cân bằng sổ kế toán kép | Mỗi giao dịch ghi sổ (Posted) phải có ít nhất một dòng Nợ (Debit) và một dòng Có (Credit). Tổng giá trị Nợ quy đổi tiền cơ sở phải bằng chính xác tổng giá trị Có quy đổi tiền cơ sở: $\sum \text{DebitBase} = \sum \text{CreditBase}$. Tuyệt đối không cộng gộp trực tiếp các loại tiền tệ khác nhau. |
| <a id="br02"></a>**BR02** | Phương trình kế toán mở rộng | Hệ thống quản lý đủ 5 loại tài khoản: Tài sản (Asset), Nợ phải trả (Liability), Vốn chủ (Equity), Thu nhập (Income), Chi phí (Expense). Số dư Tài sản = Nợ - Có; Số dư Nợ/Vốn/Thu = Có - Nợ; Số dư Chi phí = Nợ - Có. Phương trình kế toán luôn thỏa mãn: $\text{Asset} = \text{Liability} + \text{Equity} + (\text{Income} - \text{Expense})$. |
| <a id="br03"></a>**BR03** | Độ chính xác số học thập phân | Toàn bộ các phép tính tiền tệ phải sử dụng số thập phân chính xác tuyệt đối (Decimal/BigDecimal), cấm sử dụng số thực dấu phẩy động (Floating-point) nhị phân. VND có 0 chữ số lẻ; USD/EUR có 2 chữ số lẻ; tỷ giá ngoại tệ lưu tối đa 8 chữ số lẻ. Quy tắc làm tròn số: Half-Up về đơn vị tiền nhỏ nhất; sai số làm tròn được ghi vào dòng tài khoản chênh lệch làm tròn riêng biệt. |
| <a id="br04"></a>**BR04** | Lát cắt tỷ giá bất biến | Tỷ giá quy đổi giao dịch sử dụng bản ghi hợp lệ mới nhất có $effectiveAt \le \text{thời điểm giao dịch}$ và tuổi dữ liệu không vượt quá 24 giờ. Bản ghi tỷ giá tại thời điểm giao dịch được gắn liền với giao dịch và trở thành bất biến sau khi commit; không tự động tính lại giá trị giao dịch lịch sử khi có tỷ giá mới. |
| <a id="br05"></a>**BR05** | Phân loại hạch toán cơ bản | - Thu nhập: Ghi Nợ Ví tài sản / Ghi Có Tài khoản Thu nhập.<br>- Chi tiêu: Ghi Nợ Tài khoản Chi phí / Ghi Có Ví tài sản (hoặc Ví nợ).<br>- Chuyển tiền nội bộ: Ghi Nợ Ví đích / Ghi Có Ví nguồn (không tính vào Thu nhập hay Chi phí). Cả 2 ví phải thuộc cùng một chủ sở hữu; cấm chuyển tiền vào chính ví nguồn. |
| <a id="br06"></a>**BR06** | Tính bất biến của sổ và Đảo giao dịch | Giao dịch đã ghi sổ (Posted) cấm sửa đổi hoặc xóa vật lý. Để điều chỉnh sai sót, hệ thống bắt buộc tạo bút toán Đảo (Reversal Transaction) với các dòng Nợ/Có đối ứng ngược dấu và giữ nguyên tỷ giá snapshot gốc. Nghiệp vụ sửa đổi thực chất là chuỗi nguyên tử: Đảo bản ghi cũ + Ghi nhận bản ghi mới thay thế có liên kết `originalId`. Bản nháp (Draft) được phép sửa/xóa trực tiếp. |
| <a id="br07"></a>**BR07** | Quản lý hạn mức ngân sách | Ngân sách được thiết lập theo từng danh mục chi phí (Expense Category) cho một tháng xác định theo múi giờ người dùng. Hạn mức ngân sách phải lớn hơn 0 và tính bằng tiền cơ sở. Chi tiêu thực tế được tính bằng tổng chi ròng (chi tiêu trừ đi các khoản hoàn/đảo) trong tháng; chuyển tiền nội bộ và số dư đầu kỳ không được tính vào ngân sách. Mỗi danh mục chỉ có duy nhất một ngân sách hoạt động trong một tháng. |
| <a id="br08"></a>**BR08** | Ngưỡng và chống trùng cảnh báo ngân sách | Mức sử dụng ngân sách: $Usage = \frac{\text{Chi tiêu ròng}}{\text{Hạn mức ngân sách}} \times 100\%$. Phân loại mức cảnh báo: $< 80\%$ (Bình thường - Xanh); từ $80\%$ đến $< 100\%$ (Cảnh báo - Vàng); $\ge 100\%$ (Vượt mức - Đỏ). Hệ thống chỉ phát duy nhất 1 thông báo cho mỗi mức/ngân sách/tháng. Nếu một giao dịch đẩy mức dùng từ $< 80\%$ lên $\ge 100\%$, hệ thống chỉ phát thông báo Đỏ và đánh dấu mức Vàng đã được kích hoạt. Đảo giao dịch cập nhật trạng thái nhưng không xóa thông báo lịch sử. |
| <a id="br09"></a>**BR09** | Xử lý giao dịch định kỳ | Mỗi chu kỳ định kỳ (ngày đến hạn `dueDate`) chỉ sinh tối đa 1 giao dịch hoặc bản nháp với khóa duy nhất `(subscriptionId, dueDate)`. Chế độ tự động ghi sổ phải được người dùng bật chủ động; mặc định hệ thống chỉ tạo bản nháp và phát thông báo nhắc nhở. Đối với các ngày đến hạn 29, 30, 31 trong các tháng không đủ ngày, hệ thống tự động lùi về ngày cuối cùng của tháng đó và giữ nguyên ngày neo gốc cho chu kỳ kế tiếp. |
| <a id="br10"></a>**BR10** | Nguyên tắc quản lý mục tiêu tài chính | Mục tiêu tài chính (Financial Goal) là công cụ phân bổ theo dõi logic, không tự động sinh ra tiền và không làm tăng tổng tài sản của người dùng. $CurrentAmount$ là tổng các khoản phân bổ còn hiệu lực; $TargetAmount > 0$. Tiến độ: $Progress = \frac{CurrentAmount}{TargetAmount} \times 100\%$. Thanh tiến độ trực quan hiển thị tối đa 100%, số tiền vượt định mức vẫn hiển thị đầy đủ. Mức tích lũy cần thiết hàng tháng: $MonthlySavingTarget = \frac{TargetAmount - CurrentAmount}{\text{Số tháng còn lại}}$. |
| <a id="br11"></a>**BR11** | Cô lập dữ liệu đa người dùng & Quyền riêng tư Admin | Toàn bộ bản ghi nghiệp vụ (tài khoản, ví, tài sản, nợ, giao dịch, ngân sách, mục tiêu) đều phải thuộc về một `userId` duy nhất. Máy chủ phải thẩm tra quyền sở hữu đối với 100% yêu cầu API. Quyền Quản trị viên (Admin) tuyệt đối không cho phép xem dữ liệu sổ sách tài chính cá nhân của người dùng; Admin chỉ quản trị tài khoản, tham số cấu hình và Dashboard vĩ mô. |
| <a id="br12"></a>**BR12** | Tính nguyên tử trong giao dịch (ACID) | Toàn bộ các thao tác ghi nhận giao dịch, sinh dòng sổ Nợ/Có, cập nhật số dư bộ đệm và phát sự kiện xử lý nền phải được thực thi trong một Database Transaction nguyên tử duy nhất. Nếu có bất kỳ bước nào thất bại, toàn bộ thao tác phải được Rollback về trạng thái ban đầu; không bao giờ được lưu trạng thái dở dang. |
| <a id="br13"></a>**BR13** | Cơ chế Idempotency chống trùng lặp | Mọi yêu cầu ghi nhận/đảo giao dịch gửi kèm `Idempotency-Key`: nếu cùng `userId` + cùng `key` + cùng nội dung thì hệ thống trả lại kết quả đã xử lý trước đó mà không thực hiện lại; nếu cùng `key` nhưng khác nội dung thì hệ thống từ chối với mã lỗi 409 Conflict. Khóa mức dòng (Row-level lock) phải được áp dụng để ngăn ngừa triệt để tình trạng ghi đè hoặc chi vượt số dư ví tài sản. |
| <a id="br14"></a>**BR14** | Tách biệt trạng thái giao dịch | Giao dịch ở trạng thái Nháp (Draft) không được tính vào số dư ví, không tính vào ngân sách và không xuất hiện trên báo cáo tài chính. Báo cáo tài chính chỉ tổng hợp các giao dịch đã ghi sổ (Posted) và các dòng đảo tương ứng; không được loại trừ bản ghi gốc rồi lại trừ tiếp dòng đảo làm sai lệch số liệu. |
| <a id="br15"></a>**BR15** | Phương pháp giá trị sổ ngoại tệ | Khi xuất ngoại tệ khỏi ví: giá trị quy đổi tiền cơ sở xuất sổ được tính theo phương pháp Bình quân gia quyền: $UnitRate = \frac{BookBaseBalance}{OriginalBalance}$. Khi xuất toàn bộ số dư nguyên tệ, toàn bộ giá trị $BookBaseBalance$ phải được xuất hết để triệt tiêu sai số làm tròn. Chênh lệch giữa giá trị xuất sổ và giá trị nhận được theo tỷ giá giao dịch được hạch toán vào tài khoản Lãi/Lỗ chênh lệch tỷ giá. |
| <a id="br16"></a>**BR16** | Xác định Tài sản ròng (Net Worth) | $\text{Tài sản ròng (Net Worth)} = \text{Tổng tài sản (Total Assets)} - \text{Tổng nợ (Total Liabilities)}$.<br>- Tổng tài sản bao gồm: Toàn bộ số dư các ví tài sản (tiền mặt, ngân hàng, ví điện tử), tài khoản tiết kiệm và giá trị định giá của tài sản tích lũy ngoài ví (vàng) đã ghi nhận.<br>- Tổng nợ bao gồm: Toàn bộ số dư các ví nợ (thẻ tín dụng, hạn mức thấu chi) và dư nợ gốc còn lại của các khoản vay vi mô/trả góp.<br>- Tiền đã tồn tại trong danh sách ví chỉ được tính đúng 1 lần vào Tổng tài sản. |
| <a id="br17"></a>**BR17** | Nguyên tắc quản lý khoản vay và nợ vi mô | - Nhận tiền vay: Tăng số dư ví tài sản nhận tiền và Tăng khoản nợ phải trả (Liability); cấm hạch toán số tiền vay nhận được vào Thu nhập (Income).<br>- Trả nợ định kỳ: Số tiền thanh toán được phân rã thành:<br>  + Tiền gốc (Principal Paid): Làm giảm dư nợ gốc khoản vay và giảm số dư ví chi trả.<br>  + Tiền lãi và phí (Interest/Fee Paid): Ghi nhận vào Chi phí tài chính (Expense) và giảm số dư ví chi trả.<br>- Cập nhật dư nợ: Dư nợ gốc còn lại = Số tiền vay ban đầu - Tổng tiền gốc đã trả. Khoản nợ chuyển sang trạng thái "Đã thanh toán" (Paid Off) khi dư nợ gốc về 0. |
| <a id="br18"></a>**BR18** | Xác định Dòng tiền và Tỷ lệ tiết kiệm | - Dòng tiền thuần: $\text{Net Cash Flow} = \text{Total Income} - \text{Total Expense}$ trong kỳ khảo sát.<br>- Tỷ lệ tiết kiệm: $\text{Saving Rate} = \frac{\text{Total Income} - \text{Total Expense}}{\text{Total Income}} \times 100\%$.<br>- Xử lý ngoại lệ: Nếu $\text{Total Income} \le 0$, không thực hiện phép chia; hệ thống hiển thị "Chưa xác định (N/A)" cho Saving Rate nhưng vẫn thể hiện đầy đủ giá trị chi tiêu và mức thâm hụt dòng tiền. Nếu $\text{Total Expense} > \text{Total Income}$, Saving Rate hiển thị giá trị âm màu đỏ cảnh báo. |
---

<a id="phan-3"></a>

## 3. Đặc tả Yêu cầu chức năng (Functional Requirements Specification - FR)

Cấu trúc đặc tả tuân thủ chuẩn Kỹ nghệ yêu cầu phần mềm (IEEE 29148 & Karl Wiegers): mỗi nhóm chức năng bao gồm Bảng hồ sơ quản trị & Ma trận truy vết hai chiều (UR, BR, UC, Sequence Diagram, Test Case), kèm Bảng chi tiết các yêu cầu chức năng bắt buộc (`FRnn.xx`) định rõ hành vi hệ thống, dữ liệu đầu vào & ràng buộc, kết quả kỳ vọng và mã phản hồi kỹ thuật.

### FR01 - Định danh, xác thực và quản lý phiên truy cập

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR01** |
| **Tên chức năng** | Quản lý định danh, xác thực và phiên truy cập người dùng |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR01](#ur01)<br>• **Quy tắc nghiệp vụ:** [BR11](#br11)<br>• **Use Case tương ứng:** [UC01](#uc01)<br>• **Biểu đồ tuần tự:** [SQ01](./uml_modeling/sequence_diagram/sq01_auth_jwt.md)<br>• **Ca kiểm thử:** [TC-F01.01-P](#tc-f01) |
| **Mô tả tổng quát** | Hệ thống cung cấp cơ chế đăng ký an toàn, thẩm định danh tính, cấp phát và thu hồi phiên truy cập JWT, đồng thời đảm bảo an toàn mật khẩu và cô lập tài khoản theo [NFR02](#nfr02). |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR01.01** | Đăng ký tài khoản | Chuẩn hóa email (trim, lowercase), kiểm tra tính duy nhất, băm mật khẩu an toàn và khởi tạo tài khoản mới. | • Email, Password, Họ tên, Tiền cơ sở.<br>• Password tuân thủ chính sách [NFR02](#nfr02). | • Tạo bản ghi User `Active`, gán bộ danh mục thu/chi mặc định (`201 Created`).<br>• Trùng email: `409 Conflict`. |
| **FR01.02** | Đăng nhập hệ thống | Kiểm tra email/password, trạng thái tài khoản `Active`. Cấp cặp Token JWT (Access & Refresh). Chống User Enumeration. | • Email, Password. | • Cấp JWT vào Cookie / Response (`200 OK`).<br>• Đăng nhập sai: `401 Unauthorized` chung, không tiết lộ tài khoản tồn tại hay không. |
| **FR01.03** | Đăng xuất | Thu hồi hiệu lực phiên truy cập hiện tại ngay lập tức. | • Access Token / Refresh Token hiện hành. | • Đưa Refresh Token vào Blacklist, xóa Cookie HttpOnly (`200 OK`). |
| **FR01.04** | Đổi mật khẩu | Xác thực mật khẩu cũ, băm và cập nhật mật khẩu mới, tự động hủy bỏ mọi phiên truy cập trên thiết bị khác. | • `currentPassword`, `newPassword`. | • Cập nhật Hash mới, nâng `tokenVersion` làm hết hạn các phiên cũ (`200 OK`).<br>• Sai mật khẩu cũ: `400 Bad Request`. |

---

### FR02 - Quản lý tài khoản thanh toán và ví tiền

> **Construction integration note, 2026-10-18:** Thiết kế FR02.01/02.02 vẫn yêu cầu hỗ trợ quy đổi ngoại tệ chính xác. Trong source hiện tại, chỉ cho phép nhập số dư đầu kỳ >0 cho ví VND không phải CREDIT, do chưa có luồng xác nhận tỷ giá snapshot ngay khi mở ví; ví ngoại tệ chỉ được tạo với số dư 0. Không tự áp dụng tỷ giá 1:1 hoặc hiển thị tổng tài sản quy đổi giả. Đây là giới hạn triển khai **chưa đạt đầy đủ FR02.02 và FR04**, cần kiểm thử/hoàn thiện ở Construction trước khi nghiệm thu. Việc đổi tên và lưu trữ ví được triển khai theo FR02.03, không xóa cứng lịch sử. Chi tiết: `docs/03_construction/INTEGRATION_STATUS_2026-10-18.md`.


| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR02** |
| **Tên chức năng** | Quản lý tài khoản thanh toán, nguồn tiền và ví tiền cá nhân |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR02](#br02), [BR03](#br03), [BR11](#br11)<br>• **Use Case tương ứng:** [UC02](#uc02)<br>• **Biểu đồ thiết kế:** [Class Diagram (Domain Model)](./uml_modeling/class_diagram.md)<br>• **Ca kiểm thử:** [TC-F02.01-P](#tc-f02) |
| **Mô tả tổng quát** | Hệ thống cho phép người dùng khởi tạo, cấu hình, theo dõi biến động số dư các nguồn tiền (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm) và tự động hạch toán đối ứng Vốn chủ sở hữu theo nguyên lý kế toán kép. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR02.01** | Tạo ví & hạch toán số dư đầu kỳ | Cho phép tạo các loại ví (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm). Nếu số dư đầu kỳ $> 0$, tự động sinh bút toán đối ứng Vốn chủ (Equity) theo [BR01](#br01) & [BR02](#br02) trong 1 transaction nguyên tử. | • Tên ví, Loại ví, Tiền tệ, Số dư ban đầu $\ge 0$.<br>• Tên ví không trùng lặp trong cùng chủ sở hữu. | • Tạo bản ghi Wallet, sinh dòng Nợ Ví / Có Vốn chủ (`201 Created`).<br>• Số dư $< 0$: `400 Bad Request`.<br>• Trùng tên ví: `409 Conflict`. |
| **FR02.02** | Xem danh sách & số dư ví | Hiển thị số dư nguyên tệ của từng ví và tổng số dư quy đổi theo tiền cơ sở của toàn bộ danh mục ví đang hoạt động. | • `userId` từ phiên xác thực. | • Trả danh sách ví, số dư tức thời và tổng tài sản quy đổi (`200 OK`). |
| **FR02.03** | Lưu trữ ví (Archive) | Cho phép chuyển trạng thái ví sang `Archived` khi không còn sử dụng. Ví lưu trữ bị ẩn khỏi màn hình tạo giao dịch mới nhưng dữ liệu lịch sử vẫn được bảo toàn trong báo cáo. | • `walletId`.<br>• Ràng buộc: Số dư ví bắt buộc bằng 0 và không có giao dịch định kỳ đang liên kết. | • Cập nhật trạng thái ví thành `Archived` (`200 OK`).<br>• Số dư khác 0 hoặc còn giao dịch định kỳ: `400 Bad Request`. |
| **FR02.04** | Đồng bộ số dư thời gian thực | Tự động cập nhật số dư tức thì và đồng bộ khi có phát sinh bút toán thu, chi, chuyển tiền nội bộ hoặc đảo giao dịch. | • Dòng bút toán ghi sổ hợp lệ. | • Cập nhật số dư ví tức thì, kích hoạt tính toán lại Total Assets trên Dashboard. |

---

### FR03 - Quản lý giao dịch tài chính và sổ kế toán kép

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR03** |
| **Tên chức năng** | Quản lý giao dịch thu chi, chuyển tiền và động cơ sổ kế toán kép |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR02](#br02), [BR03](#br03), [BR04](#br04), [BR05](#br05), [BR06](#br06), [BR12](#br12), [BR13](#br13), [BR14](#br14), [BR15](#br15)<br>• **Use Case tương ứng:** [UC03](#uc03)<br>• **Biểu đồ tuần tự:** [SQ02](./uml_modeling/sequence_diagram/sq02_expense_budget.md), [SQ03](./uml_modeling/sequence_diagram/sq03_transfer_reversal.md)<br>• **Ca kiểm thử:** [TC-F03.01-P](#tc-f03) |
| **Mô tả tổng quát** | Động cơ kế toán lõi tiếp nhận các giao dịch thu, chi, chuyển tiền nội bộ, sinh các dòng Nợ/Có cân bằng tuyệt đối, bảo đảm tính bất biến của sổ sách và xử lý điều chỉnh thông qua cơ chế Đảo bút toán (Reversal). |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR03.01** | Hạch toán kép cân bằng | Tự động sinh tối thiểu 1 dòng Nợ (Debit) và 1 dòng Có (Credit) cho mỗi giao dịch ghi sổ theo [BR05](#br05). Kiểm tra nghiêm ngặt $\sum \text{DebitBase} = \sum \text{CreditBase}$ trước khi commit theo [BR01](#br01). | • Loại giao dịch (Thu/Chi), Ví nguồn/Ví nợ, Danh mục, Số tiền $> 0$, Ngày phát sinh.<br>• Phép tính Decimal theo [BR03](#br03). | • Bản ghi Transaction trạng thái `Posted`, các dòng LedgerEntry cân bằng (`201 Created`).<br>• Mất cân bằng hoặc số tiền $\le 0$: Rollback và trả `400 Bad Request`. |
| **FR03.02** | Chuyển tiền nội bộ đa ví | Xử lý chuyển tiền giữa 2 ví khác nhau của cùng một chủ sở hữu; cấm chuyển vào chính ví nguồn. Với cặp ví khác tiền tệ, áp dụng snapshot tỷ giá theo [BR04](#br04) và giá trị xuất sổ theo [BR15](#br15). Tuyệt đối không tính vào Thu nhập hay Chi phí. | • `fromWalletId`, `toWalletId` (`fromWalletId` $\ne$ `toWalletId`), Số tiền chuyển.<br>• Ví nguồn phải đủ số dư khả dụng. | • Ghi Nợ Ví đích / Có Ví nguồn, cập nhật số dư đồng thời (`200 OK`).<br>• Cùng ví hoặc số dư không đủ: `400 Bad Request`. |
| **FR03.03** | Đảo giao dịch bất biến (Reversal) | Nghiêm cấm xóa hoặc sửa vật lý giao dịch đã `Posted`. Nghiệp vụ sửa/xóa thực thi nguyên tử: tạo bút toán Đảo ngược dòng Nợ/Có kèm tỷ giá gốc, rồi tạo giao dịch mới liên kết `originalId` theo [BR06](#br06). | • `transactionId`, Lý do đảo bút toán.<br>• Giao dịch gốc phải ở trạng thái `Posted` và chưa bị đảo. | • Sinh bút toán Reversal đối ứng ngược dấu, khôi phục số dư ví (`200 OK`).<br>• Giao dịch không tồn tại hoặc đã đảo: `400 Bad Request`. |
| **FR03.04** | Chống trừ tiền trùng lặp & Bảo vệ số dư ví | Ngăn chặn việc trừ tiền 2 lần khi người dùng bấm nút "Lưu" nhiều lần hoặc mạng chập chờn gửi lại yêu cầu (thông qua mã `Idempotency-Key` theo [BR13](#br13)). Đồng thời khóa tạm thời bản ghi ví trong cơ sở dữ liệu khi đang trừ tiền để xếp hàng xử lý tuần tự, ngăn ngừa việc bị trừ âm tiền khi có nhiều giao dịch xảy ra cùng lúc. | • Header `Idempotency-Key` (mã định danh duy nhất của mỗi lần bấm gửi giao dịch).<br>• Khóa dòng dữ liệu số dư ví trong DB theo [BR12](#br12). | • Nếu phát hiện yêu cầu gửi lại trùng lặp: Trả ngay kết quả giao dịch trước đó (`200 OK`), không trừ tiền thêm.<br>• Nếu dùng lại mã key cũ nhưng đổi số tiền/nội dung khác: Từ chối (`409 Conflict`). |
| **FR03.05** | Quản lý danh mục thu chi | Cung cấp khả năng tạo mới, đổi tên, đổi icon/màu sắc và lưu trữ (Archive) danh mục thu nhập/chi tiêu cá nhân. Khi lưu trữ, các giao dịch cũ gắn với danh mục vẫn giữ nguyên giá trị báo cáo. | • Tên danh mục, Loại (Income/Expense), Icon, Màu sắc.<br>• Không được trùng tên trong cùng loại và cùng `userId`. | • Bản ghi Category được tạo/cập nhật (`201 Created` / `200 OK`).<br>• Trùng tên danh mục: `409 Conflict`. |

---

### FR04 - Đa tiền tệ và tỷ giá hối đoái thời điểm (Snapshot)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR04** |
| **Tên chức năng** | Quản lý đa tiền tệ, tỷ giá hối đoái và bảo toàn giá trị lịch sử (Snapshot) |
| **Mức độ ưu tiên** | **P2 (Quan trọng)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02)<br>• **Quy tắc nghiệp vụ:** [BR03](#br03), [BR04](#br04), [BR15](#br15)<br>• **Use Case tương ứng:** [UC02](#uc02), [UC03](#uc03)<br>• **Biểu đồ tuần tự:** [SQ03](./uml_modeling/sequence_diagram/sq03_transfer_reversal.md)<br>• **Ca kiểm thử:** [TC-F04.01-P](#tc-f04) |
| **Mô tả tổng quát** | Hệ thống hỗ trợ quản lý các ví và giao dịch bằng nhiều loại tiền tệ (VND, USD, EUR), tự động ghi nhận cố định lát cắt tỷ giá (snapshot) tại đúng thời điểm phát sinh để số liệu lịch sử không bị biến động trong tương lai, đồng thời tự động tính lãi/lỗ chênh lệch tỷ giá theo phương pháp bình quân gia quyền. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR04.01** | Cập nhật bảng tỷ giá | Tiếp nhận hoặc định kỳ cập nhật tỷ giá hối đoái giữa các đồng tiền hỗ trợ (VND, USD, EUR) từ dịch vụ tỷ giá và lưu lịch sử theo mốc thời gian hiệu lực. | • Bảng tỷ giá ngoại tệ, mốc thời gian áp dụng.<br>• Tỷ giá $> 0$, thời gian hiệu lực trong vòng 24 giờ. | • Lưu bản ghi tỷ giá mới vào kho dữ liệu (`200 OK`).<br>• Dữ liệu tỷ giá không hợp lệ: Báo lỗi và giữ tỷ giá hợp lệ gần nhất. |
| **FR04.02** | Chốt tỷ giá bất biến (Snapshot) | Khi người dùng thực hiện giao dịch bằng ngoại tệ, hệ thống phải ghi nhận cố định lát cắt tỷ giá tại đúng thời điểm đó và lưu bất biến cùng giao dịch. Tuyệt đối không tính toán lại số tiền quy đổi của các giao dịch trong quá khứ khi tỷ giá thị trường biến động theo [BR04](#br04). | • Giao dịch ngoại tệ, thời điểm phát sinh giao dịch. | • Lưu tỷ giá cố định kèm bản ghi giao dịch; đảm bảo số liệu báo cáo quá khứ không bao giờ bị sai lệch. |
| **FR04.03** | Tính chênh lệch lãi/lỗ tỷ giá | Khi người dùng chi tiêu hoặc chuyển ngoại tệ ra khỏi ví, hệ thống phải tự động tính giá trị xuất ví theo phương pháp bình quân gia quyền theo [BR15](#br15) và tự động hạch toán khoản lãi hoặc lỗ chênh lệch tỷ giá phát sinh. | • Số lượng ngoại tệ xuất ví, tỷ giá giao dịch thực tế.<br>• Làm tròn số theo [BR03](#br03). | • Tự động sinh dòng bút toán Nợ/Có vào tài khoản "Chênh lệch tỷ giá", xuất hết giá trị quy đổi khi số dư ngoại tệ về 0. |

---

### FR05 - Thiết lập và kiểm soát ngân sách chi tiêu

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR05** |
| **Tên chức năng** | Thiết lập hạn mức và kiểm soát cảnh báo bội chi ngân sách |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR03](#ur03)<br>• **Quy tắc nghiệp vụ:** [BR07](#br07), [BR08](#br08)<br>• **Use Case tương ứng:** [UC05](#uc05)<br>• **Biểu đồ tuần tự:** [SQ02](./uml_modeling/sequence_diagram/sq02_expense_budget.md)<br>• **Ca kiểm thử:** [TC-F05.03-P](#tc-f05) |
| **Mô tả tổng quát** | Hệ thống cho phép người dùng đặt hạn mức chi tiêu theo từng danh mục trong tháng, tự động tính toán tổng chi tiêu thực tế theo thời gian thực và phát đi cảnh báo trực quan trước khi hoặc ngay khi người dùng chi tiêu vượt hạn mức. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR05.01** | Thiết lập hạn mức ngân sách | Cho phép người dùng tạo mới, xem, sửa đổi số tiền hạn mức hoặc lưu trữ ngân sách cho một danh mục chi tiêu cụ thể theo tháng xác định (YYYY-MM). | • Danh mục chi tiêu, Tháng áp dụng (YYYY-MM), Hạn mức ngân sách $> 0$.<br>• Mỗi danh mục chỉ có duy nhất 1 ngân sách hoạt động trong 1 tháng theo [BR07](#br07). | • Bản ghi Budget được lưu trữ thành công (`201 Created` / `200 OK`).<br>• Hạn mức $\le 0$: `400 Bad Request`.<br>• Trùng danh mục trong cùng tháng: `409 Conflict`. |
| **FR05.02** | Giám sát chi tiêu thực tế | Tự động tổng hợp chi tiêu ròng trong tháng (tổng chi trừ các khoản được hoàn hoặc đảo) và tính phần trăm đã sử dụng so với hạn mức: $Usage = \frac{\text{Chi tiêu ròng}}{\text{Hạn mức}} \times 100\%$. Không tính chuyển tiền nội bộ vào chi tiêu ngân sách. | • Các giao dịch chi tiêu trạng thái `Posted` trong tháng theo múi giờ người dùng. | • Cập nhật thanh tiến độ chi tiêu tức thời trên giao diện người dùng (`200 OK`). |
| **FR05.03** | Cảnh báo tiệm cận và vượt mức | Tự động kích hoạt thông báo theo 2 ngưỡng: **Cảnh báo Vàng** khi chi tiêu đạt từ $80\%$ đến $< 100\%$, và **Cảnh báo Đỏ** khi chi tiêu đạt $\ge 100\%$. Áp dụng quy tắc chống spam thông báo theo [BR08](#br08) (chỉ gửi thông báo duy nhất 1 lần cho mỗi mức cảnh báo trong tháng). | • Tỷ lệ sử dụng ngân sách sau mỗi giao dịch ghi sổ mới.<br>• Trạng thái các cảnh báo đã phát trong tháng. | • Phát thông báo cảnh báo đến chuông thông báo (Notification Center) của người dùng.<br>• Không chặn thao tác chi tiêu thực tế của người dùng. |

---

### FR06 - Xử lý giao dịch định kỳ

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR06** |
| **Tên chức năng** | Quản lý và xử lý giao dịch định kỳ tự động (Recurring Transactions) |
| **Mức độ ưu tiên** | **P2 (Quan trọng)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR04](#ur04)<br>• **Quy tắc nghiệp vụ:** [BR09](#br09)<br>• **Use Case tương ứng:** [UC04](#uc04)<br>• **Biểu đồ tuần tự:** [SQ04](./uml_modeling/sequence_diagram/sq04_subscription_cron.md)<br>• **Ca kiểm thử:** [TC-F06.01-P](#tc-f06) |
| **Mô tả tổng quát** | Hệ thống hỗ trợ người dùng lên lịch tự động các khoản thu/chi lặp lại hàng tháng (tiền thuê nhà, hóa đơn điện nước, dịch vụ định kỳ), cung cấp cơ chế tạo bản nháp hoặc tự động ghi sổ, kèm thuật toán xử lý ngày cuối tháng thiếu ngày an toàn. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR06.01** | Quản lý khoản định kỳ | Cho phép người dùng thiết lập, xem danh sách, tạm dừng (`Paused`), kích hoạt lại (`Active`) và xóa các khoản giao dịch định kỳ hàng tháng. | • Tên khoản định kỳ, Loại (Thu/Chi), Số tiền $> 0$, Ví liên kết, Danh mục, Ngày neo đến hạn (`anchorDay`: 1 - 31). | • Bản ghi Subscription được tạo/cập nhật (`201 Created` / `200 OK`).<br>• Ngày neo ngoài 1 - 31 hoặc số tiền $\le 0$: `400 Bad Request`. |
| **FR06.02** | Chế độ ghi sổ linh hoạt | Hỗ trợ 2 chế độ xử lý khi đến hạn: **Nhắc nhở tạo bản nháp** (mặc định) và **Tự động ghi sổ** (chỉ thực hiện khi người dùng chủ động bật). | • Tùy chọn cấu hình `autoPost` từ người dùng. | • Nếu `autoPost = false`: Tự động tạo giao dịch `Draft` và phát thông báo duyệt.<br>• Nếu `autoPost = true`: Tự động ghi sổ `Posted` khi ví đủ tiền. |
| **FR06.03** | Quét định kỳ & Chống trùng lặp | Tiến trình nền (SYS02) tự động quét các khoản đến hạn. Áp dụng khóa duy nhất `(subscriptionId, dueDate)` để bảo đảm mỗi chu kỳ chỉ sinh đúng 1 bản ghi duy nhất. Đối với tháng thiếu ngày (ngày 29, 30, 31), tự động lùi về ngày cuối tháng và giữ nguyên ngày neo gốc cho tháng sau theo [BR09](#br09). | • Tiến trình lập lịch Cron chạy định kỳ theo múi giờ hệ thống. | • Sinh bản ghi giao dịch/bản nháp chính xác ngày đến hạn.<br>• Nếu ví không đủ tiền khi tự động ghi sổ: Chuyển sang `Draft` và gửi thông báo cảnh báo nạp tiền. |

---

### FR07 - Quản lý mục tiêu tích lũy tài chính

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR07** |
| **Tên chức năng** | Quản lý mục tiêu tích lũy và kế hoạch tiết kiệm khả thi (Financial Goals) |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR05](#ur05)<br>• **Quy tắc nghiệp vụ:** [BR10](#br10)<br>• **Use Case tương ứng:** [UC09](#uc09)<br>• **Biểu đồ thiết kế:** [Class Diagram (Domain Model)](./uml_modeling/class_diagram.md)<br>• **Ca kiểm thử:** [TC-F07.01-P](#tc-f07) |
| **Mô tả tổng quát** | Hệ thống cung cấp công cụ tạo lập và theo dõi tiến độ các mục tiêu tích lũy (mua laptop, quỹ khẩn cấp), hỗ trợ phân bổ số tiền tích lũy logic mà không làm biến động tổng tài sản, đồng thời tự động tính toán kế hoạch tiết kiệm bình quân mỗi tháng. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR07.01** | Quản lý vòng đời mục tiêu | Cho phép người dùng tạo mới, xem danh sách, chỉnh sửa thông tin, hủy bỏ hoặc hoàn thành mục tiêu tài chính cá nhân. | • Tên mục tiêu, Số tiền đích (`targetAmount` $> 0$), Ngày thời hạn (`deadlineDate`).<br>• Ngày thời hạn bắt buộc ở tương lai. | • Bản ghi Goal ở trạng thái `InProgress` (`201 Created`).<br>• Số tiền đích $\le 0$ hoặc thời hạn ở quá khứ: `400 Bad Request`. |
| **FR07.02** | Phân bổ tiền tích lũy logic | Cho phép người dùng ghi nhận các khoản phân bổ tiền tích lũy vào mục tiêu. Tuân thủ nghiêm ngặt [BR10](#br10): Mục tiêu tài chính là phân bổ theo dõi logic, tuyệt đối không tự sinh ra tiền và không làm tăng Tổng tài sản (Total Assets) của người dùng. | • `goalId`, Số tiền phân bổ $> 0$. | • Cập nhật `currentAmount` của mục tiêu (`200 OK`).<br>• Tự động đổi trạng thái sang `Achieved` khi tích lũy $\ge$ mục tiêu đích. |
| **FR07.03** | Tính tiến độ & Kế hoạch hàng tháng | Tự động tính toán phần trăm tiến độ $Progress = \frac{\text{Current}}{\text{Target}} \times 100\%$ và tính số tiền tiết kiệm bình quân cần thiết mỗi tháng: $MonthlySaving = \frac{\text{Target} - \text{Current}}{\text{Số tháng còn lại}}$. Xử lý an toàn tránh chia cho 0 khi thời hạn đến hạn trong tháng hiện tại. | • Dữ liệu tích lũy hiện hành và thời điểm hiện tại. | • Hiển thị thanh tiến độ trực quan (tối đa 100%, tiền vượt định mức hiển thị số thực) và số tiền gợi ý tiết kiệm mỗi tháng trên giao diện. |

---

### FR08 - Quản lý tài sản tích lũy ngoài ví (Vàng) và Tài sản ròng (Personal Wealth & Net Worth)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR08** |
| **Tên chức năng** | Quản lý tài sản tích lũy ngoài ví (Vàng) và tổng hợp Tài sản ròng (Net Worth) |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR06](#ur06), [UR08](#ur08)<br>• **Quy tắc nghiệp vụ:** [BR02](#br02), [BR16](#br16)<br>• **Use Case tương ứng:** [UC08](#uc08), [UC10](#uc10)<br>• **Biểu đồ tuần tự:** [SQ06](./uml_modeling/sequence_diagram/sq06_gold_networth.md)<br>• **Ca kiểm thử:** [TC-F08.04-P](#tc-f08) |
| **Mô tả tổng quát** | Hệ thống cho phép theo dõi danh mục tài sản tích lũy ngoài ví (vàng nhẫn 9999, vàng miếng SJC), tự động tổng hợp Tổng tài sản (Total Assets) và Tổng nợ (Total Liabilities), từ đó xác định chính xác Tài sản ròng (Net Worth) của người dùng theo thời gian thực. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR08.01** | Quản lý tài sản vàng tích lũy | Cho phép người dùng thêm mới, cập nhật thị giá định giá lại (Revaluation) và xóa/thanh lý tài sản vàng tích trữ ngoài ví (nhẫn tròn trơn 9999, vàng miếng SJC). | • Tên tài sản, Loại vàng, Số lượng (chỉ) $\ge 0$, Giá trị quy đổi hoặc thị giá cập nhật $\ge 0$, Ngày định giá. | • Bản ghi Asset được tạo/cập nhật (`201 Created` / `200 OK`).<br>• Giá trị hoặc số lượng $< 0$: `400 Bad Request`. |
| **FR08.02** | Tổng hợp Tổng tài sản (Total Assets) | Tự động tổng hợp Tổng tài sản = Tổng số dư quy đổi tiền cơ sở của toàn bộ ví tài sản (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm) + Tổng giá trị định giá của toàn bộ tài sản vàng tích lũy đang quản lý. | • Dữ liệu số dư ví và tài sản vàng thời gian thực. | • Trả về con số Tổng tài sản hợp nhất theo tiền cơ sở (`200 OK`). |
| **FR08.03** | Tổng hợp Tổng nợ (Total Liabilities) | Tự động tổng hợp Tổng nợ = Tổng số dư các ví nợ (Thẻ tín dụng) + Tổng dư nợ gốc còn lại của toàn bộ các khoản vay vi mô/trả góp đang hoạt động. | • Dữ liệu ví nợ và dư nợ gốc khoản vay thời gian thực. | • Trả về con số Tổng nợ hợp nhất theo tiền cơ sở (`200 OK`). |
| **FR08.04** | Tính toán Tài sản ròng (Net Worth) | Tính toán chính xác giá trị thực tế thuộc quyền sở hữu của người dùng: $\text{Net Worth} = \text{Total Assets} - \text{Total Liabilities}$ theo [BR16](#br16); lưu trữ lịch sử biến động để hiển thị biểu đồ xu hướng. | • Total Assets và Total Liabilities tại thời điểm tính toán. | • Hiển thị chỉ số Net Worth tức thời và vẽ biểu đồ biến động lịch sử trên Dashboard. |
| **FR08.05** | Chống tính trùng lặp tài sản | Bảo đảm tiền trong danh sách ví chỉ được tính đúng 1 lần vào Total Assets; các nghiệp vụ chuyển tiền nội bộ giữa các ví tuyệt đối không làm thay đổi Net Worth theo [BR16](#br16). | • Bản ghi giao dịch ghi sổ. | • Net Worth không bị đội ảo khi di chuyển tiền giữa các ví cá nhân. |

---

### FR09 - Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance Tracking)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR09** |
| **Tên chức năng** | Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance Tracking) |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR07](#ur07)<br>• **Quy tắc nghiệp vụ:** [BR05](#br05), [BR17](#br17)<br>• **Use Case tương ứng:** [UC06](#uc06), [UC07](#uc07)<br>• **Biểu đồ tuần tự:** [SQ05](./uml_modeling/sequence_diagram/sq05_microfinance_loan.md)<br>• **Ca kiểm thử:** [TC-F09.03-P](#tc-f09) |
| **Mô tả tổng quát** | Hệ thống cung cấp phân hệ theo dõi các khoản vay nợ quy mô nhỏ (đi vay, cho vay bạn bè/người thân, mua trả góp), tự động phân rã các khoản thanh toán định kỳ thành tiền gốc và tiền lãi/phí theo chuẩn kế toán, đồng thời gửi thông báo nhắc nợ khi sắp đến hạn. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR09.01** | Tạo & theo dõi hồ sơ khoản vay | Cho phép người dùng tạo, xem chi tiết, chỉnh sửa thông tin và theo dõi các khoản vay mượn cá nhân (bao gồm cả Đi vay và Cho vay). | • Tên khoản vay, Loại (Borrow/Lend), Đối tác vay, Số tiền gốc $> 0$, Lãi suất, Ngày bắt đầu, Ngày đến hạn ($\ge$ Ngày bắt đầu). | • Bản ghi Loan ở trạng thái `Active` (`201 Created` / `200 OK`).<br>• Tiền gốc $\le 0$ hoặc ngày không hợp lệ: `400 Bad Request`. |
| **FR09.02** | Hạch toán giải ngân vào ví | Khi tạo khoản đi vay có nhận tiền giải ngân thực tế vào ví, hệ thống phải tự động sinh giao dịch: Tăng số dư ví nhận tiền và Tăng khoản Nợ phải trả (Liability). Tuyệt đối cấm hạch toán số tiền vay nhận được vào Thu nhập (Income) theo [BR17](#br17). | • `walletId` nhận tiền, Số tiền giải ngân. | • Ghi Nợ Ví / Có Nợ phải trả (`201 Created`).<br>• Tổng tài sản và Tổng nợ tăng tương ứng; Net Worth không đổi. |
| **FR09.03** | Phân rã thanh toán trả nợ | Khi người dùng trả nợ từng kỳ, hệ thống bắt buộc phân rã số tiền thanh toán thành 2 phần: **Tiền trả nợ gốc** (làm giảm trực tiếp dư nợ gốc khoản vay và giảm số dư ví chi trả) và **Tiền lãi/phí** (hạch toán vào Chi phí tài chính và giảm số dư ví chi trả) theo [BR17](#br17). | • `loanId`, Tiền trả gốc $\ge 0$, Tiền lãi/phí $\ge 0$, `walletId` chi trả.<br>• Ràng buộc: Tiền trả gốc $\le$ Dư nợ gốc hiện tại; Tổng tiền trả $\le$ Số dư ví. | • Ghi nhận thanh toán trả nợ (`200 OK`). Báo cáo chi tiêu chỉ tăng phần tiền lãi/phí, không tính tiền gốc vào chi tiêu tháng. |
| **FR09.04** | Cập nhật dư nợ & Tất toán | Tự động tính toán dư nợ gốc còn lại sau mỗi lần trả nợ: $\text{Dư nợ còn lại} = \text{Gốc ban đầu} - \sum \text{Gốc đã trả}$. Tự động chuyển trạng thái khoản vay sang "Đã tất toán" (`PaidOff`) khi dư nợ gốc về 0. | • Lịch sử các lần thanh toán `LoanRepayment`. | • Cập nhật `remainingPrincipal` tức thời; giảm trừ nghĩa vụ nợ trên Total Liabilities. |
| **FR09.05** | Nhắc nợ sắp đến hạn | Tiến trình nền (SYS03) tự động quét các khoản vay sắp đến hạn thanh toán trong vòng 3 ngày và phát thông báo nhắc nhở người dùng. | • Ngày đến hạn của các khoản vay đang `Active`. | • Gửi thông báo nhắc nợ đến chuông thông báo (Notification Center) của người dùng. |

---

### FR10 - Dashboard sức khỏe tài chính và Tổng quan tài sản ròng

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR10** |
| **Tên chức năng** | Dashboard sức khỏe tài chính và Tổng quan tài sản ròng |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR08](#ur08)<br>• **Quy tắc nghiệp vụ:** [BR02](#br02), [BR14](#br14), [BR16](#br16), [BR18](#br18)<br>• **Use Case tương ứng:** [UC10](#uc10)<br>• **Biểu đồ tuần tự:** [SQ06](./uml_modeling/sequence_diagram/sq06_gold_networth.md)<br>• **Ca kiểm thử:** [TC-F10.01-P](#tc-f10) |
| **Mô tả tổng quát** | Bảng điều khiển trung tâm cung cấp góc nhìn toàn cảnh về tiềm lực tài chính cá nhân theo thời gian thực: Tổng tài sản, Tổng nợ, Tài sản ròng (Net Worth), Dòng tiền thuần, Tỷ lệ tiết kiệm và tiến độ các mục tiêu tài chính trên một giao diện thống nhất. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR10.01** | Tổng hợp chỉ số tài chính | Tổng hợp và hiển thị đồng thời các chỉ số tài chính trọng yếu (Total Assets, Total Liabilities, Net Worth, Thu nhập, Chi phí, Dòng tiền) trong một màn hình duy nhất theo thời gian thực, đáp ứng tiêu chuẩn hiệu năng [NFR01](#nfr01). | • `userId` từ phiên xác thực, kỳ thời gian xem (mặc định tháng hiện tại). | • Trả về gói dữ liệu tổng hợp Dashboard hoàn chỉnh (`200 OK`). |
| **FR10.02** | Trực quan hóa biểu đồ xu hướng | Vẽ biểu đồ đường thể hiện biến động Net Worth theo các mốc thời gian lịch sử và biểu đồ tròn phân bổ cơ cấu tài sản (tiền mặt, ngân hàng, vàng) và cơ cấu chi tiêu theo danh mục. | • Dữ liệu lịch sử số dư và giao dịch trạng thái `Posted` (không tính `Draft` theo [BR14](#br14)). | • Hiển thị biểu đồ đồ họa trực quan, hỗ trợ tương tác rê chuột xem chi tiết từng điểm dữ liệu. |
| **FR10.03** | Giám sát tiến độ & Cảnh báo nhanh | Hiển thị widget tiến độ tổng hợp của các mục tiêu tích lũy tài chính, trạng thái các ngân sách chi tiêu tiệm cận ngưỡng và danh sách các khoản nợ sắp đến hạn thanh toán. | • Dữ liệu các module Goal, Budget, Loan hiện hành. | • Giúp người dùng nắm bắt ngay các vấn đề tài chính cần chú ý mà không phải duyệt từng trang riêng lẻ. |

---

### FR11 - Phân tích dòng tiền và Tỷ lệ tiết kiệm

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR11** |
| **Tên chức năng** | Phân tích dòng tiền và Tỷ lệ tiết kiệm (Cash Flow & Saving Rate) |
| **Mức độ ưu tiên** | **P1 (Cốt lõi)** |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR08](#ur08)<br>• **Quy tắc nghiệp vụ:** [BR18](#br18)<br>• **Use Case tương ứng:** [UC10](#uc10)<br>• **Biểu đồ thiết kế:** [Class Diagram (Domain Model)](./uml_modeling/class_diagram.md)<br>• **Ca kiểm thử:** [TC-F11.03-E](#tc-f11) |
| **Mô tả tổng quát** | Hệ thống phân tích dòng tiền vào/ra trong kỳ, tính toán dòng tiền thuần (Net Cash Flow) và tỷ lệ tiết kiệm (Saving Rate) của người dùng, tích hợp cơ chế phòng ngừa lỗi chia cho 0 an toàn. |

**Chi tiết các yêu cầu chức năng bắt buộc:**

| Mã ID | Tên chức năng | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR11.01** | Tính toán Dòng tiền thuần | Tính chênh lệch dòng tiền thực tế theo công thức: $\text{Net Cash Flow} = \text{Total Income} - \text{Total Expense}$ trong kỳ khảo sát. Chỉ tổng hợp các giao dịch `Posted` và các dòng đảo tương ứng; không tính chuyển tiền nội bộ. | • Kỳ khảo sát (Tuần / Tháng / Năm / Tùy chọn). | • Hiển thị dòng tiền dương (Thặng dư - Xanh lá) hoặc âm (Thâm hụt - Đỏ) (`200 OK`). |
| **FR11.02** | Đo lường Tỷ lệ tiết kiệm | Tính phần trăm thu nhập được giữ lại tích lũy theo công thức: $\text{Saving Rate} = \frac{\text{Total Income} - \text{Total Expense}}{\text{Total Income}} \times 100\%$ theo [BR18](#br18). | • Dữ liệu tổng thu nhập và tổng chi phí trong kỳ. | • Hiển thị phần trăm tỷ lệ tiết kiệm trên Dashboard (`200 OK`). |
| **FR11.03** | Xử lý an toàn ngoại lệ số học | Nếu $\text{Total Income} \le 0$, hệ thống không thực hiện phép chia; hiển thị "Chưa xác định (N/A)" an toàn nhằm triệt tiêu lỗi chia cho 0 (`DivideByZeroException`) theo [BR18](#br18). Nếu chi tiêu vượt thu nhập ($\text{Expense} > \text{Income}$), Saving Rate hiển thị giá trị âm màu đỏ cảnh báo. | • Dữ liệu kỳ không có thu nhập hoặc thâm hụt tài chính. | • Ứng dụng duy trì hoạt động ổn định 100%, hiển thị chỉ báo cảnh báo an toàn (`200 OK`). |

---

### FR12 - Tra cứu lịch sử, đối soát và xuất tệp CSV

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR12** |
| **Tên chức năng** | **Tra cứu lịch sử, đối soát và xuất tệp CSV** |
| **Mức độ ưu tiên** | **P1 (Cao)** |
| **Ma trận truy vết** | • Yêu cầu người dùng: [UR09](#ur09)<br>• Quy tắc nghiệp vụ: [BR01](#br01), [BR11](#br11), [BR14](#br14)<br>• Use Case: [UC11](#uc11)<br>• Sequence Diagram: [SQ07](./uml_modeling/sequence_diagram/sq07_reconciliation_csv.md)<br>• Kiểm thử nghiệm thu: [TC-F12](#tc-f12) |
| **Mô tả tổng quát** | Cung cấp công cụ tìm kiếm, lọc phân trang lịch sử giao dịch toàn diện; cho phép người dùng tự đối soát số dư bộ đệm của ví với sổ cái kép bất biến và hỗ trợ xuất dữ liệu ra file CSV chuẩn UTF-8 có kèm BOM, được bảo vệ nghiêm ngặt chống lỗ hổng CSV Formula Injection. |

**Bảng chi tiết các yêu cầu chức năng con:**

| Mã ID | Tên chức năng con | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR12.01** | Tìm kiếm & lọc phân trang lịch sử | Cho phép người dùng tìm kiếm, lọc lịch sử giao dịch đa tiêu chí và xem chi tiết từng bút toán (nguyên tệ, tiền cơ sở, tỷ giá snapshot, các dòng sổ Nợ/Có, liên kết đảo/thay thế). | • Bộ lọc: Khoảng thời gian, loại giao dịch, ví nguồn, danh mục, từ khóa.<br>• Phân trang: `page`, `pageSize` ($\le 100$). | • Trả về danh sách giao dịch phân trang kèm metadata (`200 OK`).<br>• Không có kết quả: Trả về mảng rỗng (`200 OK`). |
| **FR12.02** | Đối soát số dư ví với sổ cái | Cho phép người dùng kích hoạt đối soát tự động: so sánh số dư bộ đệm hiện tại của từng ví với tổng lũy kế phát sinh Nợ/Có từ sổ cái kép theo [BR01](#br01) và [BR14](#br14). | • `walletId` hợp lệ thuộc quyền sở hữu của người dùng. | • Khớp đúng ($100\%$): Báo trạng thái "Khớp sổ" (`200 OK`).<br>• Phát hiện lệch: Hiển thị cảnh báo chênh lệch và kích hoạt đồng bộ an toàn (`200 OK`). |
| **FR12.03** | Xuất báo cáo lịch sử giao dịch CSV | Cho phép xuất danh sách giao dịch (theo bộ lọc hiện hành hoặc toàn bộ) ra tệp định dạng CSV mã hóa UTF-8 kèm tiền tố BOM (`\uFEFF`) để tương thích hoàn toàn với Microsoft Excel và các trình đọc bảng tính tiếng Việt. | • Tiêu chí lọc xuất dữ liệu.<br>• Giới hạn tối đa 10.000 dòng/lần xuất. | • Tải về tệp CSV an toàn với header `Content-Type: text/csv; charset=utf-8` (`200 OK`). |
| **FR12.04** | Khử ký tự độc hại chống CSV Injection | Tự động kiểm tra và khử ký tự độc hại trong toàn bộ các trường văn bản tự do (ghi chú, tên danh mục, đối tác...) trước khi ghi file CSV; nếu chuỗi bắt đầu bằng một trong các ký tự `=`, `+`, `-`, `@`, hệ thống tự động thêm dấu nháy đơn (`'`) ở đầu chuỗi theo chuẩn bảo mật OWASP. | • Nội dung các trường văn bản xuất khẩu. | • Ngăn chặn triệt để nguy cơ thực thi mã độc macro khi mở bằng Excel (`TC-F12.04-E`). |

---

### FR13 - Cấu hình hệ thống và Phát thông báo người dùng (Admin)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR13** |
| **Tên chức năng** | **Cấu hình hệ thống và Phát thông báo người dùng (Admin)** |
| **Mức độ ưu tiên** | **P2 (Trung bình)** |
| **Ma trận truy vết** | • Yêu cầu người dùng: [UR10](#ur10)<br>• Quy tắc nghiệp vụ: [BR07](#br07), [BR08](#br08), [BR11](#br11)<br>• Use Case: [UC13](#uc13)<br>• Sequence Diagram: [SQ08](./uml_modeling/sequence_diagram/sq08_admin_user_broadcast.md)<br>• Kiểm thử nghiệm thu: [TC-F13](#tc-f13) |
| **Mô tả tổng quát** | Cung cấp giao diện quản trị cho Quản trị viên (Admin) để thiết lập danh mục thu/chi mặc định của hệ thống gán cho người dùng mới, điều chỉnh các tham số vận hành chung và phát thông báo phát thanh (Broadcast) đến toàn bộ người dùng. |

**Bảng chi tiết các yêu cầu chức năng con:**

| Mã ID | Tên chức năng con | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR13.01** | Quản lý danh mục hệ thống mặc định | Cho phép Quản trị viên thêm, sửa tên, biểu tượng và kích hoạt/hủy kích hoạt danh mục thu/chi mẫu của hệ thống; bộ danh mục này được tự động gán vào tài khoản người dùng mới khi đăng ký tài khoản. | • Quyền `Admin`.<br>• Tên danh mục, loại (`INCOME` / `EXPENSE`), biểu tượng (icon). | • Lưu thành công danh mục mẫu (`200 OK` / `201 Created`).<br>• Trùng tên danh mục mặc định: Báo lỗi (`409 Conflict`). |
| **FR13.02** | Cấu hình tham số vận hành chung | Cho phép Quản trị viên thiết lập và cập nhật các tham số vận hành hệ thống: ngưỡng cảnh báo ngân sách mặc định (mặc định 80% theo [BR07](#br07), [BR08](#br08)), thời gian timeout phiên truy cập JWT và giới hạn xuất tệp CSV. | • Quyền `Admin`.<br>• Giá trị tham số nằm trong dải cho phép (ví dụ: ngưỡng cảnh báo từ $50\%$ đến $95\%$). | • Cập nhật cấu hình thành công, có hiệu lực toàn hệ thống (`200 OK`).<br>• Giá trị ngoài dải hợp lệ: Từ chối (`422 Unprocessable Entity`). |
| **FR13.03** | Phát thông báo phát thanh (Broadcast) | Cho phép Quản trị viên soạn và phát thông báo khẩn cấp hoặc định kỳ (bảo trì hệ thống, cập nhật tính năng) đến toàn bộ người dùng; bản ghi thông báo tự động hiển thị trong Notification Center của người dùng khi đăng nhập. | • Quyền `Admin`.<br>• Tiêu đề (tối đa 150 ký tự), nội dung thông báo (bắt buộc, tối đa 2000 ký tự), mức độ ưu tiên (`INFO`, `WARNING`, `URGENT`). | • Tạo bản ghi broadcast thành công; toàn bộ người dùng nhận được thông báo (`201 Created`).<br>• Bỏ trống nội dung: Từ chối (`400 Bad Request`). |

---

### FR14 - Quản trị tài khoản người dùng và Dashboard thống kê vĩ mô (Admin)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR14** |
| **Tên chức năng** | **Quản trị tài khoản người dùng và Dashboard thống kê vĩ mô (Admin)** |
| **Mức độ ưu tiên** | **P1 (Cao)** |
| **Ma trận truy vết** | • Yêu cầu người dùng: [UR10](#ur10)<br>• Quy tắc nghiệp vụ: [BR11](#br11)<br>• Use Case: [UC12](#uc12)<br>• Sequence Diagram: [SQ08](./uml_modeling/sequence_diagram/sq08_admin_user_broadcast.md)<br>• Kiểm thử nghiệm thu: [TC-F14](#tc-f14) |
| **Mô tả tổng quát** | Cung cấp bảng điều khiển thống kê tổng quan vĩ mô về tình hình vận hành hệ thống và công cụ quản trị vòng đời tài khoản người dùng (tìm kiếm, khóa/mở khóa tài khoản) trong khi tuân thủ nghiêm ngặt nguyên tắc bảo vệ quyền riêng tư cá nhân người dùng theo [BR11](#br11). |

**Bảng chi tiết các yêu cầu chức năng con:**

| Mã ID | Tên chức năng con | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR14.01** | Dashboard thống kê vĩ mô Admin | Hiển thị các chỉ số vĩ mô phục vụ vận hành: Tổng số người dùng đăng ký, số lượng đăng ký mới theo tháng, số lượng tài khoản hoạt động (`Active`), số lượng tài khoản bị khóa (`Locked`) và phân bố loại tiền cơ sở. | • Quyền `Admin`. | • Trả về dữ liệu thống kê tổng hợp vĩ mô (`200 OK`). |
| **FR14.02** | Bảo mật quyền riêng tư tài chính | Tuyệt đối không hiển thị chi tiết số dư ví, tài sản tích lũy, các khoản vay nợ hay danh sách giao dịch thu chi cá nhân của bất kỳ người dùng nào trên giao diện quản trị Admin theo [BR11](#br11). | • Mọi yêu cầu truy vấn từ tài khoản có vai trò `Admin`. | • Toàn bộ API nghiệp vụ cá nhân bị chặn triệt để, không lộ bất kỳ dữ liệu nhạy cảm nào (`403 Forbidden`). |
| **FR14.03** | Khóa tài khoản kèm lý do vi phạm | Cho phép Quản trị viên tìm kiếm người dùng và thực hiện khóa tài khoản vi phạm (`Locked`); bắt buộc nhập lý do khóa; hệ thống tự động thu hồi ngay lập tức toàn bộ Refresh Token và Access Token hiện hữu của người dùng bị khóa. | • Quyền `Admin`.<br>• `targetUserId` hợp lệ; `lockReason` bắt buộc (tối thiểu 10 ký tự). | • Trạng thái tài khoản chuyển `Locked`, phiên đăng nhập bị hủy tức thì (`200 OK`).<br>• Thiếu lý do khóa: Từ chối (`400 Bad Request`). |
| **FR14.04** | Mở khóa tài khoản người dùng | Cho phép Quản trị viên khôi phục trạng thái hoạt động (`Active`) cho tài khoản sau khi người dùng đã giải trình vi phạm thỏa đáng. | • Quyền `Admin`.<br>• `targetUserId` đang ở trạng thái `Locked`. | • Trạng thái chuyển thành `Active`, người dùng có thể đăng nhập lại bình thường (`200 OK`). |
| **FR14.05** | Bảo vệ tài khoản Admin cuối cùng | Ngăn chặn Quản trị viên tự khóa tài khoản của chính mình hoặc vô hiệu hóa tài khoản Admin duy nhất còn lại trong hệ thống, nhằm ngăn ngừa triệt để nguy cơ tê liệt quyền quản trị hệ thống. | • Yêu cầu khóa tài khoản Admin duy nhất hoặc tự khóa chính mình. | • Hệ thống từ chối thao tác và hiển thị cảnh báo an toàn (`403 Forbidden` / `422 Unprocessable Entity`). |

---

### FR15 - Kiểm soát truy cập API và bảo mật dữ liệu đa người dùng

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm FR** | **FR15** |
| **Tên chức năng** | **Kiểm soát truy cập API và bảo mật dữ liệu đa người dùng** |
| **Mức độ ưu tiên** | **P1 (Cốt lõi - Bắt buộc)** |
| **Ma trận truy vết** | • Yêu cầu người dùng: [UR01](#ur01), [UR11](#ur11), [UR12](#ur12)<br>• Quy tắc nghiệp vụ: [BR11](#br11)<br>• Use Case: Áp dụng toàn bộ Use Cases ([UC01](#uc01) - [UC13](#uc13))<br>• Sequence Diagram: [SQ01](./uml_modeling/sequence_diagram/sq01_auth_jwt.md)<br>• Kiểm thử nghiệm thu: [TC-F15](#tc-f15) |
| **Mô tả tổng quát** | Đóng vai trò lớp phòng thủ kiểm soát truy cập và bảo vệ dữ liệu xuyên suốt toàn bộ API hệ thống; bảo đảm xác thực token JWT, trích xuất định danh an toàn, cô lập triệt để dữ liệu giữa các người dùng và che giấu sự tồn tại của tài nguyên trái phép để ngăn chặn tấn công IDOR. |

**Bảng chi tiết các yêu cầu chức năng con:**

| Mã ID | Tên chức năng con | Mô tả hành vi hệ thống | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng & Mã phản hồi |
| :---: | :--- | :--- | :--- | :--- |
| **FR15.01** | Xác thực JWT & trích xuất danh tính | Kiểm tra tính hợp lệ và thời hạn của JWT trên mọi API bảo vệ; trích xuất `userId` và `role` trực tiếp từ chữ ký số an toàn của Token, tuyệt đối không chấp nhận tham số `userId` do client truyền trong URL/body để chống giả mạo danh tính. | • Header `Authorization: Bearer <jwt_token>`. | • Token hợp lệ: Chuyển tiếp xử lý (`Next`).<br>• Token thiếu, sai chữ ký hoặc hết hạn: Từ chối ngay lập tức (`401 Unauthorized`). |
| **FR15.02** | Kiểm soát quyền sở hữu tài nguyên (IDOR) | Kiểm tra quyền sở hữu đối với $100\%$ yêu cầu truy cập tài nguyên (ví, giao dịch, ngân sách, tài sản, khoản nợ, mục tiêu); nếu tài nguyên không thuộc về người dùng gửi yêu cầu, hệ thống phản hồi `404 Not Found` (thay vì 403) nhằm bảo mật, che giấu sự tồn tại của tài nguyên khỏi hành vi dò quét mã. | • `userId` từ JWT và `resourceId` trong request. | • Thuộc sở hữu: Cho phép xử lý tiếp.<br>• Không thuộc sở hữu hoặc không tồn tại: Trả về `404 Not Found` (ngăn chặn rò rỉ IDOR). |
| **FR15.03** | Ngăn ngừa Admin truy cập sổ sách cá nhân | Kiểm soát phân quyền nghiêm ngặt theo [BR11](#br11): Chặn hoàn toàn các tài khoản có vai trò `Admin` thực hiện các yêu cầu API đọc, ghi hoặc sửa đổi sổ sách tài chính cá nhân của bất kỳ người dùng nào trong hệ thống. | • Token xác thực có vai trò `role: ADMIN`. | • Từ chối mọi yêu cầu truy vấn dữ liệu chi tiết người dùng (`403 Forbidden`). |

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

### 4.2 Đặc tả chi tiết các Use Case hệ thống

---

#### <a id="uc01"></a>UC01 - Đăng ký & Xác thực người dùng

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC01** |
| **Tên Use Case** | **Đăng ký & Xác thực người dùng** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Khách vãng lai (`A01-G`), Người dùng (`A01`)<br>• **Tác nhân phụ:** Quản trị viên (`A02`) |
| **Mục đích (Description)** | Cho phép khách vãng lai đăng ký tài khoản mới kèm tiền cơ sở; người dùng đăng nhập hệ thống an toàn bằng cặp Token JWT (Access/Refresh Token), đổi mật khẩu và đăng xuất an toàn. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Đăng ký" hoặc "Đăng nhập" từ trang chủ, hoặc chọn "Đổi mật khẩu" / "Đăng xuất" từ màn hình Cài đặt cá nhân. |
| **Tiền điều kiện (Pre-condition)** | • **Đăng ký:** Địa chỉ email chưa từng tồn tại trong hệ thống.<br>• **Đăng nhập:** Tài khoản đã tồn tại và ở trạng thái `Active` và không bị khóa.<br>• **Đổi mật khẩu / Đăng xuất:** Đang có phiên đăng nhập hợp lệ. |
| **Hậu điều kiện (Post-condition)** | • Cấp phiên làm việc an toàn (JWT lưu trong Cookie HttpOnly hoặc Header Authorization).<br>• Tự động gán bộ danh mục thu/chi mặc định cho tài khoản mới.<br>• Thu hồi toàn bộ token phiên làm việc khi người dùng đổi mật khẩu hoặc đăng xuất. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR01](#fr01), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR01](#ur01)<br>• **Quy tắc nghiệp vụ:** [BR11](#br11)<br>• **Biểu đồ tuần tự:** [SQ01](./uml_modeling/sequence_diagram/sq01_auth_jwt.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F01](#tc-f01) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Khách truy cập WealthFlow và chọn "Đăng ký tài khoản". | Hệ thống hiển thị biểu mẫu đăng ký kèm danh sách tiền cơ sở hỗ trợ (mặc định VND). |
| **2** | Khách điền Họ tên, Email, Mật khẩu, chọn Tiền cơ sở (`baseCurrency`); nhấn "Đăng ký". | Hệ thống kiểm tra định dạng email, chuẩn hóa (trim, lowercase) và thẩm định tính duy nhất của email. |
| **3** | — | Hệ thống kiểm tra độ phức tạp của mật khẩu theo [NFR02](#nfr02), thực hiện băm mật khẩu an toàn bằng Argon2id/bcrypt kèm Salt ngẫu nhiên. |
| **4** | — | Hệ thống khởi tạo tài khoản ở trạng thái `Active`, tự động sao chép danh mục thu/chi mẫu của hệ thống gán vào tài khoản người dùng theo [FR13.01](#fr13). |
| **5** | — | Hệ thống thông báo đăng ký thành công và chuyển hướng người dùng sang màn hình Đăng nhập. |
| **6** | Người dùng nhập Email, Mật khẩu và nhấn "Đăng nhập". | Hệ thống thẩm định danh tính, đối soát mật khẩu băm và kiểm tra trạng thái tài khoản `Active`. |
| **7** | — | Hệ thống cấp phát cặp Token JWT (Access Token 15 phút & Refresh Token 7 ngày) được ký số an toàn, lưu vết phiên và chuyển hướng người dùng vào Dashboard chính. |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF01.01** | **Người dùng Đổi mật khẩu:**<br>Tại trang Cài đặt, người dùng nhập mật khẩu hiện tại, mật khẩu mới và nhấn "Đổi mật khẩu". | Hệ thống xác minh mật khẩu cũ chính xác, kiểm tra độ mạnh mật khẩu mới, băm và cập nhật Hash mới; đồng thời tăng `tokenVersion` để tự động vô hiệu hóa toàn bộ phiên đăng nhập cũ trên các thiết bị khác theo [FR01.04](#fr01). |
| **AF01.02** | **Người dùng Đăng xuất khỏi hệ thống:**<br>Người dùng nhấn nút "Đăng xuất" trên thanh điều hướng. | Hệ thống đưa Refresh Token hiện tại vào danh sách thu hồi (Blacklist), xóa Cookie HttpOnly chứa token và kết thúc phiên làm việc ngay lập tức theo [FR01.03](#fr01). |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF01.01** | Email đã được đăng ký trước đó trong hệ thống. | Hệ thống từ chối tạo tài khoản, trả về mã lỗi `409 Conflict` kèm thông báo "Email này đã được sử dụng. Vui lòng đăng nhập hoặc sử dụng email khác". |
| **EF01.02** | Mật khẩu không thỏa mãn chính sách bảo mật theo [NFR02](#nfr02). | Hệ thống trả về `400 Bad Request` kèm thông báo lỗi cụ thể (ví dụ: "Mật khẩu tối thiểu 8 ký tự, phải bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt"). |
| **EF01.03** | Sai thông tin đăng nhập (sai email hoặc sai mật khẩu). | Hệ thống trả về mã lỗi `401 Unauthorized` kèm thông báo chung "Email hoặc mật khẩu không chính xác" nhằm phòng chống tấn công dò quét danh tính (User Enumeration). |
| **EF01.04** | Tài khoản đang ở trạng thái bị tạm khóa (`Locked`). | Hệ thống từ chối cấp token, trả về mã `403 Forbidden` kèm thông báo: "Tài khoản của bạn đã bị khóa bởi Quản trị viên. Lý do: [Lý do khóa]". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR11](#br11): Phân quyền và bảo mật dữ liệu đa người dùng tuyệt đối; Admin không có quyền xem thông tin tài chính cá nhân.<br>• Khóa cố định tiền tệ cơ sở (`baseCurrency`) ngay khi tạo tài khoản, không cho phép thay đổi để bảo đảm toàn vẹn sổ kế toán kép. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi API xác thực và cấp token $\le 200\text{ ms}$.<br>• [NFR02](#nfr02): Mật khẩu băm bằng Argon2id hoặc bcrypt (cost factor $\ge 12$); truyền dữ liệu qua HTTPS (TLS 1.3).<br>• [NFR06](#nfr06): Thông báo lỗi bằng tiếng Việt rõ ràng, thân thiện, không để lộ vết ngăn xếp kỹ thuật (Stack Trace). |

---

#### <a id="uc02"></a>UC02 - Quản lý tài khoản thanh toán và ví tiền

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC02** |
| **Tên Use Case** | **Quản lý tài khoản thanh toán và ví tiền** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng khởi tạo, cấu hình các nguồn tiền (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm); tự động hạch toán đối ứng số dư đầu kỳ vào Vốn chủ sở hữu theo nguyên lý kế toán kép và quản lý trạng thái lưu trữ của ví. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Quản lý Ví" $\rightarrow$ "Tạo ví mới" hoặc thao tác chỉnh sửa/lưu trữ ví từ danh sách ví hiện hữu. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ của người dùng (`A01`). |
| **Hậu điều kiện (Post-condition)** | • Bản ghi ví mới được tạo trong CSDL ở trạng thái `Active` kèm `walletId` duy nhất.<br>• Tự động sinh bút toán kép đối ứng Vốn chủ sở hữu (Equity) nếu số dư đầu kỳ $> 0$.<br>• Ví sẵn sàng thực hiện các giao dịch thu, chi và chuyển tiền. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR02](#fr02), [FR04](#fr04), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR02](#ur02)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR02](#br02), [BR03](#br03), [BR11](#br11)<br>• **Ca kiểm thử nghiệm thu:** [TC-F02](#tc-f02) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Quản lý Ví" và nhấn "Tạo ví mới". | Hệ thống hiển thị biểu mẫu tạo ví với các trường: Tên ví, Loại ví, Loại tiền tệ và Số dư ban đầu. |
| **2** | Người dùng nhập Tên ví, chọn Loại ví (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm), chọn Tiền tệ (VND/USD/EUR) và nhập Số dư ban đầu; nhấn "Lưu ví". | Hệ thống kiểm tra dữ liệu: số dư ban đầu $\ge 0$, tên ví không rỗng và không trùng lặp với ví đang hoạt động khác của người dùng. |
| **3** | — | Hệ thống mở Database Transaction nguyên tử: tạo bản ghi Ví mới với số dư bộ đệm bằng số dư ban đầu. |
| **4** | — | Nếu số dư ban đầu $> 0$, hệ thống tự động sinh bút toán kế toán kép: Ghi Nợ (Debit) tài khoản Ví mới và Ghi Có (Credit) tài khoản Vốn chủ sở hữu (`Equity:Opening Balance`) theo [BR01](#br01) và [BR02](#br02). |
| **5** | — | Hệ thống commit transaction, cập nhật danh sách ví trên giao diện và hiển thị thông báo "Tạo ví mới thành công". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF02.01** | **Người dùng chỉnh sửa thông tin ví:**<br>Tại danh sách ví, người dùng chọn "Chỉnh sửa" tên ví, biểu tượng hoặc màu sắc hiển thị. | Hệ thống cập nhật metadata hiển thị của ví (`200 OK`); bảo toàn nguyên vẹn số dư và lịch sử giao dịch liên quan. |
| **AF02.02** | **Người dùng lưu trữ (Archive) ví:**<br>Người dùng chọn chức năng "Lưu trữ ví" khi không còn nhu cầu sử dụng nguồn tiền này. | Hệ thống kiểm tra số dư ví bắt buộc bằng 0 và không có giao dịch định kỳ đang liên kết; chuyển trạng thái ví sang `Archived` (ẩn khỏi danh sách chọn nguồn tiền khi tạo giao dịch mới nhưng giữ nguyên dữ liệu trong báo cáo lịch sử) theo [FR02.03](#fr02). |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF02.01** | Người dùng nhập số dư đầu kỳ âm ($< 0$). | Hệ thống từ chối lưu, trả về mã lỗi `400 Bad Request` kèm thông báo "Số dư mở sổ ban đầu không được nhỏ hơn 0". |
| **EF02.02** | Tên ví bị trùng với một ví khác của cùng người dùng. | Hệ thống từ chối lưu, trả về mã `409 Conflict` kèm thông báo "Tên ví này đã tồn tại trong danh mục ví của bạn". |
| **EF02.03** | Lưu trữ ví khi số dư ví vẫn còn khác 0. | Hệ thống từ chối thao tác, trả về `400 Bad Request` kèm cảnh báo "Ví vẫn còn số dư. Vui lòng chuyển toàn bộ số dư sang ví khác trước khi lưu trữ ví". |
| **EF02.04** | Lưu trữ ví đang được liên kết trong lịch định kỳ còn hiệu lực. | Hệ thống từ chối thao tác, thông báo yêu cầu hủy bỏ hoặc đổi ví thanh toán trong các giao dịch định kỳ liên quan trước khi lưu trữ ví. |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR01](#br01): Mọi biến động số dư ví bắt buộc thông qua bút toán kép Nợ/Có cân bằng 100%.<br>• [BR02](#br02): Số dư ban đầu của ví được đối ứng Vốn chủ sở hữu, đóng góp trực tiếp vào Tổng tài sản và Net Worth.<br>• [BR03](#br03): Hỗ trợ ví đa tiền tệ; ví ngoại tệ lưu giá trị nguyên tệ và quy đổi hiển thị theo tỷ giá thời điểm. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi API tạo ví và cập nhật số dư $\le 200\text{ ms}$.<br>• [NFR07](#nfr07): Định dạng số dư theo chuẩn tiền tệ (dấu chấm phân cách hàng nghìn cho VND, không có phần thập phân). |

---

#### <a id="uc03"></a>UC03 - Ghi nhận & Quản lý giao dịch tài chính

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC03** |
| **Tên Use Case** | **Ghi nhận & Quản lý giao dịch tài chính** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng ghi nhận thu chi, chuyển tiền nội bộ; tự động sinh bút toán Nợ/Có cân bằng 100% trong một giao dịch nguyên tử theo nguyên lý kế toán kép; áp dụng chống trừ tiền trùng lặp (Idempotency) và đảo giao dịch an toàn. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng nhấn "Thêm giao dịch", "Chuyển tiền" hoặc chọn "Đảo giao dịch" từ lịch sử giao dịch. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ; có ít nhất một ví tài sản đang hoạt động (`Active`). |
| **Hậu điều kiện (Post-condition)** | • Bản ghi giao dịch được lưu ở trạng thái `Posted` với mã UUID duy nhất.<br>• Các dòng sổ kép Nợ/Có cân bằng tuyệt đối ($\sum \text{Debit} = \sum \text{Credit}$).<br>• Số dư bộ đệm của các ví liên quan được cập nhật chính xác.<br>• Kích hoạt tác vụ ngầm kiểm tra hạn mức ngân sách [SYS03](#sys03). |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR03](#fr03), [FR04](#fr04), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR02](#ur02)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR04](#br04), [BR06](#br06), [BR12](#br12), [BR13](#br13), [BR15](#br15)<br>• **Biểu đồ tuần tự:** [SQ02](./uml_modeling/sequence_diagram/sq02_double_entry_idempotency.md), [SQ03](./uml_modeling/sequence_diagram/sq03_multicurrency_revaluation.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F03](#tc-f03), [TC-F04](#tc-f04) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path - Ghi nhận Chi tiêu):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng nhấn nút "Thêm giao dịch" và chọn loại "Chi tiêu". | Hệ thống hiển thị biểu mẫu nhập giao dịch: Ví nguồn, Danh mục chi tiêu, Số tiền, Ngày giao dịch, Mô tả. |
| **2** | Người dùng chọn ví thanh toán, chọn danh mục (Ăn uống, Đi lại...), nhập số tiền, ngày giao dịch và mô tả; nhấn "Lưu giao dịch". | Hệ thống kiểm tra tính hợp lệ: số tiền $> 0$, ngày giao dịch không ở tương lai, ví thanh toán đủ số dư khả dụng. |
| **3** | — | Client tự động tạo header `Idempotency-Key` (UUID v4) gửi kèm yêu cầu POST. |
| **4** | — | Hệ thống kiểm tra `Idempotency-Key` theo [BR13](#br13). Nếu chưa từng xử lý, hệ thống mở Database Transaction nguyên tử. |
| **5** | — | Hệ thống tạo bản ghi Giao dịch ở trạng thái `Posted`; sinh dòng Nợ (Debit) Tài khoản Chi phí và dòng Có (Credit) Ví thanh toán theo [BR01](#br01); cập nhật giảm số dư bộ đệm của ví. |
| **6** | — | Hệ thống commit transaction, lưu trạng thái khóa Idempotency, kích hoạt kiểm tra ngân sách [SYS03](#sys03) và phản hồi `201 Created` kèm dữ liệu giao dịch. |
| **7** | — | Giao diện cập nhật số dư ví mới và hiển thị thông báo "Đã lưu giao dịch thành công". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF03.01** | **Chuyển tiền nội bộ giữa 2 ví:**<br>Người dùng chọn loại "Chuyển tiền", chọn ví nguồn, ví đích và số tiền chuyển. | Hệ thống kiểm tra 2 ví khác nhau; sinh dòng Nợ Ví đích và Có Ví nguồn; không làm thay đổi tổng tài sản và không ghi nhận thu/chi theo [BR04](#br04). |
| **AF03.02** | **Đảo giao dịch đã ghi sổ (Reversal Transaction):**<br>Người dùng chọn "Hủy/Đảo giao dịch" từ lịch sử cho một giao dịch sai sót. | Hệ thống tuyệt đối không xóa cứng (hard delete); tự động sinh cặp bút toán đảo đối ứng (`Reversed`), hoàn trả số dư ví và lưu liên kết tham chiếu đến giao dịch gốc theo [BR06](#br06). |
| **AF03.03** | **Giao dịch bằng ngoại tệ:**<br>Người dùng chi tiêu từ ví ngoại tệ (USD/EUR). | Hệ thống lấy tỷ giá hối đoái thời điểm, khóa cứng snapshot tỷ giá bất biến vào giao dịch và quy đổi giá trị tiền cơ sở theo [BR15](#br15). |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF03.01** | Trùng `Idempotency-Key` do mạng bị retry lại. | • Cùng payload: Hệ thống trả lại kết quả giao dịch cũ đã xử lý thành công (`200 OK`), không trừ tiền lần hai theo [BR13](#br13).<br>• Khác payload: Trả về mã `409 Conflict`. |
| **EF03.02** | Số tiền $\le 0$ hoặc ngày giao dịch vượt quá ngày hiện tại. | Hệ thống từ chối lưu, trả về `400 Bad Request` kèm thông báo lỗi trường cụ thể. |
| **EF03.03** | Số dư ví không đủ chi trả (áp dụng cho ví không cho phép số dư âm). | Hệ thống từ chối lưu, trả về `400 Bad Request` kèm thông báo "Số dư ví hiện tại không đủ để thực hiện giao dịch". |
| **EF03.04** | Lỗi kết nối CSDL hoặc sự cố hệ thống trong lúc ghi sổ. | Toàn bộ Database Transaction bị Rollback sạch sẽ 100%; không tạo bản ghi mồ côi; số dư ví giữ nguyên; ghi log kèm requestId theo [TC-F03.02-E](#tc-f03). |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR01](#br01): Sổ kế toán kép bất biến; [BR12](#br12): Khóa mức dòng (`SELECT FOR UPDATE`) trên ví khi cập nhật số dư.<br>• [BR13](#br13): Bắt buộc kiểm soát Idempotency trên 100% yêu cầu ghi sổ tài chính.<br>• [BR06](#br06): Không xóa dữ liệu tài chính, chỉ tạo bút toán đảo đối ứng. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi ghi sổ kép và kiểm tra Idempotency $T_{write} \le 500\text{ ms}$.<br>• [NFR03](#nfr03): Tính nguyên tử ACID và toàn vẹn dữ liệu sổ cái đạt 100%. |

---

#### <a id="uc04"></a>UC04 - Thiết lập & Xử lý giao dịch định kỳ

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC04** |
| **Tên Use Case** | **Thiết lập & Xử lý giao dịch định kỳ** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`)<br>• **Tác nhân phụ:** Tiến trình nền hệ thống (`SYS02`) |
| **Mục đích (Description)** | Cho phép người dùng lập lịch các khoản thu/chi lặp lại tự động định kỳ (tiền nhà, hóa đơn điện nước, dịch vụ thuê bao); tiến trình nền định kỳ quét và tự động sinh giao dịch ghi sổ đúng ngày đến hạn, xử lý an toàn tháng thiếu ngày. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Giao dịch định kỳ" $\rightarrow$ "Tạo lịch mới"; hoặc bộ lập lịch `SYS02` kích hoạt quét mỗi 5 phút. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ; ví thanh toán và danh mục liên kết đang ở trạng thái hoạt động. |
| **Hậu điều kiện (Post-condition)** | • Bản ghi lịch định kỳ được lưu với chu kỳ, ngày neo và tùy chọn tự động ghi sổ.<br>• Định kỳ đến hạn, hệ thống tự động sinh bút toán kế toán kép chuẩn xác theo [BR09](#br09). |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR06](#fr06)<br>• **Yêu cầu người dùng:** [UR04](#ur04)<br>• **Quy tắc nghiệp vụ:** [BR09](#br09), [BR12](#br12), [BR13](#br13)<br>• **Tác vụ nền liên quan:** [SYS02](#sys02)<br>• **Biểu đồ tuần tự:** [SQ04](./uml_modeling/sequence_diagram/sq04_recurring_scheduler.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F06](#tc-f06) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Giao dịch định kỳ" và nhấn "Tạo lịch mới". | Hệ thống hiển thị biểu mẫu thiết lập: Loại giao dịch, Ví thanh toán, Danh mục, Số tiền, Chu kỳ lặp (Hàng tháng, Hàng tuần), Ngày neo lặp, Ngày bắt đầu và tùy chọn "Tự động ghi sổ". |
| **2** | Người dùng điền thông tin: Chi phí tiền nhà 5.000.000 VND từ ví Vietcombank, lặp lại ngày 31 hàng tháng, bật tự động ghi sổ; nhấn "Lưu lịch". | Hệ thống kiểm tra số tiền $> 0$, ngày bắt đầu hợp lệ; lưu bản ghi định kỳ với trạng thái `Active`. |
| **3** | — | Hệ thống tính toán và xác định `nextDueDate` (ngày đến hạn kế tiếp) và hiển thị thông báo tạo thành công. |
| **4** | *(Tiến trình nền SYS02)* Đến thời điểm $nextDueDate \le \text{now}$, cron job quét phát hiện lịch đến hạn. | Hệ thống tạo khóa định danh `Idempotency-Key` duy nhất `(subscriptionId, dueDate)` theo [BR13](#br13). |
| **5** | — | Hệ thống kiểm tra số dư ví; thực thi Transaction sinh giao dịch chi tiêu kế toán kép ở trạng thái `Posted` và trừ số dư ví. |
| **6** | — | Hệ thống cập nhật `lastRunDate` và tính `nextDueDate` cho chu kỳ kế tiếp theo quy tắc ngày neo [BR09](#br09). |
| **7** | — | Hệ thống gửi thông báo toast/notification: "Đã tự động xử lý giao dịch định kỳ: Tiền nhà tháng X (5.000.000 VND)". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF04.01** | **Tùy chọn "Nhắc nhở trước khi ghi sổ" (Không bật tự động):**<br>Lịch định kỳ đến hạn nhưng người dùng không bật tự động ghi sổ. | Hệ thống không tự trừ tiền; sinh thông báo nhắc nhở đến chuông Notification kèm nút "Xác nhận ghi sổ ngay" để người dùng chủ động duyệt. |
| **AF04.02** | **Tạm dừng hoặc Hủy lịch định kỳ:**<br>Người dùng chọn "Tạm dừng" hoặc "Xóa lịch" từ danh sách. | Hệ thống chuyển trạng thái sang `Paused` hoặc `Cancelled`; tiến trình nền sẽ bỏ qua không quét lịch này trong các chu kỳ sau. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF04.01** | Ngày neo lặp rơi vào tháng thiếu ngày (ví dụ ngày neo 31 nhưng tháng 2 chỉ có 28 ngày). | Hệ thống tự động lùi ngày chạy về ngày cuối cùng của tháng đó (28/02) và bảo lưu ngày neo 31 cho tháng 3 theo quy tắc [BR09](#br09) (`TC-F06.01-P`). |
| **EF04.02** | Ví thanh toán không đủ số dư khi tiến trình nền tự động chạy. | Hệ thống không ghi sổ âm; giữ trạng thái kỳ đó là `Pending/Failed`, gửi thông báo khẩn cấp đến người dùng: "Giao dịch định kỳ thất bại do ví không đủ tiền". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR09](#br09): Xử lý ngày neo cuối tháng thiếu ngày và bảo lưu ngày neo cố định.<br>• [BR13](#br13): Khóa chống xử lý lặp `(subscriptionId, dueDate)` đảm bảo mỗi kỳ định kỳ chỉ sinh đúng 1 giao dịch duy nhất. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Tác vụ quét nền chạy không làm ảnh hưởng đến độ trễ của các API tương tác trực tiếp người dùng.<br>• [NFR04](#nfr04): Độ tin cậy thực thi tác vụ nền đạt tối thiểu 99.9%. |

---

#### <a id="uc05"></a>UC05 - Thiết lập & Kiểm soát ngân sách chi tiêu

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC05** |
| **Tên Use Case** | **Thiết lập & Kiểm soát ngân sách chi tiêu** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`)<br>• **Tác nhân phụ:** Tiến trình nền hệ thống (`SYS03`) |
| **Mục đích (Description)** | Cho phép người dùng thiết lập hạn mức chi tiêu tháng cho từng danh mục; hệ thống tự động theo dõi lũy kế chi tiêu thời gian thực và phát thông báo cảnh báo tiệm cận (80%) hoặc vượt mức (100%). |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Quản lý Ngân sách" $\rightarrow$ "Thêm ngân sách"; hoặc phát sinh một giao dịch chi tiêu mới thuộc danh mục có ngân sách. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ; có ít nhất một danh mục chi phí đang hoạt động. |
| **Hậu điều kiện (Post-condition)** | • Bản ghi hạn mức ngân sách được lưu cho tháng khảo sát.<br>• Tỷ lệ phần trăm sử dụng ngân sách được cập nhật tức thời trên thanh tiến độ.<br>• Phát sinh cảnh báo tại chuông Notification khi vượt ngưỡng quy định. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR05](#fr05)<br>• **Yêu cầu người dùng:** [UR03](#ur03)<br>• **Quy tắc nghiệp vụ:** [BR07](#br07), [BR08](#br08)<br>• **Tác vụ nền liên quan:** [SYS03](#sys03)<br>• **Ca kiểm thử nghiệm thu:** [TC-F05](#tc-f05) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Quản lý Ngân sách" và nhấn "Thêm ngân sách". | Hệ thống hiển thị biểu mẫu thiết lập: Danh mục chi tiêu, Tháng áp dụng (mặc định tháng hiện tại), Số tiền hạn mức. |
| **2** | Người dùng chọn danh mục "Ăn uống", nhập hạn mức 3.000.000 VND và nhấn "Lưu ngân sách". | Hệ thống kiểm tra: hạn mức $> 0$, chưa có ngân sách cho danh mục này trong tháng được chọn. |
| **3** | — | Hệ thống tạo bản ghi `Budget` mới; truy vấn tổng chi phí đã phát sinh của danh mục "Ăn uống" từ đầu tháng đến hiện tại. |
| **4** | — | Hệ thống tính tỷ lệ sử dụng: $\text{\% Used} = \frac{\text{Actual Expense}}{\text{Budget Amount}} \times 100\%$; hiển thị thẻ ngân sách kèm thanh tiến độ màu xanh lá (dưới 80%). |
| **5** | Khi người dùng thêm giao dịch chi tiêu ăn uống mới làm tổng chi đạt 2.550.000 VND (85%). | Hệ thống kích hoạt kiểm tra ngân sách theo [BR07](#br07); phát hiện vượt ngưỡng 80%; tự động đổi thanh tiến độ sang màu Vàng cam và đẩy thông báo "Cảnh báo ngân sách: Danh mục Ăn uống đã sử dụng 85% hạn mức". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF05.01** | **Cảnh báo vượt ngân sách 100% (Cảnh báo Đỏ):**<br>Giao dịch chi tiêu mới đưa tổng chi vượt quá 100% hạn mức (ví dụ đạt 105%). | Hệ thống hiển thị thanh tiến độ màu Đỏ; gửi thông báo khẩn cấp: "Bội chi ngân sách: Danh mục Ăn uống đã chi vượt 100% hạn mức". Hệ thống **không** chặn giao dịch thực tế theo ranh giới In-Scope/Out-of-Scope (§1.2). |
| **AF05.02** | **Điều chỉnh hạn mức ngân sách:**<br>Người dùng chọn sửa hạn mức ngân sách của tháng hiện tại. | Hệ thống lưu hạn mức mới, tự động tính toán lại tỷ lệ phần trăm và cập nhật trạng thái màu sắc cảnh báo tức thời. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF05.01** | Hạn mức ngân sách nhập vào $\le 0$. | Hệ thống từ chối lưu, trả về mã lỗi `400 Bad Request` kèm thông báo "Hạn mức ngân sách phải lớn hơn 0". |
| **EF05.02** | Tạo trùng ngân sách cho cùng một danh mục trong cùng một tháng. | Hệ thống từ chối lưu, trả về mã `409 Conflict` kèm thông báo "Danh mục này đã được thiết lập ngân sách cho tháng được chọn". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR07](#br07): Ngưỡng cảnh báo tiệm cận ngân sách mặc định là $80\%$.<br>• [BR08](#br08): Bội chi ngân sách ($\ge 100\%$) phát cảnh báo đỏ nhưng không chặn giao dịch chi tiêu của người dùng.<br>• Phân định ngân sách theo từng tháng riêng biệt, không tự động cộng dồn số dư dư thừa sang tháng sau trừ khi người dùng chủ động cấu hình. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Tính toán lại tỷ lệ ngân sách hoàn tất trong vòng $\le 100\text{ ms}$ sau khi commit giao dịch.<br>• [NFR06](#nfr06): Giao diện trực quan với mã màu chuẩn: Xanh lá ($< 80\%$), Vàng cam ($80\% - 99\%$), Đỏ ($\ge 100\%$). |

---

#### <a id="uc06"></a>UC06 - Quản lý khoản vay và nghĩa vụ nợ vi mô

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC06** |
| **Tên Use Case** | **Quản lý khoản vay và nghĩa vụ nợ vi mô** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng theo dõi các khoản vay trả góp, vay người thân hoặc vay vi mô; quản lý dư nợ gốc, lãi suất, lịch đáo hạn và tùy chọn tự động ghi nhận tiền giải ngân vào ví tài sản. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn mục "Khoản vay & Nợ" $\rightarrow$ "Thêm khoản vay mới". |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ của người dùng (`A01`). |
| **Hậu điều kiện (Post-condition)** | • Bản ghi khoản nợ vi mô được tạo ở trạng thái `Active`.<br>• Dư nợ gốc ban đầu được ghi nhận vào Tổng nợ (`Total Liabilities`).<br>• Tự động sinh bút toán Nợ Ví / Có Nợ phải trả nếu người dùng chọn ghi nhận giải ngân. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR09](#fr09), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR07](#ur07)<br>• **Quy tắc nghiệp vụ:** [BR05](#br05), [BR17](#br17)<br>• **Tác vụ nền liên quan:** [SYS03](#sys03)<br>• **Ca kiểm thử nghiệm thu:** [TC-F09](#tc-f09) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Khoản vay & Nợ" và nhấn "Thêm khoản vay mới". | Hệ thống hiển thị biểu mẫu: Tên khoản vay, Đối tác cho vay (Ngân hàng / Người thân), Số tiền vay gốc, Lãi suất (%/năm), Ngày bắt đầu, Ngày đáo hạn, Tùy chọn "Ghi nhận giải ngân vào ví". |
| **2** | Người dùng nhập thông tin: Vay trả góp laptop 15.000.000 VND từ FE Credit, lãi suất 12%/năm, ngày đáo hạn 15/10/2027; chọn nhận tiền vào "Ví Vietcombank"; nhấn "Lưu khoản vay". | Hệ thống kiểm tra: số tiền $> 0$, lãi suất $\ge 0$, ngày đáo hạn sau ngày bắt đầu. |
| **3** | — | Hệ thống mở Database Transaction nguyên tử: tạo bản ghi `Loan` ở trạng thái `Active` với `remainingPrincipal = 15.000.000 VND`. |
| **4** | — | Vì có chọn giải ngân, hệ thống tự động sinh bút toán kế toán kép: Ghi Nợ (Debit) Ví Vietcombank (15tr) và Ghi Có (Credit) Tài khoản Nợ phải trả (`Liability:Loan`) (15tr) theo [BR01](#br01). |
| **5** | — | Hệ thống cập nhật số dư ví Vietcombank tăng 15tr; cập nhật Dashboard: Total Assets tăng 15tr, Total Liabilities tăng 15tr, Net Worth không đổi theo [BR05](#br05). |
| **6** | — | Hệ thống commit transaction và hiển thị thông báo "Đã tạo khoản vay thành công". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF06.01** | **Tạo khoản nợ đã phát sinh trước đó (Không nhận tiền giải ngân):**<br>Khoản vay đã giải ngân từ trước ngày sử dụng phần mềm, người dùng chỉ muốn theo dõi nghĩa vụ trả nợ. | Hệ thống không ghi bút toán tăng ví; chỉ khởi tạo bản ghi theo dõi dư nợ nợ gốc và cộng giá trị vào Tổng nợ (`Total Liabilities`) trên Dashboard. |
| **AF06.02** | **Cập nhật ghi chú và lịch nhắc nợ:**<br>Người dùng chỉnh sửa thông tin liên hệ chủ nợ hoặc ghi chú kỳ thanh toán. | Hệ thống cập nhật thông tin bổ trợ (`200 OK`), bảo toàn nguyên vẹn số dư nợ gốc. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF06.01** | Số tiền nợ gốc nhập vào $\le 0$. | Hệ thống từ chối lưu, trả về `400 Bad Request` kèm thông báo "Số tiền nợ gốc phải lớn hơn 0". |
| **EF06.02** | Lãi suất nhập vào là số âm ($< 0$). | Hệ thống từ chối lưu, trả về `400 Bad Request` kèm thông báo "Lãi suất không được là số âm". |
| **EF06.03** | Ngày đáo hạn trước hoặc bằng ngày bắt đầu vay. | Hệ thống từ chối lưu, trả về `400 Bad Request` kèm thông báo "Ngày đáo hạn phải sau ngày bắt đầu vay". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR05](#br05): Tiền vay nhận về không được tính vào Thu nhập; nợ gốc chỉ làm tăng Tổng nợ và giữ nguyên Net Worth.<br>• [BR17](#br17): Tách bạch bắt buộc giữa theo dõi nợ gốc và chi phí phát sinh.<br>• Ranh giới Out-of-Scope (§1.2): Ứng dụng chỉ đóng vai trò sổ theo dõi nợ, không giải ngân tiền mặt hay liên kết thẩm định tín dụng thực tế. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi API tạo khoản vay và hạch toán giải ngân $\le 200\text{ ms}$.<br>• [NFR06](#nfr06): Hiển thị bảng tóm tắt tiến độ trả nợ rõ ràng theo từng kỳ. |

---

#### <a id="uc07"></a>UC07 - Ghi nhận thanh toán trả nợ vay

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC07** |
| **Tên Use Case** | **Ghi nhận thanh toán trả nợ vay** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng ghi nhận thanh toán từng kỳ hoặc tất toán khoản nợ; thực hiện bóc tách bắt buộc giữa tiền Trả nợ gốc (giảm nợ, không tính vào chi tiêu) và Tiền lãi/phí (ghi nhận chi phí chi tiêu) theo chuẩn kế toán kép. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn một khoản nợ đang `Active` và nhấn "Thanh toán trả nợ". |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ; khoản nợ đang ở trạng thái `Active` có dư nợ $> 0$; ví thanh toán có đủ số dư. |
| **Hậu điều kiện (Post-condition)** | • Dư nợ gốc của khoản vay giảm tương ứng với số tiền trả gốc.<br>• Số dư ví thanh toán giảm tổng số tiền (Gốc + Lãi/phí).<br>• Chi phí lãi được ghi nhận vào báo cáo thu chi; tiền trả gốc không bị trừ vào hạn mức ngân sách sinh hoạt.<br>• Khoản nợ tự động chuyển sang `Closed` nếu dư nợ gốc về 0. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR03](#fr03), [FR09](#fr09), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR07](#ur07)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR05](#br05), [BR17](#br17)<br>• **Biểu đồ tuần tự:** [SQ05](./uml_modeling/sequence_diagram/sq05_loan_repayment_split.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F09](#tc-f09) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng chọn khoản vay cần thanh toán và nhấn "Thanh toán kỳ". | Hệ thống hiển thị biểu mẫu thanh toán kèm số dư nợ gốc hiện tại (ví dụ: 10.000.000 VND). |
| **2** | Người dùng nhập: Tiền trả gốc (2.000.000 VND), Tiền lãi/phí (150.000 VND), chọn Ví thanh toán (Ví Ngân hàng, số dư 5.000.000 VND), ngày trả nợ; nhấn "Xác nhận thanh toán". | Hệ thống kiểm tra: Tiền trả gốc $\le$ Dư nợ hiện tại (2tr $\le$ 10tr); Tổng tiền thanh toán (2.150.000 VND) $\le$ Số dư ví (5.000.000 VND). |
| **3** | — | Hệ thống mở Database Transaction nguyên tử theo [BR12](#br12). |
| **4** | — | Hệ thống cập nhật giảm dư nợ gốc khoản vay: $10.000.000 - 2.000.000 = 8.000.000\text{ VND}$. |
| **5** | — | Hệ thống sinh bút toán kép phân rã theo [BR05](#br05) & [BR17](#br17):<br>• Ghi Nợ: Tài khoản Nợ phải trả (`Liability`): 2.000.000 VND.<br>• Ghi Nợ: Tài khoản Chi phí lãi vay (`Expense:Interest`): 150.000 VND.<br>• Ghi Có: Ví Ngân hàng (`Asset:Wallet`): 2.150.000 VND. |
| **6** | — | Hệ thống cập nhật giảm số dư ví Ngân hàng còn 2.850.000 VND. |
| **7** | — | Hệ thống commit transaction; cập nhật Dashboard: Total Assets giảm 2.150k, Total Liabilities giảm 2.000k, Net Worth chỉ giảm đúng 150k (tiền lãi). |
| **8** | — | Hệ thống hiển thị thông báo "Đã ghi nhận thanh toán nợ kỳ 1 thành công". |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF07.01** | **Tất toán toàn bộ khoản vay (Thanh toán lần cuối):**<br>Người dùng thanh toán toàn bộ số dư nợ còn lại. | Hệ thống trừ hết dư nợ về 0; tự động chuyển trạng thái khoản vay sang `Closed` (Đã hoàn tất nghĩa vụ nợ). |
| **AF07.02** | **Chỉ thanh toán tiền lãi/phí (Không trả gốc):**<br>Người dùng nhập Tiền trả gốc = 0, chỉ trả tiền lãi phát sinh trong kỳ. | Hệ thống chỉ sinh bút toán Nợ Chi phí lãi / Có Ví; bảo lưu nguyên vẹn dư nợ gốc của khoản vay. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF07.01** | Tiền trả nợ gốc nhập vào lớn hơn dư nợ gốc hiện tại của khoản vay. | Hệ thống từ chối lưu, trả về mã lỗi `400 Bad Request` kèm thông báo "Số tiền trả nợ gốc không được vượt quá dư nợ hiện tại". |
| **EF07.02** | Số dư ví thanh toán không đủ tổng số tiền (Gốc + Lãi). | Hệ thống từ chối thanh toán, trả về `400 Bad Request` kèm thông báo "Số dư ví không đủ để thực hiện thanh toán". |
| **EF07.03** | Thực hiện thanh toán trên một khoản vay đã ở trạng thái đóng (`Closed`). | Hệ thống từ chối thao tác, thông báo "Khoản vay này đã được tất toán hoàn tất". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR05](#br05): Tiền trả nợ gốc KHÔNG được tính vào chi phí chi tiêu sinh hoạt để tránh làm sai lệch báo cáo dòng tiền và ngân sách.<br>• [BR17](#br17): Bắt buộc phân rã dòng tiền Nợ gốc vs Lãi/phí trong cùng một bút toán nguyên tử. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi ghi sổ thanh toán nợ $\le 500\text{ ms}$.<br>• [NFR03](#nfr03): Tính toàn vẹn dữ liệu kế toán kép đạt 100%, không để lệch sổ giữa Nợ phải trả và Ví thanh toán. |

---

#### <a id="uc08"></a>UC08 - Quản lý tài sản tích lũy ngoài ví (Vàng)

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC08** |
| **Tên Use Case** | **Quản lý tài sản tích lũy ngoài ví (Vàng)** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng theo dõi vàng tích trữ (nhẫn tròn trơn 9999, vàng miếng SJC); ghi nhận số lượng (chỉ/lượng) và định giá thị trường để đóng góp chính xác vào Tổng tài sản (Total Assets) và Tài sản ròng (Net Worth). |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Tài sản tích lũy" $\rightarrow$ "Thêm tài sản vàng" hoặc thao tác bán/định giá lại thị trường. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ của người dùng (`A01`). |
| **Hậu điều kiện (Post-condition)** | • Bản ghi tài sản vàng được lưu trong CSDL kèm số lượng và thị giá.<br>• Giá trị quy đổi của vàng được phản ánh trung thực vào Tổng tài sản và Net Worth theo [BR16](#br16). |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR08](#fr08), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR06](#ur06)<br>• **Quy tắc nghiệp vụ:** [BR02](#br02), [BR16](#br16)<br>• **Biểu đồ tuần tự:** [SQ06](./uml_modeling/sequence_diagram/sq06_gold_asset_pricing.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F08](#tc-f08) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Tài sản tích lũy" và nhấn "Thêm tài sản vàng". | Hệ thống hiển thị biểu mẫu: Loại vàng (Vàng nhẫn 9999 / Vàng miếng SJC), Số lượng (chỉ), Đơn giá mua (VND/chỉ), Đơn giá thị trường hiện tại (VND/chỉ), Nơi cất giữ. |
| **2** | Người dùng nhập: 2 chỉ Vàng nhẫn 9999, đơn giá mua 8.200.000 VND/chỉ, đơn giá hiện tại 8.500.000 VND/chỉ; nhấn "Lưu tài sản". | Hệ thống kiểm tra số lượng $> 0$, đơn giá $> 0$. |
| **3** | — | Hệ thống tạo bản ghi tài sản ở trạng thái `Active`; tự động tính giá trị thị trường: $\text{Market Value} = 2 \times 8.500.000 = 17.000.000\text{ VND}$. |
| **4** | — | Hệ thống cập nhật Dashboard: Total Assets tăng 17.000.000 VND, Net Worth tăng 17.000.000 VND theo [BR16](#br16). |
| **5** | — | Hệ thống hiển thị thông báo "Đã lưu tài sản vàng thành công" và hiển thị thẻ tài sản kèm lãi/lỗ danh nghĩa (+600.000 VND). |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF08.01** | **Bán/Thanh lý vàng nhận tiền vào ví:**<br>Người dùng chọn bán một phần hoặc toàn bộ số vàng, nhập số lượng bán và ví nhận tiền. | Hệ thống giảm số lượng vàng tương ứng; ghi nhận tiền thực tế nhận được vào ví tài sản; hạch toán lãi/lỗ thực hiện chênh lệch giá theo [BR02](#br02). |
| **AF08.02** | **Cập nhật định giá thị trường mới:**<br>Người dùng nhập đơn giá mới theo biến động thị trường. | Hệ thống cập nhật lại Market Value của tài sản và điều chỉnh lại Total Assets trên Dashboard tức thời. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF08.01** | Khối lượng vàng nhập vào $\le 0$ hoặc đơn giá $\le 0$. | Hệ thống từ chối lưu, trả về mã `400 Bad Request` kèm thông báo lỗi trường cụ thể. |
| **EF08.02** | Bán khối lượng vàng vượt quá số lượng hiện đang sở hữu. | Hệ thống từ chối thao tác, thông báo "Số lượng vàng bán không được vượt quá số lượng hiện có". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR16](#br16): Giá trị vàng đóng góp trực tiếp vào Tổng tài sản và Net Worth, định giá theo giá trị thị trường cập nhật gần nhất.<br>• Ranh giới Out-of-Scope (§1.2): Giới hạn theo dõi vàng tích trữ (có thị giá rõ ràng); không mở rộng sang bất động sản, xe cộ để tránh bài toán khấu hao bất khả thi. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi cập nhật định giá tài sản $\le 200\text{ ms}$.<br>• [NFR06](#nfr06): Hiển thị đơn vị đo lường chuẩn xác tiếng Việt (chỉ, lượng, cây). |

---

#### <a id="uc09"></a>UC09 - Quản lý mục tiêu tích lũy tài chính

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC09** |
| **Tên Use Case** | **Quản lý mục tiêu tích lũy tài chính** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng thiết lập mục tiêu tài chính (quỹ khẩn cấp, mua laptop, tích lũy mua xe); tính toán kế hoạch tiết kiệm hàng tháng và phân bổ tiền từ ví vào mục tiêu mà không làm tăng tài sản ảo theo [BR10](#br10). |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn "Mục tiêu tài chính" $\rightarrow$ "Tạo mục tiêu mới" hoặc thao tác phân bổ/rút tiền từ mục tiêu. |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ; có ít nhất một ví tài sản có số dư để phân bổ. |
| **Hậu điều kiện (Post-condition)** | • Bản ghi mục tiêu được lưu kèm tiến độ tích lũy (%).<br>• Tiền phân bổ vào mục tiêu tuân thủ [BR10](#br10): không làm tăng Total Assets hay Net Worth của người dùng. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR07](#fr07), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR05](#ur05)<br>• **Quy tắc nghiệp vụ:** [BR10](#br10)<br>• **Ca kiểm thử nghiệm thu:** [TC-F07](#tc-f07) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Mục tiêu tài chính" và nhấn "Tạo mục tiêu mới". | Hệ thống hiển thị biểu mẫu: Tên mục tiêu, Số tiền cần tích lũy, Ngày dự kiến hoàn thành, Màu sắc/Biểu tượng. |
| **2** | Người dùng nhập: "Quỹ khẩn cấp 6 tháng", Số tiền 30.000.000 VND, Hạn hoàn thành 31/12/2027; nhấn "Lưu mục tiêu". | Hệ thống kiểm tra số tiền $> 0$, ngày kết thúc trong tương lai; lưu mục tiêu ở trạng thái `Active`. |
| **3** | — | Hệ thống tự động tính toán kế hoạch: Số tháng còn lại và Số tiền cần tiết kiệm trung bình mỗi tháng; hiển thị thẻ mục tiêu (Tiến độ 0%). |
| **4** | Người dùng nhấn nút "Nạp tiền vào mục tiêu", chọn trích 5.000.000 VND từ "Ví Tiết kiệm"; nhấn Xác nhận. | Hệ thống kiểm tra số dư ví Tiết kiệm $\ge 5.000.000\text{ VND}$. |
| **5** | — | Hệ thống thực thi Transaction: Tăng số dư đã tích lũy của mục tiêu lên 5.000.000 VND; trừ số dư khả dụng của ví Tiết kiệm 5.000.000 VND. |
| **6** | — | Hệ thống bảo đảm nguyên tắc [BR10](#br10): Tổng tài sản (Total Assets) và Net Worth giữ nguyên không đổi; cập nhật thanh tiến độ đạt $\approx 16.7\%$. |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF09.01** | **Rút tiền từ mục tiêu về lại ví:**<br>Khi có việc cần, người dùng chọn "Rút tiền", nhập số tiền cần rút và ví đích. | Hệ thống giảm số tiền tích lũy của mục tiêu và hoàn trả số dư khả dụng về ví đích; Total Assets và Net Worth vẫn giữ nguyên không đổi theo [BR10](#br10). |
| **AF09.02** | **Hoàn thành mục tiêu 100%:**<br>Khoản nạp mới đưa số tiền tích lũy đạt $\ge$ số tiền mục tiêu. | Hệ thống hiển thị hiệu ứng chúc mừng, gắn huy hiệu "Hoàn thành" và chuyển trạng thái mục tiêu sang `Completed`. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF09.01** | Số tiền mục tiêu $\le 0$ hoặc ngày hoàn thành trong quá khứ. | Hệ thống từ chối lưu, trả về mã lỗi `400 Bad Request` kèm thông báo lỗi cụ thể. |
| **EF09.02** | Ví nguồn trích tiền không đủ số dư khả dụng. | Hệ thống từ chối nạp tiền, thông báo "Số dư ví nguồn không đủ để phân bổ vào mục tiêu". |
| **EF09.03** | Rút số tiền lớn hơn số tiền hiện có trong mục tiêu. | Hệ thống từ chối rút, thông báo "Số tiền rút không được vượt quá số tiền đã tích lũy trong mục tiêu". |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR10](#br10): Phân bổ tiền vào mục tiêu bản chất là giữ chỗ (earmark) nguồn tiền, tuyệt đối không được ghi nhận làm tăng thêm Tổng tài sản hay Net Worth ảo.<br>• Cho phép gắn một mục tiêu với nhiều ví nguồn khác nhau để gom tích lũy. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian phản hồi thao tác phân bổ/rút tiền $\le 200\text{ ms}$.<br>• [NFR06](#nfr06): Giao diện hiển thị thanh phần trăm và dự báo thời gian đạt mục tiêu trực quan. |

---

#### <a id="uc10"></a>UC10 - Xem Dashboard sức khỏe tài chính & Net Worth

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC10** |
| **Tên Use Case** | **Xem Dashboard sức khỏe tài chính & Net Worth** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cung cấp bức tranh toàn cảnh về sức khỏe tài chính cá nhân trong một màn hình: Tổng tài sản, Tổng nợ, Tài sản ròng (Net Worth), Dòng tiền thuần (Net Cash Flow), Tỷ lệ tiết kiệm (Saving Rate) và biểu đồ xu hướng. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng đăng nhập thành công vào hệ thống hoặc nhấn vào menu "Dashboard / Tổng quan". |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ của người dùng (`A01`). |
| **Hậu điều kiện (Post-condition)** | • Hiển thị đầy đủ các chỉ số tài chính tổng hợp trung thực thời gian thực.<br>• Không làm thay đổi hay sửa đổi trạng thái cơ sở dữ liệu. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR08](#fr08), [FR10](#fr10), [FR11](#fr11)<br>• **Yêu cầu người dùng:** [UR06](#ur06), [UR08](#ur08)<br>• **Quy tắc nghiệp vụ:** [BR14](#br14), [BR16](#br16), [BR18](#br18)<br>• **Ca kiểm thử nghiệm thu:** [TC-F10](#tc-f10), [TC-F11](#tc-f11) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập màn hình Dashboard chính. | Hệ thống tiếp nhận yêu cầu và trích xuất danh tính `userId` an toàn từ JWT Token. |
| **2** | — | Hệ thống thực thi truy vấn tổng hợp dữ liệu song song:<br>• **Total Assets:** Tổng số dư các ví tài sản + Giá trị vàng tích lũy [BR16](#br16).<br>• **Total Liabilities:** Dư nợ các ví nợ + Dư nợ gốc các khoản vay vi mô [BR05](#br05).<br>• **Net Worth:** $\text{Total Assets} - \text{Total Liabilities}$.<br>• **Dòng tiền tháng:** Tổng thu nhập, Tổng chi phí, Net Cash Flow.<br>• **Saving Rate:** $\frac{\text{Income} - \text{Expense}}{\text{Income}} \times 100\%$ theo [BR18](#br18). |
| **3** | — | Hệ thống vẽ biểu đồ đường biểu diễn xu hướng Net Worth theo các lát cắt lịch sử và biểu đồ tròn cơ cấu tài sản / danh mục chi tiêu. |
| **4** | — | Hệ thống hiển thị widget danh sách cảnh báo ngân sách chi tiêu tiệm cận ($ge 80\%$) và các kỳ trả nợ sắp đến hạn trong vòng 3 ngày tới. |
| **5** | Người dùng theo dõi và đánh giá toàn diện sức khỏe tài chính cá nhân. | Hệ thống duy trì hiển thị ổn định, hỗ trợ thao tác chạm/click xem chi tiết từng thành phần. |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF10.01** | **Thay đổi kỳ khảo sát dòng tiền:**<br>Người dùng chọn xem theo Tuần này, Tháng trước, Năm nay hoặc Tùy chọn khoảng ngày. | Hệ thống tự động tính toán lại các chỉ số Total Income, Total Expense, Net Cash Flow và Saving Rate tương ứng với khoảng thời gian được chọn. |
| **AF10.02** | **Bật/Tắt chế độ bảo mật số dư (Privacy Mode):**<br>Người dùng nhấn biểu tượng con mắt trên Dashboard khi mở ứng dụng ở nơi công cộng. | Hệ thống ẩn toàn bộ các con số tài chính nhạy cảm bằng dấu sao (`******`); chỉ hiển thị các tỷ lệ phần trăm và biểu đồ cơ cấu. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF10.01** | Kỳ khảo sát có Tổng thu nhập $\le 0$ (không có thu nhập hoặc thâm hụt). | Hệ thống không thực hiện phép chia; hiển thị Saving Rate là "N/A" (Chưa xác định) an toàn theo [BR18](#br18), triệt tiêu hoàn toàn lỗi chia cho 0 (`DivideByZeroException`) (`TC-F11.03-E`). |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR14](#br14): Đảm bảo số dư hiển thị khớp đúng 100% với lũy kế sổ cái kép.<br>• [BR16](#br16): Công thức tính Net Worth chuẩn: $\text{Net Worth} = \text{Total Assets} - \text{Total Liabilities}$.<br>• [BR18](#br18): Quy tắc tính Saving Rate và xử lý an toàn mẫu số bằng 0. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian tải toàn bộ Dashboard $T_{resp} \le 200\text{ ms}$.<br>• [NFR05](#nfr05): Giao diện tương thích hoàn hảo trên Mobile, Tablet và Desktop; tự động co giãn biểu đồ responsive. |

---

#### <a id="uc11"></a>UC11 - Báo cáo thống kê & Xuất dữ liệu CSV

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC11** |
| **Tên Use Case** | **Báo cáo thống kê & Xuất dữ liệu CSV** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Người dùng (`A01`) |
| **Mục đích (Description)** | Cho phép người dùng tra cứu, lọc lịch sử giao dịch đa tiêu chí; xem chi tiết bút toán sổ kép Nợ/Có; thực hiện đối soát số dư ví với sổ cái và xuất báo cáo ra file CSV chuẩn UTF-8 có kèm BOM, chống tấn công CSV Formula Injection. |
| **Sự kiện kích hoạt (Trigger)** | Người dùng chọn menu "Báo cáo & Sổ cái" hoặc nhấn nút "Xuất file CSV" / "Đối soát sổ cái". |
| **Tiền điều kiện (Pre-condition)** | Phiên đăng nhập hợp lệ của người dùng (`A01`). |
| **Hậu điều kiện (Post-condition)** | • Danh sách giao dịch phân trang được trả về kèm tổng thu, tổng chi.<br>• Tệp CSV an toàn được tải về thiết bị của người dùng, tương thích hoàn toàn với Microsoft Excel. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR12](#fr12), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR09](#ur09)<br>• **Quy tắc nghiệp vụ:** [BR01](#br01), [BR11](#br11), [BR14](#br14)<br>• **Biểu đồ tuần tự:** [SQ07](./uml_modeling/sequence_diagram/sq07_reconciliation_csv.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F12](#tc-f12) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Người dùng truy cập mục "Báo cáo & Sổ cái". | Hệ thống hiển thị thanh công cụ lọc: Khoảng thời gian (Từ ngày - Đến ngày), Ví tài khoản, Danh mục thu/chi, Loại giao dịch. |
| **2** | Người dùng chọn khoảng ngày (từ 01/10/2026 đến 31/10/2026) và nhấn "Tìm kiếm". | Hệ thống kiểm tra khoảng ngày hợp lệ; truy vấn CSDL và trả về danh sách giao dịch phân trang. |
| **3** | Người dùng xem bảng giao dịch, nhấn vào một bản ghi để kiểm tra chi tiết. | Hệ thống hiển thị modal chi tiết: Số tiền nguyên tệ, tiền cơ sở, tỷ giá snapshot, các dòng Nợ/Có đối ứng và liên kết đảo (nếu có). |
| **4** | Người dùng nhấn nút "Xuất file CSV". | Hệ thống trích xuất danh sách giao dịch thỏa mãn tiêu chí lọc. |
| **5** | — | Hệ thống tự động khử ký tự nguy hiểm (`=`, `+`, `-`, `@`) trong toàn bộ các trường văn bản tự do theo [FR12.04](#fr12). |
| **6** | — | Hệ thống sinh tệp CSV mã hóa UTF-8 có kèm tiền tố BOM (`\uFEFF`) và truyền file về trình duyệt qua header `Content-Disposition: attachment`. |
| **7** | Người dùng mở tệp CSV bằng Microsoft Excel. | Excel hiển thị tiếng Việt có dấu hoàn hảo, không bị vỡ font; chuỗi công thức nguy hiểm hiển thị dưới dạng văn bản an toàn (`TC-F12.04-E`). |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF11.01** | **Kích hoạt chức năng Đối soát số dư sổ cái:**<br>Người dùng nhấn nút "Đối soát sổ cái" cho một ví tài sản. | Hệ thống tự động tính tổng phát sinh Nợ/Có lịch sử của ví từ đầu đến hiện tại và so sánh với số dư bộ đệm hiện tại; hiển thị thông báo "Số dư sổ cái hoàn toàn khớp đúng 100%" theo [BR14](#br14). |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF11.01** | Khoảng ngày tìm kiếm không hợp lệ (Từ ngày sau Đến ngày). | Hệ thống từ chối tìm kiếm, trả về `400 Bad Request` kèm thông báo "Ngày bắt đầu không được lớn hơn ngày kết thúc". |
| **EF11.02** | Bộ lọc không tìm thấy bất kỳ giao dịch nào phù hợp. | Hệ thống trả về mảng rỗng (`200 OK`), hiển thị thông báo thân thiện: "Không tìm thấy giao dịch nào trong khoảng thời gian đã chọn". |
| **EF11.03** | Trường mô tả chứa chuỗi công thức độc hại (ví dụ: `=cmd|' /C calc'!A0`). | Hệ thống tự động phát hiện và chèn dấu nháy đơn vào đầu chuỗi (`'=cmd...`) theo chuẩn OWASP chống Formula Injection (`TC-F12.04-E`). |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR01](#br01): Bút toán sổ cái kép bất biến; [BR11](#br11): Cô lập dữ liệu cá nhân tuyệt đối.<br>• [BR14](#br14): Cơ chế đối soát số dư độc lập giữa số dư bộ đệm và tổng lũy kế phát sinh sổ cái. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thời gian tạo và truyền file CSV dưới 10.000 dòng $\le 1.0\text{ giây}$.<br>• [NFR02](#nfr02): Triệt tiêu 100% lỗ hổng CSV Injection theo tiêu chuẩn bảo mật OWASP. |

---

#### <a id="uc12"></a>UC12 - Quản trị người dùng & Dashboard thống kê vĩ mô (Admin)

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC12** |
| **Tên Use Case** | **Quản trị người dùng & Dashboard thống kê vĩ mô** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Quản trị viên hệ thống (`A02`) |
| **Mục đích (Description)** | Cung cấp Dashboard vĩ mô cho Admin theo dõi số lượng người dùng và phân bố tiền tệ; quản lý tài khoản người dùng (tìm kiếm, khóa/mở khóa tài khoản) kèm lý do bắt buộc; tuân thủ nghiêm ngặt nguyên tắc bảo vệ quyền riêng tư cá nhân theo [BR11](#br11). |
| **Sự kiện kích hoạt (Trigger)** | Quản trị viên đăng nhập vào hệ thống và truy cập menu "Quản trị người dùng". |
| **Tiền điều kiện (Pre-condition)** | Đăng nhập với tài khoản có vai trò `Admin`. |
| **Hậu điều kiện (Post-condition)** | • Hiển thị số liệu thống kê vĩ mô toàn hệ thống.<br>• Tài khoản vi phạm bị chuyển trạng thái `Locked`, thu hồi ngay toàn bộ token phiên làm việc.<br>• Ghi nhật ký kiểm toán hành chính Admin Audit Log; tuyệt đối không để lộ dữ liệu tài chính cá nhân của người dùng. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR14](#fr14), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR10](#ur10)<br>• **Quy tắc nghiệp vụ:** [BR11](#br11)<br>• **Biểu đồ tuần tự:** [SQ08](./uml_modeling/sequence_diagram/sq08_admin_user_broadcast.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F14](#tc-f14) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path - Khóa tài khoản vi phạm):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Quản trị viên truy cập trang quản trị Admin. | Hệ thống hiển thị Dashboard thống kê vĩ mô: Tổng số người dùng, số lượng đăng ký mới trong tháng, số tài khoản `Active` / `Locked`, phân bố tiền cơ sở (VND/USD/EUR). Tuyệt đối không hiển thị số dư ví hay giao dịch người dùng theo [BR11](#br11). |
| **2** | Quản trị viên chuyển sang tab "Danh sách người dùng". | Hệ thống hiển thị danh sách người dùng phân trang: ID, Họ tên, Email, Tiền cơ sở, Ngày đăng ký, Trạng thái. |
| **3** | Quản trị viên tìm kiếm một tài khoản vi phạm và nhấn nút "Khóa tài khoản". | Hệ thống hiển thị hộp thoại yêu cầu nhập lý do khóa bắt buộc. |
| **4** | Quản trị viên nhập lý do: "Spam giao dịch rác và vi phạm điều khoản" và nhấn "Xác nhận khóa". | Hệ thống kiểm tra: lý do không để trống, tài khoản bị khóa không phải là tài khoản Admin duy nhất của hệ thống theo [FR14.05](#fr14). |
| **5** | — | Hệ thống cập nhật trạng thái tài khoản sang `Locked`; tự động thu hồi ngay lập tức toàn bộ Refresh Token và Access Token hiện hữu của người dùng này. |
| **6** | — | Hệ thống ghi nhật ký kiểm toán Admin Audit Log (thời điểm, mã Admin, mã User bị khóa, lý do khóa). |
| **7** | — | Hệ thống cập nhật giao diện và hiển thị thông báo "Đã khóa tài khoản thành công". Người dùng bị khóa khi gửi yêu cầu tiếp theo sẽ bị từ chối `401/403` (`TC-F14.03-P`). |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF12.01** | **Mở khóa tài khoản người dùng:**<br>Quản trị viên chọn tài khoản đang bị `Locked` và nhấn "Mở khóa". | Hệ thống chuyển trạng thái sang `Active`, ghi Audit Log hành chính và cho phép người dùng đăng nhập lại bình thường. |
| **AF12.02** | **Lọc và tìm kiếm người dùng:**<br>Quản trị viên tìm kiếm theo email hoặc lọc theo trạng thái tài khoản. | Hệ thống trả về danh sách phân trang thỏa mãn tiêu chí tìm kiếm. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF12.01** | Khóa tài khoản nhưng bỏ trống lý do khóa. | Hệ thống chặn thao tác, trả về `400 Bad Request` kèm thông báo "Lý do khóa tài khoản là thông tin bắt buộc (tối thiểu 10 ký tự)". |
| **EF12.02** | Quản trị viên cố gắng khóa chính mình hoặc vô hiệu hóa tài khoản Admin duy nhất còn lại của hệ thống. | Hệ thống từ chối thao tác, trả về mã lỗi `403 Forbidden` kèm thông báo an toàn: "Không thể vô hiệu hóa tài khoản Quản trị viên duy nhất còn lại của hệ thống" theo [FR14.05](#fr14). |
| **EF12.03** | Tài khoản Admin gửi yêu cầu API đọc số dư ví hoặc danh sách giao dịch cá nhân của người dùng. | Hệ thống từ chối truy cập ngay lập tức với mã lỗi `403 Forbidden` nhằm bảo vệ quyền riêng tư cá nhân theo [BR11](#br11). |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR11](#br11): Bảo vệ quyền riêng tư tài chính cá nhân tuyệt đối; Admin bị cấm truy cập sổ sách người dùng.<br>• Mọi thao tác thay đổi trạng thái tài khoản bắt buộc phải được ghi nhận vào Admin Audit Log bất biến. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR02](#nfr02): Cơ chế thu hồi phiên tức thời làm mất hiệu lực JWT token ngay khi tài khoản bị khóa.<br>• [NFR04](#nfr04): Độ sẵn sàng và ổn định của phân hệ quản trị đạt 99.9%. |

---

#### <a id="uc13"></a>UC13 - Cấu hình hệ thống & Phát thông báo người dùng (Admin)

##### 1. Thông tin chung (Summary)

| Trường thông tin | Nội dung chi tiết |
| :--- | :--- |
| **Mã Use Case (ID)** | **UC13** |
| **Tên Use Case** | **Cấu hình hệ thống & Phát thông báo người dùng** |
| **Tác nhân (Actors)** | • **Tác nhân chính:** Quản trị viên hệ thống (`A02`) |
| **Mục đích (Description)** | Cho phép Quản trị viên cấu hình danh mục thu/chi mặc định gán cho người dùng mới; thiết lập tham số vận hành chung và soạn/phát thông báo phát thanh (Broadcast Announcement) đến toàn bộ người dùng trong hệ thống. |
| **Sự kiện kích hoạt (Trigger)** | Quản trị viên truy cập menu "Cấu hình & Thông báo" và thực hiện thao tác. |
| **Tiền điều kiện (Pre-condition)** | Đăng nhập với tài khoản có vai trò `Admin`. |
| **Hậu điều kiện (Post-condition)** | • Bản ghi thông báo phát thanh được lưu và đẩy vào Notification Center của toàn bộ người dùng đang hoạt động.<br>• Các danh mục mẫu hoặc tham số vận hành mới có hiệu lực cho toàn hệ thống. |
| **Ma trận truy vết** | • **Yêu cầu chức năng:** [FR13](#fr13), [FR15](#fr15)<br>• **Yêu cầu người dùng:** [UR10](#ur10)<br>• **Quy tắc nghiệp vụ:** [BR07](#br07), [BR08](#br08), [BR11](#br11)<br>• **Biểu đồ tuần tự:** [SQ08](./uml_modeling/sequence_diagram/sq08_admin_user_broadcast.md)<br>• **Ca kiểm thử nghiệm thu:** [TC-F13](#tc-f13) |

##### 2. Các luồng sự kiện (Flow of Events)

**a. Luồng cơ bản (Basic Flow / Happy Path - Phát thông báo Broadcast):**

| Bước | Hành động của Tác nhân (Actor Action) | Phản hồi của Hệ thống (System Response) |
| :---: | :--- | :--- |
| **1** | Quản trị viên truy cập mục "Cấu hình & Thông báo hệ thống". | Hệ thống hiển thị giao diện gồm: Tab Cấu hình tham số và Tab Phát thông báo phát thanh. |
| **2** | Quản trị viên chọn "Tạo thông báo mới", nhập Tiêu đề: "Bảo trì nâng cấp hệ thống", Nội dung: "Hệ thống sẽ tạm dừng bảo trì từ 23:00 đến 23:30 tối nay", chọn Mức độ ưu tiên: "Bảo trì / URGENT". | Hệ thống kiểm tra: Tiêu đề không rỗng, nội dung không để trống. |
| **3** | Quản trị viên nhấn nút "Phát thông báo cho toàn bộ người dùng". | Hệ thống tạo bản ghi `SystemAnnouncement` mới trong CSDL. |
| **4** | — | Hệ thống đẩy bản ghi thông báo vào hàng đợi Notification của 100% tài khoản người dùng đang hoạt động (`Active`). |
| **5** | — | Hệ thống ghi nhật ký Admin Audit Log xác nhận hành động phát thông báo. |
| **6** | — | Hệ thống hiển thị thông báo "Đã phát thông báo hệ thống thành công đến toàn bộ người dùng" (`TC-F13.01-P`). |
| **7** | Khi người dùng đăng nhập hoặc đang mở ứng dụng. | Biểu tượng chuông thông báo (Notification Center) sáng đèn chỉ báo tin mới; người dùng click vào xem được toàn văn thông báo. |

**b. Luồng thay thế (Alternative Flows):**

| Mã luồng | Điều kiện kích hoạt & Hướng rẽ | Hành vi xử lý của Hệ thống |
| :---: | :--- | :--- |
| **AF13.01** | **Quản lý danh mục thu/chi mặc định:**<br>Quản trị viên thêm mới hoặc sửa đổi bộ danh mục mẫu (Ăn uống, Tiền lương, Đi lại...). | Hệ thống lưu danh mục mẫu; khi có người dùng mới đăng ký qua [UC01](#uc01), hệ thống tự động sao chép bộ danh mục này cho người dùng mới theo [FR13.01](#fr13). |
| **AF13.02** | **Cấu hình tham số vận hành chung:**<br>Quản trị viên cập nhật ngưỡng cảnh báo mặc định (mặc định 80%) hoặc thời gian timeout của phiên JWT. | Hệ thống lưu cấu hình và áp dụng ngay lập tức cho các giao dịch và phiên truy cập kế tiếp. |

**c. Luồng ngoại lệ (Exception Flows):**

| Mã lỗi | Tình huống phát sinh lỗi | Hành vi & Phản hồi của Hệ thống |
| :---: | :--- | :--- |
| **EF13.01** | Tiêu đề hoặc nội dung thông báo phát thanh để trống. | Hệ thống từ chối phát thông báo, trả về mã lỗi `400 Bad Request` kèm thông báo "Tiêu đề và nội dung thông báo là bắt buộc". |
| **EF13.02** | Giá trị tham số cấu hình nằm ngoài dải an toàn (ví dụ: đặt ngưỡng cảnh báo ngân sách $< 10\%$ hoặc $> 100\%$). | Hệ thống từ chối lưu, trả về mã `422 Unprocessable Entity` kèm thông báo lỗi giới hạn giá trị. |

##### 3. Ràng buộc bổ sung (Additional Info)

| Hạng mục | Ràng buộc cụ thể |
| :--- | :--- |
| **Quy định nghiệp vụ (Business Rules)** | • [BR07](#br07), [BR08](#br08): Ngưỡng cảnh báo ngân sách cấu hình có hiệu lực áp dụng mặc định cho người dùng chưa tùy chỉnh riêng.<br>• [BR11](#br11): Thông báo phát thanh được gửi broadcast cho toàn thể người dùng mà không can thiệp vào dữ liệu cá nhân. |
| **Yêu cầu phi chức năng (NFR)** | • [NFR01](#nfr01): Thao tác đẩy thông báo phát thanh cho toàn bộ người dùng hoàn tất trong vòng $\le 2.0\text{ giây}$.<br>• [NFR06](#nfr06): Nội dung thông báo hiển thị định dạng rõ ràng, hỗ trợ ngắt dòng và mức độ cảnh báo theo màu sắc. |

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

Toàn bộ các yêu cầu phi chức năng được chuẩn hóa và lượng hóa theo tiêu chuẩn quốc tế **ISO/IEC 25010:2023** (Systems and software Quality Requirements and Evaluation - SQRE) trên môi trường kiểm thử tiêu chuẩn:
- **E1 (Môi trường mạng):** Mạng giả lập độ trễ mạng tiêu chuẩn $100\text{ ms}$.
- **D1 (Quy mô dữ liệu mẫu):** Cơ sở dữ liệu mẫu chứa $50.000$ dòng giao dịch và $1.000$ tài khoản.
- **L1 (Mức tải đồng thời):** Tải đồng thời 50 người dùng tương tác liên tục (50 concurrent users).

---

### <a id="nfr01"></a>NFR01 - Hiệu năng và Thời gian đáp ứng (Performance Efficiency)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR01** |
| **Phân loại ISO/IEC 25010** | **Hiệu năng và Thời gian đáp ứng (Performance Efficiency)** |
| **Môi trường kiểm thử chuẩn** | Môi trường E1 (Độ trễ 100ms), D1 (CSDL mẫu 50.000 dòng), L1 (50 người dùng đồng thời) |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02), [UR03](#ur03), [UR06](#ur06), [UR08](#ur08), [UR09](#ur09), [UR12](#ur12)<br>• **Use Case liên quan:** [UC02](#uc02), [UC03](#uc03), [UC05](#uc05), [UC08](#uc08), [UC10](#uc10), [UC11](#uc11)<br>• **Yêu cầu chức năng:** [FR02](#fr02), [FR03](#fr03), [FR05](#fr05), [FR08](#fr08), [FR10](#fr10), [FR11](#fr11), [FR12](#fr12) |
| **Mục tiêu chất lượng** | Đảm bảo tốc độ xử lý nhanh, phản hồi tức thời dưới ngưỡng cảm nhận độ trễ của người dùng, duy trì sự mượt mà khi chịu tải đồng thời. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR01.01** | Thời gian phản hồi API đọc/ghi | • $95\%$ các yêu cầu API đọc (Xem ví, Dashboard, Danh mục): $T_{resp} \le 200\text{ ms}$.<br>• $95\%$ các yêu cầu ghi sổ kép có khóa giao dịch & Idempotency: $T_{write} \le 500\text{ ms}$. | Môi trường D1 (50.000 dòng), L1 (50 VU), mạng E1. | Đo thời gian phản hồi bằng Postman Performance / k6 Load Testing. |
| **NFR01.02** | Tải dữ liệu ban đầu (FCP) | Thời gian hiển thị nội dung có ý nghĩa đầu tiên (First Contentful Paint - FCP) của giao diện Web trên máy khách không vượt quá **$1.5\text{ giây}$**. | Trình duyệt Chrome qua kết nối băng thông tiêu chuẩn (Fast 3G/4G). | Đo bằng Google Lighthouse Audit và Chrome DevTools Performance. |
| **NFR01.03** | Khả năng chịu tải đồng thời | Hệ thống duy trì tỷ lệ lỗi nhỏ hơn **$0.1\%$** khi chịu tải 50 người dùng đồng thời (50 concurrent users) thực hiện ghi nhận giao dịch liên tục. | 50 người dùng ảo thực hiện ghi sổ kép đồng thời trong 10 phút. | Kịch bản kiểm thử tải Ramp-up bằng k6 / Apache JMeter. |

---

### <a id="nfr02"></a>NFR02 - Bảo mật và Toàn vẹn thông tin (Security)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR02** |
| **Phân loại ISO/IEC 25010** | **Bảo mật và Toàn vẹn thông tin (Security)** |
| **Môi trường kiểm thử chuẩn** | Môi trường Staging bảo mật, giao thức mã hóa HTTPS (TLS 1.3), CSDL mã hóa |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR01](#ur01), [UR05](#ur05), [UR07](#ur07), [UR09](#ur09), [UR10](#ur10), [UR11](#ur11), [UR12](#ur12)<br>• **Use Case liên quan:** [UC01](#uc01), [UC06](#uc06), [UC07](#uc07), [UC09](#uc09), [UC11](#uc11), [UC12](#uc12), [UC13](#uc13)<br>• **Yêu cầu chức năng:** [FR01](#fr01), [FR07](#fr07), [FR09](#fr09), [FR12](#fr12), [FR13](#fr13), [FR14](#fr14), [FR15](#fr15)<br>• **Ca kiểm thử nghiệm thu:** [TC-F15](#tc-f15) |
| **Mục tiêu chất lượng** | Bảo vệ tuyệt đối thông tin định danh và dữ liệu tài chính cá nhân; triệt tiêu các lỗ hổng bảo mật phổ biến theo chuẩn OWASP Top 10. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR02.01** | Mã hóa đường truyền & lưu trữ | • $100\%$ kết nối giữa máy khách và máy chủ bắt buộc qua HTTPS (TLS 1.3).<br>• Mật khẩu người dùng bắt buộc băm bằng Argon2id hoặc bcrypt (Cost Factor $\ge 12$, Salt ngẫu nhiên $\ge 16\text{ bytes}$). | Mọi yêu cầu HTTP/HTTPS và kiểm tra CSDL trực tiếp. | Kiểm tra cấu hình SSL/TLS qua SSL Labs; kiểm tra dữ liệu băm trong DB. |
| **NFR02.02** | Bảo vệ phiên truy cập JWT | • Phiên truy cập sử dụng Token JWT có chữ ký số HMAC-SHA256 hoặc RSA-256.<br>• Access Token có thời hạn tối đa $60\text{ phút}$.<br>• Refresh Token lưu trữ an toàn trong HttpOnly, Secure, SameSite=Strict Cookie. | Môi trường trình duyệt Web và kiểm tra API. | Kiểm tra cờ bảo mật Cookie trong Network tab; kiểm thử tự động làm mới token. |
| **NFR02.03** | Phòng chống lỗ hổng OWASP Top 10 | Hệ thống phải triệt tiêu **$100\%$** các nguy cơ lỗ hổng:<br>• **SQL Injection:** $100\%$ truy vấn dùng Parameterized Query / ORM.<br>• **XSS:** Thoát mã ký tự HTML/JS trên giao diện.<br>• **CSRF:** Cơ chế SameSite Cookie kèm CSRF Token bảo vệ.<br>• **CSV Injection:** Khử ký tự nguy hiểm theo [FR12.04](#fr12). | Quét lỗ hổng tự động và kiểm thử thâm nhập (Pen-test). | Quét bảo mật bằng OWASP ZAP / SonarQube; thực thi ca kiểm thử [TC-F12](#tc-f12). |
| **NFR02.04** | Bảo vệ quyền riêng tư theo BR11 | Cơ chế phân quyền RBAC phải bảo đảm cách ly $100\%$ dữ liệu tài chính của người dùng; Quản trị viên (Admin) tuyệt đối không thể truy cập chi tiết số dư hay giao dịch cá nhân. | Kiểm thử phân quyền tài khoản Admin trên API cá nhân. | Kiểm thử API kiểm tra mã phản hồi `403 Forbidden` theo [FR14.02](#fr14). |

---

### <a id="nfr03"></a>NFR03 - Độ tin cậy và Tính toàn vẹn dữ liệu (Reliability & Consistency)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR03** |
| **Phân loại ISO/IEC 25010** | **Độ tin cậy và Tính nhất quán dữ liệu (Reliability & Consistency)** |
| **Môi trường kiểm thử chuẩn** | Môi trường D1/L1, giả lập lỗi ngắt kết nối mạng và xung đột giao dịch đồng thời |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02), [UR04](#ur04), [UR06](#ur06), [UR07](#ur07), [UR11](#ur11), [UR12](#ur12)<br>• **Use Case liên quan:** [UC02](#uc02), [UC03](#uc03), [UC04](#uc04), [UC06](#uc06), [UC07](#uc07), [UC08](#uc08)<br>• **Yêu cầu chức năng:** [FR02](#fr02), [FR03](#fr03), [FR04](#fr04), [FR06](#fr06), [FR08](#fr08), [FR09](#fr09)<br>• **Ca kiểm thử nghiệm thu:** [TC-F03](#tc-f03) |
| **Mục tiêu chất lượng** | Đảm bảo dữ liệu kế toán kép luôn cân bằng tuyệt đối, giao dịch có tính nguyên tử ACID, phục hồi an toàn khi có sự cố. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR03.01** | Toàn vẹn sổ kế toán kép | Tính cân bằng sổ kế toán kép $\sum \text{DebitBase} = \sum \text{CreditBase}$ phải đạt tỷ lệ chính xác tuyệt đối **$100.000\%$**. Bất kỳ phát hiện mất cân bằng nào phải kích hoạt Rollback tự động ngay lập tức. | Mọi giao dịch ghi sổ tài chính đơn tệ và đa tiền tệ. | Unit/Integration Tests kiểm tra Assert cân bằng Nợ/Có trên $100\%$ test cases. |
| **NFR03.02** | Tính khả dụng hệ thống | Độ sẵn sàng của hệ thống (System Availability) đạt tối thiểu **$99.5\%$** trong toàn bộ thời gian vận hành thử nghiệm. | Máy chủ vận hành liên tục 24/7 trong pha thử nghiệm. | Giám sát Uptime thông qua Health Check Endpoint (`/health`) và UptimeRobot. |
| **NFR03.03** | Khôi phục sau sự cố (RPO & RTO) | • **RPO (Recovery Point Objective):** $\le 24\text{ giờ}$ (mất mát dữ liệu tối đa không quá 24h qua backup định kỳ hàng ngày).<br>• **RTO (Recovery Time Objective):** $\le 60\text{ phút}$ (thời gian phục hồi dịch vụ hoạt động lại sau sự cố máy chủ). | Diễn tập kịch bản khôi phục CSDL từ bản sao lưu gần nhất. | Đo thời gian chạy script restore cơ sở dữ liệu trên môi trường diễn tập. |

---

### <a id="nfr04"></a>NFR04 - Khả năng bảo trì và Tiến hóa (Maintainability)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR04** |
| **Phân loại ISO/IEC 25010** | **Khả năng bảo trì và Tiến hóa (Maintainability)** |
| **Môi trường kiểm thử chuẩn** | Môi trường tích hợp liên tục CI/CD (GitHub Actions) và phân tích mã tĩnh |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR04](#ur04), [UR10](#ur10), [UR12](#ur12)<br>• **Use Case liên quan:** [UC04](#uc04), [UC12](#uc12), [UC13](#uc13)<br>• **Yêu cầu chức năng:** [FR06](#fr06), [FR13](#fr13), [FR14](#fr14) |
| **Mục tiêu chất lượng** | Đảm bảo kiến trúc mã nguồn phân tầng rõ ràng (Layered Architecture), dễ bảo trì, dễ mở rộng tính năng mới và ghi vết nhật ký chuẩn hóa. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR04.01** | Độ bao phủ kiểm thử tự động | Tỷ lệ bao phủ mã nguồn (Code Coverage) của bộ kiểm thử tự động đạt tối thiểu **$80\%$** đối với Tầng Nghiệp vụ (Domain Layer) và Tầng Dịch vụ Ứng dụng (Application Services). | Chạy bộ kiểm thử tự động toàn diện trong pipeline CI. | Đo độ bao phủ bằng Jest / JaCoCo / pytest-cov trong CI workflow. |
| **NFR04.02** | Định dạng nhật ký chuẩn hóa | Nhật ký ứng dụng (Application Logs) bắt buộc được ghi nhận dưới định dạng **JSON có cấu trúc chuẩn** gồm: `timestamp`, `level`, `traceId`, `userId`, `action`, `message`, `executionTimeMs` nhằm hỗ trợ truy vết sự cố nhanh chóng. | Mọi thao tác ghi log trên máy chủ ứng dụng. | Kiểm tra cú pháp log qua Winston / Serilog / Logstash format validator. |

---

### <a id="nfr05"></a>NFR05 - Khả năng tương thích và Đa nền tảng (Compatibility)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR05** |
| **Phân loại ISO/IEC 25010** | **Khả năng tương thích và Đa nền tảng (Compatibility)** |
| **Môi trường kiểm thử chuẩn** | Các trình duyệt hiện đại trên hệ điều hành Windows, macOS, Android và iOS |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR12](#ur12)<br>• **Use Case liên quan:** Toàn bộ Use Cases ([UC01](#uc01) - [UC13](#uc13))<br>• **Yêu cầu chức năng:** Toàn bộ [FR01](#fr01) - [FR15](#fr15) |
| **Mục tiêu chất lượng** | Giao diện hiển thị nhất quán, hoạt động ổn định trên các trình duyệt phổ biến và tự động thích ứng với mọi kích thước màn hình. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR05.01** | Tương thích trình duyệt Web | Giao diện Web phải hiển thị đúng thiết kế và hoạt động ổn định trên **$100\%$** các trình duyệt hiện đại:<br>• Google Chrome (phiên bản $\ge 115$)<br>• Mozilla Firefox (phiên bản $\ge 115$)<br>• Microsoft Edge (phiên bản $\ge 115$)<br>• Apple Safari (phiên bản $\ge 16$). | Môi trường kiểm thử đa trình duyệt trên desktop và mobile. | Kiểm thử giao diện tự động bằng Playwright / BrowserStack cross-browser test. |
| **NFR05.02** | Thiết kế đáp ứng (Responsive Design) | Giao diện tự động tối ưu hóa bố cục hiển thị mượt mà trên 3 dải độ phân giải chuẩn:<br>• **Mobile:** $375\text{px} - 430\text{px}$ (iPhone, Android phones)<br>• **Tablet:** $768\text{px} - 1024\text{px}$ (iPad, Galaxy Tab)<br>• **Desktop:** $1280\text{px} - 1920\text{px}$ (Laptops, màn hình Full HD). | Kiểm tra hiển thị responsive trên các viewport chuẩn. | Kiểm tra qua Chrome Responsive Viewport mode và Playwright viewport matrix. |

---

### <a id="nfr06"></a>NFR06 - Tính khả dụng và Trải nghiệm người dùng (Usability)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR06** |
| **Phân loại ISO/IEC 25010** | **Tính khả dụng và Trải nghiệm người dùng (Usability)** |
| **Môi trường kiểm thử chuẩn** | Môi trường kiểm thử người dùng cuối (User Acceptance Testing - UAT) |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR01](#ur01), [UR03](#ur03), [UR05](#ur05), [UR06](#ur06), [UR07](#ur07), [UR08](#ur08), [UR09](#ur09), [UR10](#ur10), [UR12](#ur12)<br>• **Use Case liên quan:** [UC01](#uc01), [UC05](#uc05), [UC06](#uc06), [UC07](#uc07), [UC08](#uc08), [UC09](#uc09), [UC10](#uc10), [UC11](#uc11), [UC12](#uc12), [UC13](#uc13)<br>• **Yêu cầu chức năng:** [FR01](#fr01), [FR05](#fr05), [FR07](#fr07), [FR08](#fr08), [FR09](#fr09), [FR10](#fr10), [FR11](#fr11), [FR12](#fr12), [FR13](#fr13), [FR14](#fr14) |
| **Mục tiêu chất lượng** | Tối ưu hóa quy trình nhập liệu tài chính, giao diện thân thiện, thông báo lỗi tiếng Việt dễ hiểu và giúp người dùng không cảm thấy quá tải thông tin. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR06.01** | Tối ưu hóa thao tác nhập liệu | Số thao tác nhấn (clicks / taps) để hoàn thành ghi nhận một giao dịch chi tiêu thông thường không vượt quá **4 bước**: Chọn ví $\rightarrow$ Chọn danh mục $\rightarrow$ Nhập số tiền $\rightarrow$ Nhấn Lưu. | Thao tác nhập liệu chi tiêu từ màn hình chính. | Đo đếm số click/tap thực tế trong kịch bản kiểm thử UAT tiêu chuẩn. |
| **NFR06.02** | Thông báo lỗi thân thiện tiếng Việt | $100\%$ thông báo lỗi trả về cho người dùng phải bằng **tiếng Việt rõ ràng, dễ hiểu**, chỉ rõ trường dữ liệu bị sai và hướng dẫn cách khắc phục cụ thể; **tuyệt đối không hiển thị vết ngăn xếp kỹ thuật (Stack Trace)** ra giao diện. | Kiểm thử mọi tình huống biên và kịch bản luồng ngoại lệ. | Kiểm tra đối soát nội dung thông báo lỗi trên UI đối chiếu từ điển thông báo chuẩn. |

---

### <a id="nfr07"></a>NFR07 - Ràng buộc pháp lý và Chuẩn mực kế toán (Compliance)

| Thuộc tính | Nội dung chi tiết |
| :--- | :--- |
| **Mã nhóm NFR** | **NFR07** |
| **Phân loại ISO/IEC 25010** | **Ràng buộc pháp lý và Chuẩn mực kế toán (Compliance)** |
| **Môi trường kiểm thử chuẩn** | Môi trường sản xuất và đối chiếu quy định pháp lý Việt Nam |
| **Ma trận truy vết** | • **Yêu cầu người dùng:** [UR02](#ur02), [UR04](#ur04), [UR11](#ur11), [UR12](#ur12)<br>• **Use Case liên quan:** [UC02](#uc02), [UC03](#uc03), [UC04](#uc04)<br>• **Yêu cầu chức năng:** [FR02](#fr02), [FR03](#fr03), [FR04](#fr04), [FR06](#fr06) |
| **Mục tiêu chất lượng** | Tuân thủ nghiêm ngặt quy định pháp luật Việt Nam về bảo vệ dữ liệu cá nhân và chuẩn mực hiển thị kế toán tiền tệ Việt Nam. |

**Bảng chi tiết các chỉ tiêu lượng hóa:**

| Mã ID | Tên tiêu chí chất lượng | Đặc tả yêu cầu & Ngưỡng định lượng (Metrics) | Điều kiện kiểm thử & Môi trường | Phương pháp đo kiểm & Công cụ |
| :---: | :--- | :--- | :--- | :--- |
| **NFR07.01** | Bảo mật dữ liệu cá nhân theo luật | Tuân thủ Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân: người dùng có toàn quyền xem, chỉnh sửa thông tin hồ sơ và yêu cầu hủy/xóa vĩnh viễn tài khoản cá nhân. | Quy trình quản lý tài khoản và chính sách bảo mật ứng dụng. | Rà soát quy trình quản lý dữ liệu (Privacy Policy Checklist) và API xóa tài khoản. |
| **NFR07.02** | Chuẩn mực tiền tệ và kế toán VN | Đồng tiền cơ sở VND hiển thị dấu phân cách hàng nghìn bằng dấu chấm (ví dụ: `1.000.000 đ`) và không có phần thập phân theo tập quán kế toán Việt Nam; ngoại tệ (USD/EUR) hiển thị chuẩn 2 chữ số thập phân (ví dụ: `$120.50`). | Mọi màn hình hiển thị số tiền và báo cáo xuất CSV. | Kiểm tra định dạng tiền tệ thông qua bộ hàm `Intl.NumberFormat('vi-VN')` trên UI. |

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
| <a id="tc-f01"></a>**TC-F01.01-P** | Luồng chính | Kiểm tra đăng ký tài khoản mới kèm tiền cơ sở | Đăng ký với email hợp lệ, mật khẩu mạnh, tiền cơ sở VND. | Hệ thống tạo tài khoản `Active`, gán danh mục mặc định, khóa tiền cơ sở ban đầu. |
| <a id="tc-f02"></a>**TC-F02.01-P** | Luồng chính | Kiểm tra tạo ví mới và hạch toán số dư đầu kỳ | Mở ví tiền mặt 2.000.000 VND và ví tiết kiệm 10.000.000 VND. | Sinh bút toán đối ứng Vốn chủ sở hữu (Equity) cân bằng 100%; số dư ví cập nhật chính xác. |
| <a id="tc-f03"></a>**TC-F03.01-P** | Luồng chính | Kiểm tra cân bằng sổ kế toán kép (Double-Entry) | Ghi nhận chi tiêu 150.000 VND từ ví tiền mặt cho Ăn uống. | Sinh đúng dòng Nợ Chi phí 150k và Có Ví 150k; $\sum \text{Debit} = \sum \text{Credit} = 150.000$ VND; số dư ví trừ 150k. |
| **TC-F03.04-E** | Luồng ngoại lệ | Kiểm tra chống trừ tiền trùng lặp (Idempotency) | Gửi 5 yêu cầu HTTP POST cùng `Idempotency-Key` tạo chi tiêu 200.000 VND. | Chỉ tạo đúng 1 bản ghi giao dịch; ví chỉ bị trừ 200.000 VND đúng 1 lần; 4 yêu cầu sau nhận mã 200 kèm kết quả cũ. |
| **TC-F03.02-E** | Luồng ngoại lệ | Kiểm tra tính nguyên tử (Rollback khi có lỗi) | Giả lập lỗi ngắt kết nối DB sau khi tạo dòng Nợ chi phí nhưng trước khi tạo dòng Có ví. | Toàn bộ giao dịch bị Rollback sạch sẽ; không có bút toán mồ côi; số dư ví không đổi; ghi log lỗi kèm `requestId`. |
| <a id="tc-f04"></a>**TC-F04.01-P** | Luồng chính | Kiểm tra snapshot tỷ giá và hạch toán chênh lệch ngoại tệ | Mua 100 USD lúc tỷ giá 25.000; xuất bán 100 USD lúc tỷ giá 25.500 VND. | Snapshot tỷ giá gắn bất biến với giao dịch; hạch toán lãi tỷ giá 50.000 VND theo [BR15](#br15). |
| <a id="tc-f05"></a>**TC-F05.03-P** | Luồng chính | Kiểm tra cảnh báo vượt ngưỡng ngân sách chi tiêu | Ngân sách Ăn uống 2tr. Đã chi 1,5tr. Chi thêm 200k (tổng 1,7tr = 85%). | Hệ thống gửi thông báo Cảnh báo Vàng (85%) đến chuông thông báo của người dùng; chi thêm vượt 2tr phát thông báo Đỏ. |
| <a id="tc-f06"></a>**TC-F06.01-P** | Tiến trình nền | Kiểm tra tự động xử lý giao dịch định kỳ tháng thiếu ngày | Thiết lập tiền nhà ngày 31 hàng tháng. Kiểm tra tiến trình nền tháng 2 (28 ngày). | Hệ thống sinh giao dịch đúng ngày 28/02 và giữ nguyên ngày neo 31 cho tháng 3 theo [BR09](#br09). |
| <a id="tc-f07"></a>**TC-F07.01-P** | Luồng chính | Kiểm tra phân bổ mục tiêu tiết kiệm không tăng tài sản | Phân bổ 5.000.000 VND từ ví tiền mặt vào mục tiêu "Quỹ khẩn cấp". | Tiến độ mục tiêu tăng tương ứng; Tổng tài sản (Total Assets) giữ nguyên theo [BR10](#br10). |
| <a id="tc-f08"></a>**TC-F08.04-P** | Luồng chính | Kiểm tra định giá vàng tích lũy và tính Net Worth | Người dùng sở hữu 2 chỉ vàng 9999 định giá 17.000.000 VND, ví tiền 5.000.000 VND. | Tổng tài sản hiển thị 22.000.000 VND; Net Worth tính đúng theo [BR16](#br16). |
| <a id="tc-f09"></a>**TC-F09.03-P** | Luồng chính | Kiểm tra phân rã trả nợ vi mô (Gốc và Lãi) | Khoản vay 10tr. Trả kỳ 1: 2tr gốc + 150k lãi từ Ví ngân hàng số dư 5tr. | Ví NH còn 2.850.000 VND; Dư nợ khoản vay còn 8.000.000 VND; Chi phí ghi nhận 150.000 VND; Chi tiêu không bị trừ 2tr gốc. |
| <a id="tc-f10"></a>**TC-F10.01-P** | Luồng chính | Kiểm tra tổng hợp chỉ số tài chính trên Dashboard | Tổng hợp tài sản ví, vàng, dư nợ vay và dòng tiền tháng hiện tại. | Dashboard hiển thị đồng bộ Total Assets, Total Liabilities, Net Worth, Cash Flow trong thời gian đáp ứng [NFR01](#nfr01). |
| <a id="tc-f11"></a>**TC-F11.03-E** | Luồng ngoại lệ | Kiểm tra tính Saving Rate khi không có thu nhập | Kỳ khảo sát có Thu nhập = 0 VND, Chi tiêu = 1.500.000 VND. | Cash Flow = -1.500.000 VND; Saving Rate hiển thị "N/A" an toàn (không bị lỗi chia cho 0 `DivideByZeroException`). |
| <a id="tc-f12"></a>**TC-F12.04-E** | Luồng ngoại lệ | Kiểm tra chống CSV Formula Injection | Tạo giao dịch có mô tả: `=cmd|' /C calc'!A0`. Thực hiện xuất báo cáo CSV. | Tệp CSV tải về có trường mô tả được trung hòa ký tự (tiền tố dấu nháy đơn `'=cmd...`), mở bằng Excel không tự kích hoạt lệnh. |
| <a id="tc-f13"></a>**TC-F13.01-P** | Luồng chính | Kiểm tra Admin phát thông báo Broadcast | Admin phát thông báo mức độ "Bảo trì" cho toàn hệ thống. | 100% người dùng hoạt động nhận thông báo trên Notification Center; không tác động dữ liệu tài chính. |
| <a id="tc-f14"></a>**TC-F14.03-P** | Luồng chính | Kiểm tra Quản trị viên khóa tài khoản vi phạm | Admin chọn tài khoản UserA, nhập lý do "Spam giao dịch rác" và nhấn Khóa. | Trạng thái UserA chuyển sang `Locked`; toàn bộ token phiên của UserA bị thu hồi; yêu cầu API tiếp theo của UserA bị từ chối 401. |
| <a id="tc-f15"></a>**TC-F15.01-P** | Bảo mật | Kiểm tra cô lập dữ liệu đa người dùng tuyệt đối | UserA gửi API yêu cầu xem ví hoặc giao dịch thuộc sở hữu của UserB. | Hệ thống từ chối với mã lỗi `403 Forbidden` hoặc `404 Not Found`; dữ liệu tài chính của UserB được bảo vệ 100%. |

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
