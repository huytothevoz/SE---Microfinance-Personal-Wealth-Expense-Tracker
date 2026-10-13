# SQ06: Quản lý Vàng & Tổng hợp Net Worth Dashboard

> Tham chiếu: [SRS.md](../../SRS.md) v2.1 | [sequence_diagram.md](../sequence_diagram.md) | [class_diagram.md](../class_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ06** |
| **Tên Kịch bản** | **Cập nhật thị giá vàng tích lũy (Revaluation) & Tổng hợp Dashboard Sức khỏe Tài chính Net Worth** |
| **Use Cases bao phủ** | `UC08` (Quản lý tài sản tích lũy ngoài ví - Vàng), `UC10` (Xem Dashboard sức khỏe tài chính & Net Worth) |
| **Quy tắc Nghiệp vụ (BR)** | `BR02` (Vốn chủ sở hữu), `BR03` & `BR04` (Định giá vàng), `BR16` (Công thức Net Worth), `BR18` (An toàn tỷ lệ tiết kiệm) |
| **Yêu cầu Chức năng (FR)** | `FR08.01` - `FR08.05`, `FR10.01` - `FR10.03`, `FR11.01` - `FR11.03`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa 2 luồng xử lý: (1) Cập nhật thị giá mới cho vàng nhẫn 9999/SJC và lưu vết lịch sử Revaluation; (2) Truy vấn song song dữ liệu tài chính đa nguồn: Tổng số dư ví tài sản + Giá trị vàng thị trường $\rightarrow$ Total Assets; Tổng nợ thẻ tín dụng + Dư nợ gốc các khoản vay $\rightarrow$ Total Liabilities; Tính toán Net Worth = Total Assets - Total Liabilities; Tính Cash Flow; và xử lý an toàn ngoại lệ Saving Rate khi Thu nhập $\le 0$ trả về "N/A" theo BR18 tránh lỗi chia cho 0. |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **User** | Actor | Người dùng cập nhật thị giá vàng hoặc mở xem màn hình Dashboard. |
| **DashboardController** | Boundary | Endpoint tiếp nhận HTTP GET `/api/dashboard/overview`. |
| **GoldController** | Boundary | Endpoint tiếp nhận HTTP PUT `/api/assets/gold/{id}/revalue`. |
| **AnalyticsService** | Control | Tổng hợp chỉ số tài chính, tính toán Net Worth, Cash Flow và Saving Rate. |
| **GoldService** | Control | Quản lý bản ghi vàng tích lũy và lưu trữ các phiên bản định giá lại. |
| **AssetRepository** | Entity / Persistence | Truy vấn đa nguồn số dư ví, tài sản vàng, dư nợ vay và doanh số thu/chi. |
| **PostgreSQL Database** | Storage | Cơ sở dữ liệu quan hệ cung cấp các bảng dữ liệu và view tổng hợp. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User)
    participant DC as DashboardController
    participant GC as GoldController
    participant AnS as AnalyticsService
    participant GS as GoldService
    participant AR as AssetRepository
    participant DB as PostgreSQL Database

    %% Luồng Định giá lại Vàng
    rect rgb(254, 243, 199)
        note over User, DB: 1. KỊCH BẢN CẬP NHẬT THỊ GIÁ VÀNG (GOLD REVALUATION)
        User ->> GC: PUT /api/assets/gold/{id}/revalue (newMarketPrice, revalDate)
        activate GC
        GC ->> GS: revalueGoldAsset(userId, goldId, dto)
        activate GS
        GS ->> AR: saveRevaluationRecord(goldId, newPrice, revalDate)
        activate AR
        AR ->> DB: BEGIN TRANSACTION
        AR ->> DB: INSERT INTO gold_revaluations (gold_id, market_price, revalued_at)
        AR ->> DB: UPDATE gold_assets SET current_market_value = newPrice WHERE id = goldId
        AR ->> DB: COMMIT TRANSACTION
        DB -->> AR: Success
        AR -->> GS: updatedGoldAsset
        deactivate AR
        GS -->> GC: GoldResponse(goldId, currentMarketValue)
        deactivate GS
        GC -->> User: 200 OK (Cập nhật thị giá thành công, Total Assets tăng)
        deactivate GC
    end

    %% Luồng Tổng hợp Dashboard Net Worth
    rect rgb(240, 253, 244)
        note over User, DB: 2. KỊCH BẢN TỔNG HỢP DASHBOARD SỨC KHỎE TÀI CHÍNH & NET WORTH
        User ->> DC: GET /api/dashboard/overview?period=month
        activate DC
        DC ->> AnS: getFinancialHealthOverview(userId, 'month')
        activate AnS

        %% Truy vấn song song các cấu phần tài chính
        par Truy vấn Tổng ví tài sản
            AnS ->> AR: getTotalWalletAssets(userId)
            activate AR
            AR ->> DB: SELECT SUM(balance) FROM wallets WHERE user_id = ? AND type != 'CREDIT'
            DB -->> AR: walletAssetsTotal (ví dụ: 50.000.000 VND)
            AR -->> AnS: walletAssetsTotal
            deactivate AR
        and Truy vấn Tổng giá trị vàng
            AnS ->> AR: getTotalGoldAssets(userId)
            activate AR
            AR ->> DB: SELECT SUM(current_market_value) FROM gold_assets WHERE user_id = ?
            DB -->> AR: goldAssetsTotal (ví dụ: 17.000.000 VND)
            AR -->> AnS: goldAssetsTotal
            deactivate AR
        and Truy vấn Tổng nợ phải trả
            AnS ->> AR: getTotalLiabilities(userId)
            activate AR
            AR ->> DB: SELECT (SELECT COALESCE(SUM(balance),0) FROM wallets WHERE type='CREDIT') + (SELECT COALESCE(SUM(remaining_principal),0) FROM loans WHERE status='ACTIVE')
            DB -->> AR: totalLiabilities (ví dụ: 15.000.000 VND)
            AR -->> AnS: totalLiabilities
            deactivate AR
        and Truy vấn Dòng tiền trong tháng
            AnS ->> AR: getCashFlowMonthly(userId, month)
            activate AR
            AR ->> DB: SELECT total_income, total_expense FROM monthly_cashflow_view WHERE ...
            DB -->> AR: CashFlowData(income = 20.000.000, expense = 12.000.000)
            AR -->> AnS: CashFlowData
            deactivate AR
        end

        %% Tính toán các chỉ số tài chính
        AnS ->> AnS: calculateTotalAssets() -> 50tr + 17tr = 67.000.000 VND
        AnS ->> AnS: calculateNetWorth() -> Total Assets (67tr) - Total Liabilities (15tr) = 52.000.000 VND
        AnS ->> AnS: calculateCashFlow() -> Income (20tr) - Expense (12tr) = +8.000.000 VND

        %% Xử lý an toàn tỷ lệ tiết kiệm (BR18)
        alt Total Income <= 0
            AnS ->> AnS: setSavingRate("N/A") (Tránh lỗi chia cho 0 an toàn theo BR18)
        else Total Income > 0
            AnS ->> AnS: calculateSavingRate() -> ((20tr - 12tr) / 20tr) * 100% = 40%
        end

        AnS -->> DC: FinancialOverviewData(TotalAssets, TotalLiabilities, NetWorth, CashFlow, SavingRate)
        deactivate AnS
        DC -->> User: 200 OK (Render Dashboard hoàn chỉnh: Cards chỉ số, Biểu đồ biến động Net Worth)
        deactivate DC
    end
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ06_Quan_Ly_Vang_Va_Net_Worth
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "DashboardController" as DC
participant "AnalyticsService" as AnS
participant "AssetRepository" as AR
database "PostgreSQL Database" as DB

