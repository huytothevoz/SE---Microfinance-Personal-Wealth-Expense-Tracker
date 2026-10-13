# Sơ đồ Hoạt động (Activity Diagrams) - WealthFlow

> Tham chiếu: [SRS.md](../SRS.md) v2.1 | [usecase_diagram.md](./usecase_diagram.md) | [class_diagram.md](./class_diagram.md) | [sequence_diagram.md](./sequence_diagram.md)

---

## Danh mục 08 Quy trình Hoạt động theo Làn bơi (Swimlanes)

| Mã Sơ đồ | Tên Quy trình Nghiệp vụ | Use Cases bao phủ | Các Làn bơi (Swimlanes theo thứ tự từ Trái sang Phải) |
| :---: | :--- | :---: | :--- |
| **AD01** | Đăng ký, Xác thực & Khởi tạo Không gian Tài chính | `UC01`, `UC02` | `Người dùng (User)` -> `Hệ thống WealthFlow` |
| **AD02** | Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm soát Ngân sách *(Lõi)* | `UC03`, `UC05` | `Người dùng (User)` -> `Hệ thống WealthFlow` |
| **AD03** | Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal) | `UC03` | `Người dùng (User)` -> `Hệ thống WealthFlow` |
| **AD04** | Xử lý Giao dịch Định kỳ Tự động (Subscription Cron) | `UC04`, `SYS02` | `Bộ lập lịch (SYS02)` -> `Hệ thống WealthFlow` -> `Người dùng (User)` |
| **AD05** | Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô *(Microfinance)* | `UC06`, `UC07` | `Người vay / Người dùng` -> `Hệ thống WealthFlow` |
| **AD06** | Quản lý Vàng Tích lũy & Tính toán Sức khỏe Net Worth | `UC08`, `UC10` | `Người dùng (User)` -> `Hệ thống WealthFlow` |
| **AD07** | Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn | `UC11` | `Người dùng (User)` -> `Hệ thống WealthFlow` |
| **AD08** | Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo | `UC12`, `UC13` | `Quản trị viên (Admin)` -> `Hệ thống WealthFlow` -> `Người dùng (User)` |

---

## 3. Nội dung Chi tiết các Sơ đồ Hoạt động Phân làn

---

### AD01: Quy trình Đăng ký, Xác thực & Khởi tạo Không gian Tài chính
- **Use Case liên quan:** `UC01` (Đăng ký & Xác thực), `UC02` (Quản lý ví).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR02` (Số dư đầu kỳ đối ứng Equity), `BR11` (Bảo mật phiên JWT/Argon2id).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người dùng (User / Guest)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI DÙNG (User / Guest)"]
        direction TB
        U01_Start((Bắt đầu))
        U01_FillReg["Nhập thông tin đăng ký:<br/>Họ tên, Email, Mật khẩu, Tiền cơ sở"]
        U01_SubmitReg["Bấm 'Đăng ký tài khoản'"]
        U01_ShowRegErr["Nhận thông báo lỗi:<br/>Email đã tồn tại / Form không hợp lệ"]
        U01_Login["Nhập Email & Mật khẩu đăng nhập"]
        U01_SubmitLogin["Bấm 'Đăng nhập'"]
        U01_ShowLoginErr["Nhận thông báo lỗi đăng nhập /<br/>Tài khoản đã bị tạm khóa"]
        U01_Dashboard["Truy cập Dashboard chính<br/>Sẵn sàng quản lý tài chính"]
        U01_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        S01_CheckReg{"Kiểm tra form &<br/>Email đã tồn tại?"}
        S01_CreateUser["1. Băm mật khẩu bằng Argon2id/bcrypt<br/>2. Tạo User mới (Status = Active)<br/>3. Tự động gán bộ Danh mục mặc định"]
        S01_CheckLogin{"Xác thực mật khẩu &<br/>Trạng thái tài khoản?"}
        S01_IssueJWT["1. Ký số cặp Token (Access + Refresh JWT)<br/>2. Ghi nhật ký Auth Audit Log<br/>3. Trả về thông tin phiên làm việc"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U01_Start --> U01_FillReg --> U01_SubmitReg
    U01_SubmitReg --> S01_CheckReg

    S01_CheckReg -- "Email trùng / Lỗi" --> U01_ShowRegErr
    U01_ShowRegErr --> U01_FillReg

    S01_CheckReg -- "Hợp lệ" --> S01_CreateUser
    S01_CreateUser --> U01_Login

    U01_Login --> U01_SubmitLogin
    U01_SubmitLogin --> S01_CheckLogin

    S01_CheckLogin -- "Sai thông tin / Locked" --> U01_ShowLoginErr
    U01_ShowLoginErr --> U01_Login

    S01_CheckLogin -- "Hợp lệ (Active)" --> S01_IssueJWT
    S01_IssueJWT --> U01_Dashboard --> U01_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD01_Dang_Ky_Va_Xac_Thuc
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người dùng (User)|
start
:Nhập thông tin đăng ký (Họ tên, Email, Mật khẩu, Tiền cơ sở);
:Bấm "Đăng ký tài khoản";

|#E0F2FE|Hệ thống WealthFlow|
if (Email đã tồn tại hoặc form không hợp lệ?) then (Có lỗi)
  |#F3E8FF|Người dùng (User)|
  :Nhận thông báo lỗi (Email đã tồn tại);
  stop
else (Hợp lệ)
  |#E0F2FE|Hệ thống WealthFlow|
  :Băm mật khẩu bằng Argon2id/bcrypt;
  :Tạo tài khoản User mới (Status = Active);
  :Sao chép bộ Danh mục Thu/Chi mặc định;
  
  |#F3E8FF|Người dùng (User)|
  :Nhập Email & Mật khẩu để đăng nhập;
  :Bấm "Đăng nhập";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Thông tin hợp lệ và tài khoản Active?) then (Không)
    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo lỗi đăng nhập hoặc tài khoản bị khóa;
    stop
  else (Đúng)
    |#E0F2FE|Hệ thống WealthFlow|
    :Ký số cặp JWT Tokens (Access Token + Refresh Token);
    :Ghi nhật ký Auth Audit Log;

    |#F3E8FF|Người dùng (User)|
    :Truy cập Dashboard chính thành công;
    stop
  endif
endif
@enduml
```
</details>

---

