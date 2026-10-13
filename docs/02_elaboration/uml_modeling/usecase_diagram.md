# TÀI LIỆU MÔ HÌNH HÓA USE CASE (USE CASE DIAGRAMS)
## HỆ THỐNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN, TÀI SẢN VÀ TÀI CHÍNH VI MÔ (WEALTHFLOW)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG**  
**KHOA CÔNG NGHỆ THÔNG TIN**  

- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Nhóm / Lớp:** Voz / N3
- **Giảng viên hướng dẫn:** ThS. Võ Thị Kim Anh
- **Thành viên nhóm:**
  - Võ Trịnh Quốc Huy
  - Võ Văn Khôi Nguyên
  - Nguyễn Thành Trung
  - Phan Nguyễn Tuấn Kiệt
- **Pha tiến trình:** Elaboration (Pha Tinh chế / Phân tích chi tiết)
- **Tài liệu tham chiếu:** [docs/02_elaboration/SRS.md](../SRS.md) v2.1 (Elaboration Refined Baseline - 13 Core Use Cases)

---

## 1. Giới thiệu và Quy ước Mô hình hóa

Tài liệu này trực quan hóa toàn bộ **13 Use Cases cốt lõi (`UC01` – `UC13`)** và **3 Kịch bản nền (`SYS01` – `SYS03`)** đã được tinh gọn và đặc tả chuẩn hóa trong tài liệu SRS v2.1. Để đảm bảo tính khả dụng, tính công thái học và tránh quá tải thị giác, mô hình được tổ chức theo:
1. **01 Sơ đồ Tổng quan Toàn hệ thống (System Overview Diagram):** Thể hiện sự phân bổ 13 Use Cases theo 6 phân hệ nghiệp vụ và mối quan hệ với các tác nhân.
2. **06 Sơ đồ Chi tiết theo Phân hệ (Subsystem Diagrams):** Gom nhóm các Use Case có chung Functional Requirements (FR) và luồng dữ liệu tương hỗ, làm rõ các mối quan hệ cấu trúc chuẩn UML 2.5:
   - **Liên kết cơ bản (Association):** Đường nối liền giữa Tác nhân và Use Case.
   - **Quan hệ `«include»` (Bao hàm):** Mũi tên nét đứt từ Base Use Case trỏ đến Included Use Case (`Base -.->|«include»| Included`), biểu thị hành vi bắt buộc phải thực thi.
   - **Quan hệ `«extend»` (Mở rộng):** Mũi tên nét đứt từ Extension Use Case trỏ ngược về Base Use Case (`Extension -.->|«extend»| Base`), biểu thị hành vi mở rộng có điều kiện tại Extension Point.
   - **Kế thừa tác nhân (Generalization):** Mũi tên trỏ từ tác nhân chuyên biệt về tác nhân tổng quát.

---

## 2. Danh mục Tác nhân Hệ thống (Actors)

| Mã Tác nhân | Tên Tác nhân | Loại | Định nghĩa và Phạm vi tương tác |
| :---: | :--- | :---: | :--- |
| **A01** | **Người dùng (End-User)** | Chính (Primary) | Cá nhân sử dụng hệ thống để quản lý tài chính, ví tiền, thu chi, tài sản vàng tích lũy, vay nợ vi mô và xem báo cáo. |
| **A01-G** | **Khách vãng lai (Guest)** | Chính (Primary) | Người dùng chưa đăng nhập, chỉ có thể xem trang giới thiệu, thực hiện đăng ký tài khoản hoặc đăng nhập (`UC01`). |
| **A02** | **Quản trị viên (System Admin)** | Quản trị (Admin) | Quản lý tài khoản người dùng (khóa/mở khóa), xem Dashboard thống kê vĩ mô, cấu hình hệ thống và phát thông báo (`UC12, UC13`). Tuyệt đối không xem sổ sách cá nhân. |
| **A03** | **Exchange Rate Service** | Hỗ trợ (Secondary) | API nhà cung cấp tỷ giá hối đoái tiền tệ phục vụ quy đổi đa tiền tệ về đồng tiền cơ sở (`baseCurrency`). |
| **SYS** | **System Scheduler (Bộ lập lịch hệ thống)** | Nội bộ (Internal) | Bộ đếm thời gian và tiến trình chạy nền xử lý các tác vụ định kỳ tự động (`SYS01` - `SYS03`). |

---

## 3. Sơ đồ Use Case Tổng quan Toàn hệ thống (System Overview)

Sơ đồ tổng quan thể hiện kiến trúc phân hệ vĩ mô của hệ thống WealthFlow và mối quan hệ tương tác giữa các tác nhân (Actors) với **6 cụm phân hệ nghiệp vụ cốt lõi**:

