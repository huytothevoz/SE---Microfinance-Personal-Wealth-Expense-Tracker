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
- **Phiên bản:** 2.0 (Elaboration Baseline)
- **Ngày hoàn thiện:** Tháng 10/2026

---

### Mục lục tài liệu

| Phần | Tiêu đề | Nội dung chính |
| :---: | :--- | :--- |
| **[Phần 1](#phan-1)** | **Giới thiệu và Yêu cầu người dùng** | Tầm nhìn sản phẩm, mục đích, phạm vi 3 trụ cột, đối tượng người dùng, danh mục User Requirements (UR) và thuật ngữ chuyên ngành. |
| **[Phần 2](#phan-2)** | **Bối cảnh và Kiến trúc nền tảng** | Ranh giới hệ thống, tác nhân, kiến trúc tham chiếu Layered + Modular Monolith, giao diện tích hợp (IF), chính sách đa tiền tệ và 18 quy tắc nghiệp vụ cốt lõi (BR). |
| **[Phần 3](#phan-3)** | **Đặc tả Yêu cầu chức năng (FR)** | Đặc tả chi tiết 16 nhóm chức năng (FR01 - FR16) theo chuẩn Ian Sommerville §4.3.2, bao quát Microfinance (vay/nợ), Personal Wealth (tài sản/Net Worth/Saving Rate) và Expense Tracker. |
| **[Phần 4](#phan-4)** | **Mô hình tương tác và Use Case** | Danh mục 25 Use Cases (UC01 - UC25) đặc tả đầy đủ luồng chính, luồng phụ và luồng ngoại lệ tách biệt; 5 kịch bản xử lý nền (SYS01 - SYS05) và ma trận hạch toán kế toán kép. |
| **[Phần 5](#phan-5)** | **Yêu cầu phi chức năng (NFR)** | Lượng hóa 7 nhóm NFR theo tiêu chuẩn quốc tế ISO/IEC 25010:2023 trên môi trường kiểm thử chuẩn E1/D1/L1. |
| **[Phần 6](#phan-6)** | **Ma trận truy vết và Nghiệm thu** | Ma trận truy vết hai chiều (UR - FR - UC - BR - NFR - Test Suite), ca kiểm thử mẫu phân rã theo luồng và tiêu chí nghiệm thu pha Elaboration. |

---

<a id="phan-1"></a>

## 1. Giới thiệu và Yêu cầu người dùng

### 1.1 Mục đích và tầm nhìn sản phẩm
Tài liệu Đặc tả Yêu cầu Phần mềm (Software Requirements Specification - SRS) này định nghĩa toàn diện các yêu cầu chức năng, yêu cầu phi chức năng, quy tắc nghiệp vụ và khung kiến trúc nền tảng cho hệ thống **WealthFlow** trong pha **Elaboration** theo tiến trình Unified Process (UP/RUP).

Tầm nhìn cốt lõi của **WealthFlow** là hỗ trợ người dùng chuyển dịch căn bản trong tư duy tài chính cá nhân:
$$\text{Từ: "Passive Expense Tracking" (Ghi chép thu chi thụ động)}$$
$$\text{Sang: "Active Personal Wealth Management" (Chủ động quản lý tài sản, kiểm soát nợ và tích lũy bền vững)}$$

Thay vì chỉ dừng lại ở việc trả lời câu hỏi *"Tôi đã tiêu bao nhiêu tiền?"*, WealthFlow giúp người dùng làm chủ tài chính thông qua việc trả lời 3 câu hỏi cốt tử:
1. **Tôi đang có bao nhiêu tài sản thực tế?** (Tổng hợp tiền mặt, ngân hàng, ví điện tử, sổ tiết kiệm, tài sản hiện vật và trừ đi tổng nghĩa vụ nợ để xác định **Tài sản ròng - Net Worth**).
2. **Dòng tiền của tôi đang đi đâu?** (Phân loại thu nhập, chi phí sinh hoạt, dòng tiền luân chuyển, hạn mức ngân sách và thói quen tiêu dùng).
3. **Tình hình tài chính của tôi đang tốt lên hay xấu đi?** (Theo dõi xu hướng Net Worth, Cash Flow, Tỷ lệ tiết kiệm - Saving Rate, mức độ gánh nặng nợ và tiến độ đạt các mục tiêu tài chính cá nhân theo thời gian).

### 1.2 Phạm vi hệ thống (Scope)
Hệ thống tích hợp chặt chẽ 3 phân hệ cấu thành:
* **Microfinance (Tài chính vi mô):** Quản lý các dòng tiền nhỏ, theo dõi các khoản vay mượn cá nhân/tổ chức, các khoản mua trả góp, nghĩa vụ nợ, lập lịch trả nợ từng kỳ, bóc tách nợ gốc và lãi/phí, hỗ trợ kế hoạch trả nợ có trật tự và cảnh báo khoản nợ sắp đến hạn.
* **Personal Wealth (Tài sản cá nhân):** Quản lý đa dạng danh mục tài sản (tiền gửi, tiền tiết kiệm, tài sản hiện vật có giá trị), quản lý nợ phải trả, tính toán tài sản ròng (Net Worth), theo dõi tỷ lệ tiết kiệm (Saving Rate) và lập kế hoạch tích lũy hướng tới mục tiêu tài chính dài hạn.
* **Expense Tracker (Theo dõi thu chi chuẩn mực):** Ghi chép thu/chi/chuyển tiền đa tài khoản, quản lý ngân sách tháng theo danh mục với cảnh báo chủ động, tự động hóa các khoản định kỳ và trích xuất dữ liệu từ hóa đơn qua ảnh chụp (OCR).

### 1.3 Đối tượng người dùng mục tiêu (Target Users)
Hệ thống đặc biệt tối ưu cho các nhóm đối tượng có nguồn tài chính ban đầu chưa lớn nhưng cần kiểm soát chặt chẽ để tích lũy:
* **Học sinh, sinh viên:** Quản lý tiền trợ cấp từ gia đình, tiền làm thêm, học phí, chi phí sinh hoạt hàng tháng và bắt đầu thói quen tích lũy từ những khoản tiền nhỏ.
* **Người mới đi làm (Young Professionals):** Quản lý lương, kiểm soát chi tiêu ngoài kế hoạch, lập quỹ khẩn cấp và trả các khoản vay học tập/mua sắm ban đầu.
* **Người có thu nhập thấp đến trung bình, Freelancer:** Dòng tiền không cố định, cần cân đối thu chi hàng ngày, quản lý các khoản vay mua trả góp (laptop, xe máy) và tránh rơi vào bẫy nợ tín dụng.
* **Cá nhân và hộ kinh doanh vi mô:** Theo dõi rõ ràng tiền ở nhiều tài khoản/ví, tách bạch tài sản và nợ, giám sát dòng tiền thuần (Net Cash Flow) và xây dựng kế hoạch dự phòng.

### 1.4 Danh mục Yêu cầu người dùng (User Requirements - UR)

| Mã UR | Nhu cầu người dùng | Lý do nghiệp vụ | Ánh xạ FR |
| :---: | :--- | :--- | :--- |
| **UR01** | Tạo tài khoản an toàn, bảo vệ quyền riêng tư dữ liệu tài chính. | Đảm bảo chỉ chủ tài khoản mới được truy cập và sửa đổi số liệu nhạy cảm. | FR01, FR14, FR15 |
| **UR02** | Quản lý nhiều ví/nguồn tiền và ghi nhận thu/chi/chuyển tiền chuẩn xác. | Nắm giữ tiền ở tiền mặt, ngân hàng, ví điện tử mà không bị thất thoát, có dấu vết kiểm toán khi sửa đổi. | FR02, FR03, FR04 |
| **UR03** | Thiết lập hạn mức ngân sách và nhận cảnh báo sớm trước khi bội chi. | Ngăn chặn hành vi chi tiêu quá tay theo từng nhóm nhu cầu (Ăn uống, Giải trí, Mua sắm...). | FR05 |
| **UR04** | Tự động hóa ghi nhận các khoản thu/chi định kỳ hàng tháng. | Tránh quên thanh toán hóa đơn điện/nước/internet, tiền thuê nhà hoặc dịch vụ đăng ký. | FR06 |
| **UR05** | Thiết lập các mục tiêu tích lũy và tính toán kế hoạch tiết kiệm khả thi. | Giúp người dùng biết cần tiết kiệm bao nhiêu mỗi tháng và bao lâu sẽ đạt mục tiêu (mua máy tính, quỹ khẩn cấp...). | FR07 |
| **UR06** | Theo dõi toàn diện danh mục tài sản, các khoản nợ và tính toán tài sản ròng (Net Worth). | Người dùng biết rõ giá trị thực sự mình sở hữu sau khi trừ hết các khoản nợ, theo dõi sự tăng trưởng tài sản. | FR08, FR10 |
| **UR07** | Quản lý chi tiết từng khoản vay, khoản nợ nhỏ và theo dõi lịch thanh toán (Microfinance). | Nắm rõ nợ ai, nợ bao nhiêu, lịch trả nợ, số tiền đã trả và phân định rõ tiền gốc so với tiền lãi/phí. | FR09 |
| **UR08** | Xem Dashboard trực quan về sức khỏe tài chính: Net Worth, Cash Flow, Saving Rate. | Cung cấp góc nhìn toàn cảnh về tình hình tài chính trong một màn hình, biết tài chính đang tốt lên hay xấu đi. | FR10, FR11 |
| **UR09** | Tra cứu lịch sử, đối soát sai lệch số dư và xuất báo cáo tài chính sang tệp mở. | Cho phép người dùng kiểm tra lại chi tiết từng giao dịch và xuất ra CSV để tự lưu trữ hoặc phân tích sâu. | FR12 |
| **UR10** | Quét hóa đơn/biên nhận từ hình ảnh để tạo giao dịch nhanh chóng. | Giảm thiểu thao tác nhập liệu thủ công trên điện thoại/máy tính, nâng cao trải nghiệm người dùng. | FR16 |
| **UR11** | Vận hành hệ thống ổn định, có cơ chế sao lưu tự động và phục hồi khi xảy ra sự cố. | Đảm bảo tính sẵn sàng cao, không bị mất mát dữ liệu kế toán quan trọng. | FR13, FR14 |
| **UR12** | Giao diện hiện đại, tốc độ phản hồi nhanh, bảo mật thông tin trên mọi thiết bị. | Đem lại trải nghiệm liền mạch, tiện dụng và an tâm cho người dùng. | Toàn bộ NFR |

### 1.5 Thuật ngữ và từ viết tắt (Glossary)
* **Net Worth (Tài sản ròng):** Giá trị tài sản còn lại của cá nhân sau khi lấy Tổng tài sản (Total Assets) trừ đi Tổng nợ phải trả (Total Liabilities).
* **Cash Flow (Dòng tiền thuần):** Chênh lệch giữa tổng thu nhập thực nhận và tổng chi phí thực tế phát sinh trong một kỳ ($NetCashFlow = TotalIncome - TotalExpense$).
* **Saving Rate (Tỷ lệ tiết kiệm):** Phần trăm thu nhập được giữ lại sau khi chi tiêu: $SavingRate = \frac{Income - Expense}{Income} \times 100\%$.
* **Microfinance (Tài chính vi mô):** Phân hệ quản lý các khoản vay quy mô nhỏ, khoản trả góp, công nợ bạn bè/người thân và các dòng tiền nhỏ lẻ thường ngày.
* **Double-Entry Bookkeeping (Kế toán kép):** Nguyên lý ghi sổ mọi nghiệp vụ tài chính dưới dạng ít nhất một dòng Nợ (Debit) và một dòng Có (Credit) với tổng giá trị tiền cơ sở luôn bằng nhau.
* **Idempotency Key (Khóa bất biến / chống trùng lặp):** Khóa duy nhất do máy khách gửi kèm để bảo đảm một yêu cầu khi gửi nhiều lần do sự cố đường truyền mạng chỉ được xử lý đúng một lần duy nhất.
* **Base Currency (Tiền cơ sở):** Đồng tiền chuẩn do người dùng chọn khi khởi tạo tài khoản (ví dụ VND), dùng để quy đổi và lập báo cáo tài chính hợp nhất.
* **Snapshot Exchange Rate (Tỷ giá chụp tại thời điểm):** Bản ghi tỷ giá hối đoái có hiệu lực tại thời điểm phát sinh giao dịch, được lưu bất biến cùng giao dịch.

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
│  └──────────────────────────────────────────────────┘  │
└───────▲────────────────────────▲───────────────▲───────┘
        │                        │               │
        │ IF02                   │ IF03          │ IF04
        ▼                        ▼               ▼
┌──────────────┐         ┌───────────────┐ ┌───────────────┐
│ A03: Dịch vụ │         │ Engine xử lý  │ │ Kho lưu trữ   │
│ Tỷ giá ngoài │         │ ảnh hóa đơn   │ │ sao lưu dữ    │
│  (Rates API) │         │ (OCR Service) │ │ liệu an toàn  │
└──────────────┘         └───────────────┘ └───────────────┘
                                 ▲
                                 │ Vận hành / Giám sát
                         ┌───────┴───────────────┐
                         │ A02: Quản trị viên    │
                         │ (System Administrator)│
                         └───────────────────────┘
```

#### Bảng định nghĩa Tác nhân (Actors)
* **A01 - Người dùng (User):** Người dùng cuối (khách vãng lai khi đăng ký/đăng nhập; người dùng đã xác thực khi thao tác dữ liệu cá nhân). Có toàn quyền quản lý tài sản, nợ, ví, giao dịch của bản thân; tuyệt đối không thể xem hay sửa dữ liệu của người dùng khác.
* **A02 - Quản trị viên (System Administrator):** Chịu trách nhiệm bảo trì hệ thống, quản lý khóa/mở khóa tài khoản người dùng vi phạm, cấu hình tham số vận hành, kích hoạt sao lưu và khôi phục dữ liệu. Không có quyền xem số liệu sổ sách tài chính cá nhân của người dùng.
* **A03 - Nhà cung cấp tỷ giá (Exchange Rate Provider):** Hệ thống API bên ngoài cung cấp bảng tỷ giá tiền tệ theo thời gian thực hoặc định kỳ hàng ngày thông qua giao tiếp IF02.

### 2.2 Kiến trúc tham chiếu nền tảng (Layered Architecture + Modular Monolith)
Nhằm bảo đảm tính cô lập cao, khả năng bảo trì tốt (Maintainability) và kiểm thử triệt để (Testability) trong pha Elaboration, WealthFlow áp dụng mô hình kiến trúc phân tầng kết hợp đơn khối module hóa:

1. **Presentation Layer (Tầng trình diễn):** Giao diện Web Responsive hiện đại, tối ưu hiển thị trên màn hình Desktop, Tablet và Mobile. Giao tiếp với tầng ứng dụng hoàn toàn thông qua giao thức RESTful JSON API chuẩn hóa.
2. **Application Layer (Tầng ứng dụng):** Bao gồm các bộ điều phối dịch vụ (Application Services): `AuthService`, `WalletService`, `TransactionService`, `BudgetService`, `WealthService`, `LoanService`, `GoalService`, `ReportService`, `NotificationService`. Tầng này điều phối luồng nghiệp vụ, kiểm soát xác thực và phân quyền truy cập.
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
* **IF03 - Giao diện ảnh và hóa đơn (OCR):** Hỗ trợ tệp định dạng JPEG, PNG với dung lượng tối đa 10 MB/ảnh. Xác thực chữ ký nhị phân (Magic bytes) trước khi đưa vào module xử lý OCR để phòng chống mã độc tải lên.
* **IF04 - Kho lưu trữ sao lưu độc lập:** Lưu trữ các bản sao lưu cơ sở dữ liệu được mã hóa AES-256 kèm theo tệp kê khai (Manifest) và mã băm SHA-256 để kiểm tra tính toàn vẹn khi phục hồi.

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
| **BR11** | Cô lập dữ liệu đa người dùng | Toàn bộ bản ghi nghiệp vụ (tài khoản, ví, tài sản, nợ, giao dịch, ngân sách, mục tiêu, hình ảnh) đều phải thuộc về một `userId` duy nhất. Máy chủ phải thẩm tra quyền sở hữu đối với 100% yêu cầu API, truy vấn danh sách, xuất tệp và tác vụ nền. Quyền Quản trị viên (Admin) tuyệt đối không cho phép xem dữ liệu sổ sách tài chính cá nhân của người dùng. |
| **BR12** | Tính nguyên tử trong giao dịch (ACID) | Toàn bộ các thao tác ghi nhận giao dịch, sinh dòng sổ Nợ/Có, cập nhật số dư bộ đệm và phát sự kiện xử lý nền phải được thực thi trong một Database Transaction nguyên tử duy nhất. Nếu có bất kỳ bước nào thất bại, toàn bộ thao tác phải được Rollback về trạng thái ban đầu; không bao giờ được lưu trạng thái dở dang. |
| **BR13** | Cơ chế Idempotency chống trùng lặp | Mọi yêu cầu ghi nhận/đảo giao dịch gửi kèm `Idempotency-Key`: nếu cùng `userId` + cùng `key` + cùng nội dung thì hệ thống trả lại kết quả đã xử lý trước đó mà không thực hiện lại; nếu cùng `key` nhưng khác nội dung thì hệ thống từ chối với mã lỗi 409 Conflict. Khóa mức dòng (Row-level lock) phải được áp dụng để ngăn ngừa triệt để tình trạng ghi đè hoặc chi vượt số dư ví tài sản. |
| **BR14** | Tách biệt trạng thái giao dịch | Giao dịch ở trạng thái Nháp (Draft) không được tính vào số dư ví, không tính vào ngân sách và không xuất hiện trên báo cáo tài chính. Báo cáo tài chính chỉ tổng hợp các giao dịch đã ghi sổ (Posted) và các dòng đảo tương ứng; không được loại trừ bản ghi gốc rồi lại trừ tiếp dòng đảo làm sai lệch số liệu. |
| **BR15** | Phương pháp giá trị sổ ngoại tệ | Khi xuất ngoại tệ khỏi ví: giá trị quy đổi tiền cơ sở xuất sổ được tính theo phương pháp Bình quân gia quyền: $UnitRate = \frac{BookBaseBalance}{OriginalBalance}$. Khi xuất toàn bộ số dư nguyên tệ, toàn bộ giá trị $BookBaseBalance$ phải được xuất hết để triệt tiêu sai số làm tròn. Chênh lệch giữa giá trị xuất sổ và giá trị nhận được theo tỷ giá giao dịch được hạch toán vào tài khoản Lãi/Lỗ chênh lệch tỷ giá. |
| **BR16** | Xác định Tài sản ròng (Net Worth) | $\text{Tài sản ròng (Net Worth)} = \text{Tổng tài sản (Total Assets)} - \text{Tổng nợ (Total Liabilities)}$.<br>- Tổng tài sản bao gồm: Toàn bộ số dư các ví tài sản (tiền mặt, ngân hàng, ví điện tử), tài khoản tiết kiệm và giá trị định giá của các tài sản khác đã ghi nhận.<br>- Tổng nợ bao gồm: Toàn bộ số dư các ví nợ (thẻ tín dụng, hạn mức thấu chi) và dư nợ gốc còn lại của các khoản vay vi mô/trả góp.<br>- Tiền đã tồn tại trong danh sách ví chỉ được tính đúng 1 lần vào Tổng tài sản. |
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
         ├────────────────────►│ (Vật chất, Xe..)│
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
         └────────────────────►└─────────────────┘            └─────────────────┘
                               ┌─────────────────┐
                               │  Subscription   │
                               │(Định kỳ, Chu kỳ)│
                               └─────────────────┘
```

#### Mô tả các thực thể dữ liệu chính:
* **User (Người dùng):** `id`, `email`, `passwordHash`, `fullName`, `role` (User/Admin), `baseCurrency` (VND/USD/EUR), `timeZone`, `status` (Active/Locked), `createdAt`, `version`.
* **Wallet / LedgerAccount (Ví / Tài khoản sổ cái):** `id`, `userId`, `name`, `type` (Asset: Cash/Bank/E-Wallet/Saving; Liability: Credit/Overdraft), `currency`, `balanceOriginal`, `balanceBase`, `isArchived`, `createdAt`.
* **Asset (Tài sản khác ngoài ví):** `id`, `userId`, `name`, `category` (Vehicle, Electronics, RealEstate, Investment, Other), `estimatedValue`, `valuationDate`, `description`, `createdAt`.
* **Loan / Debt (Khoản vay & Nợ vi mô):** `id`, `userId`, `title`, `type` (Borrow - Đi vay / Lend - Cho vay), `lenderOrBorrowerName`, `initialPrincipal`, `remainingPrincipal`, `interestRate`, `startDate`, `dueDate`, `status` (Active, PaidOff, Overdue), `notes`.
* **LoanRepayment (Thanh toán nợ):** `id`, `loanId`, `transactionId`, `repaymentDate`, `principalAmount`, `interestAmount`, `feeAmount`, `paymentWalletId`.
* **Transaction (Giao dịch):** `id`, `userId`, `type` (Income, Expense, Transfer, LoanDisbursement, LoanRepayment), `effectiveDate`, `description`, `status` (Draft, Posted, Reversed), `idempotencyKey`, `originalId`, `receiptImageUrl`, `createdAt`.
* **LedgerEntry (Bút toán sổ kép):** `id`, `transactionId`, `accountId`, `direction` (Debit/Credit), `amountOriginal`, `currency`, `amountBase`, `exchangeRateSnapshot`.
* **Budget (Ngân sách):** `id`, `userId`, `categoryId`, `monthYear` (YYYY-MM), `limitAmountBase`, `isArchived`.
* **FinancialGoal (Mục tiêu tài chính):** `id`, `userId`, `title`, `targetAmountBase`, `currentAmountBase`, `deadlineDate`, `status` (InProgress, Achieved, Overdue, Cancelled).
* **GoalContribution (Phân bổ tích lũy mục tiêu):** `id`, `goalId`, `amountBase`, `contributionDate`, `sourceWalletId`, `notes`.
* **Subscription (Khoản định kỳ):** `id`, `userId`, `name`, `amount`, `currency`, `cycle` (Monthly/Yearly), `anchorDay`, `nextDueDate`, `walletId`, `categoryId`, `autoPost`, `status` (Active, Paused, Cancelled).

---

<a id="phan-3"></a>

## 3. Đặc tả Yêu cầu chức năng (Functional Requirements Specification - FR)

Cấu trúc đặc tả tuân thủ phương pháp kỹ nghệ yêu cầu của Ian Sommerville (§4.3.2): mỗi nhóm yêu cầu định rõ thuộc tính chuẩn hóa, đầu vào/nguồn, đầu ra/đích, tiền điều kiện, các câu mệnh lệnh bắt buộc (`FRnn.xx`), xử lý ngoại lệ, hậu điều kiện, tác động phụ và tiêu chí kiểm thử nghiệm thu (`TC-Fnn`).

### FR01 - Định danh, xác thực và quản lý phiên truy cập
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR01, BR11 | Use Case: UC01, UC02, UC03 | Kiểm thử: TC-F01
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

### FR02 - Quản lý tài khoản thanh toán, ví và giao dịch thu chi
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR02, BR01 - BR06, BR11 - BR15 | Use Case: UC04, UC05, UC06, UC07, UC08 | Kiểm thử: TC-F02
- **Đầu vào / Nguồn:** Tên ví, loại ví, tiền tệ, số dư mở sổ; hoặc thông tin giao dịch: loại thu/chi/chuyển tiền, ví nguồn, ví đích, danh mục, số tiền, ngày hiệu lực, mô tả và `Idempotency-Key`.
- **Đầu ra / Đích nhận:** Mã định danh ví và số dư mới; hoặc mã giao dịch, trạng thái Posted/Draft và thông tin bút toán sổ trả về cho người dùng.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; ví và danh mục phải thuộc quyền sở hữu của người dùng; ví tài sản phải đủ số dư khi chi tiêu; ngày giao dịch không ở tương lai.

**FR02.01** Hệ thống phải cho phép tạo các loại ví: Tiền mặt, Tài khoản ngân hàng, Ví điện tử, Tài khoản tiết kiệm; số dư đầu kỳ lớn hơn 0 phải được hạch toán đối ứng với tài khoản Vốn chủ sở hữu (Equity) trong một thao tác nguyên tử.  
**FR02.02** Hệ thống phải cho phép lưu, truy vấn, chỉnh sửa và xóa các bản ghi giao dịch ở trạng thái Nháp (Draft) mà không làm biến động số dư sổ cái.  
**FR02.03** Hệ thống phải ghi sổ (Posted) các giao dịch thu nhập và chi tiêu hợp lệ với số tiền $> 0$, đúng danh mục và đúng loại tiền tệ của ví liên quan theo nguyên lý kế toán kép.  
**FR02.04** Hệ thống phải xử lý giao dịch chuyển tiền giữa 2 ví khác nhau của cùng một chủ sở hữu; chuyển khác tiền tệ bắt buộc cung cấp số tiền nguồn và số tiền đích để chốt snapshot quy đổi theo BR04 và BR15.  
**FR02.05** Hệ thống phải từ chối xóa hoặc sửa vật lý đối với giao dịch đã ghi sổ (Posted); thao tác sửa giao dịch bắt buộc phải thực thi nguyên tử: tạo bút toán Đảo (Reversal) bản gốc và ghi nhận giao dịch thay thế mới có liên kết `originalId`.  
**FR02.06** Hệ thống phải áp dụng cơ chế chống trùng lặp Idempotency theo BR13 cho 100% các yêu cầu tạo giao dịch, đảo và thay thế giao dịch.  
**FR02.07** Hệ thống phải cho phép lưu trữ (Archive) ví có số dư bằng 0 và không còn giao dịch định kỳ hoạt động; ví đã lưu trữ không được phép phát sinh giao dịch mới nhưng vẫn hiển thị trong lịch sử báo cáo.  

- **Xử lý ngoại lệ:** Thiếu tỷ giá: chuyển giao dịch sang Draft hoặc từ chối mở ví ngoại tệ có số dư đầu kỳ. Số tiền $\le 0$, ví không đủ số dư khả dụng, chuyển tiền vào chính ví nguồn: từ chối và nêu rõ thông báo lỗi. Lỗi hệ thống: Rollback toàn bộ dữ liệu.
- **Hậu điều kiện:** Ví và giao dịch được lưu trữ nhất quán; số dư ví cập nhật chính xác; không bao giờ để ví tài sản bị âm ngoài quy định.
- **Tác động phụ:** Kích hoạt sự kiện cập nhật số liệu ngân sách và dashboard; ghi nhật ký kiểm toán giao dịch.
- **Tiêu chí kiểm chứng TC-F02:** Mở ví tài sản 2.000.000 VND và ví nợ 1.000.000 VND: kiểm tra Net Worth đúng 1.000.000 VND. Thử ghi thu, chi, chuyển tiền nội bộ, lưu trữ ví còn tiền, gửi lặp cùng khóa Idempotency và đảo giao dịch 2 lần.

---

### FR03 - Sổ kế toán kép và tính nguyên tử hạch toán
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR02, BR01, BR02, BR03, BR12, BR13 | Use Case: UC05 - UC08, SYS02 | Kiểm thử: TC-F03
- **Đầu vào / Nguồn:** Lệnh ghi sổ đã kiểm tra tính hợp lệ từ dịch vụ giao dịch, mở ví hoặc tác vụ định kỳ nền.
- **Đầu ra / Đích nhận:** Tập hợp các bản ghi dòng sổ Nợ/Có cân bằng hoàn hảo, số dư mới của các tài khoản sổ cái được cập nhật bền vững.
- **Tiền điều kiện:** Các tài khoản liên quan hợp lệ và cùng chủ sở hữu; các giá trị nguyên tệ và quy đổi tiền cơ sở đã được xác định; chưa từng xử lý khóa Idempotency này.

**FR03.01** Hệ thống phải tự động tạo các dòng Nợ và Có tương ứng với loại giao dịch và bắt buộc kiểm tra tính cân bằng tuyệt đối: $\sum \text{DebitBase} = \sum \text{CreditBase}$ trước khi commit dữ liệu vào cơ sở dữ liệu.  
**FR03.02** Hệ thống phải thực thi đồng thời trong một Database Transaction duy nhất đối với: bản ghi giao dịch, các dòng bút toán sổ cái, số dư đệm của ví và sự kiện thông báo tiếp nối theo chuẩn ACID.  
**FR03.03** Hệ thống phải tính toán số dư ví từ lịch sử các dòng sổ cái đã commit và có cơ chế phát hiện sai lệch số dư lưu đệm khi thực hiện đối soát tự động.  
**FR03.04** Hệ thống phải bảo đảm tính bất biến của các bút toán đã ghi; khi tạo bút toán đảo phải bảo toàn nguyên vẹn tham chiếu gốc, tài khoản đối ứng, số tiền và snapshot tỷ giá ban đầu.  

- **Xử lý ngoại lệ:** Mất cân bằng Nợ/Có dù chỉ 1 đơn vị tiền tệ nhỏ nhất: lập tức Rollback toàn bộ, ghi lỗi nghiêm trọng vào log vận hành.
- **Hậu điều kiện:** Không bao giờ tồn tại giao dịch mồ côi dòng sổ hoặc số dư ví bị cập nhật một phần.
- **Tác động phụ:** Ghi Audit Log cho các giao dịch thành công.
- **Tiêu chí kiểm chứng TC-F03:** Giả lập sự cố gián đoạn kết nối sau khi ghi dòng Nợ trước khi ghi dòng Có: kiểm tra toàn bộ thao tác đã Rollback sạch sẽ; kiểm tra xử lý đồng thời 100 giao dịch tranh chấp trên cùng 1 ví không gây sai lệch số dư cuối.

---

### FR04 - Quản lý tiền tệ và quy đổi tỷ giá hối đoái
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR02, BR03, BR04, BR15 | Use Case: UC04 - UC08, UC16 | Tác vụ: SYS01 | Kiểm thử: TC-F04
- **Đầu vào / Nguồn:** Cặp tiền tệ, thời điểm nghiệp vụ; dữ liệu tỷ giá từ A03 thông qua IF02; cấu hình tiền tệ cơ sở của người dùng.
- **Đầu ra / Đích nhận:** Snapshot tỷ giá hợp lệ và giá trị quy đổi tiền cơ sở trả về cho sổ cái và báo cáo tài chính.
- **Tiền điều kiện:** Cặp tiền tệ nằm trong danh mục hỗ trợ (VND, USD, EUR); số tiền $> 0$; tiền cơ sở đã được thiết lập.

**FR04.01** Hệ thống phải tiếp nhận, kiểm tra tính hợp lệ và lưu trữ lịch sử tỷ giá theo phiên bản kèm mốc thời gian `effectiveAt`, `fetchedAt` và nguồn gốc dữ liệu; tỷ giá của tiền cơ sở với chính nó luôn bằng 1.  
**FR04.02** Hệ thống phải chọn tỷ giá thị trường hợp lệ theo BR04 để quy đổi giá trị giao dịch; dòng xuất ví ngoại tệ phải áp dụng tỷ lệ ghi sổ theo BR15 và lưu trữ đầy đủ cả snapshot thị trường và tỷ lệ ghi sổ.  
**FR04.03** Hệ thống phải hạch toán phần chênh lệch giữa giá trị xuất sổ của ví ngoại tệ và giá trị thực tế của các dòng đối ứng vào tài khoản Lãi/Lỗ chênh lệch tỷ giá để bảo đảm tổng giá trị tiền cơ sở luôn cân bằng tuyệt đối.  
**FR04.04** Hệ thống phải giữ nguyên giá trị quy đổi tiền cơ sở của các giao dịch lịch sử khi có tỷ giá mới được cập nhật; báo cáo tài chính phải tách bạch chênh lệch tỷ giá khỏi dòng tiền thu/chi thực tế.  

- **Xử lý ngoại lệ:** Dịch vụ tỷ giá bên ngoài gặp sự cố: tiếp tục sử dụng phiên bản tỷ giá hợp lệ gần nhất trong vòng 24 giờ. Nếu quá 24 giờ mà không có tỷ giá mới, hệ thống tạm chặn ghi sổ các giao dịch ngoại tệ mới và giữ ở trạng thái Draft; các giao dịch nội tệ vẫn hoạt động bình thường.
- **Hậu điều kiện:** Toàn bộ giao dịch ngoại tệ có căn cứ quy đổi rõ ràng, không làm biến dạng dữ liệu lịch sử.
- **Tác động phụ:** Ghi nhật ký đồng bộ tỷ giá; cảnh báo quản trị khi dịch vụ tỷ giá quá hạn cập nhật.
- **Tiêu chí kiểm chứng TC-F04:** Nhận 100 USD với tỷ giá 25.000 (giá trị 2.500.000 VND). Xuất toàn bộ 100 USD đổi lấy 2.480.000 VND: ghi nhận lỗ tỷ giá 20.000 VND, số dư ví USD về 0 cả nguyên tệ lẫn giá trị cơ sở.

---

### FR05 - Quản lý ngân sách chi tiêu và cảnh báo ngưỡng
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR03, BR07, BR08 | Use Case: UC12, UC13 | Tác vụ: SYS03 | Kiểm thử: TC-F05
- **Đầu vào / Nguồn:** Danh mục chi tiêu, tháng áp dụng, hạn mức ngân sách từ người dùng; sự kiện giao dịch mới commit hoặc giao dịch bị đảo.
- **Đầu ra / Đích nhận:** Bản ghi ngân sách, số tiền đã chi, số tiền còn lại, phần trăm sử dụng và thông báo cảnh báo gửi đến giao diện người dùng.
- **Tiền điều kiện:** Danh mục chi tiêu thuộc quyền sở hữu của người dùng; hạn mức ngân sách $> 0$; không bị trùng lặp danh mục trong cùng một tháng.

**FR05.01** Hệ thống phải cho phép người dùng tạo mới, xem chi tiết, điều chỉnh hạn mức và lưu trữ ngân sách chi tiêu theo danh mục cho từng tháng xác định.  
**FR05.02** Hệ thống phải tự động tính toán tổng chi tiêu ròng và tỷ lệ phần trăm sử dụng ngân sách ngay sau khi có giao dịch chi tiêu mới commit, giao dịch bị đảo hoặc khi người dùng thay đổi hạn mức.  
**FR05.03** Hệ thống phải tự động tạo và gửi thông báo cảnh báo trong ứng dụng theo các ngưỡng BR08 (Vàng khi $\ge 80\%$, Đỏ khi $\ge 100\%$), áp dụng cơ chế khóa chống phát trùng lặp thông báo trong cùng một kỳ ngân sách.  
**FR05.04** Hệ thống phải cung cấp danh sách thông báo cảnh báo thuộc quyền sở hữu của người dùng, hỗ trợ xem chi tiết nguyên nhân cảnh báo và đánh dấu đã đọc.  

- **Xử lý ngoại lệ:** Danh mục chưa thiết lập ngân sách: bỏ qua việc kiểm tra cảnh báo. Lỗi trong quá trình tạo thông báo: ghi nhận hàng đợi thử lại, không được gây lỗi hoặc rollback giao dịch chi tiêu đã commit thành công.
- **Hậu điều kiện:** Dữ liệu ngân sách phản ánh tức thời tình trạng chi tiêu thực tế; thông báo lịch sử được bảo lưu.
- **Tác động phụ:** Cập nhật thông số ngân sách trên Dashboard tổng quan.
- **Tiêu chí kiểm chứng TC-F05:** Ngân sách ăn uống 2.000.000 VND/tháng. Thử chi 1.500.000 VND (75% - chưa cảnh báo); chi tiếp 150.000 VND (tổng 1.650.000 VND = 82,5% - phát cảnh báo Vàng duy nhất); chi tiếp 400.000 VND (tổng 2.050.000 VND = 102,5% - phát cảnh báo Đỏ duy nhất); thử gửi lại sự kiện chi tiêu kiểm tra không sinh thêm thông báo thừa.

---

### FR06 - Quản lý khoản giao dịch định kỳ (Subscriptions / Recurring)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR04, BR09, BR12, BR13 | Use Case: UC14 | Tác vụ: SYS02 | Kiểm thử: TC-F06
- **Đầu vào / Nguồn:** Tên khoản định kỳ, ví thanh toán, danh mục chi/thu, số tiền, chu kỳ (Tháng/Năm), ngày neo trong tháng, chế độ (Nháp hoặc Tự ghi sổ).
- **Đầu ra / Đích nhận:** Lịch định kỳ hoạt động, trạng thái từng lần đến hạn (`occurrence`), giao dịch/nháp tương ứng và thông báo nhắc nhở.
- **Tiền điều kiện:** Ví và danh mục đang hoạt động và thuộc sở hữu người dùng; số tiền $> 0$; người dùng xác nhận đồng ý nếu chọn chế độ tự ghi sổ.

**FR06.01** Hệ thống phải cho phép người dùng tạo, xem, chỉnh sửa lịch cho các kỳ tương lai, tạm dừng (Pause), tiếp tục (Resume) hoặc hủy bỏ lịch định kỳ mà không làm ảnh hưởng các kỳ đã ghi nhận trong quá khứ.  
**FR06.02** Hệ thống phải định kỳ quét các khoản đến hạn; mỗi kỳ đến hạn chỉ được sinh duy nhất một bản ghi kết quả xử lý với khóa chống trùng `(subscriptionId, dueDate)`.  
**FR06.03** Hệ thống phải tạo bản ghi Nháp (Draft) và gửi thông báo nhắc nhở ở chế độ mặc định; ở chế độ tự ghi sổ, hệ thống phải áp dụng đầy đủ quy tắc kiểm tra số dư và sổ kép như một giao dịch thông thường.  
**FR06.04** Hệ thống phải tự động tính toán ngày đến hạn tiếp theo dựa trên ngày neo gốc; tự động điều chỉnh về ngày cuối tháng đối với các tháng thiếu ngày theo BR09 và có cơ chế thử lại đối với các kỳ xử lý thất bại do lỗi tạm thời.  

- **Xử lý ngoại lệ:** Ví không đủ tiền hoặc bị lưu trữ khi tự ghi sổ: ghi nhận trạng thái kỳ là Failed kèm lý do, gửi thông báo cho người dùng và không ghi sổ dở dang. Các kỳ rơi vào khoảng thời gian lịch bị tạm dừng (Paused) sẽ được bỏ qua khi tiếp tục lịch.
- **Hậu điều kiện:** Kỳ đến hạn được đánh dấu hoàn thành chỉ sau khi bản nháp hoặc giao dịch đã được lưu trữ bền vững; không bao giờ sinh giao dịch trùng lặp khi chạy lại tiến trình.
- **Tác động phụ:** Ghi nhật ký kiểm toán; chế độ tự ghi sổ có thể kích hoạt cảnh báo ngân sách tương ứng.
- **Tiêu chí kiểm chứng TC-F06:** Thiết lập lịch thanh toán tiền nhà ngày 31 hàng tháng bắt đầu từ 31/01: kiểm tra kỳ tháng 2 rơi vào 28/02 (năm thường) và kỳ tháng 3 quay lại đúng ngày 31/03; giả lập 2 tiến trình nền chạy đồng thời kiểm tra không sinh 2 giao dịch.

---

### FR07 - Quản lý mục tiêu tài chính và kế hoạch tích lũy
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR05, BR10 | Use Case: UC15 | Kiểm thử: TC-F07
- **Đầu vào / Nguồn:** Tên mục tiêu, số tiền đích (`targetAmount`), ngày thời hạn (`deadlineDate`) từ người dùng; các khoản phân bổ tiền tích lũy hoặc hủy phân bổ sai sót.
- **Đầu ra / Đích nhận:** Bản ghi mục tiêu, tổng số tiền đã tích lũy (`currentAmount`), phần trăm tiến độ, số tiền còn thiếu và số tiền cần tiết kiệm trung bình mỗi tháng.
- **Tiền điều kiện:** Người dùng đã đăng nhập; số tiền đích $> 0$; ngày thời hạn không được trước ngày hiện tại.

**FR07.01** Hệ thống phải cho phép người dùng tạo mới, xem danh sách, chỉnh sửa thông tin (tên, số tiền đích, thời hạn), hủy bỏ và lưu trữ mục tiêu tài chính.  
**FR07.02** Hệ thống phải cho phép người dùng ghi nhận các khoản phân bổ tiền tích lũy ($amount > 0$) gắn với mục tiêu và cho phép hủy bỏ khoản phân bổ nhập sai có kèm lý do mà không làm thay đổi số dư thực tế của ví tiền (theo BR10).  
**FR07.03** Hệ thống phải tự động tính toán tiến độ hoàn thành: $Progress = \frac{CurrentAmount}{TargetAmount} \times 100\%$ và tự động phân loại trạng thái mục tiêu: Đạt (Achieved khi $Current \ge Target$), Quá hạn (Overdue khi hết thời hạn mà chưa đạt), Đang thực hiện (InProgress).  
**FR07.04** Hệ thống phải tự động tính toán và hiển thị kế hoạch tiết kiệm gợi ý: số tiền cần tích lũy bình quân mỗi tháng: $MonthlySaving = \frac{TargetAmount - CurrentAmount}{\text{Số tháng còn lại}}$; nếu đã quá hạn hoặc đã đạt, hiển thị trạng thái tương ứng và không thực hiện phép chia cho 0.  

- **Xử lý ngoại lệ:** Số tiền đích $\le 0$, thời hạn ở quá khứ: từ chối và báo lỗi. Hủy một khoản phân bổ đã bị hủy trước đó: từ chối thao tác.
- **Hậu điều kiện:** Tiến độ mục tiêu và kế hoạch tiết kiệm được tính toán nhất quán; dữ liệu sổ kế toán không bị thay đổi.
- **Tác động phụ:** Cập nhật tiến độ mục tiêu trên Dashboard tài chính.
- **Tiêu chí kiểm chứng TC-F07:** Mục tiêu mua laptop 30.000.000 VND trong 10 tháng. Phân bổ lần 1: 6.000.000 VND (tiến độ 20%, cần 2.400.000 VND/tháng cho 10 tháng); phân bổ lần 2: 9.000.000 VND (tổng 15.000.000 VND, tiến độ 50%, cần 1.500.000 VND/tháng); hủy phân bổ lần 1: kiểm tra số dư tích lũy giảm về 9.000.000 VND (tiến độ 30%).

---

### FR08 - Quản lý danh mục tài sản và tài sản ròng (Personal Wealth & Net Worth)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR06, UR08, BR02, BR16 | Use Case: UC09, UC16 | Kiểm thử: TC-F08
- **Đầu vào / Nguồn:** Thông tin tài sản vật chất khác ngoài ví (Tên tài sản, phân loại: Phương tiện, Thiết bị, Bất động sản, Vàng/Đầu tư; Giá trị định giá, Ngày định giá, Mô tả); yêu cầu tính toán Tài sản ròng từ người dùng.
- **Đầu ra / Đích nhận:** Danh mục tài sản cá nhân, Tổng tài sản (Total Assets), Tổng nợ (Total Liabilities), Tài sản ròng (Net Worth) và biểu đồ biến động Net Worth theo thời gian.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; giá trị định giá tài sản $\ge 0$.

**FR08.01** Hệ thống phải cho phép người dùng thêm mới, cập nhật thông tin, cập nhật giá trị định giá lại và xóa các tài sản vật chất khác ngoài ví tiền.  
**FR08.02** Hệ thống phải tự động tổng hợp Tổng tài sản (Total Assets) bao gồm: tổng số dư quy đổi tiền cơ sở của toàn bộ các ví tài sản (Tiền mặt, Ngân hàng, Ví điện tử, Sổ tiết kiệm) cộng với tổng giá trị định giá của toàn bộ tài sản vật chất khác đang theo dõi.  
**FR08.03** Hệ thống phải tự động tổng hợp Tổng nợ (Total Liabilities) bao gồm: tổng số dư các ví nợ (Thẻ tín dụng) cộng với tổng dư nợ gốc còn lại của toàn bộ các khoản vay/nợ vi mô đang hoạt động.  
**FR08.04** Hệ thống phải tính toán chính xác Tài sản ròng tại thời điểm bất kỳ theo công thức: $\text{Net Worth} = \text{Total Assets} - \text{Total Liabilities}$ theo chuẩn BR16 và lưu trữ lịch sử mốc thời gian để vẽ biểu đồ xu hướng.  
**FR08.05** Hệ thống phải bảo đảm nguyên tắc không tính trùng lặp: số tiền đã nằm trong số dư ví tiền chỉ được tính duy nhất 1 lần vào Tổng tài sản; việc chuyển tiền giữa các ví nội bộ tuyệt đối không làm thay đổi Net Worth.  

- **Xử lý ngoại lệ:** Giá trị tài sản $< 0$: từ chối và yêu cầu nhập số dương. Khi người dùng chưa có tài sản hoặc nợ: hiển thị giá trị 0 và hướng dẫn nhập liệu.
- **Hậu điều kiện:** Số liệu Net Worth phản ánh trung thực toàn bộ tiềm lực tài chính cá nhân của người dùng.
- **Tác động phụ:** Cập nhật các chỉ số trọng yếu trên Dashboard.
- **Tiêu chí kiểm chứng TC-F08:** Người dùng có Ví ngân hàng 20.000.000 VND, Sổ tiết kiệm 50.000.000 VND, Xe máy định giá 25.000.000 VND, Khoản vay ngân hàng còn nợ 15.000.000 VND, Thẻ tín dụng nợ 5.000.000 VND. Kiểm tra: Total Assets = 95.000.000 VND; Total Liabilities = 20.000.000 VND; Net Worth = 75.000.000 VND. Thực hiện chuyển 5.000.000 VND từ ngân hàng sang tiết kiệm: Net Worth giữ nguyên 75.000.000 VND.

---

### FR09 - Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance Tracking)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR07, BR05, BR17 | Use Case: UC10, UC11 | Tác vụ: SYS05 | Kiểm thử: TC-F09
- **Đầu vào / Nguồn:** Thông tin khoản vay: Tên khoản vay, loại (Đi vay / Cho vay), bên liên quan, số tiền gốc ban đầu, lãi suất (nếu có), ngày bắt đầu, ngày đến hạn, ghi chú; Thông tin trả nợ: ngày trả, số tiền gốc, tiền lãi/phí, ví thanh toán.
- **Đầu ra / Đích nhận:** Bản ghi khoản vay, dư nợ gốc còn lại, lịch sử trả nợ, trạng thái khoản vay (Active, PaidOff, Overdue) và các cảnh báo hạn thanh toán nợ.
- **Tiền điều kiện:** Người dùng đã xác thực; số tiền vay $> 0$; ngày đến hạn $\ge$ ngày bắt đầu; ví thanh toán đủ số dư khi ghi nhận trả nợ.

**FR09.01** Hệ thống phải cho phép người dùng tạo, xem chi tiết danh sách, chỉnh sửa thông tin và theo dõi các khoản vay mượn vi mô (bao gồm cả khoản đi vay và khoản cho vay người khác).  
**FR09.02** Khi tạo khoản đi vay mới có nhận tiền thực tế vào ví, hệ thống phải tự động sinh giao dịch ghi nợ: Tăng số dư ví nhận tiền và Tăng nợ phải trả tương ứng; tuyệt đối cấm ghi nhận số tiền vay nhận được vào tài khoản Thu nhập (theo BR17).  
**FR09.03** Hệ thống phải cho phép ghi nhận các đợt thanh toán trả nợ từng kỳ; hệ thống bắt buộc phân rã số tiền thanh toán thành: Tiền trả nợ gốc (làm giảm trực tiếp dư nợ gốc của khoản vay và giảm số dư ví chi trả) và Tiền lãi/phí phát sinh (hạch toán vào Chi phí tài chính và giảm số dư ví chi trả) theo chuẩn kế toán.  
**FR09.04** Hệ thống phải tự động tính toán dư nợ gốc còn lại sau mỗi lần trả nợ: $\text{Dư nợ còn lại} = \text{Số tiền gốc ban đầu} - \sum \text{Tiền gốc đã trả}$; tự động chuyển trạng thái sang "Đã thanh toán" (PaidOff) khi dư nợ gốc bằng 0.  
**FR09.05** Hệ thống phải tự động quét các khoản vay sắp đến hạn thanh toán trong vòng 3 ngày và gửi thông báo nhắc nhở người dùng (SYS05).  

- **Xử lý ngoại lệ:** Số tiền trả gốc vượt quá số dư nợ còn lại: từ chối thao tác và cảnh báo người dùng. Ví chi trả không đủ tiền: từ chối ghi nhận và yêu cầu chọn ví khác hoặc nạp thêm tiền.
- **Hậu điều kiện:** Dư nợ gốc của khoản vay và số dư ví tiền được cập nhật đồng bộ; các bút toán hạch toán tuân thủ sổ kép.
- **Tác động phụ:** Cập nhật Total Liabilities và Net Worth của người dùng.
- **Tiêu chí kiểm chứng TC-F09:** Tạo khoản vay mua laptop 15.000.000 VND. Trả nợ kỳ 1 gồm 3.000.000 VND gốc + 200.000 VND lãi từ Ví ngân hàng: kiểm tra số dư Ví ngân hàng giảm 3.200.000 VND, dư nợ khoản vay còn 12.000.000 VND, chi phí trong kỳ tăng 200.000 VND (thu chi không bị tính 3.000.000 VND gốc).

---

### FR10 - Dashboard tổng quan tài chính cá nhân và tài sản ròng
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR08, BR02, BR14, BR16, BR18 | Use Case: UC16 | Kiểm thử: TC-F10
- **Đầu vào / Nguồn:** Khoảng thời gian lọc (Mặc định tháng hiện tại), bộ lọc tài khoản từ yêu cầu xem của người dùng.
- **Đầu ra / Đích nhận:** Màn hình Dashboard trực quan hiển thị: Tài sản ròng (Net Worth), Tổng tài sản, Tổng nợ, Thu nhập trong tháng, Chi phí trong tháng, Dòng tiền thuần (Cash Flow), Tỷ lệ tiết kiệm (Saving Rate), Tình trạng ngân sách và Tiến độ mục tiêu tài chính.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.

**FR10.01** Hệ thống phải hiển thị giá trị Tài sản ròng (Net Worth), Tổng tài sản và Tổng nợ tại thời điểm hiện tại của người dùng theo giá trị sổ sách quy đổi tiền cơ sở.  
**FR10.02** Hệ thống phải hiển thị tổng Thu nhập, tổng Chi phí, Dòng tiền thuần ($CashFlow = Income - Expense$) và Tỷ lệ tiết kiệm ($SavingRate$) trong kỳ được chọn từ cùng một mốc snapshot dữ liệu nhất quán.  
**FR10.03** Hệ thống phải hiển thị biểu đồ cơ cấu chi tiêu theo danh mục, biểu đồ xu hướng số dư/tài sản ròng theo thời gian, trạng thái các ngân sách chi tiêu và tiến độ các mục tiêu tài chính đang thực hiện.  
**FR10.04** Khi người dùng chưa có bất kỳ giao dịch nào, hệ thống phải hiển thị các thẻ chỉ số bằng 0 kèm thông điệp hướng dẫn người dùng bắt đầu tạo ví và ghi nhận giao dịch đầu tiên.  

- **Xử lý ngoại lệ:** Bộ lọc thời gian không hợp lệ (ngày bắt đầu sau ngày kết thúc): báo lỗi trường. Dữ liệu đang trong tiến trình tính toán lại: hiển thị trạng thái đang tải (Loading), không hiển thị dữ liệu chắp vá giữa hai mốc thời gian khác nhau.
- **Hậu điều kiện:** Dữ liệu hiển thị thuộc quyền sở hữu của người dùng; thao tác xem không làm thay đổi dữ liệu sổ sách.
- **Tác động phụ:** Không có.
- **Tiêu chí kiểm chứng TC-F10:** Đối chiếu toàn bộ các số liệu hiển thị trên Dashboard với kết quả tính toán chi tiết từ sổ cái; xác nhận số liệu Net Worth, Cash Flow và Saving Rate khớp chính xác 100%.

---

### FR11 - Phân tích dòng tiền, tỷ lệ tiết kiệm và xu hướng tài chính
- **Thuộc tính yêu cầu:** Mức ưu tiên: P2 | Truy vết: UR08, BR05, BR14, BR18 | Use Case: UC17 | Kiểm thử: TC-F11
- **Đầu vào / Nguồn:** Khoảng ngày tùy chọn (Tuần, Tháng, Quý, Năm), cấp độ nhóm dữ liệu và bộ lọc ví từ người dùng.
- **Đầu ra / Đích nhận:** Chuỗi số liệu phân tích: Tổng thu, Tổng chi, Dòng tiền thuần, Tỷ lệ tiết kiệm, Nhóm chi tiêu cao nhất và Bảng dữ liệu so sánh đa kỳ (Kỳ này so với kỳ trước).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; ngày bắt đầu $\le$ ngày kết thúc.

**FR11.01** Hệ thống phải tổng hợp thu nhập và chi phí theo ngày hiệu lực trong khoảng thời gian được chọn, bao gồm đầy đủ tác động bù trừ của các bút toán đảo tương ứng.  
**FR11.02** Hệ thống phải tính toán chính xác $NetCashFlow = TotalIncome - TotalExpense$ và $SavingRate = \frac{TotalIncome - TotalExpense}{TotalIncome} \times 100\%$; loại trừ hoàn toàn các giao dịch chuyển tiền nội bộ, số dư mở sổ và chênh lệch tỷ giá khỏi doanh số thu/chi.  
**FR11.03** Hệ thống phải xử lý an toàn phép tính Saving Rate theo BR18: hiển thị "N/A" khi thu nhập $\le 0$ và hiển thị giá trị âm khi chi tiêu vượt thu nhập nhằm cảnh báo thâm hụt dòng tiền.  
**FR11.04** Hệ thống phải cung cấp báo cáo so sánh xu hướng giữa các kỳ liên tiếp (ví dụ: tháng này so với tháng trước): mức tăng/giảm chi tiêu, thay đổi tài sản ròng và biến động tỷ lệ tiết kiệm.  

- **Xử lý ngoại lệ:** Khoảng ngày không có giao dịch phát sinh: hệ thống trả về dãy giá trị 0 cho toàn bộ các mốc thời gian trong khoảng lọc.
- **Hậu điều kiện:** Dữ liệu phân tích là chỉ đọc (Read-only), có thể đối chiếu chéo với lịch sử sổ cái.
- **Tác động phụ:** Không có.
- **Tiêu chí kiểm chứng TC-F11:** Tháng có Thu nhập 10.000.000 VND, Chi tiêu 6.500.000 VND, Chuyển ví 2.000.000 VND: kiểm tra Net Cash Flow = 3.500.000 VND; Saving Rate = 35%; tiền chuyển ví không làm biến dạng số liệu thu chi.

---

### FR12 - Tra cứu lịch sử, đối soát sổ và xuất báo cáo tài chính
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Tra cứu/Đối soát), P2 (Xuất tệp) | Truy vết: UR09, BR01, BR11, BR14 | Use Case: UC18, UC19 | Kiểm thử: TC-F12
- **Đầu vào / Nguồn:** Tiêu chí tìm kiếm (Khoảng ngày, ví, danh mục, loại giao dịch, trạng thái, từ khóa mô tả) hoặc lệnh yêu cầu đối soát số dư từ người dùng.
- **Đầu ra / Đích nhận:** Danh sách giao dịch phân trang, thông tin chi tiết từng dòng sổ Nợ/Có, kết quả đối soát số dư hoặc tệp CSV tải về máy người dùng.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; chỉ tra cứu trên tập dữ liệu thuộc quyền sở hữu của chính mình.

**FR12.01** Hệ thống phải cho phép tìm kiếm, lọc phân trang lịch sử giao dịch và xem chi tiết từng giao dịch bao gồm: số tiền nguyên tệ, loại tiền, số tiền quy đổi tiền cơ sở, snapshot tỷ giá, các dòng sổ Nợ/Có và các liên kết đảo/thay thế liên quan.  
**FR12.02** Hệ thống phải cung cấp chức năng đối soát tự động: đối chiếu số dư tính toán từ tổng các dòng sổ cái trong cơ sở dữ liệu với số dư bộ đệm lưu trữ của từng ví; hiển thị thông báo "Khớp hoàn toàn" hoặc liệt kê chi tiết các khoản chênh lệch phát hiện được.  
**FR12.03** Hệ thống phải hỗ trợ xuất dữ liệu báo cáo sang tệp CSV mã hóa UTF-8 chuẩn xác theo đúng bộ lọc của người dùng; số tiền sử dụng dấu chấm thập phân, không chứa dấu phân cách hàng nghìn.  
**FR12.04** Hệ thống phải áp dụng biện pháp phòng chống tấn công chèn công thức bảng tính (CSV Formula Injection) đối với các trường văn bản tự do (mô tả, tên danh mục, ghi chú) trước khi xuất tệp.  

- **Xử lý ngoại lệ:** Không có dữ liệu khớp bộ lọc: trả về danh sách rỗng hoặc tệp CSV chỉ chứa dòng tiêu đề (Header). Vượt quá giới hạn xuất dữ liệu (tối đa 100.000 bản ghi/tệp): yêu cầu người dùng thu hẹp khoảng thời gian lọc.
- **Hậu điều kiện:** Dữ liệu báo cáo khớp chính xác 100% với số liệu sổ cái tại thời điểm xuất; thao tác xuất không làm thay đổi dữ liệu hệ thống.
- **Tác động phụ:** Ghi nhật ký kiểm toán hành vi xuất dữ liệu (Audit Log).
- **Tiêu chí kiểm chứng TC-F12:** Tạo giao dịch có mô tả chứa ký tự công thức nguy hiểm như `=cmd|' /C calc'!A0`: xuất CSV và kiểm tra ký tự đã được vô hiệu hóa an toàn; đối chiếu tổng tiền trong tệp CSV khớp chính xác với sổ cái.

---

### FR13 - Sao lưu và phục hồi dữ liệu hệ thống
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR11, BR01, BR11, BR12 | Use Case: UC24, UC25 | Tác vụ: SYS04 | Kiểm thử: TC-F13
- **Đầu vào / Nguồn:** Lịch sao lưu tự động định kỳ (mỗi 12 giờ) hoặc lệnh sao lưu thủ công từ Quản trị viên (A02); mã bản sao lưu và yêu cầu xác thực phục hồi dữ liệu.
- **Đầu ra / Đích nhận:** Bản sao lưu dữ liệu toàn vẹn kèm Manifest và Checksum SHA-256; báo cáo kết quả kiểm tra phục hồi dữ liệu cho Quản trị viên.
- **Tiền điều kiện:** Quản trị viên có đặc quyền hệ thống; kho lưu trữ IF04 sẵn sàng; thao tác khôi phục dữ liệu bắt buộc kích hoạt trong cửa sổ bảo trì có chặn ghi dữ liệu mới.

**FR13.01** Hệ thống phải tự động thực hiện sao lưu toàn bộ dữ liệu (cơ sở dữ liệu, quan hệ bảng, tệp đính kèm, cấu hình) định kỳ tối thiểu mỗi 12 giờ một lần và cho phép kích hoạt sao lưu theo yêu cầu từ Quản trị viên.  
**FR13.02** Hệ thống phải tự động tính toán mã băm SHA-256, lập tệp kê khai Manifest và kiểm tra tính toàn vẹn của bản sao lưu ngay sau khi hoàn tất.  
**FR13.03** Khi thực hiện khôi phục dữ liệu, hệ thống phải tiến hành giải nén và kiểm tra tính nhất quán (cân bằng sổ kế toán, toàn vẹn khóa ngoại) trong môi trường thử nghiệm cô lập trước khi chuyển sang môi trường phục vụ chính thức.  
**FR13.04** Hệ thống phải tự động chụp bản sao lưu hiện trạng trước khi tiến hành khôi phục dữ liệu mới; bảo đảm khả năng quay trở lại bản sao lưu trước đó nếu tiến trình khôi phục gặp sự cố.  

- **Xử lý ngoại lệ:** Bản sao lưu bị lỗi mã băm Checksum hoặc không đúng phiên bản lược đồ: từ chối khôi phục và giữ nguyên hiện trạng hệ thống.
- **Hậu điều kiện:** Dữ liệu hệ thống được phục hồi chính xác về mốc thời gian sao lưu; mọi phiên đăng nhập cũ bị thu hồi để đảm bảo an toàn.
- **Tác động phụ:** Tạm dừng các tác vụ nền định kỳ trong quá trình khôi phục dữ liệu.
- **Tiêu chí kiểm chứng TC-F13:** Tạo bản sao lưu của hệ thống có chứa đầy đủ giao dịch đa tiền tệ, khoản vay và mục tiêu; giả lập sự cố và thực hiện khôi phục; kiểm tra toàn bộ số dư và tính cân bằng sổ cái sau khôi phục đạt chuẩn 100%; đo thời gian RPO $< 12$ giờ và RTO $< 60$ phút.

---

### FR14 - Quản trị danh mục, cấu hình và người dùng
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 | Truy vết: UR01, UR11, BR04, BR07, BR11 | Use Case: UC03, UC21, UC22, UC23 | Kiểm thử: TC-F14
- **Đầu vào / Nguồn:** Danh mục thu/chi từ người dùng; tham số cấu hình vận hành (nguồn tỷ giá, chu kỳ job, tham số lưu trữ) hoặc lệnh khóa/mở khóa tài khoản từ Quản trị viên.
- **Đầu ra / Đích nhận:** Danh mục cập nhật, phiên bản cấu hình vận hành mới, trạng thái tài khoản người dùng và nhật ký vận hành hệ thống.
- **Tiền điều kiện:** Đúng phạm vi quyền hạn (User chỉ quản lý danh mục của mình; Admin quản lý cấu hình hệ thống); Quản trị viên phải nhập lý do bắt buộc khi khóa tài khoản người dùng.

**FR14.01** Hệ thống phải cho phép người dùng tự tạo mới, đổi tên và lưu trữ các danh mục thu nhập và chi tiêu cá nhân; danh mục đã phát sinh giao dịch không được phép xóa vật lý hoặc đổi loại thu/chi.  
**FR14.02** Hệ thống phải cho phép người dùng lựa chọn tiền cơ sở (`baseCurrency`) trước khi phát sinh giao dịch đầu tiên và khóa vĩnh viễn tùy chọn này sau khi giao dịch đầu tiên được ghi sổ.  
**FR14.03** Hệ thống phải cho phép Quản trị viên điều chỉnh các tham số vận hành hợp lệ, lưu trữ lịch sử cấu hình theo phiên bản và người thực hiện; cấm hiển thị khóa bảo mật thô trên giao diện.  
**FR14.04** Hệ thống phải cho phép Quản trị viên xem danh sách trạng thái người dùng, thực hiện khóa/mở khóa tài khoản với lý do bắt buộc; hệ thống tự động thu hồi toàn bộ phiên đăng nhập của tài khoản ngay khi bị khóa.  
**FR14.05** Hệ thống tuyệt đối không cho phép Quản trị viên tự vô hiệu hóa tài khoản Quản trị viên duy nhất còn lại của hệ thống.  

- **Xử lý ngoại lệ:** Trùng tên danh mục cùng cấp: báo lỗi trường. Thay đổi tham số vận hành không hợp lệ: từ chối toàn bộ và giữ nguyên phiên bản cấu hình trước đó.
- **Hậu điều kiện:** Cấu hình và trạng thái tài khoản có hiệu lực ngay lập tức kể từ yêu cầu tiếp theo; lịch sử dữ liệu của tài khoản bị khóa vẫn được bảo toàn trọn vẹn.
- **Tác động phụ:** Ghi nhật ký kiểm toán hành chính bắt buộc.
- **Tiêu chí kiểm chứng TC-F14:** Kiểm tra khóa tiền cơ sở sau khi ghi nhận 1 giao dịch; Quản trị viên khóa tài khoản người dùng đang đăng nhập: kiểm tra yêu cầu API tiếp theo của người dùng bị từ chối 401/403 ngay lập tức.

---

### FR15 - Kiểm soát truy cập API và bảo mật dữ liệu
- **Thuộc tính yêu cầu:** Mức ưu tiên: P1 (Cốt lõi) | Truy vết: UR01, UR12, BR11 | Áp dụng: Toàn bộ UC bảo vệ | Kiểm thử: TC-F15
- **Đầu vào / Nguồn:** Token xác thực, thông tin định danh vai trò (Role), định danh tài nguyên cần thao tác từ mọi yêu cầu HTTP gửi đến API.
- **Đầu ra / Đích nhận:** Cho phép yêu cầu tiếp tục xử lý hoặc từ chối truy cập kèm mã lỗi HTTP chuẩn hóa (401, 403, 404).
- **Tiền điều kiện:** Đường dẫn công khai duy nhất là Đăng ký, Đăng nhập và các tài sản giao diện tĩnh; toàn bộ các API còn lại đều được đặt dưới cơ chế bảo vệ nghiêm ngặt.

**FR15.01** Hệ thống phải kiểm chứng tính hợp lệ và thời hạn hiệu lực của Token xác thực trước khi chuyển tiếp yêu cầu đến tầng xử lý nghiệp vụ; danh tính người dùng bắt buộc phải được trích xuất từ Token đã được ký số an toàn, tuyệt đối không tin tưởng tham số `userId` do máy khách truyền lên trong phần thân yêu cầu.  
**FR15.02** Hệ thống phải kiểm tra quyền sở hữu đối với 100% các đối tượng tài nguyên dữ liệu (ví, tài sản, khoản nợ, giao dịch, ngân sách, tệp xuất, hình ảnh); nếu người dùng yêu cầu tài nguyên không thuộc quyền sở hữu của mình, hệ thống phải trả về mã lỗi 404 Not Found (nhằm che giấu sự tồn tại của dữ liệu) thay vì 403.  
**FR15.03** Hệ thống phải áp dụng nguyên tắc đặc quyền tối thiểu (Least Privilege) cho các tiến trình xử lý nền; tự động thẩm tra trạng thái hoạt động của chủ tài khoản trước khi thực thi ghi sổ tự động cho các khoản định kỳ.  

- **Xử lý ngoại lệ:** Token không hợp lệ hoặc hết hạn: trả về 401 Unauthorized; sai vai trò quản trị: trả về 403 Forbidden; tài khoản bị khóa: từ chối truy cập.
- **Hậu điều kiện:** Các yêu cầu bị từ chối không gây ra bất kỳ biến động nào đối với dữ liệu tài chính của người khác.
- **Tác động phụ:** Ghi nhật ký cảnh báo bảo mật khi phát hiện dấu hiệu dò quét tài nguyên trái phép.
- **Tiêu chí kiểm chứng TC-F15:** Người dùng UserA dùng ID thật của giao dịch thuộc UserB để yêu cầu chi tiết/tải ảnh/xuất tệp: kiểm tra hệ thống trả về 404 Not Found và không làm lộ bất kỳ dữ liệu nào của UserB.

---

### FR16 - Nhận dạng và nhập tự động giao dịch từ hóa đơn/ảnh (OCR)
- **Thuộc tính yêu cầu:** Mức ưu tiên: P2 | Truy vết: UR10, BR11, BR12, BR13 | Use Case: UC20 | Kiểm thử: TC-F16
- **Đầu vào / Nguồn:** Tệp hình ảnh hóa đơn/biên nhận thanh toán định dạng JPEG/PNG do người dùng tải lên thông qua IF03; thông tin xác nhận/chỉnh sửa từ người dùng.
- **Đầu ra / Đích nhận:** Bản ghi Nháp (Draft) chứa các trường dữ liệu trích xuất tự động (Số tiền, Ngày, Đơn vị tiền tệ, Tên nhà cung cấp/Mô tả) và giao dịch Posted sau khi người dùng xác nhận.
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; tệp ảnh đúng định dạng và dung lượng $\le 10$ MB; người dùng lựa chọn ví thanh toán và danh mục chi tiêu.

**FR16.01** Hệ thống phải kiểm tra chữ ký nhị phân của tệp ảnh tải lên và kích hoạt module OCR trích xuất tự động các trường thông tin cơ bản để tạo thành bản ghi Nháp; đánh dấu rõ ràng các trường dữ liệu có độ tin cậy thấp hoặc chưa nhận dạng được.  
**FR16.02** Hệ thống bắt buộc phải hiển thị kết quả trích xuất cho người dùng kiểm tra, chỉnh sửa toàn bộ các trường dữ liệu và chỉ được ghi sổ (Posted) sau khi có thao tác xác nhận chủ động của người dùng; tuyệt đối không tự động hạch toán trực tiếp từ kết quả nhận dạng máy học.  
**FR16.03** Hệ thống phải áp dụng cùng quy trình kiểm tra sổ kép và số dư khả dụng như giao dịch nhập tay thông thường; lưu trữ liên kết an toàn giữa tệp ảnh gốc và giao dịch được tạo ra.  
**FR16.04** Hệ thống phải tính toán mã băm Checksum của tệp ảnh và hiển thị cảnh báo nếu người dùng tải lên tệp ảnh trùng lặp đã từng được sử dụng trước đó để phòng ngừa ghi nhận trùng chi phí.  

- **Xử lý ngoại lệ:** Tệp ảnh mờ, rách hoặc không thể nhận dạng văn bản: thông báo nhận dạng không thành công và hướng dẫn người dùng chuyển sang nhập liệu thủ công. Tệp ảnh giả mạo hoặc vượt quá 10 MB: từ chối tiếp nhận. Người dùng hủy thao tác: không tạo bút toán nào trong sổ cái.
- **Hậu điều kiện:** Chỉ các dữ liệu được người dùng xác nhận mới được ghi sổ; tệp ảnh được phân quyền riêng tư chỉ chủ tài khoản mới được truy cập.
- **Tác động phụ:** Lưu trữ tệp ảnh an toàn trong kho đính kèm.
- **Tiêu chí kiểm chứng TC-F16:** Tải lên bộ 20 ảnh hóa đơn thực tế (rõ nét, mờ, chụp nghiêng, hóa đơn tiếng Việt): kiểm tra tỷ lệ trích xuất đúng trường số tiền; kiểm tra không có ảnh nào tự động sinh giao dịch Posted mà không qua bước người dùng nhấn nút Xác nhận.

---

<a id="phan-4"></a>

## 4. Mô hình tương tác, Use Cases và Kịch bản xử lý nền

### 4.1 Danh mục Use Case theo tác nhân

```
                               ┌────────────────────────────────────────────────────────┐
                               │                    WEALTHFLOW SYSTEM                   │
                               │                                                        │
                               │  [Định danh & Hồ sơ]                                   │
                               │  ├── UC01: Đăng ký tài khoản                           │
                               │  ├── UC02: Quản lý phiên & Đổi mật khẩu                │
                               │  └── UC03: Cập nhật hồ sơ & Tiền cơ sở                 │
                               │                                                        │
                               │  [Ví & Giao dịch hàng ngày]                            │
                               │  ├── UC04: Quản lý tài khoản / Ví                      │
                               │  ├── UC05: Ghi nhận thu nhập                           │
                               │  ├── UC06: Ghi nhận chi tiêu                           │
                               │  ├── UC07: Chuyển tiền giữa các ví                     │
                               │  ├── UC08: Đảo / Thay thế giao dịch                    │
                               │  └── UC20: Nhập giao dịch từ ảnh (OCR)                 │
                               │                                                        │
     ┌──────────────────┐      │  [Tài sản & Tài chính vi mô (Microfinance)]            │
     │                  │      │  ├── UC09: Quản lý danh mục tài sản khác               │
     │                  ├─────►│  ├── UC10: Quản lý khoản vay & nghĩa vụ nợ             │
     │                  │      │  └── UC11: Ghi nhận thanh toán trả nợ                  │
     │  A01: Người dùng │      │                                                        │
     │                  │      │  [Ngân sách, Định kỳ & Mục tiêu tích lũy]              │
     │                  ├─────►│  ├── UC12: Thiết lập ngân sách chi tiêu                │
     │                  │      │  ├── UC13: Xem và quản lý cảnh báo ngân sách           │
     │                  │      │  ├── UC14: Quản lý khoản giao dịch định kỳ             │
     │                  │      │  ├── UC15: Lập và theo dõi mục tiêu tài chính          │
     │                  │      │  └── UC21: Quản lý danh mục thu/chi                    │
     │                  │      │                                                        │
     │                  │      │  [Phân tích, Dashboard & Báo cáo]                      │
     │                  ├─────►│  ├── UC16: Xem Dashboard tổng quan tài sản ròng        │
     │                  │      │  ├── UC17: Phân tích dòng tiền & Tỷ lệ tiết kiệm       │
     │                  │      │  ├── UC18: Tra cứu lịch sử & Đối soát sổ cái           │
     │                  │      │  └── UC19: Xuất báo cáo tài chính (CSV)                │
     └──────────────────┘      └────────────────────────────────────────────────────────┘
                               ┌────────────────────────────────────────────────────────┐
     ┌──────────────────┐      │  [Quản trị & Vận hành hệ thống]                        │
     │                  │      │  ├── UC22: Quản trị tài khoản người dùng               │
     │ A02: Quản trị    ├─────►│  ├── UC23: Cấu hình tham số vận hành                   │
     │      viên (Admin)│      │  ├── UC24: Tạo và kiểm tra bản sao lưu dữ liệu         │
     │                  │      │  └── UC25: Khôi phục dữ liệu từ bản sao lưu            │
     └──────────────────┘      └────────────────────────────────────────────────────────┘
```

#### Bảng tổng hợp ánh xạ Use Case

| Mã UC | Tên Use Case | Tác nhân chính | Ánh xạ FR |
| :---: | :--- | :---: | :--- |
| **UC01** | Đăng ký tài khoản mới | A01: Khách | FR01.01 |
| **UC02** | Đăng nhập, đăng xuất và quản lý phiên | A01: Người dùng | FR01.02, FR01.03, FR01.04, FR15 |
| **UC03** | Cập nhật hồ sơ cá nhân và tiền cơ sở | A01: Người dùng | FR01.04, FR14.02 |
| **UC04** | Quản lý tài khoản thanh toán và ví tiền | A01: Người dùng | FR02.01, FR02.07, FR03, FR04 |
| **UC05** | Ghi nhận giao dịch thu nhập | A01: Người dùng | FR02.02, FR02.03, FR03, FR04, FR15 |
| **UC06** | Ghi nhận giao dịch chi tiêu | A01: Người dùng | FR02.02, FR02.03, FR03, FR04, FR05, FR15 |
| **UC07** | Chuyển tiền giữa các ví nội bộ | A01: Người dùng | FR02.04, FR03, FR04, FR15 |
| **UC08** | Đảo và điều chỉnh thay thế giao dịch | A01: Người dùng | FR02.05, FR03.04, FR04, FR15 |
| **UC09** | Quản lý danh mục tài sản vật chất khác | A01: Người dùng | FR08.01, FR08.02, FR08.04 |
| **UC10** | Quản lý khoản vay và nghĩa vụ nợ vi mô | A01: Người dùng | FR09.01, FR09.02, FR09.04, FR08.03 |
| **UC11** | Ghi nhận thanh toán trả nợ vay | A01: Người dùng | FR09.03, FR09.04, FR03, FR08.03 |
| **UC12** | Thiết lập và quản lý ngân sách chi tiêu | A01: Người dùng | FR05.01, FR05.02, FR15 |
| **UC13** | Xem và quản lý cảnh báo ngân sách | A01: Người dùng | FR05.03, FR05.04 |
| **UC14** | Thiết lập và theo dõi khoản định kỳ | A01: Người dùng | FR06.01, FR06.04, SYS02 |
| **UC15** | Lập và theo dõi mục tiêu tích lũy tài chính | A01: Người dùng | FR07.01, FR07.02, FR07.03, FR07.04 |
| **UC16** | Xem Dashboard tổng quan tài sản ròng | A01: Người dùng | FR10.01, FR10.02, FR10.03, FR08.04 |
| **UC17** | Phân tích dòng tiền và tỷ lệ tiết kiệm | A01: Người dùng | FR11.01, FR11.02, FR11.03, FR11.04 |
| **UC18** | Tra cứu lịch sử giao dịch và đối soát sổ | A01: Người dùng | FR12.01, FR12.02, FR03.03 |
| **UC19** | Xuất báo cáo tài chính sang tệp CSV | A01: Người dùng | FR12.03, FR12.04, FR15 |
| **UC20** | Nhận dạng và tạo giao dịch từ ảnh hóa đơn | A01: Người dùng | FR16.01, FR16.02, FR16.03, FR16.04 |
| **UC21** | Quản lý danh mục thu nhập và chi tiêu | A01: Người dùng | FR14.01, FR15 |
| **UC22** | Quản trị trạng thái tài khoản người dùng | A02: Admin | FR14.04, FR14.05, FR15 |
| **UC23** | Cấu hình tham số vận hành hệ thống | A02: Admin | FR14.03, FR15 |
| **UC24** | Kích hoạt và kiểm tra bản sao lưu | A02: Admin | FR13.01, FR13.02, SYS04 |
| **UC25** | Khôi phục hệ thống từ bản sao lưu | A02: Admin | FR13.03, FR13.04 |

---

### 4.2 Đặc tả chi tiết các Use Case hệ thống

---

#### UC01 - Đăng ký tài khoản mới
- **Tác nhân:** A01 (Khách vãng lai).
- **Tiền điều kiện:** Chưa đăng nhập; địa chỉ email chưa từng đăng ký trong hệ thống.
- **Dữ liệu đầu vào:** Họ và tên, Email, Mật khẩu, Xác nhận mật khẩu, Tiền tệ cơ sở (`baseCurrency`).
- **Dữ liệu đầu ra:** Thông báo đăng ký thành công, bản ghi tài khoản người dùng mới ở trạng thái `Active`.

**Luồng sự kiện chính (Main Flow):**
1. Khách truy cập trang đăng ký của WealthFlow.
2. Hệ thống hiển thị biểu mẫu đăng ký với danh sách tiền tệ cơ sở hỗ trợ (mặc định VND).
3. Khách nhập đầy đủ thông tin cá nhân, chọn tiền tệ cơ sở và nhấn "Đăng ký tài khoản".
4. Hệ thống chuẩn hóa email (trim khoảng trắng, chuyển chữ thường) và kiểm tra định dạng dữ liệu đầu vào.
5. Hệ thống kiểm tra tính duy nhất của email trong cơ sở dữ liệu.
6. Hệ thống băm mật khẩu bằng thuật toán an toàn (Argon2id/bcrypt) kèm Salt ngẫu nhiên.
7. Hệ thống tạo bản ghi người dùng mới với vai trò `User`, thiết lập danh mục thu/chi mặc định ban đầu và commit giao dịch.
8. Hệ thống hiển thị thông báo thành công và chuyển hướng người dùng sang màn hình đăng nhập.

**Luồng phụ (Alternative Flows):**
* *3a. Khách chọn đồng tiền cơ sở là ngoại tệ (USD hoặc EUR):* Hệ thống thiết lập `baseCurrency` theo lựa chọn và khóa ghi nhận đơn vị tiền tệ báo cáo chuẩn.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Dữ liệu không hợp lệ:* Email sai định dạng, mật khẩu không đạt chính sách bảo mật NFR02 hoặc mật khẩu xác nhận không khớp. Hệ thống giữ lại các trường hợp lệ, hiển thị thông báo lỗi cụ thể tại từng trường bị sai.
* *5e1. Email đã tồn tại:* Hệ thống thông báo email đã được đăng ký và gợi ý chuyển sang đăng nhập hoặc khôi phục quyền truy cập, không tạo bản ghi mới.
* *7e1. Sự cố cơ sở dữ liệu:* Hệ thống Rollback toàn bộ dữ liệu, trả thông báo lỗi hệ thống và ghi nhận `requestId` vào nhật ký kiểm toán.

- **Hậu điều kiện:** Tài khoản mới được tạo thành công; chưa phát sinh phiên đăng nhập tự động; tiền cơ sở được gán cố định ban đầu.

---

#### UC02 - Đăng nhập, đăng xuất và quản lý phiên
- **Tác nhân:** A01 (Người dùng / Quản trị viên).
- **Tiền điều kiện:** Tài khoản đã tồn tại trong hệ thống.
- **Dữ liệu đầu vào:** Email, Mật khẩu; hoặc Mật khẩu hiện tại, Mật khẩu mới (khi đổi mật khẩu).
- **Dữ liệu đầu ra:** Mã Token JWT phiên làm việc hợp lệ; hoặc trạng thái phiên bị hủy.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập trang đăng nhập.
2. Hệ thống hiển thị biểu mẫu yêu cầu Email và Mật khẩu.
3. Người dùng nhập thông tin xác thực và nhấn "Đăng nhập".
4. Hệ thống kiểm tra số lần đăng nhập thất bại liên tiếp (không vượt quá 5 lần trong 15 phút).
5. Hệ thống xác thực mật khẩu băm và kiểm tra trạng thái tài khoản là `Active`.
6. Hệ thống cấp phát Token JWT có chữ ký số an toàn và ghi nhận thời điểm đăng nhập thành công.
7. Hệ thống chuyển hướng người dùng vào Dashboard tổng quan tài chính.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng chọn "Đăng xuất":* Người dùng nhấn Đăng xuất trên thanh điều hướng; hệ thống thu hồi Token hiện tại, xóa phiên phía máy khách và chuyển về trang đăng nhập.
* *1b. Người dùng chọn "Đổi mật khẩu":* Người dùng nhập mật khẩu cũ và mật khẩu mới trong mục Cài đặt; hệ thống kiểm tra mật khẩu cũ chính xác, cập nhật mật khẩu mới và tự động hủy bỏ mọi phiên đăng nhập cũ trên các thiết bị khác.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Tài khoản bị tạm khóa do nhập sai quá số lần quy định:* Hệ thống từ chối xác thực và thông báo người dùng thử lại sau 15 phút.
* *5e1. Thông tin xác thực không chính xác:* Hệ thống trả thông báo lỗi chung "Email hoặc mật khẩu không chính xác", không tiết lộ trường nào bị sai.
* *5e2. Tài khoản ở trạng thái Khóa (Locked):* Hệ thống thông báo tài khoản đã bị vô hiệu hóa bởi Quản trị viên và từ chối truy cập.

- **Hậu điều kiện:** Phiên làm việc hợp lệ được thiết lập khi đăng nhập; toàn bộ quyền truy cập bị chấm dứt khi đăng xuất hoặc đổi mật khẩu.

---

#### UC03 - Cập nhật hồ sơ cá nhân và tiền cơ sở
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Họ và tên, Múi giờ (`timeZone`), Tiền cơ sở (`baseCurrency` - chỉ khi chưa có giao dịch).
- **Dữ liệu đầu ra:** Thông tin hồ sơ được cập nhật thành công.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập trang "Cài đặt hồ sơ cá nhân".
2. Hệ thống tải và hiển thị thông tin hiện tại: Họ tên, Email, Múi giờ, Tiền cơ sở và trạng thái khóa của tiền cơ sở.
3. Người dùng chỉnh sửa họ tên hoặc chọn lại múi giờ sinh hoạt và nhấn "Lưu thay đổi".
4. Hệ thống kiểm tra tính hợp lệ của dữ liệu đầu vào.
5. Hệ thống cập nhật bản ghi hồ sơ và lưu trữ vào cơ sở dữ liệu.
6. Hệ thống hiển thị thông báo cập nhật hồ sơ thành công.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng thay đổi tiền cơ sở khi chưa có giao dịch nào:* Hệ thống kiểm tra tài khoản chưa từng phát sinh bút toán sổ cái nào; hệ thống cho phép cập nhật `baseCurrency` mới và lưu trữ.

**Luồng ngoại lệ (Exception Flows):**
* *3e1. Người dùng cố gắng thay đổi tiền cơ sở sau khi đã phát sinh giao dịch:* Hệ thống khóa trường `baseCurrency`, từ chối yêu cầu và thông báo tiền cơ sở đã bị khóa vĩnh viễn sau giao dịch đầu tiên theo quy tắc nghiệp vụ.
* *4e1. Múi giờ không hợp lệ:* Hệ thống báo lỗi trường và giữ nguyên múi giờ hiện tại.

- **Hậu điều kiện:** Hồ sơ cá nhân được cập nhật; các kỳ ngân sách và lịch định kỳ tương lai áp dụng múi giờ mới.

---

#### UC04 - Quản lý tài khoản thanh toán và ví tiền
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; tiền cơ sở đã được xác lập.
- **Dữ liệu đầu vào:** Tên ví, loại ví (Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm, Nợ tín dụng), đơn vị tiền tệ, số dư đầu kỳ (nếu có).
- **Dữ liệu đầu ra:** Bản ghi ví mới được tạo kèm tài khoản sổ cái liên kết; hoặc trạng thái ví được cập nhật/lưu trữ.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Quản lý ví & Tài khoản" và nhấn "Thêm ví mới".
2. Hệ thống hiển thị biểu mẫu nhập thông tin ví.
3. Người dùng nhập tên ví, chọn loại ví, chọn loại tiền tệ và nhập số dư đầu kỳ (mặc định 0).
4. Người dùng nhấn "Tạo ví".
5. Hệ thống kiểm tra tên ví không trùng lặp trong danh mục ví của người dùng.
6. Hệ thống khởi tạo Database Transaction nguyên tử:
   - Tạo bản ghi ví mới kèm tài khoản sổ cái (`LedgerAccount`) tương ứng.
   - Nếu số dư đầu kỳ $> 0$, tự động sinh bút toán mở sổ: Nợ Ví tài sản / Có Vốn chủ sở hữu đầu kỳ (Equity) với giá trị quy đổi tiền cơ sở theo BR01, BR02.
   - Commit giao dịch.
7. Hệ thống hiển thị thông báo tạo ví thành công kèm số dư cập nhật.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng đổi tên ví:* Người dùng chọn chỉnh sửa tên ví đang hoạt động; hệ thống kiểm tra và lưu tên mới mà không làm thay đổi các bút toán lịch sử.
* *1b. Người dùng lưu trữ (Archive) ví:* Người dùng chọn lưu trữ ví; hệ thống kiểm tra số dư ví bằng 0 và không còn khoản định kỳ liên kết; hệ thống chuyển trạng thái ví sang `isArchived = true`.

**Luồng ngoại lệ (Exception Flows):**
* *5e1. Tên ví bị trùng lặp:* Hệ thống báo lỗi trùng tên ví và yêu cầu đổi tên khác.
* *6e1. Thiếu tỷ giá quy đổi cho số dư đầu kỳ ngoại tệ:* Hệ thống từ chối tạo ví có số dư đầu kỳ ngoại tệ khi chưa có snapshot tỷ giá hợp lệ trong 24 giờ.
* *1be1. Ví còn số dư hoặc còn lịch định kỳ khi yêu cầu lưu trữ:* Hệ thống từ chối lưu trữ và nêu rõ lý do (ví vẫn còn tiền hoặc còn lịch định kỳ đang trích tiền từ ví này).

- **Hậu điều kiện:** Ví và tài khoản sổ cái được khởi tạo cân bằng; số dư ví sẵn sàng phục vụ các giao dịch tiếp theo.

---

#### UC05 - Ghi nhận giao dịch thu nhập
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Người dùng có ít nhất một ví tài sản và danh mục thu nhập đang hoạt động.
- **Dữ liệu đầu vào:** Mã ví nhận, mã danh mục thu, số tiền, ngày thu, mô tả, `Idempotency-Key`.
- **Dữ liệu đầu ra:** Bản ghi giao dịch thu nhập ở trạng thái `Posted`, số dư ví nhận tăng tương ứng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn "Thêm khoản thu" trên giao diện.
2. Hệ thống hiển thị biểu mẫu thu nhập với ngày mặc định là ngày hiện tại.
3. Người dùng nhập số tiền, chọn ví nhận, chọn danh mục thu nhập, nhập mô tả và nhấn "Ghi sổ".
4. Hệ thống kiểm tra tính hợp lệ của dữ liệu (số tiền $> 0$, ngày $\le$ hiện tại) và kiểm tra khóa Idempotency.
5. Hệ thống thực thi giao dịch nguyên tử:
   - Tạo bản ghi giao dịch thu nhập trạng thái `Posted`.
   - Sinh dòng Nợ Ví tài sản và dòng Có Tài khoản Thu nhập (Income).
   - Kiểm tra cân bằng $\sum \text{DebitBase} = \sum \text{CreditBase}$.
   - Cộng số dư ví nhận và commit giao dịch.
6. Hệ thống kích hoạt sự kiện cập nhật Dashboard và hiển thị thông báo thành công kèm số dư ví mới.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng chọn "Lưu nháp":* Người dùng nhấn Lưu nháp; hệ thống lưu giao dịch ở trạng thái `Draft`, không tăng số dư ví và không sinh bút toán sổ cái.
* *3b. Thu nhập bằng ngoại tệ khác tiền cơ sở:* Hệ thống tự động áp dụng snapshot tỷ giá hợp lệ theo BR04 để quy đổi ra `amountBase` trước khi commit.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số tiền không hợp lệ:* Số tiền $\le 0$ hoặc để trống. Hệ thống hiển thị thông báo lỗi tại trường số tiền.
* *4e2. Trùng lặp khóa Idempotency với nội dung khác:* Hệ thống trả về lỗi 409 Conflict và giữ nguyên dữ liệu.
* *5e1. Lỗi cân bằng sổ cái:* Hệ thống Rollback toàn bộ, trả về mã lỗi 500 và ghi log chi tiết `requestId`.

- **Hậu điều kiện:** Thu nhập được ghi nhận vĩnh viễn; số dư ví tài sản tăng; tổng doanh số thu nhập trong kỳ tăng.

---

#### UC06 - Ghi nhận giao dịch chi tiêu
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Người dùng có ít nhất một ví tài sản còn đủ số dư khả dụng (hoặc ví nợ thẻ tín dụng) và danh mục chi tiêu đang hoạt động.
- **Dữ liệu đầu vào:** Mã ví, mã danh mục chi, số tiền, ngày chi, mô tả, tệp ảnh hóa đơn tùy chọn, `Idempotency-Key`.
- **Dữ liệu đầu ra:** Giao dịch chi tiêu ở trạng thái `Posted`, số dư ví giảm, cảnh báo ngân sách được kích hoạt nếu chạm ngưỡng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn chức năng "Ghi chi tiêu" từ màn hình chính hoặc Dashboard.
2. Hệ thống hiển thị biểu mẫu nhập liệu với ngày mặc định là ngày hiện tại và tiền tệ mặc định theo ví được chọn.
3. Người dùng nhập số tiền, chọn ví trích tiền, chọn danh mục chi tiêu, nhập mô tả và nhấn "Xác nhận ghi sổ".
4. Hệ thống kiểm tra tính hợp lệ của dữ liệu đầu vào, kiểm tra số dư khả dụng của ví tài sản và kiểm tra tính bất biến của khóa Idempotency.
5. Hệ thống khởi tạo Database Transaction nguyên tử:
   - Ghi bản ghi giao dịch mới ở trạng thái `Posted`.
   - Sinh dòng Nợ cho Tài khoản Chi phí (Expense) và dòng Có cho Ví thanh toán (Asset/Liability).
   - Kiểm tra cân bằng $\sum \text{DebitBase} = \sum \text{CreditBase}$.
   - Trừ số dư ví thanh toán và commit giao dịch.
6. Hệ thống kích hoạt sự kiện nền `TransactionPostedEvent` để cập nhật số liệu ngân sách và dashboard.
7. Hệ thống hiển thị thông báo thành công kèm số dư ví mới cập nhật.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng chọn "Lưu nháp":* Hệ thống lưu giao dịch ở trạng thái `Draft`, không trừ số dư ví và không sinh bút toán sổ cái.
* *3b. Người dùng đính kèm tệp ảnh hóa đơn:* Hệ thống tải tệp ảnh lên kho lưu trữ an toàn IF03, tính toán checksum và gắn liên kết ảnh với giao dịch chi tiêu.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số dư ví tài sản không đủ:* Số dư khả dụng của ví tài sản nhỏ hơn số tiền chi tiêu. Hệ thống dừng giao dịch, hiển thị thông báo số dư hiện tại không đủ và yêu cầu chọn ví khác hoặc điều chỉnh số tiền.
* *4e2. Trùng khóa Idempotency:* Yêu cầu gửi lại cùng khóa Idempotency và cùng nội dung. Hệ thống trả lại kết quả của giao dịch đã thực hiện trước đó mà không sinh thêm bút toán.
* *4e3. Xung đột khóa Idempotency khác nội dung:* Gửi lại khóa Idempotency đã dùng nhưng sửa đổi số tiền hoặc ví. Hệ thống từ chối với mã lỗi 409 Conflict.
* *5e1. Lỗi hạch toán sổ cái:* Lỗi kết nối DB hoặc vi phạm ràng buộc dữ liệu. Hệ thống Rollback toàn bộ dữ liệu, trả thông báo lỗi hệ thống và không thay đổi số dư ví.

- **Hậu điều kiện:** Khoản chi được ghi nhận vĩnh viễn trong sổ cái; số dư ví cập nhật chính xác; sự kiện ngân sách được kích hoạt.

---

#### UC07 - Chuyển tiền giữa các ví nội bộ
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Người dùng có ít nhất 2 ví đang hoạt động; ví nguồn có đủ số dư khả dụng.
- **Dữ liệu đầu vào:** Mã ví nguồn, mã ví đích, số tiền chuyển, số tiền nhận (nếu khác tiền tệ), ngày chuyển, mô tả.
- **Dữ liệu đầu ra:** Bút toán chuyển tiền nội bộ được ghi sổ, số dư ví nguồn giảm và số dư ví đích tăng đồng thời.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn chức năng "Chuyển tiền nội bộ".
2. Hệ thống hiển thị biểu mẫu chuyển tiền với danh sách các ví của người dùng.
3. Người dùng chọn ví nguồn, ví đích (khác ví nguồn), nhập số tiền chuyển và mô tả.
4. Hệ thống kiểm tra số dư ví nguồn $\ge$ số tiền chuyển và kiểm tra hai ví thuộc cùng chủ sở hữu.
5. Hệ thống thực thi giao dịch hạch toán nguyên tử:
   - Ghi dòng Có Ví nguồn (giảm tài sản nguồn) và dòng Nợ Ví đích (tăng tài sản đích).
   - Kiểm tra cân bằng $\sum \text{DebitBase} = \sum \text{CreditBase}$.
   - Trừ số dư ví nguồn và cộng số dư ví đích.
   - Commit giao dịch (theo BR05: không tính vào Thu nhập hay Chi phí).
6. Hệ thống hiển thị thông báo chuyển tiền thành công và cập nhật số dư cả 2 ví.

**Luồng phụ (Alternative Flows):**
* *3a. Chuyển tiền giữa hai ví khác loại tiền tệ (ví dụ VND sang USD):* Người dùng nhập số tiền VND trích đi và số tiền USD nhận được tại ví đích; hệ thống ghi nhận cả 2 vế, áp dụng tỷ lệ ghi sổ và hạch toán chênh lệch tỷ giá phát sinh (nếu có) vào tài khoản Lãi/Lỗ tỷ giá theo BR15.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Ví đích trùng ví nguồn:* Người dùng chọn ví chuyển đến trùng với ví trích tiền. Hệ thống báo lỗi và yêu cầu chọn ví đích khác.
* *4e2. Ví nguồn không đủ số dư:* Hệ thống từ chối thực hiện và thông báo số dư ví nguồn không đủ.
* *5e1. Lỗi hệ thống khi commit:* Hệ thống Rollback toàn bộ, cả hai ví giữ nguyên số dư ban đầu.

- **Hậu điều kiện:** Số tiền được luân chuyển an toàn giữa 2 ví; tổng tài sản và Net Worth của người dùng giữ nguyên không đổi.

---

#### UC08 - Đảo và điều chỉnh thay thế giao dịch
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Giao dịch cần đảo đang ở trạng thái `Posted`, chưa từng bị đảo trước đó và không phải là một bút toán đảo.
- **Dữ liệu đầu vào:** Mã giao dịch gốc, lý do đảo; thông tin giao dịch mới thay thế (nếu là nghiệp vụ Sửa sai).
- **Dữ liệu đầu ra:** Bút toán đảo đối ứng được ghi nhận, trạng thái giao dịch gốc chuyển sang `Reversed`; giao dịch thay thế mới được tạo (nếu có).

**Luồng sự kiện chính (Main Flow):**
1. Người dùng mở màn hình chi tiết của một giao dịch đã ghi sổ và chọn "Đảo giao dịch" (hoặc "Sửa giao dịch").
2. Hệ thống hiển thị cảnh báo về tác động tài chính của việc đảo giao dịch và yêu cầu xác nhận.
3. Người dùng nhập lý do đảo và nhấn "Xác nhận đảo".
4. Hệ thống kiểm tra trạng thái giao dịch gốc hợp lệ và kiểm tra việc đảo giao dịch không làm âm số dư ví tài sản.
5. Hệ thống khởi tạo Database Transaction nguyên tử:
   - Cập nhật trạng thái giao dịch gốc thành `Reversed`.
   - Sinh giao dịch đảo (`Reversal Transaction`) với các dòng Nợ/Có đảo ngược dấu hoàn toàn so với bản gốc và giữ nguyên snapshot tỷ giá cũ.
   - Hoàn trả số dư ví tương ứng.
   - Commit giao dịch.
6. Hệ thống kích hoạt sự kiện tính toán lại ngân sách chi tiêu và dashboard.
7. Hệ thống hiển thị thông báo đảo giao dịch thành công.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng chọn "Sửa giao dịch":* Người dùng điều chỉnh lại thông tin số tiền/danh mục đúng; hệ thống thực thi chuỗi nguyên tử: tạo bút toán Đảo bản gốc + tạo bản ghi Giao dịch mới thay thế có gắn `originalId` trỏ về bản gốc.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Giao dịch gốc đã bị đảo trước đó:* Hệ thống từ chối thao tác và thông báo giao dịch này đã được hoàn tác.
* *4e2. Việc đảo giao dịch làm âm số dư ví tài sản:* (Ví dụ đảo một khoản thu nhập mà số tiền đó đã bị chi tiêu hết). Hệ thống từ chối đảo và thông báo số dư ví không đủ để thu hồi khoản thu này.
* *5e1. Lỗi hệ thống khi commit:* Rollback toàn bộ, trạng thái giao dịch gốc giữ nguyên `Posted`.

- **Hậu điều kiện:** Dấu vết kiểm toán được bảo toàn trọn vẹn theo BR06; số dư ví và báo cáo tài chính phản ánh số liệu đã điều chỉnh.

---

#### UC09 - Quản lý danh mục tài sản vật chất khác (Personal Wealth)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tên tài sản, phân loại (Phương tiện, Thiết bị, Bất động sản, Vàng/Đầu tư, Khác), giá trị định giá, ngày định giá, mô tả.
- **Dữ liệu đầu ra:** Bản ghi tài sản được lưu trữ/cập nhật, Tổng tài sản và Net Worth được tính toán lại.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Tài sản cá nhân" (Personal Wealth) và nhấn "Thêm tài sản".
2. Hệ thống hiển thị biểu mẫu nhập thông tin tài sản vật chất ngoài ví.
3. Người dùng nhập tên tài sản (ví dụ: "Xe máy Honda SH"), chọn danh mục, nhập giá trị định giá ("60.000.000 VND"), ngày định giá và nhấn "Lưu".
4. Hệ thống kiểm tra giá trị định giá $\ge 0$ và ngày định giá không ở tương lai.
5. Hệ thống lưu bản ghi tài sản mới vào cơ sở dữ liệu.
6. Hệ thống cập nhật Tổng tài sản (Total Assets) và tính lại Net Worth theo BR16.
7. Hệ thống hiển thị thông báo thành công và danh mục tài sản cập nhật.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng cập nhật định giá lại tài sản:* Người dùng chọn tài sản hiện có và nhập giá trị định giá mới (ví dụ hao mòn xe máy hoặc vàng tăng giá); hệ thống ghi nhận lịch sử định giá mới và cập nhật Net Worth.
* *1b. Người dùng xóa tài sản đã thanh lý:* Người dùng chọn xóa tài sản; hệ thống xác nhận và xóa tài sản khỏi danh mục theo dõi, trừ giá trị khỏi Total Assets.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Giá trị định giá không hợp lệ:* Giá trị $< 0$ hoặc để trống. Hệ thống báo lỗi trường.
* *5e1. Lỗi lưu trữ cơ sở dữ liệu:* Hệ thống Rollback và giữ nguyên trạng thái danh mục tài sản.

- **Hậu điều kiện:** Tài sản vật chất được quản lý độc lập với ví tiền; giá trị định giá đóng góp trực tiếp vào Total Assets và Net Worth.

---

#### UC10 - Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tiêu đề khoản nợ, loại (Đi vay / Cho vay), tên người/tổ chức liên quan, số tiền gốc ban đầu, ngày vay, ngày đến hạn, ghi chú; tùy chọn ví nhận tiền nếu giải ngân mới.
- **Dữ liệu đầu ra:** Bản ghi khoản vay ở trạng thái `Active`, dư nợ gốc được theo dõi, Total Liabilities và Net Worth cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Quản lý nợ & Khoản vay" (Microfinance).
2. Hệ thống hiển thị danh sách các khoản vay hiện có: tổng dư nợ còn lại, danh sách khoản nợ sắp đến hạn và nút "Thêm khoản vay mới".
3. Người dùng chọn thêm khoản vay, nhập các thông tin: Tiêu đề ("Vay mua laptop"), Bên cho vay ("HD Saison"), Số tiền ("15.000.000 VND"), Ngày vay, Hạn trả và chọn ví nhận tiền thực tế ("Ví Ngân hàng").
4. Người dùng nhấn "Lưu khoản vay".
5. Hệ thống kiểm tra dữ liệu đầu vào (số tiền $> 0$, ngày đến hạn $\ge$ ngày bắt đầu).
6. Hệ thống khởi tạo Database Transaction nguyên tử:
   - Tạo bản ghi khoản vay mới với dư nợ gốc = 15.000.000 VND, trạng thái `Active`.
   - Tạo giao dịch giải ngân: Tăng số dư Ví Ngân hàng 15.000.000 VND và Tăng Tài khoản Nợ phải trả (Liability) 15.000.000 VND (tuyệt đối không ghi nhận vào Thu nhập theo BR17).
   - Commit giao dịch.
7. Hệ thống cập nhật Tổng nợ (Total Liabilities), Tổng tài sản và tính toán lại Net Worth.
8. Hệ thống hiển thị thông báo thành công và chuyển về màn hình chi tiết khoản vay.

**Luồng phụ (Alternative Flows):**
* *3a. Khoản vay cũ đã có từ trước (không giải ngân mới vào ví):* Người dùng bỏ chọn ví nhận tiền; hệ thống chỉ ghi nhận theo dõi dư nợ ban đầu trong danh mục nợ mà không sinh bút toán tăng tiền trong ví.
* *3b. Người dùng tạo khoản "Cho vay" (Lend):* Người dùng chọn loại Cho vay người khác; hệ thống trích tiền từ ví của người dùng và ghi nhận một khoản Phải thu (Tài sản) thay vì Nợ phải trả.

**Luồng ngoại lệ (Exception Flows):**
* *5e1. Ngày đến hạn không hợp lệ:* Ngày đến hạn trước ngày bắt đầu vay hoặc số tiền $\le 0$. Hệ thống báo lỗi trường và yêu cầu điều chỉnh.
* *6e1. Lỗi hệ thống khi commit:* Rollback toàn bộ; không tạo khoản vay và không thay đổi số dư ví.

- **Hậu điều kiện:** Khoản vay được theo dõi với lịch sử dư nợ chuẩn xác; Total Liabilities tăng tương ứng; Net Worth phản ánh đúng thực tế.

---

#### UC11 - Ghi nhận thanh toán trả nợ vay
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Khoản vay đang ở trạng thái `Active` và có dư nợ gốc $> 0$; ví thanh toán có đủ tiền.
- **Dữ liệu đầu vào:** Mã khoản vay, ngày trả nợ, số tiền trả nợ gốc, số tiền trả lãi/phí (nếu có), ví trích tiền, ghi chú.
- **Dữ liệu đầu ra:** Bản ghi `LoanRepayment`, dư nợ gốc giảm, chi phí tài chính ghi nhận, Total Liabilities cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng mở chi tiết khoản vay cần thanh toán và chọn "Trả nợ".
2. Hệ thống hiển thị dư nợ gốc hiện tại và biểu mẫu thanh toán.
3. Người dùng nhập: Số tiền trả gốc ("3.000.000 VND"), Tiền lãi ("200.000 VND"), chọn ví thanh toán ("Ví Vietcombank") và nhấn "Xác nhận thanh toán".
4. Hệ thống kiểm tra số dư ví Vietcombank $\ge 3.200.000$ VND và số tiền trả gốc $\le$ dư nợ còn lại của khoản vay.
5. Hệ thống thực thi giao dịch hạch toán nguyên tử:
   - Giảm số dư Ví Vietcombank: 3.200.000 VND.
   - Giảm dư nợ gốc của khoản vay: 3.000.000 VND.
   - Hạch toán dòng Có Ví Vietcombank: 3.200.000 VND.
   - Hạch toán dòng Nợ Tài khoản Nợ phải trả (giảm nợ): 3.000.000 VND.
   - Hạch toán dòng Nợ Tài khoản Chi phí tài chính (lãi vay): 200.000 VND.
   - Tạo bản ghi lịch sử trả nợ `LoanRepayment`.
   - Nếu dư nợ gốc còn lại $= 0$, cập nhật trạng thái khoản vay sang `PaidOff`.
   - Commit giao dịch.
6. Hệ thống hiển thị thông báo thanh toán thành công và cập nhật lại Tổng nợ trên Dashboard.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng tất toán toàn bộ khoản vay:* Người dùng nhấn "Tất toán toàn bộ"; hệ thống tự động điền số tiền trả gốc bằng đúng dư nợ gốc còn lại.
* *3b. Thanh toán khoản vay không phát sinh lãi/phí:* Người dùng để trống hoặc nhập 0 cho tiền lãi/phí; hệ thống chỉ hạch toán giảm nợ gốc và giảm ví thanh toán.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Ví thanh toán không đủ số dư:* Tổng số tiền thanh toán (Gốc + Lãi) vượt quá số dư khả dụng của ví. Hệ thống từ chối và báo rõ số tiền còn thiếu.
* *4e2. Tiền trả gốc vượt quá dư nợ còn lại:* Hệ thống thông báo số tiền trả gốc không được lớn hơn dư nợ gốc hiện tại của khoản vay.
* *5e1. Lỗi cơ sở dữ liệu khi hạch toán:* Rollback toàn bộ, dư nợ khoản vay và số dư ví giữ nguyên.

- **Hậu điều kiện:** Dư nợ gốc của khoản vay giảm chính xác; chi phí lãi vay được hạch toán vào kỳ báo cáo; Total Liabilities giảm.

---

#### UC12 - Thiết lập và quản lý ngân sách chi tiêu
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; danh mục chi tiêu đang hoạt động.
- **Dữ liệu đầu vào:** Danh mục chi tiêu, tháng áp dụng (YYYY-MM), hạn mức ngân sách tiền cơ sở.
- **Dữ liệu đầu ra:** Bản ghi ngân sách hoạt động, tỷ lệ sử dụng hiện tại và hạn mức được lưu trữ.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Ngân sách chi tiêu" và nhấn "Tạo ngân sách".
2. Hệ thống hiển thị biểu mẫu thiết lập ngân sách.
3. Người dùng chọn danh mục chi tiêu (ví dụ: Ăn uống), chọn tháng áp dụng và nhập hạn mức ("3.000.000 VND").
4. Người dùng nhấn "Lưu ngân sách".
5. Hệ thống kiểm tra hạn mức $> 0$ và kiểm tra danh mục chưa có ngân sách hoạt động trong tháng đó.
6. Hệ thống lưu bản ghi ngân sách mới và quét tổng chi tiêu ròng hiện có trong tháng của danh mục đó.
7. Hệ thống tính toán tỷ lệ sử dụng, hiển thị thanh tiến độ ngân sách và thông báo thành công.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng điều chỉnh hạn mức ngân sách hiện có:* Người dùng mở ngân sách đã tạo và sửa lại số tiền hạn mức; hệ thống tính lại tỷ lệ phần trăm sử dụng ngay lập tức.
* *1b. Người dùng lưu trữ ngân sách:* Người dùng chọn lưu trữ ngân sách tháng cũ; hệ thống ngừng quét cảnh báo cho ngân sách đó.

**Luồng ngoại lệ (Exception Flows):**
* *5e1. Trùng lặp ngân sách trong cùng một tháng:* Danh mục đã có ngân sách trong tháng được chọn. Hệ thống từ chối tạo mới và gợi ý chỉnh sửa ngân sách hiện có.
* *5e2. Hạn mức ngân sách không hợp lệ:* Hạn mức $\le 0$. Hệ thống báo lỗi trường.

- **Hậu điều kiện:** Ngân sách được kích hoạt; các giao dịch chi tiêu tiếp theo sẽ được kiểm tra với hạn mức này.

---

#### UC13 - Xem và quản lý cảnh báo ngân sách
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Bộ lọc thông báo (Tất cả / Chưa đọc), mã thông báo cần đánh dấu.
- **Dữ liệu đầu ra:** Danh sách thông báo cảnh báo chi tiêu; trạng thái thông báo được cập nhật `readAt`.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng nhấn vào biểu tượng Chuông thông báo trên thanh điều hướng.
2. Hệ thống truy vấn và hiển thị danh sách các thông báo cảnh báo thuộc quyền sở hữu của người dùng (phân loại mức Vàng $\ge 80\%$, Đỏ $\ge 100\%$).
3. Người dùng chọn xem chi tiết một thông báo cảnh báo.
4. Hệ thống hiển thị chi tiết: Tên danh mục, Hạn mức, Số tiền đã chi tiêu, Tỷ lệ sử dụng và ngày kích hoạt cảnh báo.
5. Người dùng nhấn "Đánh dấu đã đọc".
6. Hệ thống cập nhật trường `readAt` cho bản ghi thông báo và cập nhật số lượng thông báo chưa đọc.

**Luồng phụ (Alternative Flows):**
* *2a. Người dùng chọn "Đánh dấu tất cả đã đọc":* Hệ thống cập nhật hàng loạt trường `readAt` cho toàn bộ thông báo chưa đọc của người dùng.

**Luồng ngoại lệ (Exception Flows):**
* *3e1. Yêu cầu xem thông báo không thuộc quyền sở hữu:* Hệ thống trả về mã lỗi 404 Not Found theo quy tắc bảo mật BR11.

- **Hậu điều kiện:** Trạng thái đọc của thông báo được cập nhật; số liệu sổ cái và ngân sách không bị thay đổi.

---

#### UC14 - Quản lý khoản giao dịch định kỳ (Subscriptions / Recurring)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Ví thanh toán và danh mục liên quan đang hoạt động.
- **Dữ liệu đầu vào:** Tên khoản định kỳ, số tiền, loại giao dịch (Chi/Thu), chu kỳ (Hàng tháng / Hàng năm), ngày neo thanh toán, ví liên kết, danh mục, chế độ (Nháp hoặc Tự ghi sổ).
- **Dữ liệu đầu ra:** Lịch định kỳ hoạt động, ngày đến hạn tiếp theo (`nextDueDate`) được tính toán.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Khoản định kỳ" và nhấn "Tạo lịch định kỳ mới".
2. Hệ thống hiển thị biểu mẫu thiết lập lịch định kỳ.
3. Người dùng nhập tên ("Tiền mạng Internet"), số tiền ("300.000 VND"), chu kỳ ("Hàng tháng"), ngày thanh toán ("Ngày 15"), chọn ví thanh toán, danh mục và chọn chế độ ghi nhận.
4. Người dùng nhấn "Lưu lịch định kỳ".
5. Hệ thống kiểm tra số tiền $> 0$ và tính toán ngày đến hạn tiếp theo (`nextDueDate`) dựa trên ngày hiện tại và ngày neo.
6. Hệ thống lưu bản ghi lịch định kỳ ở trạng thái `Active`.
7. Hệ thống hiển thị thông báo thành công kèm thông tin kỳ thanh toán tiếp theo.

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng bật chế độ "Tự động ghi sổ":* Người dùng bật tùy chọn tự động ghi sổ; hệ thống hiển thị hộp thoại xác nhận đồng ý tự động trừ tiền ví khi đến hạn; người dùng xác nhận và hệ thống lưu trạng thái `autoPost = true`.
* *1a. Người dùng tạm dừng (Pause) hoặc hủy (Cancel) lịch định kỳ:* Người dùng chọn tạm dừng lịch; hệ thống đổi trạng thái sang `Paused`, tác vụ nền sẽ bỏ qua lịch này cho đến khi được kích hoạt lại.

**Luồng ngoại lệ (Exception Flows):**
* *5e1. Dữ liệu nhập không hợp lệ:* Số tiền $\le 0$ hoặc ngày neo không nằm trong khoảng từ 1 đến 31. Hệ thống báo lỗi trường.
* *6e1. Lỗi hệ thống khi lưu trữ:* Hệ thống Rollback và giữ nguyên danh sách lịch định kỳ.

- **Hậu điều kiện:** Lịch định kỳ sẵn sàng để tác vụ nền SYS02 quét và xử lý khi đến ngày đến hạn.

---

#### UC15 - Lập và theo dõi mục tiêu tích lũy tài chính
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tiêu đề mục tiêu, số tiền cần đạt (`targetAmount`), ngày đến hạn mong muốn; số tiền phân bổ tích lũy.
- **Dữ liệu đầu ra:** Bản ghi mục tiêu, thanh tiến độ (%), số tiền cần tiết kiệm trung bình mỗi tháng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Mục tiêu tài chính" (Financial Goals) và nhấn "Tạo mục tiêu mới".
2. Hệ thống hiển thị biểu mẫu thiết lập mục tiêu.
3. Người dùng nhập thông tin: Tiêu đề ("Quỹ khẩn cấp"), Số tiền đích ("30.000.000 VND"), Thời hạn ("10 tháng tới") và nhấn "Lưu".
4. Hệ thống kiểm tra số tiền đích $> 0$ và ngày đến hạn ở tương lai.
5. Hệ thống tạo mục tiêu mới với tiến độ ban đầu 0%, tự động tính toán kế hoạch gợi ý: cần tích lũy 3.000.000 VND/tháng theo BR10.
6. Khi có tiền dành riêng, người dùng chọn "Phân bổ tiền tích lũy", nhập số tiền ("6.000.000 VND") và lưu.
7. Hệ thống cộng dồn vào `currentAmount` của mục tiêu, tính lại tiến độ: $20\%$ và cập nhật mức tích lũy cần thiết cho các tháng còn lại: $\frac{24.000.000}{9} \approx 2.666.667$ VND/tháng.
8. Hệ thống hiển thị thông báo phân bổ thành công.

**Luồng phụ (Alternative Flows):**
* *6a. Số tiền tích lũy đạt hoặc vượt đích:* Người dùng phân bổ đủ số tiền đích; hệ thống cập nhật tiến độ $100\%$ và tự động chuyển trạng thái mục tiêu sang `Achieved` kèm lời chúc mừng.
* *6b. Người dùng hủy khoản phân bổ nhập sai:* Người dùng chọn hủy một lần phân bổ trước đó; hệ thống trừ lại số tiền và tính toán lại tiến độ chuẩn xác.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số tiền đích hoặc thời hạn không hợp lệ:* Số tiền đích $\le 0$ hoặc thời hạn ở quá khứ. Hệ thống báo lỗi trường.
* *6e2. Hủy một khoản phân bổ đã bị hủy:* Hệ thống từ chối thao tác và thông báo khoản phân bổ không tồn tại.

- **Hậu điều kiện:** Tiến độ mục tiêu và gợi ý tiết kiệm được cập nhật trực quan mà không làm sai lệch số dư thực tế trong ví tiền (theo BR10).

---

#### UC16 - Xem Dashboard tổng quan tài sản ròng (Personal Wealth Dashboard)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Khoảng thời gian khảo sát (mặc định tháng hiện tại), tùy chọn bộ lọc ví.
- **Dữ liệu đầu ra:** Màn hình Dashboard trực quan với các chỉ số Net Worth, Cash Flow, Saving Rate, biểu đồ cơ cấu và xu hướng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng truy cập trang chủ hệ thống (Dashboard).
2. Hệ thống tải dữ liệu tổng hợp từ cùng một mốc snapshot nhất quán:
   - Tính Tổng tài sản = $\sum \text{Ví tài sản} + \sum \text{Tài sản khác}$.
   - Tính Tổng nợ = $\sum \text{Ví nợ} + \sum \text{Dư nợ khoản vay vi mô}$.
   - Tính Tài sản ròng $\text{Net Worth} = \text{Tổng tài sản} - \text{Tổng nợ}$.
   - Tính Thu nhập, Chi phí, Dòng tiền thuần ($CashFlow$) và Tỷ lệ tiết kiệm ($SavingRate$) trong tháng.
   - Tải danh sách ngân sách có nguy cơ vượt hạn mức (Vàng/Đỏ).
   - Tải tiến độ các mục tiêu tài chính và các khoản vay sắp đến hạn.
3. Hệ thống kết xuất giao diện trực quan với các thẻ chỉ số nổi bật, biểu đồ xu hướng Net Worth và cơ cấu chi tiêu.
4. Người dùng có thể thay đổi khoảng thời gian xem để so sánh với các tháng trước.

**Luồng phụ (Alternative Flows):**
* *2a. Người dùng chưa phát sinh giao dịch nào:* Hệ thống hiển thị các thẻ chỉ số bằng 0 kèm thông điệp hướng dẫn người dùng bắt đầu tạo ví và ghi nhận giao dịch đầu tiên.

**Luồng ngoại lệ (Exception Flows):**
* *2e1. Lỗi truy vấn dữ liệu tổng hợp:* Hệ thống hiển thị thông báo lỗi tạm thời và cung cấp nút "Tải lại trang", không hiển thị số liệu chắp vá giữa các mốc thời gian khác nhau.

- **Hậu điều kiện:** Người dùng nắm bắt toàn bộ bức tranh tài chính cá nhân trong một màn hình duy nhất mà không làm thay đổi dữ liệu sổ sách.

---

#### UC17 - Phân tích dòng tiền và tỷ lệ tiết kiệm
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Khoảng ngày tùy chọn (Tuần, Tháng, Quý, Năm), cấp độ nhóm thời gian, bộ lọc ví.
- **Dữ liệu đầu ra:** Bảng số liệu và biểu đồ phân tích Cash Flow, Saving Rate, cơ cấu thu chi so sánh giữa các kỳ.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Báo cáo & Phân tích dòng tiền".
2. Hệ thống hiển thị bộ lọc thời gian và mặc định chọn 6 tháng gần nhất.
3. Người dùng chọn khoảng thời gian và cấp độ nhóm (ví dụ: theo Tháng) và nhấn "Xem báo cáo".
4. Hệ thống kiểm tra khoảng ngày hợp lệ (ngày bắt đầu $\le$ ngày kết thúc).
5. Hệ thống tổng hợp dữ liệu từ các giao dịch đã ghi sổ:
   - Tính tổng Thu nhập thực tế và Chi phí thực tế từng kỳ (loại trừ chuyển ví nội bộ theo BR05).
   - Tính Dòng tiền thuần $NetCashFlow = TotalIncome - TotalExpense$.
   - Tính Tỷ lệ tiết kiệm $SavingRate = \frac{TotalIncome - TotalExpense}{TotalIncome} \times 100\%$ theo BR18.
6. Hệ thống hiển thị biểu đồ cột so sánh Thu/Chi/Dòng tiền và bảng số liệu chi tiết từng kỳ.

**Luồng phụ (Alternative Flows):**
* *5a. Kỳ khảo sát có thu nhập bằng 0:* Hệ thống hiển thị Saving Rate là "N/A" và hiển thị đầy đủ số tiền chi tiêu thực tế mà không thực hiện phép chia cho 0.
* *5b. Kỳ khảo sát có chi tiêu vượt thu nhập:* Hệ thống hiển thị Saving Rate mang giá trị âm màu đỏ cảnh báo thâm hụt dòng tiền.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Khoảng ngày không hợp lệ:* Ngày bắt đầu lớn hơn ngày kết thúc. Hệ thống hiển thị thông báo lỗi và yêu cầu chọn lại khoảng ngày.

- **Hậu điều kiện:** Dữ liệu phân tích là chỉ đọc, cung cấp góc nhìn sâu sắc về hiệu quả tích lũy tài chính của người dùng.

---

#### UC18 - Tra cứu lịch sử giao dịch và đối soát sổ cái
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Bộ lọc tìm kiếm (Khoảng ngày, ví, danh mục, loại thu/chi, từ khóa mô tả), tùy chọn kích hoạt đối soát sổ cái.
- **Dữ liệu đầu ra:** Danh sách giao dịch phân trang, chi tiết bút toán Nợ/Có, kết quả đối soát số dư ví.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Lịch sử giao dịch & Sổ cái".
2. Hệ thống hiển thị danh sách giao dịch gần nhất kèm thanh công cụ tìm kiếm và lọc nâng cao.
3. Người dùng nhập tiêu chí lọc (ví dụ: tìm giao dịch chi tiêu trong tháng 9 từ Ví Vietcombank) và nhấn "Tìm kiếm".
4. Hệ thống thực thi truy vấn phân trang trên tập dữ liệu thuộc quyền sở hữu của người dùng.
5. Hệ thống hiển thị danh sách kết quả; người dùng nhấn vào một giao dịch để xem chi tiết các dòng bút toán Nợ/Có và snapshot tỷ giá.
6. Người dùng nhấn nút "Đối soát số dư ví".
7. Hệ thống tự động tính toán tổng các dòng sổ cái từ đầu đến hiện tại và đối chiếu với số dư lưu đệm của ví; hiển thị thông báo "Số dư sổ cái hoàn toàn khớp đúng với số dư ví".

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng xóa bộ lọc:* Người dùng nhấn "Đặt lại bộ lọc"; hệ thống hiển thị lại toàn bộ lịch sử giao dịch mặc định.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Không tìm thấy giao dịch nào khớp bộ lọc:* Hệ thống hiển thị danh sách rỗng kèm thông báo không có kết quả phù hợp.
* *7e1. Phát hiện chênh lệch số dư đối soát:* (Do lỗi dữ liệu lưu đệm). Hệ thống hiển thị cảnh báo sai lệch số dư, liệt kê chi tiết mức chênh lệch và ghi nhận sự cố vào nhật ký vận hành để xử lý.

- **Hậu điều kiện:** Người dùng kiểm tra được tính minh bạch và toàn vẹn của toàn bộ dữ liệu kế toán cá nhân.

---

#### UC19 - Xuất báo cáo tài chính sang tệp CSV
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Bộ lọc phạm vi xuất dữ liệu (Khoảng ngày, ví, danh mục).
- **Dữ liệu đầu ra:** Tệp dữ liệu CSV mã hóa UTF-8 tải về máy tính/thiết bị của người dùng.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn chức năng "Xuất báo cáo (CSV)".
2. Hệ thống hiển thị biểu mẫu xác nhận phạm vi xuất dữ liệu (khoảng ngày, danh mục).
3. Người dùng chọn phạm vi cần xuất và nhấn "Tải tệp CSV".
4. Hệ thống kiểm tra số lượng bản ghi trong phạm vi yêu cầu (không vượt quá 100.000 bản ghi theo giới hạn bảo vệ).
5. Hệ thống truy vấn dữ liệu từ cùng một snapshot nhất quán, thực hiện chuẩn hóa văn bản phòng chống tấn công CSV Formula Injection theo FR12.04.
6. Hệ thống tạo luồng dữ liệu định dạng CSV mã hóa UTF-8 với dấu phân cách chuẩn và gửi tệp về trình duyệt của người dùng.
7. Hệ thống ghi nhận nhật ký kiểm toán hành vi xuất dữ liệu (Audit Log).

**Luồng phụ (Alternative Flows):**
* *3a. Người dùng xuất toàn bộ lịch sử từ trước đến nay:* Người dùng chọn "Tất cả thời gian"; hệ thống xử lý xuất toàn bộ dữ liệu trong phạm vi tài khoản của người dùng.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Số lượng bản ghi vượt quá 100.000 bản ghi:* Hệ thống từ chối xuất tệp và yêu cầu người dùng thu hẹp khoảng thời gian lọc để đảm bảo hiệu năng.
* *5e1. Phiên làm việc hết hạn trong quá trình chuẩn bị tệp:* Hệ thống chuyển hướng người dùng yêu cầu đăng nhập lại và từ chối tải tệp.

- **Hậu điều kiện:** Tệp CSV được tải về thành công; toàn bộ công thức độc hại bị vô hiệu hóa an toàn; dữ liệu hệ thống không bị thay đổi.

---

#### UC20 - Nhận dạng và tạo giao dịch từ ảnh hóa đơn (OCR)
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ; có tệp ảnh hóa đơn chụp rõ nét.
- **Dữ liệu đầu vào:** Tệp ảnh JPEG/PNG ($\le 10$ MB), thông tin điều chỉnh và xác nhận từ người dùng.
- **Dữ liệu đầu ra:** Bản ghi giao dịch chi tiêu mới được tạo sau khi người dùng xác nhận.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn chức năng "Quét hóa đơn từ ảnh (OCR)".
2. Hệ thống hiển thị khu vực tải ảnh lên.
3. Người dùng chọn hoặc chụp ảnh hóa đơn và tải lên hệ thống.
4. Hệ thống kiểm tra định dạng nhị phân (Magic bytes), kiểm tra dung lượng $\le 10$ MB và tính checksum SHA-256.
5. Hệ thống kích hoạt module OCR phân tích hình ảnh và trích xuất các trường: Số tiền, Ngày hóa đơn, Đơn vị tiền tệ, Tên nhà cung cấp/Mô tả.
6. Hệ thống hiển thị biểu mẫu giao dịch với các trường đã được điền tự động từ kết quả nhận dạng để người dùng kiểm tra.
7. Người dùng chọn ví thanh toán, chọn danh mục chi tiêu, chỉnh sửa các trường nhận dạng sai (nếu có) và nhấn "Xác nhận ghi sổ".
8. Hệ thống ghi sổ giao dịch theo quy trình chuẩn của UC06 và lưu liên kết tệp ảnh gốc.
9. Hệ thống hiển thị thông báo thành công kèm số dư ví mới.

**Luồng phụ (Alternative Flows):**
* *7a. Người dùng hủy bỏ:* Người dùng nhận thấy ảnh không thể sử dụng và nhấn "Hủy bỏ"; hệ thống xóa tệp tạm và không tạo bất kỳ bút toán nào.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Tệp không đúng định dạng ảnh hoặc vượt quá 10 MB:* Hệ thống từ chối tiếp nhận và thông báo định dạng/dung lượng không hợp lệ.
* *4e2. Phát hiện tệp ảnh trùng lặp checksum:* Tệp ảnh này đã từng được sử dụng để tạo giao dịch trước đó. Hệ thống hiển thị cảnh báo trùng lặp và yêu cầu người dùng xác nhận có tiếp tục hay không để tránh ghi trùng chi tiêu.
* *5e1. Ảnh quá mờ hoặc không nhận dạng được văn bản:* Hệ thống thông báo nhận dạng không thành công và mở biểu mẫu rỗng để người dùng nhập liệu thủ công.

- **Hậu điều kiện:** Giao dịch chỉ được ghi sổ khi có sự phê duyệt chủ động của người dùng; tệp ảnh được bảo mật riêng tư.

---

#### UC21 - Quản lý danh mục thu nhập và chi tiêu
- **Tác nhân:** A01 (Người dùng đã xác thực).
- **Tiền điều kiện:** Phiên đăng nhập hợp lệ.
- **Dữ liệu đầu vào:** Tên danh mục, loại danh mục (Thu nhập hoặc Chi tiêu), biểu tượng/màu sắc.
- **Dữ liệu đầu ra:** Bản ghi danh mục mới được tạo hoặc cập nhật.

**Luồng sự kiện chính (Main Flow):**
1. Người dùng chọn mục "Quản lý danh mục thu/chi".
2. Hệ thống hiển thị danh sách các danh mục hiện có được phân nhóm theo Thu nhập và Chi tiêu.
3. Người dùng nhấn "Thêm danh mục mới", nhập tên danh mục (ví dụ: "Tiền thưởng dự án"), chọn loại danh mục và nhấn "Lưu".
4. Hệ thống kiểm tra tên danh mục không trùng lặp trong cùng nhóm của người dùng.
5. Hệ thống lưu bản ghi danh mục mới vào cơ sở dữ liệu.
6. Hệ thống hiển thị thông báo thành công và cập nhật danh sách hiển thị.

**Luồng phụ (Alternative Flows):**
* *1a. Người dùng đổi tên danh mục:* Người dùng chọn chỉnh sửa tên danh mục; hệ thống lưu tên mới và giữ nguyên lịch sử phân loại của các giao dịch cũ.
* *1b. Người dùng lưu trữ (Archive) danh mục không còn dùng:* Người dùng chọn lưu trữ danh mục; hệ thống ẩn danh mục khỏi danh sách lựa chọn khi tạo giao dịch mới nhưng bảo lưu hoàn toàn trong các báo cáo lịch sử cũ.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Trùng tên danh mục:* Tên danh mục đã tồn tại trong cùng nhóm Thu hoặc Chi. Hệ thống báo lỗi trùng tên.
* *1be1. Cố gắng thay đổi loại Thu/Chi của danh mục đã có giao dịch:* Hệ thống từ chối thay đổi loại và giải thích danh mục đã gắn liền với các bút toán lịch sử.

- **Hậu điều kiện:** Danh mục sẵn sàng phục vụ việc phân loại giao dịch và ngân sách.

---

#### UC22 - Quản trị trạng thái tài khoản người dùng (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với tài khoản có vai trò `Admin`.
- **Dữ liệu đầu vào:** Mã người dùng cần quản trị, trạng thái mới (`Active` / `Locked`), lý do thay đổi trạng thái.
- **Dữ liệu đầu ra:** Trạng thái tài khoản người dùng được cập nhật, nhật ký kiểm toán quản trị được ghi nhận.

**Luồng sự kiện chính (Main Flow):**
1. Quản trị viên truy cập trang "Quản trị người dùng".
2. Hệ thống hiển thị danh sách người dùng kèm trạng thái tài khoản, vai trò và ngày tạo.
3. Quản trị viên tìm kiếm và chọn một tài khoản cần xử lý vi phạm.
4. Quản trị viên chọn thao tác "Khóa tài khoản", nhập lý do bắt buộc và nhấn "Xác nhận khóa".
5. Hệ thống kiểm tra tài khoản bị khóa không phải là tài khoản Quản trị viên duy nhất của hệ thống.
6. Hệ thống cập nhật trạng thái tài khoản sang `Locked`, thu hồi toàn bộ các phiên Token đang hoạt động của tài khoản đó.
7. Hệ thống ghi nhật ký kiểm toán hành chính bắt buộc (Audit Log) kèm mã Admin thực hiện và lý do.
8. Hệ thống hiển thị thông báo khóa tài khoản thành công.

**Luồng phụ (Alternative Flows):**
* *4a. Quản trị viên mở khóa tài khoản:* Quản trị viên chọn tài khoản đang bị khóa và nhấn "Mở khóa"; hệ thống cập nhật trạng thái sang `Active` và cho phép người dùng đăng nhập lại bình thường.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Quản trị viên không nhập lý do khóa:* Hệ thống yêu cầu nhập lý do bắt buộc trước khi cho phép xác nhận.
* *5e1. Cố gắng vô hiệu hóa tài khoản Quản trị viên cuối cùng:* Hệ thống từ chối thao tác theo quy tắc an toàn FR14.05 để bảo vệ quyền quản trị hệ thống.

- **Hậu điều kiện:** Tài khoản bị khóa mất quyền truy cập API ngay lập tức; dữ liệu sổ sách tài chính cá nhân của người dùng được bảo toàn nguyên vẹn.

---

#### UC23 - Cấu hình tham số vận hành hệ thống (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với quyền `Admin`.
- **Dữ liệu đầu vào:** URL dịch vụ tỷ giá, tần suất chạy tác vụ sao lưu, thời gian lưu trữ tệp nhật ký, hạn ngạch tải tệp.
- **Dữ liệu đầu ra:** Phiên bản cấu hình vận hành mới được áp dụng.

**Luồng sự kiện chính (Main Flow):**
1. Quản trị viên truy cập mục "Cấu hình tham số hệ thống".
2. Hệ thống hiển thị bảng các tham số vận hành hiện tại kèm phiên bản cấu hình đang có hiệu lực.
3. Quản trị viên điều chỉnh tham số (ví dụ: đổi tần suất quét lịch sao lưu) và nhấn "Lưu cấu hình".
4. Hệ thống kiểm tra tính hợp lệ của các giá trị tham số (không vi phạm chỉ tiêu an toàn và RPO).
5. Hệ thống lưu cấu hình mới theo phiên bản tăng dần, ghi nhận danh tính Quản trị viên thay đổi và công bố phiên bản mới cho các tiến trình nền.
6. Hệ thống hiển thị thông báo cập nhật cấu hình thành công.

**Luồng phụ (Alternative Flows):**
* *1a. Quản trị viên xem lịch sử cấu hình:* Quản trị viên nhấn "Lịch sử cấu hình"; hệ thống hiển thị toàn bộ các phiên bản cấu hình trước đây kèm người sửa và thời điểm.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Tham số không hợp lệ:* Giá trị tham số nằm ngoài khoảng an toàn (ví dụ tần suất sao lưu vượt quá 24 giờ làm vi phạm RPO). Hệ thống từ chối lưu và yêu cầu nhập giá trị hợp lệ.

- **Hậu điều kiện:** Toàn bộ tiến trình nền tự động áp dụng cấu hình mới từ chu kỳ tiếp theo mà không cần khởi động lại máy chủ.

---

#### UC24 - Kích hoạt và kiểm tra bản sao lưu dữ liệu (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với quyền `Admin`; kho lưu trữ sao lưu IF04 sẵn sàng hoạt động.
- **Dữ liệu đầu vào:** Lệnh kích hoạt sao lưu tức thời từ Quản trị viên.
- **Dữ liệu đầu ra:** Bản sao lưu dữ liệu toàn vẹn kèm tệp Manifest và mã băm SHA-256 được lưu trữ vào kho IF04.

**Luồng sự kiện chính (Main Flow):**
1. Quản trị viên truy cập mục "Sao lưu & Phục hồi dữ liệu".
2. Hệ thống hiển thị danh sách các bản sao lưu hiện có và trạng thái kiểm chứng tính toàn vẹn.
3. Quản trị viên nhấn nút "Tạo bản sao lưu ngay".
4. Hệ thống tạo snapshot nhất quán của cơ sở dữ liệu và các tệp đính kèm.
5. Hệ thống nén dữ liệu, mã hóa bằng chuẩn AES-256, tính toán mã băm SHA-256 và lập tệp kê khai Manifest.
6. Hệ thống chuyển tệp sao lưu sang kho lưu trữ độc lập IF04 và tự động kiểm tra lại tính toàn vẹn (Verify Checksum).
7. Hệ thống cập nhật trạng thái bản sao lưu là `Verified` và hiển thị thông báo thành công cho Quản trị viên.

**Luồng phụ (Alternative Flows):**
* *2a. Quản trị viên kiểm tra lại tính toàn vẹn của một bản sao lưu cũ:* Quản trị viên chọn bản sao lưu và nhấn "Kiểm tra tính toàn vẹn"; hệ thống tính lại checksum và xác nhận tệp không bị suy hao.

**Luồng ngoại lệ (Exception Flows):**
* *6e1. Kho lưu trữ độc lập mất kết nối hoặc đầy dung lượng:* Hệ thống ghi nhận trạng thái bản sao lưu là `Failed`, giữ lại bản sao lưu thành công trước đó và phát cảnh báo khẩn cấp cho Quản trị viên.

- **Hậu điều kiện:** Có thêm một bản sao lưu toàn vẹn sẵn sàng phục vụ khôi phục khi xảy ra sự cố.

---

#### UC25 - Khôi phục hệ thống từ bản sao lưu (Admin)
- **Tác nhân:** A02 (Quản trị viên hệ thống).
- **Tiền điều kiện:** Đăng nhập với quyền `Admin`; có bản sao lưu hợp lệ ở trạng thái `Verified`; đã kích hoạt cửa sổ bảo trì hệ thống (Maintenance Mode).
- **Dữ liệu đầu vào:** Mã bản sao lưu cần khôi phục, mật khẩu xác thực lại của Quản trị viên, xác nhận chấp nhận ghi đè dữ liệu.
- **Dữ liệu đầu ra:** Dữ liệu toàn hệ thống được phục hồi về đúng mốc thời gian của bản sao lưu được chọn.

**Luồng sự kiện chính (Main Flow):**
1. Quản trị viên kích hoạt chế độ bảo trì hệ thống (chặn toàn bộ yêu cầu ghi dữ liệu từ người dùng).
2. Quản trị viên chọn một bản sao lưu có trạng thái `Verified` trong danh mục và nhấn "Khôi phục dữ liệu".
3. Hệ thống hiển thị cảnh báo nghiêm trọng về việc toàn bộ dữ liệu mới hơn mốc sao lưu sẽ bị thay thế và yêu cầu Quản trị viên nhập lại mật khẩu xác thực.
4. Quản trị viên nhập mật khẩu xác nhận và nhấn "Bắt đầu khôi phục".
5. Hệ thống tự động chụp bản sao lưu hiện trạng trước khôi phục (để phòng ngừa rủi ro).
6. Hệ thống giải nén bản sao lưu vào môi trường kiểm thử tách biệt, thẩm tra tính cân bằng của toàn bộ sổ cái và tính toàn vẹn của các khóa ngoại.
7. Khi toàn bộ kiểm tra đạt chuẩn 100%, hệ thống chuyển đổi cơ sở dữ liệu sang môi trường phục vụ chính thức, thu hồi toàn bộ các phiên đăng nhập cũ của người dùng.
8. Hệ thống tắt chế độ bảo trì, mở lại dịch vụ và hiển thị biên bản phục hồi thành công.

**Luồng phụ (Alternative Flows):**
* *4a. Quản trị viên hủy thao tác trước khi xác nhận:* Quản trị viên nhấn "Hủy bỏ"; hệ thống giữ nguyên hiện trạng và tắt chế độ bảo trì.

**Luồng ngoại lệ (Exception Flows):**
* *4e1. Mật khẩu xác thực của Quản trị viên không chính xác:* Hệ thống từ chối tiến hành khôi phục.
* *6e1. Bản sao lưu bị lỗi cấu trúc hoặc kiểm tra đối soát không đạt:* Hệ thống lập tức hủy bỏ tiến trình phục hồi, giữ nguyên bản dữ liệu hiện trạng, duy trì chế độ bảo trì và xuất báo cáo lỗi chi tiết cho Quản trị viên.

- **Hậu điều kiện:** Hệ thống hoạt động trở lại ổn định với dữ liệu được khôi phục chính xác về mốc thời gian sao lưu; mọi giao dịch lịch sử trước mốc sao lưu được bảo toàn trọn vẹn.

---

### 4.3 Kịch bản xử lý nền (Background Tasks / Scheduled Services)

| Mã tác vụ | Kích hoạt & Tần suất | Luồng xử lý nghiệp vụ | Kết quả kỳ vọng & Xử lý lỗi |
| :---: | :--- | :--- | :--- |
| **SYS01** | Lịch tự động: 00:05 hàng ngày hoặc khi khởi động. | Gửi yêu cầu đến A03 qua IF02 lấy tỷ giá mới nhất; kiểm tra giá trị $> 0$, thời điểm hợp lệ; lưu phiên bản tỷ giá mới. Không làm thay đổi snapshot của giao dịch cũ. | Thành công có tỷ giá mới. Thất bại: thử lại sau 1, 5, 15 phút rồi chuyển sang mỗi giờ; quá 24h cảnh báo Admin và chặn ghi sổ ngoại tệ mới. |
| **SYS02** | Lịch tự động: mỗi 5 phút quét một lần. | Quét các lịch định kỳ hoạt động có $nextDueDate \le \text{thời điểm hiện tại}$ chưa xử lý. Nhận khóa `(subscriptionId, dueDate)`; tạo bản nháp hoặc tự ghi sổ nếu người dùng bật; cập nhật ngày đến hạn kế tiếp theo BR09. | Mỗi kỳ đến hạn xử lý đúng một lần. Lỗi số dư: giữ bản ghi chờ và thông báo người dùng; lỗi tạm thời: thử lại với cùng khóa Idempotency. |
| **SYS03** | Hướng sự kiện: kích hoạt ngay sau khi commit hoặc đảo giao dịch chi tiêu. | Lấy danh mục chi tiêu của giao dịch; tính lại chi tiêu ròng trong tháng; so sánh với hạn mức ngân sách; xác định màu cảnh báo (Vàng $\ge 80\%$, Đỏ $\ge 100\%$). | Gửi thông báo trong ứng dụng nếu chưa từng phát trong kỳ; lỗi thông báo không làm ảnh hưởng giao dịch chi tiêu đã commit. |
| **SYS04** | Lịch tự động: mỗi 12 giờ một lần (bảo đảm RPO $< 12$h). | Chụp snapshot nhất quán của DB; nén và mã hóa AES-256; tính checksum SHA-256; ghi tệp Manifest; đẩy bản sao lưu sang kho độc lập IF04. | Thất bại: cảnh báo khẩn Quản trị viên, tự động thử lại sau 15 phút. Lưu trữ lịch sử sao lưu 30 ngày. |
| **SYS05** | Lịch tự động: 08:00 sáng hàng ngày. | Quét danh mục các khoản vay/nợ vi mô đang `Active` có ngày đến hạn trả nợ trong vòng 3 ngày tới hoặc đã quá hạn; tạo thông báo nhắc nhở nợ gửi đến người dùng. | Giúp người dùng chủ động chuẩn bị tiền trả nợ, tránh phát sinh phí phạt hoặc nợ xấu; áp dụng khóa chống trùng lặp thông báo trong cùng ngày. |

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
| **7. Mua xe máy 25.000.000 VND bằng tiền mặt** | Tài sản khác (Xe): 25.000.000 | Ví Ngân hàng: 25.000.000 | Không đổi (0 VND) | Hoán đổi cấu trúc tài sản; Thu/Chi = 0. |
| **8. Đảo khoản chi ăn uống 100.000 VND** | Ví tiền mặt: 100.000 | Chi phí ăn uống: 100.000 | Tăng 100.000 VND | Bút toán đảo đối ứng; giảm chi ròng. |
| **9. Đổi 100 USD (giá sổ 2.500.000) lấy 2.480.000 VND** | Ví VND: 2.480.000<br>Lỗ tỷ giá: 20.000 | Ví USD: 100 USD (Quy đổi Base: 2.500.000) | Giảm 20.000 VND | Cân bằng tiền cơ sở; ví USD hết sạch số dư. |

---

<a id="phan-5"></a>

## 5. Yêu cầu phi chức năng (Non-Functional Requirements - NFR)

Toàn bộ các yêu cầu phi chức năng được chuẩn hóa và lượng hóa theo tiêu chuẩn quốc tế **ISO/IEC 25010:2023** (Systems and software Quality Requirements and Evaluation - SQRE).

### 5.1 Điều kiện môi trường đo lường chuẩn (Test Baselines)
* **E1 - Môi trường máy chủ thử nghiệm chuẩn:** Máy chủ ứng dụng (Web/API): 4 vCPU, 8 GB RAM; Máy chủ cơ sở dữ liệu: 4 vCPU, 8 GB RAM, ổ cứng SSD NVMe; độ trễ mạng nội bộ $< 5$ ms.
* **D1 - Tập dữ liệu kiểm thử tải chuẩn:** 1.000 người dùng hoạt động; 5.000 ví/tài khoản; 1.000.000 giao dịch; 2.500.000 dòng sổ cái; bao gồm đầy đủ dữ liệu đa tiền tệ, giao dịch đảo, bản nháp, khoản vay vi mô và mục tiêu tích lũy.
* **L1 - Mức tải kiểm thử chuẩn:** 100 người dùng ảo đồng thời gửi yêu cầu liên tục, tổng tải 25 yêu cầu/giây (RPS); thực hiện làm nóng 10 phút, đo liên tục trong 30 phút; lặp lại 3 lần độc lập.

---

### NFR01 - Hiệu năng và Khả năng đáp ứng (Performance Efficiency)
* **Tiêu chuẩn ISO/IEC 25010:** Time behaviour, Resource utilization, Capacity.
* **Chỉ tiêu định lượng:**
  - Thời gian phản hồi API ghi nhận giao dịch (Thu, Chi, Chuyển ví, Trả nợ) tại phân vị 95 ($p95$) phải nhỏ hơn **200 ms** dưới điều kiện tải L1.
  - Thời gian tải và hiển thị hoàn chỉnh màn hình Dashboard tổng quan tài sản ròng ($p95$) phải nhỏ hơn **2.0 giây**.
  - Tác vụ nền công bố phiên bản tỷ giá mới hoàn thành trong vòng **30 giây** kể từ khi nhận được dữ liệu hợp lệ từ IF02.
  - Sự kiện cập nhật cảnh báo ngân sách và dashboard phải phản ánh dữ liệu commit mới trong vòng **5 giây** ($p95$) và tuyệt đối không quá 30 giây trong mọi trường hợp.
* **Tài nguyên tiêu thụ:** Dưới mức tải L1, mức sử dụng CPU của máy chủ ứng dụng và DB không vượt quá $75\%$; bộ nhớ RAM không vượt quá $80\%$; tỷ lệ lỗi hệ thống (HTTP 5xx) nhỏ hơn $0.1\%$.

---

### NFR02 - Bảo mật và Toàn vẹn thông tin (Security)
* **Tiêu chuẩn ISO/IEC 25010:** Confidentiality, Integrity, Authenticity, Accountability, Resistance.
* **Xác thực và Quản lý phiên:**
  - Mật khẩu người dùng được băm bằng thuật toán Argon2id hoặc bcrypt với Salt ngẫu nhiên riêng biệt; cấm lưu trữ mật khẩu dạng rõ.
  - Chính sách mật khẩu: độ dài từ 12 đến 128 ký tự, chứa cả chữ hoa, chữ thường, số và ký tự đặc biệt.
  - Sau 5 lần đăng nhập thất bại liên tiếp trong vòng 15 phút, tài khoản bị tạm khóa thử lại trong 15 phút tiếp theo.
  - Phiên làm việc (Session/Token) tự động hết hạn sau 30 phút không hoạt động hoặc tối đa 12 giờ kể từ thời điểm đăng nhập.
* **Phân quyền và Bảo vệ dữ liệu:**
  - $100\%$ các yêu cầu truy cập trái phép vi phạm ma trận phân quyền giữa các người dùng hoặc giữa User và Admin phải bị từ chối; không để lộ bản ghi, hình ảnh hoặc báo cáo của người khác.
  - Toàn bộ dữ liệu truyền trên mạng bắt buộc mã hóa qua giao thức HTTPS (TLS 1.2 trở lên).
  - Tệp ảnh hóa đơn và bản sao lưu cơ sở dữ liệu được mã hóa bằng chuẩn AES-256 khi lưu trữ.
  - Nhật ký hệ thống (System Logs) tuyệt đối không ghi nhận mật khẩu, Token xác thực hoặc thông tin tài chính chi tiết.
  - Toàn bộ các thao tác nhạy cảm (Đăng nhập sai, Ghi/Đảo giao dịch, Thanh toán nợ, Thay đổi cấu hình, Khóa tài khoản, Xuất dữ liệu, Phục hồi DB) phải được ghi nhận vào bảng Audit Log với đầy đủ `actorId`, `requestId`, `action`, `timestamp` và `result`.

---

### NFR03 - Tính toàn vẹn dữ liệu kế toán (Data Integrity)
* **Tiêu chuẩn ISO/IEC 25010:** Functional correctness, Faultlessness, Fail safe.
* **Chỉ tiêu định lượng:**
  - $100\%$ các giao dịch ở trạng thái Posted phải có tổng giá trị Nợ quy đổi tiền cơ sở bằng chính xác tổng giá trị Có quy đổi tiền cơ sở tới đơn vị tiền tệ nhỏ nhất: $\sum \text{DebitBase} = \sum \text{CreditBase}$.
  - Tuyệt đối không tồn tại bút toán mồ côi dòng sổ, số dư ví cập nhật một phần hoặc dữ liệu sai lệch chủ sở hữu ($0\%$ lỗi toàn vẹn).
  - $100\%$ các thao tác ghi nhận giao dịch, sinh dòng sổ, cập nhật số dư và lưu vết kiểm toán phải thực thi nguyên tử trong một Transaction.
  - Toàn bộ phép tính tiền tệ sử dụng kiểu dữ liệu Decimal chính xác, triệt tiêu hoàn toàn sai số dấu phẩy động nhị phân.

---

### NFR04 - Độ tin cậy và Khả năng sẵn sàng (Reliability & Availability)
* **Tiêu chuẩn ISO/IEC 25010:** Availability, Fault tolerance, Recoverability.
* **Chỉ tiêu định lượng:**
  - Độ sẵn sàng của hệ thống (Availability) đạt tối thiểu **$99.5\%$** trong cửa sổ đo lường 30 ngày liên tục đối với các chức năng cốt lõi: Đăng nhập, Ghi nhận giao dịch nội tệ và Xem số dư.
  - **Cô lập lỗi (Fault Isolation):** Sự cố gián đoạn của dịch vụ tỷ giá bên ngoài (A03), tiến trình OCR hoặc tiến trình gửi thông báo nền tuyệt đối không làm ảnh hưởng hay đình trệ luồng ghi nhận giao dịch nội tệ của người dùng.
  - **Chỉ tiêu sao lưu phục hồi:**
    - Mục tiêu điểm phục hồi dữ liệu (**RPO - Recovery Point Objective**): $< 12$ giờ.
    - Mục tiêu thời gian phục hồi dịch vụ (**RTO - Recovery Time Objective**): $< 60$ phút đối với tập dữ liệu quy mô D1.

---

### NFR05 - Khả năng kiểm thử (Testability)
* **Tiêu chuẩn ISO/IEC 25010:** Testability, Completeness.
* **Chỉ tiêu định lượng:**
  - Độ bao phủ kiểm thử dòng mã nguồn (Code Line Coverage) đối với tầng nghiệp vụ lõi (Business Domain Layer: Sổ kép, Net Worth, Ngân sách, Tính lãi nợ) phải đạt tối thiểu **$85\%$**.
  - $100\%$ các yêu cầu chức năng con `FRnn.xx` phải có ít nhất một kịch bản kiểm thử ca thành công (Positive Test Case) và một kịch bản kiểm thử ca lỗi/biên (Negative Test Case).
  - Toàn bộ $25$ Use Cases và $5$ tác vụ nền SYS phải có kịch bản kiểm thử nghiệm thu tự động (Automated Test Suite) có thể chạy lại trong quy trình CI/CD.

---

### NFR06 - Khả năng sử dụng và Tiếp cận (Usability & Accessibility)
* **Tiêu chuẩn ISO/IEC 25010:** Learnability, Operability, User error protection.
* **Chỉ tiêu định lượng:**
  - Người dùng mới sau tối đa 10 phút làm quen có thể hoàn thành việc nhập một khoản chi tiêu hợp lệ trong thời gian dưới **60 giây** mà không cần trợ giúp.
  - Từ màn hình Dashboard chính, người dùng có thể kích hoạt biểu mẫu ghi thu/chi trong không quá **2 lượt nhấn chuột/chạm màn hình**.
  - Biểu mẫu nhập liệu hiển thị thông báo lỗi trường cụ thể ngay dưới trường dữ liệu bị sai và giữ nguyên các trường dữ liệu đúng đã nhập trước đó.
  - Giao diện đáp ứng mượt mà (Responsive Web Design) trên các độ phân giải màn hình chuẩn: Desktop (1440px), Tablet (768px), Mobile (360px); độ tương phản màu sắc văn bản đạt chuẩn WCAG 2.1 mức AA (tỷ lệ tối thiểu 4.5:1).

---

### NFR07 - Khả năng bảo trì và Tiến hóa (Maintainability)
* **Tiêu chuẩn ISO/IEC 25010:** Modularity, Analysability, Modifiability.
* **Chỉ tiêu định lượng:**
  - Mã nguồn được cấu trúc module hóa phân tầng rõ rệt; việc thay thế nguồn cấp tỷ giá (IF02) bằng bộ giả lập Mock không đòi hỏi sửa đổi bất kỳ dòng mã nào trong tầng nghiệp vụ sổ cái lõi.
  - $100\%$ các lỗi phát sinh tại API hoặc tiến trình nền phải có mã `requestId`/`jobId` đính kèm để kỹ sư vận hành có thể tra cứu và xác định nguyên nhân gốc rễ trong vòng không quá **15 phút**.
  - Hệ thống cho phép mở rộng khả năng chịu tải lên 50 RPS khi nâng cấp tài nguyên máy chủ mà không đòi hỏi tái cấu trúc kiến trúc phần mềm.

---

<a id="phan-6"></a>

## 6. Ma trận truy vết và Tiêu chí nghiệm thu (Traceability & Acceptance)

### 6.1 Ma trận truy vết hai chiều (Bidirectional Traceability Matrix)

| Yêu cầu người dùng (UR) | Yêu cầu chức năng (FR) | Use Case / Tác vụ nền | Quy tắc nghiệp vụ (BR) | Yêu cầu phi chức năng (NFR) | Mã bộ kiểm thử |
| :---: | :---: | :---: | :---: | :---: | :---: |
| **UR01** | FR01, FR14, FR15 | UC01, UC02, UC03, UC22 | BR11 | NFR02, NFR06 | TC-F01, TC-U01, TC-U02 |
| **UR02** | FR02, FR03, FR04 | UC04, UC05, UC06, UC07, UC08 | BR01, BR02, BR03, BR04, BR05, BR06, BR12, BR13, BR15 | NFR01, NFR03, NFR07 | TC-F02, TC-F03, TC-F04, TC-U04 - TC-U08 |
| **UR03** | FR05 | UC12, UC13, SYS03 | BR07, BR08 | NFR01, NFR06 | TC-F05, TC-U12, TC-U13, TC-S03 |
| **UR04** | FR06 | UC14, SYS02 | BR09, BR12, BR13 | NFR03, NFR04, NFR07 | TC-F06, TC-U14, TC-S02 |
| **UR05** | FR07 | UC15 | BR10 | NFR02, NFR06 | TC-F07, TC-U15 |
| **UR06** | FR08, FR10 | UC09, UC16 | BR02, BR16 | NFR01, NFR03, NFR06 | TC-F08, TC-F10, TC-U09, TC-U16 |
| **UR07** | FR09 | UC10, UC11, SYS05 | BR05, BR17 | NFR02, NFR03, NFR06 | TC-F09, TC-U10, TC-U11, TC-S05 |
| **UR08** | FR10, FR11 | UC16, UC17 | BR14, BR16, BR18 | NFR01, NFR06 | TC-F10, TC-F11, TC-U16, TC-U17 |
| **UR09** | FR12 | UC18, UC19 | BR01, BR11, BR14 | NFR01, NFR02, NFR06 | TC-F12, TC-U18, TC-U19 |
| **UR10** | FR16 | UC20 | BR11, BR12, BR13 | NFR02, NFR06 | TC-F16, TC-U20 |
| **UR11** | FR13, FR14 | UC24, UC25, SYS04 | BR01, BR11, BR12 | NFR02, NFR04, NFR07 | TC-F13, TC-F14, TC-U24, TC-U25, TC-S04 |
| **UR12** | FR15, Toàn bộ | Toàn bộ UC bảo vệ | BR11, BR13 | NFR01 - NFR07 | TC-F15, TC-N01 - TC-N07 |

---

### 6.2 Ca kiểm thử mẫu tiêu biểu (Representative Test Cases)

| Mã ca kiểm thử | Loại kịch bản | Mục đích kiểm thử | Dữ liệu đầu vào & Kích thích | Kết quả kỳ vọng |
| :---: | :---: | :--- | :--- | :--- |
| **TC-F02.06-P** | Luồng chính | Kiểm tra chống trùng lặp giao dịch (Idempotency) | Gửi 5 yêu cầu HTTP POST tạo khoản chi 200.000 VND cùng một `Idempotency-Key` và cùng nội dung. | Hệ thống chỉ tạo đúng 1 bản ghi giao dịch; số dư ví chỉ bị trừ 200.000 VND một lần duy nhất; 4 yêu cầu sau nhận mã 200/201 kèm dữ liệu ban đầu. |
| **TC-F02.06-E** | Luồng ngoại lệ | Kiểm tra xung đột khóa Idempotency khác nội dung | Gửi lại `Idempotency-Key` đã sử dụng nhưng thay đổi số tiền thành 500.000 VND. | Hệ thống trả về mã lỗi 409 Conflict; số dư ví giữ nguyên; không tạo thêm giao dịch nào. |
| **TC-F03.02-E** | Luồng ngoại lệ | Kiểm tra tính nguyên tử (Rollback khi hỏng hóc) | Chèn lỗi giả lập làm gián đoạn kết nối DB sau khi tạo dòng Nợ chi phí nhưng trước khi tạo dòng Có ví. | Toàn bộ giao dịch bị Rollback sạch sẽ; không có dòng sổ mồ côi; số dư ví không bị trừ; log ghi nhận `requestId`. |
| **TC-F08.04-P** | Luồng chính | Kiểm tra tính toán Tài sản ròng (Net Worth) | Người dùng có Ví tiền mặt 5tr, Ví NH 15tr, Xe máy 20tr, Khoản vay còn nợ 10tr. | Hệ thống tính chính xác: Total Assets = 40tr; Total Liabilities = 10tr; Net Worth = 30tr. |
| **TC-F09.03-P** | Luồng chính | Kiểm tra phân rã trả nợ vay vi mô (Gốc và Lãi) | Khoản vay 10tr. Trả nợ kỳ 1: 2tr gốc + 150k lãi từ Ví ngân hàng có số dư 5tr. | Ví ngân hàng còn 2.850.000 VND; dư nợ gốc khoản vay còn 8.000.000 VND; chi phí tài chính trong kỳ ghi nhận 150.000 VND; thu chi không bị trừ 2tr gốc. |
| **TC-F09.03-E** | Luồng ngoại lệ | Kiểm tra trả gốc vượt dư nợ còn lại | Khoản vay còn dư nợ 3.000.000 VND. Người dùng gửi lệnh trả gốc 5.000.000 VND. | Hệ thống từ chối giao dịch; hiển thị thông báo lỗi số tiền trả gốc vượt quá dư nợ hiện tại; số dư ví không đổi. |
| **TC-F11.03-E** | Luồng ngoại lệ | Kiểm tra tính Saving Rate khi không có thu nhập | Kỳ khảo sát có Thu nhập = 0 VND, Chi tiêu = 1.500.000 VND. | Net Cash Flow = -1.500.000 VND; Saving Rate hiển thị "N/A" an toàn (không bị lỗi chia cho 0 `DivideByZeroException`). |
| **TC-F12.04-E** | Luồng ngoại lệ | Kiểm tra chống CSV Formula Injection | Tạo giao dịch có mô tả: `=cmd\|' /C calc'!A0`. Thực hiện xuất báo cáo CSV. | Tệp CSV tải về có trường mô tả được trung hòa ký tự (ví dụ tiền tố dấu nháy đơn `'=cmd...`), không tự kích hoạt lệnh khi mở bằng Excel. |
| **TC-F15.02-E** | Luồng ngoại lệ | Kiểm tra cô lập dữ liệu đa người dùng | UserA gửi yêu cầu xem chi tiết giao dịch hoặc tải ảnh biên nhận của UserB bằng ID thật. | Hệ thống trả về mã lỗi 404 Not Found; tuyệt đối không tiết lộ dữ liệu của UserB. |

---

### 6.3 Tiêu chí nghiệm thu sản phẩm pha Elaboration (Acceptance Criteria)

1. **Hoàn thiện đặc tả yêu cầu:** $100\%$ các yêu cầu chức năng P1 và P2 đã được mô hình hóa và phê duyệt đầy đủ; không còn các mục mở chưa rõ phương hướng giải quyết.
2. **Khung kiến trúc thực thi (Architectural Baseline):**
   - Đã hiện thực hóa khung mã nguồn kết nối thông suốt từ Presentation Layer (Web UI) qua Application/Domain Layer đến Database Layer.
   - Thử nghiệm thành công kịch bản cốt lõi: Đăng ký $\rightarrow$ Mở ví $\rightarrow$ Ghi thu chi kép $\rightarrow$ Tính Net Worth $\rightarrow$ Cập nhật Dashboard mà không phát sinh lỗi kiến trúc.
3. **Triệt tiêu rủi ro trọng yếu (Risk Retirement):**
   - Rủi ro sai lệch số dư / bất đồng bộ đã được triệt tiêu hoàn toàn thông qua cơ chế giao dịch ACID và khóa Idempotency.
   - Rủi ro đa tiền tệ và làm tròn đã được kiểm chứng thông qua thuật toán quy đổi snapshot tỷ giá và kiểm tra cân bằng sổ kép.
   - Rủi ro bảo mật dữ liệu đa người dùng đã được kiểm chứng thông qua ma trận kiểm thử phân quyền 100%.
4. **Chất lượng kiểm thử:** Bộ kiểm thử tự động (Unit Test & Integration Test) bao phủ tối thiểu $85\%$ mã nguồn nghiệp vụ cốt lõi; $100\%$ các ca kiểm thử sổ kép, rollback và bảo mật đều đạt kết quả Pass.

---

### 6.4 Danh mục quyết định kiến trúc và kỹ thuật đã chốt (Architectural Decisions)

| Hạng mục quyết định | Phương án lựa chọn pha Elaboration | Cơ sở lý luận & Ràng buộc kỹ thuật |
| :--- | :--- | :--- |
| **Phong cách kiến trúc** | Layered Architecture + Modular Monolith | Phù hợp quy mô đồ án, dễ triển khai, dễ kiểm thử toàn diện, tránh sự phức tạp phân tán không cần thiết của Microservices. |
| **Hệ quản trị CSDL** | PostgreSQL (hoặc MySQL 8.0+) | Hỗ trợ chuẩn giao dịch ACID mạnh mẽ, khóa mức dòng (Row-level lock), kiểu dữ liệu `DECIMAL` chính xác cho tiền tệ và hỗ trợ lưu trữ JSON. |
| **Chiến lược tính số dư** | Hybrid: Sổ cái bất biến + Số dư bộ đệm đối soát | Giao dịch ghi vào sổ cái Nợ/Có bất biến; duy trì số dư lưu đệm trên bảng Ví để phản hồi tức thì dưới 200ms; đối soát định kỳ để bảo đảm tính toàn vẹn. |
| **Cơ chế chống trùng lặp** | Idempotency Key trên Header HTTP | Bắt buộc client gửi khóa UUID cho mỗi yêu cầu ghi sổ; lưu trạng thái khóa trong cùng transaction của giao dịch. |
| **Quy định tỷ giá ngoại tệ** | Snapshot tại thời điểm giao dịch | Khóa tỷ giá quy đổi tại thời điểm commit; không đánh giá lại sổ sách tự động theo giá thị trường hàng ngày; tách riêng lãi/lỗ tỷ giá khi tất toán. |
| **Xử lý ảnh hóa đơn (OCR)** | Hỗ trợ nhập liệu bán tự động (Human-in-the-loop) | OCR chỉ đóng vai trò trích xuất tạo bản nháp (Draft); người dùng bắt buộc kiểm tra và chủ động nhấn Xác nhận trước khi ghi sổ chính thức. |
| **Phân định nợ vi mô** | Phân rã dòng tiền Trả nợ gốc vs Lãi/phí | Bảo đảm tuân thủ nguyên lý kế toán: tiền trả nợ gốc không được tính vào chi phí chi tiêu cá nhân để tránh làm sai lệch báo cáo dòng tiền và ngân sách. |

---
**KẾT THÚC TÀI LIỆU ĐẶC TẢ SRS PHA ELABORATION - NHÓM VOZ**