### AD02: Quy trình Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm soát Ngân sách
- **Use Case liên quan:** `UC03` (Ghi nhận giao dịch), `UC05` (Kiểm soát ngân sách), `SYS03` (Sự kiện kiểm tra ngân sách).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR06` (Bất biến sổ cái), `BR07` (Công thức ngân sách), `BR08` (Cảnh báo 80%/100%), `BR13` (Idempotency).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người dùng (User)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI DÙNG (User)"]
        direction TB
        U02_Start((Bắt đầu))
        U02_Select["Chọn chức năng 'Ghi nhận Chi tiêu'"]
        U02_Fill["Nhập form: Ví thanh toán, Danh mục, Số tiền,<br/>Ngày phát sinh, Ghi chú"]
        U02_Save["Bấm 'Lưu Giao Dịch'"]
        U02_ShowBalErr["Nhận thông báo lỗi:<br/>Số dư ví không đủ để chi trả"]
        U02_Success["Nhận thông báo thành công:<br/>• Số dư ví cập nhật giảm tức thì<br/>• Giao dịch xuất hiện trong lịch sử"]
        U02_Alert["Nhận thông báo Cảnh báo Ngân sách:<br/>Tiệm cận 80% (Vàng) hoặc Vượt 100% (Đỏ)"]
        U02_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        S02_CheckFund{"Kiểm tra số dư khả dụng:<br/>Ví nguồn >= Số tiền chi?"}
        S02_AtomicTx["THỰC THI ACID TRANSACTION NGUYÊN TỬ:<br/>1. Tạo Transaction trạng thái Posted<br/>2. Ghi Nợ: Expense Account (Chi phí)<br/>3. Ghi Có: Asset Account (Ví thanh toán)<br/>4. Giảm số dư bộ đệm của ví"]
        S02_CheckBudget{"Tính mức sử dụng Ngân sách tháng:<br/>Usage = (Chi tiêu / Hạn mức) * 100%"}
        S02_NoAlert["Usage < 80%:<br/>Mức an toàn (Tiến độ Xanh)"]
        S02_TriggerYellow["80% <= Usage < 100%:<br/>Kích hoạt Cảnh báo Vàng"]
        S02_TriggerRed["Usage >= 100%:<br/>Kích hoạt Cảnh báo Đỏ (Vượt ngân sách)"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U02_Start --> U02_Select --> U02_Fill --> U02_Save
    U02_Save --> S02_CheckFund

    S02_CheckFund -- "Không đủ tiền" --> U02_ShowBalErr
    U02_ShowBalErr --> U02_End

    S02_CheckFund -- "Đủ số dư" --> S02_AtomicTx
    S02_AtomicTx --> U02_Success

    S02_AtomicTx --> S02_CheckBudget
    S02_CheckBudget -- "< 80%" --> S02_NoAlert --> U02_End
    S02_CheckBudget -- "80% - 100%" --> S02_TriggerYellow --> U02_Alert
    S02_CheckBudget -- ">= 100%" --> S02_TriggerRed --> U02_Alert

    U02_Success --> U02_End
    U02_Alert --> U02_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD02_Ghi_Nhan_Chi_Tieu_Va_Ngan_Sach
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người dùng (User)|
start
:Chọn chức năng "Ghi nhận Chi tiêu";
:Nhập Ví thanh toán, Danh mục, Số tiền, Ngày phát sinh, Ghi chú;
:Bấm "Lưu Giao Dịch";

|#E0F2FE|Hệ thống WealthFlow|
if (Số dư ví khả dụng >= Số tiền chi?) then (Không đủ)
  |#F3E8FF|Người dùng (User)|
  :Nhận thông báo lỗi "Số dư ví không đủ chi trả";
  stop
else (Đủ số dư)
  |#E0F2FE|Hệ thống WealthFlow|
  :Tạo Transaction trạng thái Posted;
  :Hạch toán kép: Ghi Nợ Chi phí / Ghi Có Ví;
  :Cập nhật giảm số dư bộ đệm ví;
  
  |#F3E8FF|Người dùng (User)|
  :Nhận thông báo lưu thành công & Số dư cập nhật;

  |#E0F2FE|Hệ thống WealthFlow|
  :Tính mức sử dụng ngân sách tháng:
  Usage = (Chi tiêu / Hạn mức) * 100%;
  if (Đánh giá mức Usage?) then (< 80%)
    :Trạng thái an toàn (Không phát cảnh báo);
    stop
  else if (80% <= Usage < 100%) then (Cận ngưỡng)
    :Tạo Cảnh báo Vàng (Tiệm cận 80%);
    |#F3E8FF|Người dùng (User)|
    :Nhận chuông thông báo Cảnh báo Vàng;
    stop
  else (>= 100%)
    |#E0F2FE|Hệ thống WealthFlow|
    :Tạo Cảnh báo Đỏ (Vượt hạn mức ngân sách);
    |#F3E8FF|Người dùng (User)|
    :Nhận chuông thông báo Cảnh báo Đỏ;
    stop
  endif
endif
@enduml
```
</details>

---