```mermaid
flowchart LR
    %% Khai báo Actors
    A01G["Khách vãng lai<br/>(A01-G: Guest)"]
    A01["Người dùng<br/>(A01: End-User)"]
    A02["Quản trị viên<br/>(A02: Admin)"]
    A03["Dịch vụ Tỷ giá<br/>(A03: Exchange Rate)"]
    SYS["Bộ lập lịch nền<br/>(SYS: Scheduler)"]

    %% Kế thừa tác nhân
    A01G -.->|Kế thừa| A01

    %% Boundary Hệ thống WealthFlow
    subgraph WealthFlow["HỆ THỐNG WEALTHFLOW (SYSTEM BOUNDARY - 13 CORE USE CASES)"]
        direction TB

        PKG1(["Phân hệ 1: Xác thực & Ví tiền<br/><i>[FR01, FR02, FR04 • UC01, UC02]</i>"])
        PKG2(["Phân hệ 2: Expense Tracker (Thu - Chi - Ngân sách)<br/><i>[FR03, FR05, FR06 • UC03, UC04, UC05]</i>"])
        PKG3(["Phân hệ 3: Microfinance (Vay & Nợ vi mô)<br/><i>[FR09 • UC06, UC07]</i>"])
        PKG4(["Phân hệ 4: Personal Wealth (Tài sản & Tích lũy)<br/><i>[FR07, FR08 • UC08, UC09]</i>"])
        PKG5(["Phân hệ 5: Báo cáo, Đối soát & Dashboard<br/><i>[FR10, FR11, FR12 • UC10, UC11]</i>"])
        PKG6(["Phân hệ 6: Quản trị Hệ thống (Admin)<br/><i>[FR13, FR14 • UC12, UC13]</i>"])
    end

    %% Tương tác của Khách vãng lai
    A01G --- PKG1

    %% Tương tác của Người dùng (A01)
    A01 --- PKG1
    A01 --- PKG2
    A01 --- PKG3
    A01 --- PKG4
    A01 --- PKG5

    %% Tương tác của Quản trị viên (A02)
    A02 --- PKG1
    A02 --- PKG6

    %% Tương tác của Dịch vụ bên ngoài
    PKG1 -.- A03
    PKG2 -.- A03

    %% Tương tác của Tiến trình chạy nền (SYS)
    SYS --- PKG2
    SYS --- PKG3
    SYS --- PKG5
```

---

## 4. Sơ đồ Chi tiết theo Phân hệ Nghiệp vụ

---

### 4.1 Phân hệ 1: Xác thực, Hồ sơ Cá nhân & Quản lý Ví tiền
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR01, FR02, FR04, FR15**

```mermaid
flowchart LR
    %% Actors
    Guest["A01-G: Khách vãng lai"]
    User["A01: Người dùng"]
    FX["A03: Exchange Rate API"]

    subgraph Boundary_Auth["PHÂN HỆ XÁC THỰC & QUẢN LÝ VÍ"]
        direction TB

        UC01(["UC01: Đăng ký & Xác thực người dùng"])
        UC01_Hash(["Băm mật khẩu an toàn (Argon2id/bcrypt)"])
        UC01_JWT(["Cấp & Thu hồi Token JWT"])
        UC01_Reset(["Đổi mật khẩu"])

        UC02(["UC02: Quản lý tài khoản thanh toán & ví tiền"])
        UC02_Equity(["Hạch toán Vốn chủ sở hữu đối ứng (BR02)"])
        UC02_Archive(["Lưu trữ ví (Archive)"])
    end

    %% Tương tác
    Guest --> UC01
    User --> UC01
    User --> UC02
    UC02 -.- FX

    %% Include / Extend
    UC01 -.->|"«include»"| UC01_Hash
    UC01 -.->|"«include»"| UC01_JWT
    UC01_Reset -.->|"«extend» [Chủ động đổi MK]"| UC01

    UC02 -.->|"«include» [Số dư ban đầu > 0]"| UC02_Equity
    UC02_Archive -.->|"«extend» [Khi số dư = 0]"| UC02
```

---

### 4.2 Phân hệ 2: Expense Tracker (Giao dịch, Định kỳ & Ngân sách)
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR03, FR05, FR06, FR15**