User -> DC: GET /api/dashboard/overview?period=month
activate DC
DC -> AnS: getFinancialHealthOverview(userId, 'month')
activate AnS

par Tổng hợp Tài sản Ví & Vàng
  AnS -> AR: getTotalAssets(userId)
  activate AR
  AR -> DB: SELECT SUM(balance) FROM wallets ...
  AR -> DB: SELECT SUM(current_market_value) FROM gold_assets ...
  DB --> AR: TotalAssets
  AR --> AnS: TotalAssets
  deactivate AR
else Tổng hợp Tổng nợ
  AnS -> AR: getTotalLiabilities(userId)
  activate AR
  AR -> DB: SELECT SUM(remaining_principal) FROM loans ...
  DB --> AR: TotalLiabilities
  AR --> AnS: TotalLiabilities
  deactivate AR
else Dòng tiền thu chi
  AnS -> AR: getMonthlyCashFlow(userId)
  activate AR
  AR -> DB: SELECT total_income, total_expense FROM ...
  DB --> AR: CashFlowData(Income, Expense)
  AR --> AnS: CashFlowData
  deactivate AR
end

AnS -> AnS: NetWorth = TotalAssets - TotalLiabilities
AnS -> AnS: CashFlow = Income - Expense

alt Income <= 0
  AnS -> AnS: SavingRate = "N/A" (Tránh chia 0 theo BR18)
else Income > 0
  AnS -> AnS: SavingRate = ((Income - Expense) / Income) * 100%
end

AnS --> DC: FinancialOverviewData
deactivate AnS
DC --> User: 200 OK (Hiển thị Dashboard toàn cảnh)
deactivate DC
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ05: Khởi tạo Khoản vay & Trả nợ Vi mô](./sq05_microfinance_loan.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ07: Báo cáo Đối soát Sổ cái & Xuất CSV](./sq07_reconciliation_csv.md)