### AD03: Quy trình Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal)
- **Use Case liên quan:** `UC03` (Quản lý giao dịch).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR05` (Chuyển tiền không ảnh hưởng Thu/Chi), `BR06` (Bất biến và Đảo giao dịch), `BR13` (Idempotency).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người dùng (User)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI DÙNG (User)"]
        direction TB
        U03_Start((Bắt đầu))
        U03_Choice{"Lựa chọn nghiệp vụ?"}

        %% Chuyển tiền
        U03_InputTrans["Nhập: Ví nguồn, Ví đích, Số tiền, Diễn giải"]
        U03_SubmitTrans["Bấm 'Xác nhận chuyển tiền'"]
        U03_TransErr["Nhận thông báo lỗi:<br/>Ví nguồn không đủ tiền / Trùng ví"]
        U03_TransSuccess["Nhận thông báo chuyển tiền thành công:<br/>Cả 2 ví cập nhật số dư (Net Worth không đổi)"]

        %% Đảo giao dịch
        U03_SelectPosted["Chọn giao dịch Posted cần hủy/sửa"]
        U03_SubmitRev["Nhập lý do & Bấm 'Hoàn tác giao dịch'"]
        U03_RevErr["Nhận thông báo lỗi:<br/>Giao dịch không hợp lệ hoặc đã bị đảo"]
        U03_RevSuccess["Nhận thông báo hoàn tác thành công:<br/>Tiền được hoàn lại đầy đủ vào ví"]

        U03_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        %% Xử lý Chuyển tiền
        S03_CheckTrans{"Ví nguồn != Ví đích &<br/>Số dư Ví nguồn >= Số tiền?"}
        S03_ExecTrans["THỰC THI CHUYỂN TIỀN NGUYÊN TỬ:<br/>1. Ghi Nợ: Asset Account (Ví đích)<br/>2. Ghi Có: Asset Account (Ví nguồn)<br/>3. Tăng số dư Ví đích, Giảm Ví nguồn<br/>(CẤM tính vào Thu nhập hay Chi phí)"]

        %% Xử lý Đảo giao dịch
        S03_CheckRev{"Trạng thái == Posted &<br/>chưa từng bị đảo (isReversed=false)?"}
        S03_ExecRev["THỰC THI ĐẢO GIAO DỊCH NGUYÊN TỬ:<br/>1. Đánh dấu bản gốc: isReversed = true<br/>2. Tạo Transaction mới: type = REVERSAL<br/>3. Sinh các dòng Nợ/Có đảo chiều đối ứng<br/>4. Hoàn trả số dư ví về ban đầu"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U03_Start --> U03_Choice

    %% Nhánh 1: Chuyển tiền
    U03_Choice -- "Chuyển tiền nội bộ" --> U03_InputTrans --> U03_SubmitTrans --> S03_CheckTrans
    S03_CheckTrans -- "Lỗi số dư / Trùng ví" --> U03_TransErr --> U03_End
    S03_CheckTrans -- "Hợp lệ" --> S03_ExecTrans --> U03_TransSuccess --> U03_End

    %% Nhánh 2: Đảo giao dịch
    U03_Choice -- "Hoàn tác / Đảo giao dịch" --> U03_SelectPosted --> U03_SubmitRev --> S03_CheckRev
    S03_CheckRev -- "Không hợp lệ" --> U03_RevErr --> U03_End
    S03_CheckRev -- "Hợp lệ" --> S03_ExecRev --> U03_RevSuccess --> U03_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD03_Chuyen_Tien_Va_Dao_Giao_Dich
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người dùng (User)|
start
if (Nghiệp vụ lựa chọn?) then (Chuyển tiền nội bộ)
  :Nhập Ví nguồn, Ví đích, Số tiền, Diễn giải;
  :Bấm "Xác nhận chuyển tiền";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Ví nguồn != Ví đích và đủ số dư?) then (Không)
    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo lỗi chuyển tiền;
    stop
  else (Đủ điều kiện)
    |#E0F2FE|Hệ thống WealthFlow|
    :Hạch toán: Nợ Ví đích / Có Ví nguồn;
    :Tăng số dư Ví đích & Giảm số dư Ví nguồn;
    note right: Thu nhập & Chi phí KHÔNG đổi

    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo thành công & Số dư cập nhật;
    stop
  endif

else (Hoàn tác / Đảo giao dịch)
  |#F3E8FF|Người dùng (User)|
  :Chọn giao dịch Posted cần hủy/sửa;
  :Nhập lý do đảo và bấm "Hoàn tác giao dịch";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Trạng thái là Posted và chưa từng bị đảo?) then (Không thỏa)
    |#F3E8FF|Người dùng (User)|
    :Báo lỗi giao dịch không hợp lệ để đảo;
    stop
  else (Hợp lệ)
    |#E0F2FE|Hệ thống WealthFlow|
    :Đánh dấu giao dịch gốc: isReversed = true;
    :Tạo giao dịch mới kiểu REVERSAL (link bản gốc);
    :Sinh các dòng Nợ/Có đối ứng đảo ngược;
    :Hoàn trả số dư ví về ban đầu;

    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo hoàn tác thành công & Tiền hoàn ví;
    stop
  endif
endif
@enduml
```
</details>

---