```mermaid
flowchart LR
    %% Actors
    User["A01: Người dùng"]
    Scheduler["SYS: Scheduler"]

    subgraph Boundary_Expense["PHÂN HỆ EXPENSE TRACKER"]
        direction TB

        UC03(["UC03: Ghi nhận & Quản lý giao dịch tài chính"])
        UC03_DoubleEntry(["Kiểm tra cân bằng Nợ/Có (BR01)"])
        UC03_Transfer(["Chuyển tiền nội bộ giữa 2 ví"])
        UC03_Reverse(["Đảo / Hoàn tác giao dịch (BR06)"])

        UC04(["UC04: Thiết lập & Xử lý giao dịch định kỳ"])
        UC04_AutoPost(["Tự động ghi sổ đúng chu kỳ (BR09)"])

        UC05(["UC05: Thiết lập & Kiểm soát ngân sách chi tiêu"])
        UC05_Alert(["Phát cảnh báo ngưỡng 80% / 100% (BR08)"])
    end

    %% Tương tác
    User --> UC03
    User --> UC04
    User --> UC05
    Scheduler --> UC04
    Scheduler --> UC05

    %% Include / Extend trong UC03
    UC03 -.->|"«include»"| UC03_DoubleEntry
    UC03_Transfer -.->|"«extend» [Chuyển nội bộ]"| UC03
    UC03_Reverse -.->|"«extend» [Hoàn tác sai sót]"| UC03

    %% Include / Extend trong UC04
    UC04_AutoPost -.->|"«extend» [Đến chu kỳ & Bật auto]"| UC04

    %% Include / Extend trong UC05
    UC05 -.->|"«include» [Khi chi tiêu chạm ngưỡng]"| UC05_Alert
```

---

### 4.3 Phân hệ 3: Microfinance (Quản lý Vay & Trả nợ vi mô)
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR09, FR15**

```mermaid
flowchart LR
    %% Actors
    User["A01: Người dùng"]
    Scheduler["SYS: Scheduler"]

    subgraph Boundary_Microfinance["PHÂN HỆ MICROFINANCE (VAY & NỢ VI MÔ)"]
        direction TB

        UC06(["UC06: Quản lý khoản vay & nghĩa vụ nợ vi mô"])
        UC06_Disb(["Ghi nhận nhận tiền giải ngân vào ví (BR17)"])

        UC07(["UC07: Ghi nhận thanh toán trả nợ vay"])
        UC07_Split(["Bóc tách Nợ gốc & Tiền lãi/phí (BR17)"])
        UC07_Settle(["Tất toán khoản nợ (Khi dư nợ = 0)"])

        AlertDebt(["SYS03: Quét & Bắn thông báo nợ sắp đến hạn"])
    end

    %% Tương tác
    User --> UC06
    User --> UC07
    Scheduler --> AlertDebt

    %% Include / Extend trong UC06
    UC06_Disb -.->|"«extend» [Nhận tiền vào ví]"| UC06

    %% Include / Extend trong UC07
    UC07 -.->|"«include»"| UC07_Split
    UC07_Settle -.->|"«extend» [Khi dư nợ gốc = 0]"| UC07
```

---

### 4.4 Phân hệ 4: Personal Wealth (Tài sản tích lũy & Mục tiêu)
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR07, FR08, FR15**

```mermaid
flowchart LR
    %% Actors
    User["A01: Người dùng"]

    subgraph Boundary_Wealth["PHÂN HỆ PERSONAL WEALTH (TÀI SẢN & TÍCH LŨY)"]
        direction TB

        UC08(["UC08: Quản lý tài sản tích lũy ngoài ví - Vàng"])
        UC08_Val(["Cập nhật thị giá vàng (Revaluation)"])

        UC09(["UC09: Quản lý mục tiêu tích lũy tài chính"])
        UC09_Alloc(["Phân bổ tiền tích lũy vào mục tiêu (BR10)"])
        UC09_Target(["Tính số tiền cần dành mỗi tháng"])
    end

    %% Tương tác
    User --> UC08
    User --> UC09

    %% Include / Extend
    UC08_Val -.->|"«extend» [Cập nhật giá định kỳ]"| UC08
    UC09_Alloc -.->|"«extend» [Gán tiền vào quỹ]"| UC09
    UC09 -.->|"«include»"| UC09_Target
```

---

### 4.5 Phân hệ 5: Báo cáo, Đối soát & Dashboard Sức khỏe tài chính
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR10, FR11, FR12, FR15**

```mermaid
flowchart LR
    %% Actors
    User["A01: Người dùng"]
    Scheduler["SYS: Scheduler"]

    subgraph Boundary_Analytics["PHÂN HỆ BÁO CÁO & DASHBOARD"]
        direction TB

        UC10(["UC10: Xem Dashboard sức khỏe tài chính & Net Worth"])
        CalcNetWorth(["Tính toán Net Worth: Assets - Liabilities (BR16)"])
        CalcMetrics(["Tính toán Cash Flow & Saving Rate (BR18)"])
        DailySnapshot(["SYS03: Chụp snapshot Net Worth cuối ngày"])

        UC11(["UC11: Báo cáo thống kê & Xuất dữ liệu CSV"])
        UC11_Recon(["Đối soát số dư bộ đệm vs Sổ cái (BR01)"])
        UC11_Sanitize(["Khử ký tự độc hại chống CSV Injection"])
    end

    %% Tương tác
    User --> UC10
    User --> UC11
    Scheduler --> DailySnapshot

    %% Include / Extend
    UC10 -.->|"«include»"| CalcNetWorth
    UC10 -.->|"«include»"| CalcMetrics
    DailySnapshot -.->|"«include»"| CalcNetWorth

    UC11 -.->|"«include» [Khi xuất file]"| UC11_Sanitize
    UC11_Recon -.->|"«extend» [Chủ động đối soát]"| UC11
```

---

### 4.6 Phân hệ 6: Quản trị Hệ thống (Admin Management)
*Nhóm Yêu cầu Chức năng ánh xạ:* **FR13, FR14, FR15**

```mermaid
flowchart LR
    %% Actors
    Admin["A02: Quản trị viên (Admin)"]

    subgraph Boundary_Admin["PHÂN HỆ QUẢN TRỊ HỆ THỐNG (ADMIN)"]
        direction TB

        UC12(["UC12: Quản trị người dùng & Dashboard thống kê vĩ mô"])
        UC12_Macro(["Xem Dashboard vĩ mô: Lượng User, Tỷ lệ tiền cơ sở (BR11)"])
        UC12_Lock(["Khóa / Mở khóa tài khoản người dùng vi phạm"])
        UC12_Audit(["Ghi nhật ký Admin Audit Log"])

        UC13(["UC13: Cấu hình hệ thống & Phát thông báo người dùng"])
        UC13_Announce(["Soạn & Phát thông báo Broadcast toàn hệ thống"])
        UC13_Config(["Cấu hình danh mục mặc định & Ngưỡng cảnh báo"])
    end

    %% Tương tác
    Admin --> UC12
    Admin --> UC13

    %% Include / Extend
    UC12 -.->|"«include»"| UC12_Macro
    UC12 -.->|"«include»"| UC12_Audit
    UC12_Lock -.->|"«extend» [Xử lý vi phạm]"| UC12

    UC13_Announce -.->|"«extend» [Phát thông báo]"| UC13
    UC13_Config -.->|"«extend» [Cập nhật danh mục mẫu]"| UC13
```

---

## 5. Ma trận Phân bố Use Case theo Gói Phân hệ (Package Allocation Matrix)

| Mã UC | Tên Use Case | Phân hệ (Subsystem) | Sơ đồ Xuất hiện | Tác nhân chính |
| :---: | :--- | :--- | :---: | :---: |
| **UC01** | Đăng ký & Xác thực người dùng | 1. Xác thực & Hồ sơ | Plot 1, Plot 2 | A01-G, A01, A02 |
| **UC02** | Quản lý tài khoản thanh toán và ví tiền | 1. Xác thực & Hồ sơ | Plot 1, Plot 2 | A01: Người dùng |
| **UC03** | Ghi nhận & Quản lý giao dịch tài chính | 2. Expense Tracker | Plot 1, Plot 3 | A01: Người dùng |
| **UC04** | Thiết lập & Xử lý giao dịch định kỳ | 2. Expense Tracker | Plot 1, Plot 3 | A01, SYS |
| **UC05** | Thiết lập & Kiểm soát ngân sách chi tiêu | 2. Expense Tracker | Plot 1, Plot 3 | A01, SYS |
| **UC06** | Quản lý khoản vay và nghĩa vụ nợ vi mô | 3. Microfinance | Plot 1, Plot 4 | A01: Người dùng |
| **UC07** | Ghi nhận thanh toán trả nợ vay | 3. Microfinance | Plot 1, Plot 4 | A01: Người dùng |
| **UC08** | Quản lý tài sản tích lũy ngoài ví (Vàng) | 4. Personal Wealth | Plot 1, Plot 5 | A01: Người dùng |
| **UC09** | Quản lý mục tiêu tích lũy tài chính | 4. Personal Wealth | Plot 1, Plot 5 | A01: Người dùng |
| **UC10** | Xem Dashboard sức khỏe tài chính & Net Worth | 5. Báo cáo & Dashboard | Plot 1, Plot 6 | A01: Người dùng |
| **UC11** | Báo cáo thống kê & Xuất dữ liệu CSV | 5. Báo cáo & Dashboard | Plot 1, Plot 6 | A01: Người dùng |
| **UC12** | Quản trị người dùng & Dashboard vĩ mô | 6. Quản trị Hệ thống | Plot 1, Plot 7 | A02: Admin |
| **UC13** | Cấu hình hệ thống & Phát thông báo người dùng | 6. Quản trị Hệ thống | Plot 1, Plot 7 | A02: Admin |