### AD04: Quy trình Xử lý Giao dịch Định kỳ Tự động (Subscription Cron)
- **Use Case liên quan:** `UC04` (Giao dịch định kỳ), `SYS02` (Tiến trình quét subscription).
- **Quy tắc nghiệp vụ:** `BR07` (Ngân sách), `BR09` (Tính toán ngày neo và lùi ngày cuối tháng).
- **Phân làn Actor (3 Cột từ Trái sang Phải):**
  - **Làn 1 (Trái):** `Bộ lập lịch nền (SYS02 Scheduler)`
  - **Làn 2 (Giữa):** `Hệ thống WealthFlow (System)`
  - **Làn 3 (Phải):** `Người dùng (User)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: BỘ LẬP LỊCH NỀN (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_Scheduler["LÀN 1: BỘ LẬP LỊCH NỀN (SYS02)"]
        direction TB
        CRON04_Start((Kích hoạt 01:00 AM))
        CRON04_Scan["Quét các Subscription đến hạn:<br/>Status = ACTIVE & nextDueDate <= Hôm nay"]
        CRON04_Done["Hoàn tất đợt quét tự động<br/>Ghi nhật ký hệ thống SYS02"]
        CRON04_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (Ở GIỮA)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        S04_CheckAuto{"Bật tùy chọn<br/>'Tự động ghi sổ'<br/>(isAutoPost)?"}
        S04_CheckFund{"Ví thanh toán<br/>đủ số dư khả dụng?"}
        S04_MakePosted["Tự động tạo giao dịch POSTED:<br/>Nợ Chi phí / Có Ví & Trừ tiền ví"]
        S04_MakeDraftErr["Chuyển sang bản NHÁP (Draft)<br/>do ví không đủ số dư"]
        S04_MakeDraftNormal["Tạo bản NHÁP (Draft)<br/>chờ người dùng duyệt thủ công"]
        S04_CalcNext["Tính ngày đến hạn kế tiếp (NextDueDate):<br/>Dựa trên anchorDay gốc (1 - 31)"]
        S04_CheckShortMonth{"Tháng tiếp theo<br/>thiếu ngày so với<br/>anchorDay?"}
        S04_AdjustLast["Lùi về ngày cuối cùng của tháng<br/>(Ví dụ: 31/01 -> 28/02)"]
        S04_KeepAnchor["Đặt ngày đến hạn = anchorDay<br/>(Ví dụ: 28/02 -> 31/03)"]
        S04_UpdateSub["Cập nhật Subscription:<br/>nextDueDate mới và lastExecutedAt"]
    end

    %% =========================================================================
    %% LÀN 3: NGƯỜI DÙNG (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_User["LÀN 3: NGƯỜI DÙNG (User)"]
        direction TB
        U04_NotifSuccess["Nhận thông báo thành công:<br/>'Đã tự động thanh toán khoản định kỳ'"]
        U04_NotifAlert["Nhận cảnh báo khẩn cấp:<br/>'Ví thiếu tiền thanh toán khoản định kỳ!'"]
        U04_NotifReminder["Nhận thông báo nhắc nhở:<br/>'Đến hạn thanh toán, vui lòng vào duyệt'"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA CÁC LÀN BƠI
    %% =========================================================================
    CRON04_Start --> CRON04_Scan --> S04_CheckAuto

    S04_CheckAuto -- "BẬT (True)" --> S04_CheckFund
    S04_CheckFund -- "Đủ tiền" --> S04_MakePosted --> U04_NotifSuccess
    S04_CheckFund -- "Thiếu tiền" --> S04_MakeDraftErr --> U04_NotifAlert

    S04_CheckAuto -- "TẮT (False)" --> S04_MakeDraftNormal --> U04_NotifReminder

    S04_MakePosted --> S04_CalcNext
    S04_MakeDraftErr --> S04_CalcNext
    S04_MakeDraftNormal --> S04_CalcNext

    S04_CalcNext --> S04_CheckShortMonth
    S04_CheckShortMonth -- "Thiếu ngày" --> S04_AdjustLast --> S04_UpdateSub
    S04_CheckShortMonth -- "Đủ ngày" --> S04_KeepAnchor --> S04_UpdateSub

    S04_UpdateSub --> CRON04_Done --> CRON04_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_Scheduler fill:#FEF3C7,stroke:#D97706,stroke-width:2px,color:#78350F
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD04_Xu_Ly_Giao_Dich_Dinh_Ky
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#FEF3C7|Bộ lập lịch nền (SYS02)|
start
:Kích hoạt lúc 01:00 AM hàng ngày;
:Quét các Subscription có Status = ACTIVE và nextDueDate <= Hôm nay;

|#E0F2FE|Hệ thống WealthFlow|
if (Bật tùy chọn "Tự động ghi sổ"?) then (Có)
  if (Số dư ví khả dụng >= Số tiền?) then (Đủ tiền)
    :Tạo giao dịch POSTED;
    :Hạch toán Nợ Chi phí / Có Ví & Trừ tiền ví;
    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo "Đã tự động thanh toán khoản định kỳ";
  else (Thiếu tiền)
    |#E0F2FE|Hệ thống WealthFlow|
    :Tạo giao dịch ở trạng thái NHÁP (Draft);
    |#F3E8FF|Người dùng (User)|
    :Nhận cảnh báo khẩn cấp "Ví thiếu tiền thanh toán định kỳ";
  endif
else (Không)
  |#E0F2FE|Hệ thống WealthFlow|
  :Tạo giao dịch ở trạng thái NHÁP (Draft);
  |#F3E8FF|Người dùng (User)|
  :Nhận thông báo nhắc nhở "Đến hạn thanh toán, vui lòng duyệt";
endif

|#E0F2FE|Hệ thống WealthFlow|
:Tính ngày đến hạn kế tiếp (NextDueDate) theo anchorDay;
if (Tháng tiếp theo có đủ số ngày anchorDay?) then (Tháng thiếu ngày)
  :Lùi về ngày cuối cùng của tháng (Ví dụ: 31/01 -> 28/02);
else (Đủ ngày)
  :Đặt ngày đến hạn đúng bằng anchorDay gốc (28/02 -> 31/03);
endif
:Cập nhật nextDueDate mới vào Subscription;

|#FEF3C7|Bộ lập lịch nền (SYS02)|
:Ghi log hệ thống hoàn tất đợt quét;
stop
@enduml
```
</details>

---

### AD05: Quy trình Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô
- **Use Case liên quan:** `UC06` (Khởi tạo khoản vay), `UC07` (Thanh toán trả nợ).
- **Quy tắc nghiệp vụ:** `BR09` (Lịch trả nợ), `BR10` (Phân định Nợ vs Chi phí), `BR17` (Nguyên lý kế toán vi mô cho Vay mượn).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người vay / Người dùng (Borrower / User)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI VAY / NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI VAY / NGƯỜI DÙNG (User)"]
        direction TB
        U05_Start((Bắt đầu))
        U05_Action{"Chọn tác vụ vi mô?"}

        %% Tạo khoản vay
        U05_InputLoan["Nhập: Tên khoản vay, Gốc ban đầu, Đối tác,<br/>Ngày vay, Ngày đáo hạn, Tùy chọn 'Giải ngân vào ví'"]
        U05_SubmitLoan["Bấm 'Tạo Khoản Vay'"]
        U05_LoanDone["Xem hồ sơ vay mới:<br/>• Total Liabilities tăng<br/>• Ví tăng tiền nếu có giải ngân<br/>• Thu nhập tuyệt đối KHÔNG tăng (BR17)"]

        %% Trả nợ
        U05_SelectLoan["Chọn khoản vay Active cần thanh toán"]
        U05_InputRepay["Nhập: Tiền trả gốc, Tiền trả lãi/phí,<br/>Ví thanh toán, Ngày trả"]
        U05_SubmitRepay["Bấm 'Xác nhận Trả Nợ'"]
        U05_RepayErr["Nhận thông báo lỗi:<br/>Gốc trả vượt dư nợ / Ví không đủ tiền"]
        U05_RepayDone["Xem kết quả thanh toán:<br/>• Dư nợ giảm dần (hoặc Tất toán nếu = 0)<br/>• Ví giảm tiền = Gốc + Lãi<br/>• Chi phí chỉ ghi nhận phần LÃI"]

        U05_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        %% Xử lý tạo khoản vay
        S05_CheckDisburse{"Có tích chọn<br/>'Giải ngân vào ví'?"}
        S05_ExecDisburse["THỰC THI GIẢI NGÂN NGUYÊN TỬ:<br/>1. Tạo Loan với remainingPrincipal = Gốc<br/>2. Tăng số dư Ví nhận tiền<br/>3. Hạch toán: Nợ Ví / Có Liability<br/>(CẤM ghi nhận vào Thu nhập theo BR17)"]
        S05_ExecLoanOnly["Tạo Loan theo dõi nghĩa vụ nợ<br/>Tăng Total Liabilities (Không tăng ví)"]

        %% Xử lý trả nợ
        S05_CheckRepay{"Kiểm tra điều kiện trả nợ:<br/>1. Gốc trả <= Dư nợ gốc còn lại?<br/>2. Số dư ví >= (Gốc + Lãi)?"}
        S05_ExecRepay["THỰC THI TRẢ NỢ NGUYÊN TỬ:<br/>1. Giảm số dư Ví = (Gốc + Lãi)<br/>2. Giảm dư nợ: remaining = remaining - Gốc<br/>3. Hạch toán Chi phí tài chính = Tiền lãi/phí<br/>4. Lưu bản ghi lịch sử LoanRepayments"]
        S05_CheckPaidOff{"Dư nợ gốc còn lại = 0?"}
        S05_MarkPaidOff["Đổi trạng thái: PAID_OFF (Tất toán xong)"]
        S05_KeepActive["Giữ trạng thái: ACTIVE (Còn dư nợ)"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U05_Start --> U05_Action

    %% Nhánh 1: Tạo khoản vay
    U05_Action -- "Khởi tạo khoản vay mới" --> U05_InputLoan --> U05_SubmitLoan --> S05_CheckDisburse
    S05_CheckDisburse -- "Có giải ngân" --> S05_ExecDisburse --> U05_LoanDone --> U05_End
    S05_CheckDisburse -- "Không giải ngân" --> S05_ExecLoanOnly --> U05_LoanDone --> U05_End

    %% Nhánh 2: Thanh toán trả nợ
    U05_Action -- "Thanh toán trả nợ" --> U05_SelectLoan --> U05_InputRepay --> U05_SubmitRepay --> S05_CheckRepay
    S05_CheckRepay -- "Lỗi gốc / Thiếu tiền ví" --> U05_RepayErr --> U05_End
    S05_CheckRepay -- "Hợp lệ" --> S05_ExecRepay --> S05_CheckPaidOff

    S05_CheckPaidOff -- "Đúng (= 0)" --> S05_MarkPaidOff --> U05_RepayDone --> U05_End
    S05_CheckPaidOff -- "Sai (> 0)" --> S05_KeepActive --> U05_RepayDone --> U05_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD05_Khoi_Tao_Vay_Va_Tra_No_Vi_Mo
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người vay / Người dùng|
start
if (Nghiệp vụ lựa chọn?) then (Khởi tạo khoản vay mới)
  :Nhập Tên khoản vay, Gốc ban đầu, Đối tác, Ngày vay, Ngày hạn;
  :Lựa chọn "Giải ngân vào ví"?;
  :Bấm "Tạo Khoản Vay";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Có tích chọn giải ngân vào ví?) then (Có)
    :Tạo Loan (remaining = Gốc ban đầu);
    :Tăng số dư Ví nhận tiền giải ngân;
    :Hạch toán: Nợ Ví / Có Liability;
    note right: Tuyệt đối CẤM ghi vào Thu nhập (BR17)
  else (Không)
    :Tạo Loan ghi nhận nghĩa vụ nợ;
    :Tăng Total Liabilities;
  endif

  |#F3E8FF|Người vay / Người dùng|
  :Xem hồ sơ khoản vay và dư nợ ban đầu;
  stop

else (Thanh toán trả nợ từng kỳ)
  |#F3E8FF|Người vay / Người dùng|
  :Chọn khoản vay Active cần trả;
  :Nhập Tiền trả gốc, Tiền trả lãi/phí, Ví thanh toán, Ngày trả;
  :Bấm "Xác nhận Trả Nợ";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Gốc trả <= Dư nợ còn lại và Ví đủ tiền?) then (Không thỏa)
    |#F3E8FF|Người dùng (User)|
    :Nhận thông báo lỗi (Gốc vượt nợ hoặc thiếu tiền ví);
    stop
  else (Hợp lệ)
    |#E0F2FE|Hệ thống WealthFlow|
    :Giảm số dư Ví = (Tiền gốc + Tiền lãi);
    :Giảm dư nợ gốc: remaining = remaining - Gốc trả;
    :Hạch toán Chi phí tài chính = Tiền lãi/phí;
    :Lưu bản ghi lịch sử LoanRepayment;

    if (Dư nợ gốc còn lại == 0?) then (Hết nợ)
      :Đổi trạng thái khoản vay: PAID_OFF (Tất toán);
    else (Còn nợ)
      :Giữ trạng thái khoản vay: ACTIVE;
    endif

    |#F3E8FF|Người vay / Người dùng|
    :Xem kết quả thanh toán & Dư nợ giảm dần;
    stop
  endif
endif
@enduml
```
</details>

---

### AD06: Quy trình Quản lý Vàng Tích lũy & Tính toán Sức khỏe Net Worth
- **Use Case liên quan:** `UC08` (Tài sản vàng), `UC10` (Dashboard sức khỏe tài chính & Net Worth).
- **Quy tắc nghiệp vụ:** `BR02` (Vốn chủ sở hữu), `BR03` & `BR04` (Định giá vàng), `BR16` (Công thức Net Worth), `BR18` (An toàn tỷ lệ tiết kiệm).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người dùng (User)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI DÙNG (User)"]
        direction TB
        U06_Start((Bắt đầu))
        U06_Choice{"Lựa chọn nghiệp vụ?"}

        %% Vàng
        U06_InputGold["Nhập: Loại vàng (Nhẫn 9999/SJC), Số chỉ, Thị giá"]
        U06_SubmitGold["Bấm 'Lưu Tài Sản Vàng'"]
        U06_RevalGold["Cập nhật thị giá mới (Revaluation) theo giá thị trường"]
        U06_GoldDone["Xem danh mục vàng cập nhật & Tổng tài sản tăng"]

        %% Dashboard
        U06_OpenDash["Truy cập màn hình Dashboard Tổng quan"]
        U06_SeeDash["Nắm toàn bộ bức tranh tài chính trên 1 màn hình:<br/>• Total Assets, Liabilities, Net Worth<br/>• Net Cash Flow & Biểu đồ xu hướng<br/>• Badge Saving Rate (Xanh/Đỏ/N/A)"]

        U06_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        %% Xử lý vàng
        S06_SaveGold["Lưu bản ghi GoldAsset hoặc GoldRevaluation<br/>Cập nhật Total Assets & Net Worth"]

        %% Xử lý Dashboard
        S06_CalcTotals["1. Tổng hợp TOTAL ASSETS = Ví tài sản + Toàn bộ Vàng<br/>2. Tổng hợp TOTAL LIABILITIES = Thẻ tín dụng + Dư nợ vay<br/>3. Tính NET WORTH = Total Assets - Total Liabilities<br/>4. Tính CASH FLOW = Total Income - Total Expense"]
        S06_CheckIncome{"Tổng Thu nhập trong kỳ?<br/>(Total Income)"}
        S06_SetSavingNA["Gán Saving Rate = 'N/A'<br/>(Ngăn chặn lỗi chia cho 0 theo BR18)"]
        S06_CalcSaving["Tính Saving Rate:<br/>Saving Rate = ((Income - Expense) / Income) * 100%"]
        S06_RenderDash["Đóng gói dữ liệu Dashboard & Vẽ biểu đồ xu hướng"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U06_Start --> U06_Choice

    %% Nhánh 1: Thêm/Sửa Vàng
    U06_Choice -- "Thêm tài sản vàng" --> U06_InputGold --> U06_SubmitGold --> S06_SaveGold
    U06_Choice -- "Cập nhật thị giá vàng" --> U06_RevalGold --> S06_SaveGold
    S06_SaveGold --> U06_GoldDone --> U06_End

    %% Nhánh 2: Xem Dashboard
    U06_Choice -- "Xem Dashboard Tài chính" --> U06_OpenDash --> S06_CalcTotals --> S06_CheckIncome

    S06_CheckIncome -- "Income <= 0" --> S06_SetSavingNA --> S06_RenderDash
    S06_CheckIncome -- "Income > 0" --> S06_CalcSaving --> S06_RenderDash

    S06_RenderDash --> U06_SeeDash --> U06_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD06_Quan_Ly_Vang_Va_Net_Worth
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người dùng (User)|
start
if (Tác vụ thực hiện?) then (Thêm hoặc Định giá lại Vàng)
  :Nhập loại vàng, số chỉ, thị giá quy đổi;
  :Bấm Lưu hoặc Cập nhật thị giá (Revaluation);

  |#E0F2FE|Hệ thống WealthFlow|
  :Lưu bản ghi GoldAsset hoặc GoldRevaluation;
  :Cập nhật Total Assets và Net Worth;

  |#F3E8FF|Người dùng (User)|
  :Xem danh mục tài sản tích lũy cập nhật;
  stop

else (Xem Dashboard Sức khỏe Tài chính)
  |#F3E8FF|Người dùng (User)|
  :Truy cập trang Dashboard;

  |#E0F2FE|Hệ thống WealthFlow|
  :Tính TOTAL ASSETS = Tổng ví tài sản + Giá trị vàng;
  :Tính TOTAL LIABILITIES = Thẻ tín dụng + Dư nợ vay;
  :Tính NET WORTH = Total Assets - Total Liabilities;
  :Tính CASH FLOW = Total Income - Total Expense;

  if (Total Income trong kỳ?) then (<= 0)
    :Gán Saving Rate = "N/A" (Tránh lỗi chia 0 theo BR18);
  else (> 0)
    :Tính Saving Rate = ((Income - Expense) / Income) * 100%;
  endif

  :Đóng gói dữ liệu hiển thị Cards và Biểu đồ;

  |#F3E8FF|Người dùng (User)|
  :Xem toàn cảnh sức khỏe tài chính trên 1 giao diện;
  stop
endif
@enduml
```
</details>

---

### AD07: Quy trình Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn
- **Use Case liên quan:** `UC11` (Báo cáo & Xuất dữ liệu).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR12` (Đối soát số dư), `BR14` (Kiểm toán), `BR18` (Chuẩn mã hóa UTF-8 BOM).
- **Phân làn Actor:**
  - **Làn 1 (Trái):** `Người dùng (User)`
  - **Làn 2 (Phải):** `Hệ thống WealthFlow (System)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: NGƯỜI DÙNG (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_User["LÀN 1: NGƯỜI DÙNG (User)"]
        direction TB
        U07_Start((Bắt đầu))
        U07_Choice{"Chọn chức năng báo cáo?"}

        %% Đối soát
        U07_ClickReconcile["Chọn ví & Bấm 'Đối soát số dư Sổ cái'"]
        U07_SeeReconcile["Xem báo cáo kết quả đối soát:<br/>Khớp đúng 100% (Delta = 0) hoặc Có sai lệch"]

        %% Xuất CSV
        U07_SetFilters["Thiết lập bộ lọc: Khoảng ngày, Ví, Danh mục"]
        U07_ClickExport["Bấm 'Xuất báo cáo sang CSV'"]
        U07_DownloadCSV["Tải file CSV về máy & Mở bằng Microsoft Excel<br/>Hiển thị tiếng Việt có dấu chuẩn xác"]

        U07_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        %% Xử lý đối soát
        S07_GetBuffer["Lấy số dư hiện tại từ bộ đệm Ví (cachedBalance)"]
        S07_SumLedger["Tính tổng lũy kế toàn bộ Journal Entries của ví:<br/>Sum = Sum(Debit Lines) - Sum(Credit Lines)"]
        S07_Compare{"cachedBalance == Sum(Ledger)?"}
        S07_Matched["Xác nhận: Sổ cái toàn vẹn tuyệt đối (Delta = 0)"]
        S07_Mismatched["Cảnh báo: Phát hiện sai lệch số dư (Delta != 0)<br/>Ghi Security Audit Log"]

        %% Xử lý xuất CSV
        S07_FetchData["Truy vấn danh sách giao dịch thỏa mãn bộ lọc"]
        S07_Sanitize["Vệ sinh dữ liệu (Sanitization) chống CSV Injection:<br/>Nếu ô văn bản bắt đầu bằng =, +, -, @<br/>thì tự động thêm dấu nháy đơn ' ở đầu (FR12.04)"]
        S07_AddBOM["Gắn ký tự UTF-8 BOM (\uFEFF) vào đầu file<br/>để Excel tự động nhận diện font tiếng Việt"]
        S07_StreamCSV["Truyền tải Stream tệp CSV về máy khách"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA 2 LÀN BƠI
    %% =========================================================================
    U07_Start --> U07_Choice

    %% Nhánh 1: Đối soát
    U07_Choice -- "Đối soát số dư sổ cái" --> U07_ClickReconcile --> S07_GetBuffer --> S07_SumLedger --> S07_Compare
    S07_Compare -- "Khớp 100%" --> S07_Matched --> U07_SeeReconcile --> U07_End
    S07_Compare -- "Sai lệch" --> S07_Mismatched --> U07_SeeReconcile --> U07_End

    %% Nhánh 2: Xuất CSV
    U07_Choice -- "Xuất dữ liệu CSV" --> U07_SetFilters --> U07_ClickExport --> S07_FetchData --> S07_Sanitize --> S07_AddBOM --> S07_StreamCSV --> U07_DownloadCSV --> U07_End

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD07_Doi_Soat_Va_Xuat_CSV
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#F3E8FF|Người dùng (User)|
start
if (Chức năng thực thi?) then (Đối soát số dư sổ cái)
  :Chọn ví và bấm "Đối soát số dư Sổ cái";

  |#E0F2FE|Hệ thống WealthFlow|
  :Lấy số dư bộ đệm Ví (cachedBalance);
  :Tính tổng lũy kế toàn bộ Journal Entries của ví;
  if (cachedBalance == Sum(JournalEntries)?) then (Khớp 100%)
    :Xác nhận sổ cái toàn vẹn tuyệt đối (Delta = 0);
  else (Lệch)
    :Cảnh báo phát hiện lệch số dư (Delta != 0);
    :Ghi nhật ký Security Audit Log;
  endif

  |#F3E8FF|Người dùng (User)|
  :Xem báo cáo kết quả đối soát trên màn hình;
  stop

else (Xuất báo cáo sang CSV)
  |#F3E8FF|Người dùng (User)|
  :Thiết lập bộ lọc (Ngày, Ví, Danh mục);
  :Bấm "Xuất file CSV";

  |#E0F2FE|Hệ thống WealthFlow|
  :Truy vấn danh sách giao dịch thỏa mãn;
  :Khử ký tự nguy hiểm (Thêm dấu ' nếu bắt đầu bằng =, +, -, @);
  :Gắn ký tự UTF-8 BOM (\uFEFF) vào đầu file;
  :Truyền tải file Stream về trình duyệt;

  |#F3E8FF|Người dùng (User)|
  :Tải file CSV về máy và mở trên Microsoft Excel an toàn;
  stop
endif
@enduml
```
</details>

---

### AD08: Quy trình Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo
- **Use Case liên quan:** `UC12` (Quản trị người dùng & Thống kê vĩ mô), `UC13` (Cấu hình hệ thống & Phát thông báo).
- **Quy tắc nghiệp vụ:** `BR11` (Bảo vệ quyền riêng tư người dùng), `BR13` (Quản trị cấu hình & Kiểm toán hành chính).
- **Phân làn Actor (3 Cột từ Trái sang Phải):**
  - **Làn 1 (Trái):** `Quản trị viên (Admin A02)`
  - **Làn 2 (Giữa):** `Hệ thống WealthFlow (System)`
  - **Làn 3 (Phải):** `Người dùng hệ thống (End-User)`

#### Sơ đồ Mermaid (Swimlanes từ Trái sang Phải)

```mermaid
flowchart LR
    %% =========================================================================
    %% LÀN 1: QUẢN TRỊ VIÊN (BÊN TRÁI)
    %% =========================================================================
    subgraph Lane_Admin["LÀN 1: QUẢN TRỊ VIÊN (Admin)"]
        direction TB
        A08_Start((Bắt đầu))
        A08_Choice{"Lựa chọn tác vụ Quản trị?"}

        %% Xem thống kê
        A08_ViewMacro["Xem Dashboard thống kê vĩ mô:<br/>Tổng User, User mới, Tỷ lệ tiền cơ sở<br/>(CẤM xem số dư hay giao dịch cá nhân)"]

        %% Khóa tài khoản
        A08_SelectUser["Tìm kiếm & Chọn User vi phạm chính sách"]
        A08_EnterReason["Nhập lý do khóa bắt buộc trong Popup"]
        A08_ConfirmLock["Bấm 'Xác nhận Khóa Tài khoản'"]
        A08_LockErr["Nhận thông báo lỗi:<br/>Thiếu lý do / Không được khóa Admin cuối cùng"]
        A08_LockSuccess["Nhận thông báo khóa thành công<br/>Trạng thái User chuyển sang LOCKED"]

        %% Phát thông báo
        A08_InputBroadcast["Nhập Tiêu đề, Nội dung, Mức độ ưu tiên"]
        A08_SendBroadcast["Bấm 'Phát thông báo toàn hệ thống'"]
        A08_BroadcastDone["Nhận xác nhận thông báo đã phát thành công"]

        A08_End(((Kết thúc)))
    end

    %% =========================================================================
    %% LÀN 2: HỆ THỐNG WEALTHFLOW (Ở GIỮA)
    %% =========================================================================
    subgraph Lane_System["LÀN 2: HỆ THỐNG WEALTHFLOW (System)"]
        direction TB
        %% Khóa tài khoản logic
        S08_CheckLockEligible{"Lý do khóa != rỗng &<br/>User không phải là<br/>Admin duy nhất còn lại?"}
        S08_ExecLock["1. Cập nhật User status = LOCKED<br/>2. Thu hồi tức thì toàn bộ JWT Token & Session<br/>3. Ghi nhật ký Admin Audit Log"]

        %% Broadcast logic
        S08_CheckBroadcast{"Tiêu đề & Nội dung != rỗng?"}
        S08_ExecBroadcast["1. Lưu SystemAnnouncement mới<br/>2. Đẩy thông báo vào hàng đợi Notification<br/>của toàn bộ User đang Active"]
    end

    %% =========================================================================
    %% LÀN 3: NGƯỜI DÙNG HỆ THỐNG (BÊN PHẢI)
    %% =========================================================================
    subgraph Lane_User["LÀN 3: NGƯỜI DÙNG HỆ THỐNG (End-User)"]
        direction TB
        U08_SessionRevoked["Bị thu hồi phiên đăng nhập ngay lập tức<br/>Các yêu cầu tiếp theo bị từ chối 403 Forbidden"]
        U08_SeeNotif["Chuông thông báo (Notification Center) sáng lên<br/>Xem thông báo hệ thống mới từ Admin"]
    end

    %% =========================================================================
    %% LUỒNG TƯƠNG TÁC QUA LẠI GIỮA CÁC LÀN BƠI
    %% =========================================================================
    A08_Start --> A08_Choice

    %% Nhánh 1: Thống kê vĩ mô
    A08_Choice -- "Xem thống kê vĩ mô" --> A08_ViewMacro --> A08_End

    %% Nhánh 2: Khóa tài khoản
    A08_Choice -- "Khóa tài khoản vi phạm" --> A08_SelectUser --> A08_EnterReason --> A08_ConfirmLock --> S08_CheckLockEligible
    S08_CheckLockEligible -- "Không hợp lệ" --> A08_LockErr --> A08_End
    S08_CheckLockEligible -- "Hợp lệ" --> S08_ExecLock --> A08_LockSuccess --> A08_End
    S08_ExecLock -.-> U08_SessionRevoked

    %% Nhánh 3: Phát thông báo
    A08_Choice -- "Phát thông báo Broadcast" --> A08_InputBroadcast --> A08_SendBroadcast --> S08_CheckBroadcast
    S08_CheckBroadcast -- "Hợp lệ" --> S08_ExecBroadcast --> A08_BroadcastDone --> A08_End
    S08_ExecBroadcast -.-> U08_SeeNotif

    %% =========================================================================
    %% ĐỊNH DẠNG MÀU SẮC LÀN BƠI
    %% =========================================================================
    style Lane_Admin fill:#FEE2E2,stroke:#DC2626,stroke-width:2px,color:#991B1B
    style Lane_System fill:#E0F2FE,stroke:#0284C7,stroke-width:2px,color:#075985
    style Lane_User fill:#F3E8FF,stroke:#9333EA,stroke-width:2px,color:#581C87
```

<details>
<summary><b>Xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml AD08_Quan_Tri_Nguoi_Dung_Va_Thong_Bao
skinparam swimlaneBorderColor #4A90E2
skinparam swimlaneTitleFontStyle bold
skinparam roundCorner 10

|#FEE2E2|Quản trị viên (Admin)|
start
if (Tác vụ quản trị?) then (Xem Dashboard vĩ mô)
  :Xem tổng User, User mới, Tỷ lệ tiền cơ sở;
  note right: Tuyệt đối CẤM xem số dư hay sổ cái cá nhân (BR11)
  stop

else if (Khóa tài khoản vi phạm) then (Khóa tài khoản)
  :Tìm kiếm và chọn User vi phạm;
  :Nhập lý do khóa bắt buộc;
  :Bấm "Xác nhận Khóa Tài khoản";

  |#E0F2FE|Hệ thống WealthFlow|
  if (Lý do rỗng hoặc User là Admin cuối cùng?) then (Lỗi)
    |#FEE2E2|Quản trị viên (Admin)|
    :Nhận thông báo lỗi thao tác;
    stop
  else (Hợp lệ)
    |#E0F2FE|Hệ thống WealthFlow|
    :Cập nhật User status = LOCKED;
    :Thu hồi toàn bộ Refresh Token của User;
    :Ghi nhật ký Admin Audit Log;

    |#FEE2E2|Quản trị viên (Admin)|
    :Nhận thông báo khóa tài khoản thành công;

    |#F3E8FF|Người dùng hệ thống (End-User)|
    :Bị đăng xuất ngay lập tức (API từ chối 403);
    stop
  endif

else (Phát thông báo Broadcast)
  |#FEE2E2|Quản trị viên (Admin)|
  :Nhập Tiêu đề, Nội dung, Mức độ ưu tiên;
  :Bấm "Phát thông báo toàn hệ thống";

  |#E0F2FE|Hệ thống WealthFlow|
  :Tạo bản ghi SystemAnnouncement mới;
  :Đẩy thông báo vào hàng đợi của toàn bộ User Active;

  |#FEE2E2|Quản trị viên (Admin)|
  :Nhận xác nhận phát thông báo thành công;

  |#F3E8FF|Người dùng hệ thống (End-User)|
  :Nhận thông báo mới trên chuông Notification Center;
  stop
endif
@enduml
```
</details>

---

## 4. Ma trận Phân bổ Làn bơi & Use Cases (Swimlane Allocation Matrix)

| Mã Sơ đồ | Tên Quy trình Nghiệp vụ | Làn 1 (Bên trái) | Làn 2 (Ở giữa) | Làn 3 (Bên phải) | Use Cases bao phủ |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **AD01** | Đăng ký & Xác thực Tài khoản | `Người dùng (User)` | `Hệ thống WealthFlow` | *(Không có)* | `UC01`, `UC02` |
| **AD02** | Ghi nhận Chi tiêu & Ngân sách | `Người dùng (User)` | `Hệ thống WealthFlow` | *(Không có)* | `UC03`, `UC05` |
| **AD03** | Chuyển tiền & Đảo Giao dịch | `Người dùng (User)` | `Hệ thống WealthFlow` | *(Không có)* | `UC03` |
| **AD04** | Giao dịch Định kỳ Tự động | `Bộ lập lịch (SYS02)` | `Hệ thống WealthFlow` | `Người dùng (User)` | `UC04`, `SYS02` |
| **AD05** | Vay nợ Vi mô & Thanh toán | `Người vay / User` | `Hệ thống WealthFlow` | *(Không có)* | `UC06`, `UC07` |
| **AD06** | Quản lý Vàng & Net Worth | `Người dùng (User)` | `Hệ thống WealthFlow` | *(Không có)* | `UC08`, `UC10` |
| **AD07** | Đối soát Sổ cái & Xuất CSV | `Người dùng (User)` | `Hệ thống WealthFlow` | *(Không có)* | `UC11` |
| **AD08** | Quản trị & Phát Thông báo | `Quản trị viên (Admin)` | `Hệ thống WealthFlow` | `Người dùng (User)` | `UC12`, `UC13` |

---

## 5. Kết luận và Bước kế tiếp trong Tiến trình Unified Process

Tài liệu **Activity Diagrams with Actor Swimlanes** trên đã chuẩn hóa toàn bộ luồng nghiệp vụ theo đúng mẫu phân làn tác nhân (Actor Swimlanes) trực quan, mạch lạc, dễ hiểu cho cả sinh viên, giảng viên và hội đồng nghiệm thu.

Theo tiến trình Unified Process (luồng kỹ nghệ *Analysis & Design*), sau khi đã hoàn thiện Use Case Diagram và Activity Diagram, bước tiếp theo là:
1. **Sequence Diagrams (Sơ đồ Tuần tự):** Mô hình hóa thông điệp truyền nhận giữa các đối tượng phân tích:
   $$\text{Actor} \longrightarrow \text{Boundary/Controller} \longrightarrow \text{Control/Service} \longrightarrow \text{Entity/Repository} \longrightarrow \text{Database}$$
2. **Design Class Diagram (Sơ đồ Lớp thiết kế):** Định nghĩa các thuộc tính, phương thức, quan hệ kết tập và kế thừa chi tiết.
3. **Database Schema / ERD:** Thiết kế cơ sở dữ liệu vật lý PostgreSQL cho mô hình kế toán kép và microfinance.
