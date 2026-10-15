# WEALTHFLOW
## CODING CONVENTION & CONSTRUCTION DEVELOPMENT STANDARD
### Quy chuẩn mã nguồn và triển khai phần mềm – Pha Construction

**Sản phẩm:** WealthFlow – Hệ thống quản lý tài chính cá nhân, tài sản và tài chính vi mô
**Nhóm:** VOZ
**Phiên bản:** 1.0 – Đề xuất áp dụng
**Ngày:** 13/10/2026
**Tình trạng:** Construction baseline – cần nhóm phê duyệt trước khi triển khai
**Định dạng:** Quy ước áp dụng cho web frontend, PHP backend, MySQL và quy trình làm việc nhóm.

> **Lưu ý về phạm vi:** Đây là tài liệu quy chuẩn **đề xuất để triển khai** dựa trên SRS pha Elaboration, không phải bằng chứng rằng các lớp, bảng dữ liệu hay các công cụ được nêu đã được nhóm hiện thực. Mọi thay đổi về stack/kiến trúc hoặc phạm vi nghiệp vụ phải được nhóm duyệt và cập nhật SRS/Architecture Decision Record (ADR) tương ứng.

---

# 0. Kiểm soát tài liệu và cách sử dụng

| Trường | Thông tin |
|---|---|
| Document ID | WF-CONV-001 |
| Tài liệu nền tảng chính | `02_elaboration/SRS.md` – SRS WealthFlow, pha Elaboration |
| Tài liệu tham chiếu bổ sung | `01_inception/ Business-Modelling.md`; `00_concept/ideas.md`; `02_elaboration/uml_modeling/sequence_diagram/*.md` |
| Vai trò áp dụng | Tất cả thành viên lập trình, kiểm thử, review mã nguồn |
| Giai đoạn | Construction; duy trì trong Transition khi sửa lỗi |
| Cơ chế duyệt | Tối thiểu 01 người viết, 01 người review; trưởng nhóm phê duyệt thay đổi quy tắc |
| Chỉ báo tuân thủ | Pass lint + tests + PR checklist + yêu cầu truy vết |

### 0.1. Mục tiêu

1. Thống nhất ngôn ngữ, công nghệ và cách tổ chức source code trước khi chia việc.
2. Biến các quyết định kiến trúc của SRS thành quy định lập trình kiểm tra được.
3. Giảm lỗi bảo mật, sai số tiền, ghi trùng hoặc mất cân bằng sổ kế toán kép.
4. Giữ các module độc lập hợp lý để 4 thành viên có thể làm song song.
5. Tạo tiêu chuẩn review, nghiệm thu và truy vết từ Requirement → Code → Test.

### 0.2. Phạm vi và thuật ngữ tuân thủ

- **MUST (Bắt buộc):** Vi phạm phải sửa trước khi merge; ngoại lệ cần ADR/PR chấp thuận.
- **SHOULD (Khuyến nghị):** Được phép khác nếu ghi rõ lý do trong PR.
- **MAY (Tùy chọn):** Công nghệ/tính năng thêm khi có nhu cầu và được đánh giá rủi ro.
- **Demo:** Mã ví dụ dùng để minh họa convention, không đồng nghĩa một chức năng đã hoàn thiện.
- Quy tắc quan trọng có mã `WF-xx`, liên kết `BRxx`, `FRxx`, `UCxx`, `SQxx`, `NFRxx` trong SRS khi phù hợp.

### 0.3. Thứ tự ưu tiên khi tài liệu mâu thuẫn

1. SRS Elaboration đang được nhóm phê duyệt (đặc biệt BR và NFR).
2. Kiến trúc/Sơ đồ tuần tự Elaboration hiện hành và ADR đã phê duyệt.
3. Tài liệu Coding Convention này.
4. Quy định chuẩn cộng đồng (PHP-FIG, MDN, OWASP, MySQL).

**Điểm cần thống nhất:** `NhomVozSRS.md` là bản mô tả có cấu trúc và phạm vi khác SRS Elaboration mới; không tự động lấy FR14 “nhập giao dịch từ ảnh” hoặc Backup/Restore Web UI làm phạm vi Construction nếu chưa có change request. Tài liệu pha Elaboration mới xác định 15 FR, 13 core UC, 8 sơ đồ tuần tự và 3 scheduled services.

# 1. Chốt ngôn ngữ, công nghệ và công cụ

## 1.1. Technology stack khuyến nghị

| Lớp/hoạt động | Công nghệ | Trạng thái | Lý do |
|---|---|---|---|
| Giao diện | HTML5 semantic | MUST | Cấu trúc form, bảng, điều hướng, accessibility |
| Giao diện | CSS3 thuần; CSS Variables; BEM | MUST | Responsive, theme, giảm phụ thuộc thư viện |
| Giao diện | JavaScript ES Modules (ES2022) | MUST | Fetch API, kiểm tra form, hiển thị dữ liệu API |
| Máy chủ | PHP **8.2 trở lên** | MUST | OOP, typed properties, REST API, dễ vận hành trên XAMPP |
| Truy cập CSDL | PDO + `pdo_mysql` | MUST | Prepared statement, transaction, exception |
| Số học tiền | `ext-bcmath` + chuỗi thập phân | MUST | Tránh `float/double`; đáp ứng BR03 |
| Database | MySQL 8.0+ / InnoDB | MUST | DECIMAL chính xác, khóa mức dòng, FK, ACID |
| Quản lý gói PHP | Composer + PSR-4 | MUST | Quản lý JWT, PHPUnit, autoload |
| API payload | JSON, UTF-8 | MUST | Hợp đồng giao tiếp theo IF01 |
| Máy chủ web | Apache tại dev/prod tương thích PHP | MUST | Public document root và rewrite route API |
| Phát triển cục bộ | XAMPP / PHP CLI / VS Code | SHOULD | Khởi động nhanh, kiểm tra thuận tiện |
| Kiểm tra PHP | PHP-CS-Fixer (PSR-12), PHPStan | SHOULD | Chuẩn định dạng và kiểm tra tĩnh |
| Kiểm thử PHP | PHPUnit | MUST | Kiểm chứng nghiệp vụ, coverage NFR04 |
| Kiểm tra JS | ESLint + Prettier | SHOULD | Nhất quán JavaScript/HTML/CSS |
| Kiểm thử trình duyệt | Playwright / manual checklist | SHOULD | Xác thực UI responsive, luồng chính |
| Tác vụ nền | PHP CLI + Task Scheduler/cron | MUST cho SYS01–03 | Không chạy cron nhờ request HTTP |
| Version control | Git, GitHub Pull Request | MUST | Làm việc nhóm và lịch sử thay đổi |

**Không đưa vào baseline:** TypeScript, React, Vue, Node.js backend, Python, Java, Redis, Docker, microservices, ORM phức tạp. Chúng không bị cấm tuyệt đối nhưng chỉ dùng nếu có ADR phê duyệt, chứng minh lợi ích và kế hoạch kiểm thử/tích hợp. Node.js có thể được dùng **chỉ để chạy ESLint/Prettier**, không phải backend WealthFlow.

### 1.2. Các loại “ngôn ngữ” cần phân biệt

- **HTML**: ngôn ngữ đánh dấu; **CSS**: ngôn ngữ định kiểu.
- **JavaScript**: ngôn ngữ lập trình frontend.
- **PHP**: ngôn ngữ lập trình backend.
- **SQL**: ngôn ngữ truy vấn cơ sở dữ liệu MySQL.
- **JSON**: định dạng trao đổi dữ liệu API, không phải ngôn ngữ lập trình độc lập.
- **Markdown, `.env`, YAML (nếu dùng)**: tài liệu/cấu hình; không phải runtime ứng dụng.

### 1.3. Quyết định triển khai xác thực theo SRS

Theo SQ01/NFR02, lựa chọn **JWT** với vòng đời có thể thu hồi: Access Token có thời hạn tối đa 60 phút, giữ **trong bộ nhớ JavaScript**; Refresh Token được lưu trong cookie `HttpOnly; Secure; SameSite` trên HTTPS. Backend duy trì phiên/refresh-token record để hỗ trợ thu hồi khi logout, đổi mật khẩu, khóa tài khoản. Refresh endpoint dùng cơ chế bảo vệ CSRF phù hợp; mọi endpoint ghi khi sử dụng xác thực cookie đều phải được bảo vệ CSRF. **Không lưu token trong `localStorage` hoặc `sessionStorage`.**

Bản triển khai có thể dùng thư viện JWT PHP đã được kiểm tra và khóa version trong `composer.lock` (ví dụ `firebase/php-jwt`). Không tự viết thư viện mật mã/JWT. Trong dev HTTP tại `localhost`, cơ chế cookie `Secure` và test đăng nhập phải được cấu hình môi trường dev rõ ràng; không nới lỏng cấu hình production.

## 1.4. Ràng buộc đã chốt từ SRS cần được giữ

| Cụm yêu cầu | Nguyên tắc Construction |
|---|---|
| BR01, BR02, BR05 | Sổ cái kép; debit/credit đúng loại tài khoản và cân bằng theo base currency |
| BR03, BR04, BR15 | Không dùng floating-point cho tiền; snapshot tỷ giá; bình quân giá sổ |
| BR06, BR14 | `Posted` bất biến; sửa bằng reversal; `Draft` không vào báo cáo |
| BR07, BR08 | Ngân sách theo tháng/danh mục, cảnh báo 80% và 100%, chống trùng |
| BR09 | Mỗi `(subscription_id, due_date)` xử lý một lần |
| BR10, BR16, BR17 | Goal không sinh thêm tài sản; Net Worth không tính trùng; trả gốc khác lãi |
| BR11, FR15 | Mọi dữ liệu tài chính phải lọc `user_id`; Admin không xem sổ cá nhân |
| BR12, BR13 | ACID, lock ví, Idempotency-Key theo user + nội dung |
| IF01, NFR02 | JSON API, HTTPS production, chuẩn mã lỗi, bảo vệ mật khẩu/token |
| NFR04 | Tests cho Application/Domain đạt mục tiêu coverage ≥80% |

# 2. Kiến trúc Construction và tổ chức mã nguồn

## 2.1. Nguyên tắc tổng quát

**WF-ARCH-01 (MUST).** Giữ kiến trúc **Layered + Modular Monolith**: cùng một backend deploy nhưng tách module theo nghiệp vụ, không tạo một tệp `index.php` chứa mọi câu lệnh và HTML.

**WF-ARCH-02 (MUST).** Tách bốn trách nhiệm trong mỗi module:

| Tầng | Thư mục ví dụ | Trách nhiệm | Không được làm |
|---|---|---|---|
| Presentation / Interface | `Http/` | Route, auth guard, DTO validation, trả status JSON | SQL và tính toán kế toán trực tiếp |
| Application | `Application/` | Điều phối use case, unit of work, gọi repository, sự kiện | Trực tiếp xuất HTML |
| Domain | `Domain/` | Quy tắc BR, Money, Ledger, Value Object | Phụ thuộc `$_POST`, PDO, session, SQL |
| Infrastructure | `Infrastructure/` | PDO, truy vấn, external exchange rate, logging | Tự ý quyết định business rule |

**WF-ARCH-03 (MUST).** Frontend chỉ hiển thị, kiểm tra trải nghiệm và gửi lệnh; **backend là nguồn sự thật duy nhất** về số dư, quyền truy cập, khóa giao dịch, ngân sách, Net Worth. Không tính số dư đáng tin cậy bằng các bản ghi lấy từ browser.

**WF-ARCH-04 (SHOULD).** Hạn chế phụ thuộc chéo giữa modules; service module này gọi public application service/interface module kia khi thực sự cần; không truy cập trực tiếp các bảng thuộc module khác một cách tùy tiện.

## 2.2. Cây thư mục đề xuất

```text
wealthflow/
├── README.md
├── .gitignore
├── .editorconfig
├── composer.json
├── composer.lock
├── phpunit.xml.dist
├── .env.example
├── .github/workflows/ci.yml
├── public/                       # Web document root
│   ├── index.php                 # Front controller PHP
│   ├── index.html                 # Nếu dùng frontend tĩnh cùng origin
│   └── assets/
│       ├── css/
│       │   ├── variables.css
│       │   ├── base.css
│       │   └── components/
│       ├── js/
│       │   ├── api/http-client.js
│       │   ├── features/transactions/
│       │   ├── features/budgets/
│       │   └── main.js
│       └── images/
├── src/
│   ├── Shared/
│   │   ├── Domain/Money.php
│   │   ├── Database/ConnectionFactory.php
│   │   ├── Http/ApiResponse.php
│   │   └── Exceptions/
│   └── Modules/
│       ├── Auth/
│       ├── Wallets/
│       ├── Transactions/
│       │   ├── Http/TransactionController.php
│       │   ├── Application/PostExpenseService.php
│       │   ├── Domain/LedgerPolicy.php
│       │   └── Infrastructure/PdoTransactionRepository.php
│       ├── Budgets/
│       ├── Recurring/
│       ├── Loans/
│       ├── Wealth/
│       ├── Goals/
│       ├── Reports/
│       └── Admin/
├── config/                        # Server-side configuration only
├── database/
│   ├── migrations/
│   ├── seeds/
│   └── schema.md
├── scripts/
│   ├── sync-exchange-rates.php    # SYS01
│   ├── process-recurring.php     # SYS02
│   └── remind-loans-snapshot.php # SYS03
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── docs/
│   ├── srs-traceability.md
│   ├── api-contract.md
│   ├── adr/
│   └── coding-convention.md
└── storage/
    └── logs/                     # Không public; không commit
```

*Quy ước kỹ thuật:* Web server chỉ được expose `public/`. Nếu XAMPP cần đặt mã nguồn trong `htdocs`, thiết lập `DocumentRoot`/VirtualHost trỏ vào `public/` hoặc đảm bảo mã nguồn và `.env` hoàn toàn không thể tải trực tiếp từ web; không dùng root thư mục source làm public root. Không để thư mục `vendor/`, `storage/`, `config/`, `.git/`, `.env` trong đường dẫn public.

**WF-ARCH-05 (MUST).** Ánh xạ tên module tới SRS: `Auth (FR01)`, `Wallets (FR02)`, `Transactions (FR03/04)`, `Budgets (FR05)`, `Recurring (FR06)`, `Goals (FR07)`, `Wealth (FR08/10)`, `Loans (FR09)`, `Reports (FR11/12)`, `Admin (FR13/14)`, security xuyên suốt `FR15`.

**WF-ARCH-06 (SHOULD).** Trong SQ02/SQ03 có `LedgerService`, SRS kiến trúc gọi `TransactionService`: nhóm chốt `PostExpenseService`/`TransferService` là *use case service*, `LedgerPostingService` là dịch vụ ghi sổ chung; tránh hai service cùng chịu trách nhiệm commit độc lập.

# 3. Quy chuẩn chung về file, naming, format và comments

## 3.1. Bảng đặt tên

| Đối tượng | Quy tắc | Ví dụ hợp lệ |
|---|---|---|
| PHP class/interface/trait | PascalCase, tên danh từ, phản ánh trách nhiệm | `PostExpenseService`, `WalletRepository` |
| PHP method/variable | camelCase, method là động từ | `postExpense()`, `$remainingPrincipal` |
| PHP constant | UPPER_SNAKE_CASE | `MAX_LOGIN_ATTEMPTS` |
| PHP namespace | PSR-4, PascalCase, khớp thư mục | `WealthFlow\Modules\Budgets\Domain` |
| PHP file chứa class | PascalCase.php trùng class | `BudgetService.php` |
| HTML id | kebab-case | `budget-form` |
| HTML data attribute | `data-*`, kebab-case | `data-wallet-id` |
| CSS class | BEM, kebab-case | `.budget-card__amount--warning` |
| JavaScript file | kebab-case | `budget-list.js`, `http-client.js` |
| JavaScript variable/function | camelCase | `totalExpense`, `renderBudgetList()` |
| JS constant | UPPER_SNAKE_CASE | `API_TIMEOUT_MS` |
| MySQL table/column/index | snake_case, table danh từ số nhiều | `ledger_entries`, `created_at`, `idx_wallets_user_id` |
| API URI | danh từ số nhiều; kebab-case | `/api/v1/financial-goals` |
| JSON property | camelCase | `walletId`, `amountBase` |
| Git branch | `feature/`, `fix/`, `docs/` + ID | `feature/uc03-post-expense` |
| Unit test | theo chức năng, phản ánh điều kiện | `testPostedEntryCannotBeEdited()` |

**WF-GEN-01 (MUST).** Identifier sử dụng **tiếng Anh** nhất quán. UI, thông báo dành cho người dùng và tài liệu có thể dùng tiếng Việt đúng dấu/UTF-8.

**WF-GEN-02 (MUST).** Dùng tên có nghĩa: `$walletBalance` thay vì `$a`, `$data1`; không trộn `customerId`/`user_id` trong cùng layer. Ánh xạ JSON camelCase ↔ DB snake_case tại Repository/Mapper.

**WF-GEN-03 (MUST).** File nguồn, JSON, migration, nội dung CSDL dùng UTF-8; line ending LF, newline cuối file; indent 4 spaces cho PHP và 2 spaces cho HTML/CSS/JS/JSON.

**WF-GEN-04 (SHOULD).** Mỗi function làm một việc rõ; tránh hàm dài (> 40–60 dòng code logic là dấu hiệu cần tách); tránh nesting >3 cấp; dùng early return.

**WF-GEN-05 (MUST).** Không commit code tạm (`var_dump`, `die`, `console.log` dữ liệu nhạy cảm), magic number về quy tắc tài chính, comment bỏ quên, hardcoded secrets.

## 3.2. Comments và tài liệu hàm

- Comment giải thích **vì sao** (nghiệp vụ, edge case, rủi ro đồng thời), không thuật lại từng dòng code.
- Hàm public quan trọng nên có PHPDoc mô tả contract/exception; nêu BR/FR ở vị trí có ý nghĩa.
- Không dùng comment thay thế test; thay đổi logic phải cập nhật test và SRS traceability.
- Không ghi token, mật khẩu, giá trị nhạy cảm vào comment hay ví dụ commit.

```php
<?php

declare(strict_types=1);

/**
 * BR08: Cảnh báo Đỏ có ưu tiên cao hơn Vàng khi nhảy vượt hai ngưỡng.
 * Trả về tên mức cảnh báo, không phát notification trực tiếp tại đây.
 */
function classifyBudgetUsage(string $spent, string $limit): string
{
    if (bccomp($limit, '0', 2) <= 0) {
        throw new InvalidArgumentException('INVALID_BUDGET_LIMIT');
    }

    if (bccomp($spent, $limit, 2) >= 0) {
        return 'EXCEEDED';
    }

    if (bccomp(bcmul($spent, '100', 2), bcmul($limit, '80', 2), 2) >= 0) {
        return 'WARNING';
    }

    return 'NORMAL';
}
```

*Ví dụ sử dụng giá trị tiền đã được xác nhận và chuẩn hóa; số tiền ở đơn vị VND có scale 0 trong nghiệp vụ thực tế. Tham số của hàm trên chỉ là chuỗi thập phân cho một loại tiền cơ sở.*

# 4. Coding Convention cho HTML5

**WF-HTML-01 (MUST).** Dùng phần tử có nghĩa: `<header>`, `<nav>`, `<main>`, `<section>`, `<form>`, `<label>`, `<button>`, `<table>`; không biến mọi thứ thành `<div>`.

**WF-HTML-02 (MUST).** Mọi input có `label` khớp `for/id`; nhập số tiền phải hiển thị loại tiền, ví nguồn và thông báo lỗi; các nút có `type` cụ thể.

**WF-HTML-03 (MUST).** Dùng `<meta charset="UTF-8">`, `viewport`; đặt `lang="vi"`; dùng thuộc tính `aria-live` cho cảnh báo động, `aria-invalid` và mô tả lỗi khi hợp lệ.

**WF-HTML-04 (MUST).** Không đặt script inline xử lý nghiệp vụ (`onclick="..."`), không đặt mật khẩu/token vào HTML hay `data-*`.

**WF-HTML-05 (SHOULD).** HTML độc lập khỏi API schema phức tạp; binding và DOM update ở module JS riêng.

```html
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ghi chi tiêu | WealthFlow</title>
  <link rel="stylesheet" href="/assets/css/base.css">
  <script type="module" src="/assets/js/features/transactions/expense-form.js"></script>
</head>
<body>
  <main>
    <h1>Ghi nhận chi tiêu</h1>
    <form id="expense-form" novalidate>
      <label for="expense-amount">Số tiền (VND)</label>
      <input id="expense-amount" name="amount" type="text"
             inputmode="numeric" required aria-describedby="amount-error">
      <p id="amount-error" role="alert"></p>
      <button type="submit">Lưu chi tiêu</button>
    </form>
    <p id="form-status" role="status" aria-live="polite"></p>
  </main>
</body>
</html>
```

**Giải thích:** Text input + `inputmode` cho phép nhận và chuẩn hóa số thập phân dạng chuỗi mà không cần chuyển số tiền qua `Number` trong JavaScript. `novalidate` chỉ phù hợp khi module JavaScript có kiểm tra đầy đủ và hiển thị lỗi; backend vẫn bắt buộc validate.

# 5. Coding Convention cho CSS3 / Responsive

**WF-CSS-01 (MUST).** Sử dụng BEM (`block__element--modifier`), không trộn nhiều hệ thống đặt tên CSS gây xung đột.

**WF-CSS-02 (MUST).** CSS Variables lưu màu, khoảng cách, viền, font; hạn chế hardcode lặp lại; hỗ trợ theme light/dark bằng token.

**WF-CSS-03 (MUST).** Mobile-first; hỗ trợ tối thiểu 375–430px, 768–1024px và 1280–1920px theo NFR05; không có tràn ngang không cần thiết.

**WF-CSS-04 (SHOULD).** Ưu tiên `rem`, `%`, `min()`, `max()`, `clamp()` cho size; đặt `box-sizing: border-box`; giới hạn độ sâu selector; hạn chế `!important`.

**WF-CSS-05 (MUST).** Không chỉ dùng màu để truyền đạt trạng thái ngân sách; có text/icon hỗ trợ; đảm bảo tương phản dễ đọc cả light/dark.

```css
:root {
  --color-bg: #f7f9fc;
  --color-text: #172334;
  --color-surface: #ffffff;
  --color-warning: #9a6500;
  --space-3: 0.75rem;
}

[data-theme="dark"] {
  --color-bg: #111827;
  --color-text: #f3f4f6;
  --color-surface: #1f2937;
  --color-warning: #fbbf24;
}

.budget-card {
  padding: var(--space-3);
  background: var(--color-surface);
  color: var(--color-text);
}

.budget-card__status--warning {
  color: var(--color-warning);
  font-weight: 700;
}

@media (min-width: 48rem) {
  .budget-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
```

# 6. Coding Convention cho JavaScript frontend

**WF-JS-01 (MUST).** Dùng `type="module"`, `import/export`, `const` mặc định, `let` khi cần gán lại; cấm `var`.

**WF-JS-02 (MUST).** Sử dụng `===`/`!==`, `async/await`, `try/catch` cho Fetch API; kiểm tra `response.ok`, timeout, lỗi mạng và mã lỗi nghiệp vụ. Không sử dụng `eval()`.

**WF-JS-03 (MUST).** Không dùng `parseFloat`, phép cộng `Number` hoặc `toFixed()` để quyết định số tiền ghi sổ. Tiền API truyền **dạng chuỗi thập phân**, frontend chỉ làm kiểm tra định dạng/hiển thị. Công thức tài chính quyết định ở PHP.

**WF-JS-04 (MUST).** Không chèn dữ liệu người dùng/API bằng `innerHTML`. Dùng `textContent` hoặc tạo DOM nodes an toàn; nếu thật sự cần HTML rich text phải sanitize bằng thư viện đã được phê duyệt.

**WF-JS-05 (MUST).** Không lưu JWT/session secrets vào `localStorage`, `sessionStorage`, URL hay mã nguồn.

**WF-JS-06 (SHOULD).** Mỗi màn hình có module feature, tách `api/http-client.js` khỏi module render và business display; dùng `AbortController` cho timeout/cancel; chống double submit.

### Demo: gọi API ghi chi tiêu

```javascript
// public/assets/js/api/http-client.js
export async function apiPost(path, payload, accessToken, idempotencyKey) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 10000);

  try {
    const response = await fetch(`/api/v1${path}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`,
        'Idempotency-Key': idempotencyKey
      },
      body: JSON.stringify(payload),
      signal: controller.signal
    });

    const result = await response.json();
    if (!response.ok || result.success !== true) {
      throw new Error(result.error?.code ?? 'UNKNOWN_ERROR');
    }
    return result.data;
  } finally {
    clearTimeout(timeoutId);
  }
}
```

**Điều kiện demo:** `accessToken` chỉ nằm trong bộ nhớ của module quản lý phiên; một thao tác ghi phải **giữ nguyên `idempotencyKey` khi retry** sau timeout, không tạo key mới cho lần gửi lại cùng ý định. Backend vẫn phải kiểm tra token, quyền sở hữu và nội dung request. Hàm minh họa này chưa chứa toàn bộ logic refresh token, CSRF cho endpoint cookie, thông báo lỗi UI hay circuit breaker.

```javascript
// Không nên: HTML injection và làm toán tiền bằng Number.
resultBox.innerHTML = apiData.description;
const balance = Number(wallet.balance) - Number(expense.amount);

// Nên: output an toàn; giá trị tiền do backend trả về.
resultBox.textContent = apiData.description;
balanceLabel.textContent = formatCurrency(apiData.balance, apiData.currency);
```

**WF-JS-07 (MUST).** `formatCurrency()` chỉ phục vụ giao diện; phân tích số lớn từ chuỗi phải bảo toàn giá trị, tránh ép mất chính xác. Với dữ liệu vượt miền an toàn của `Number`, frontend giữ nguyên chuỗi và dùng formatter decimal/string được kiểm chứng thay vì chuyển sang `Number`.

# 7. Coding Convention cho PHP backend

**WF-PHP-01 (MUST).** Mỗi file PHP OOP bắt đầu bằng `<?php`, `declare(strict_types=1);`; tuân thủ **PSR-12**; chỉ viết PHP trong file `.php` server-side, không trộn HTML/SQL/logic API trong một file lớn.

**WF-PHP-02 (MUST).** Autoload bằng Composer + PSR-4. Namespace `WealthFlow\` ánh xạ `src/`. Mỗi class nên nằm trong một file trùng tên.

**WF-PHP-03 (MUST).** Type hint cho tham số/return value; ưu tiên constructor injection; hạn chế `static`/global state; không dùng superglobals trong Domain/Application.

**WF-PHP-04 (MUST).** Chỉ Controller/Adapter nhận HTTP request và trả JSON response; Service nhận DTO/value object; Repository nhận PDO để truy cập SQL.

**WF-PHP-05 (MUST).** Sử dụng exception có ý nghĩa (`ValidationException`, `NotFoundException`, `ConflictException`, `InsufficientBalanceException`); xử lý mapping mã HTTP tập trung trong error handler; ẩn stack trace ở production.

**WF-PHP-06 (MUST).** Tất cả thao tác ghi nhiều bảng phải dùng `beginTransaction`/`commit`/`rollBack` trong một transaction; đóng transaction sớm; không gọi API bên ngoài khi giữ khóa CSDL.

**WF-PHP-07 (MUST).** Không truy vấn SQL bằng cách nối chuỗi input; PDO `prepare/execute`, bật `ATTR_ERRMODE=EXCEPTION`, `ATTR_EMULATE_PREPARES=false`.

**WF-PHP-08 (MUST).** Không dùng `float`, `double`, `round()` trên float hoặc `number_format()` làm cơ sở quyết định số dư/tiền. Toán học tiền dùng BCMath với chuỗi thập phân chính xác, sau đó chuẩn hóa/làm tròn Half-Up theo BR03.

### Demo: `composer.json`

```json
{
  "name": "voz/wealthflow",
  "type": "project",
  "require": {
    "php": "^8.2",
    "ext-bcmath": "*",
    "ext-pdo_mysql": "*",
    "firebase/php-jwt": "^6.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0 || ^11.0",
    "phpstan/phpstan": "^1.0 || ^2.0"
  },
  "autoload": {
    "psr-4": { "WealthFlow\\": "src/" }
  },
  "autoload-dev": {
    "psr-4": { "WealthFlow\\Tests\\": "tests/" }
  }
}
```

*Version range ở đây là ví dụ khai báo chứ không cam kết đã kiểm tra khả năng tương thích của mọi phiên bản gói; khi khởi tạo dự án cần kiểm thử Composer resolve, chạy PHPUnit và khóa `composer.lock`.*

### Demo: PHP `Money` value object (rút gọn)

```php
<?php

declare(strict_types=1);

namespace WealthFlow\Shared\Domain;

use InvalidArgumentException;

final class Money
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency
    ) {
        if (!preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/', $amount)) {
            throw new InvalidArgumentException('INVALID_AMOUNT');
        }

        $scale = match ($currency) {
            'VND' => 0,
            'USD', 'EUR' => 2,
            default => throw new InvalidArgumentException('UNSUPPORTED_CURRENCY'),
        };

        $parts = explode('.', $amount, 2);
        if (isset($parts[1]) && strlen($parts[1]) > $scale) {
            throw new InvalidArgumentException('INVALID_CURRENCY_SCALE');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('CURRENCY_MISMATCH');
        }

        $scale = $this->currency === 'VND' ? 0 : 2;
        return new self(bcadd($this->amount, $other->amount, $scale), $this->currency);
    }
}
```

**Giới hạn:** Đây là value object demo, chưa chuẩn hóa biểu diễn dấu âm `-0`, chưa hỗ trợ quantity/rate, chưa tự thực hiện Half-Up ở các phép nhân/chia. Phần Domain chính thức phải có `MoneyCalculator`, `FxRate`, `MoneyRoundingPolicy` và unit test cho biên số, dấu âm, nhiều tiền tệ trước khi dùng production. `bcadd` **không tự động thực hiện Round Half-Up**; không nhầm scale với làm tròn.

### Demo: khởi tạo PDO an toàn

```php
<?php

declare(strict_types=1);

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']),
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

*Lưu ý:* `$_ENV` cần được nạp từ môi trường hợp lệ; không tự đặt mật khẩu DB trong mã hoặc file theo dõi Git.

# 8. Convention cho MySQL, SQL và schema migration

**WF-DB-01 (MUST).** Dùng MySQL 8.0+ `ENGINE=InnoDB`, `utf8mb4`, timestamp UTC, foreign key, unique constraint và index phù hợp.

**WF-DB-02 (MUST).** `snake_case` cho bảng/cột; bảng dạng số nhiều (`users`, `wallets`, `transactions`, `ledger_entries`). Dùng `id` khóa chính, `user_id` ownership; index bắt đầu `idx_`, unique `uq_`, foreign key `fk_`.

**WF-DB-03 (MUST).** Số tiền dùng `DECIMAL(24,2)` (độ rộng theo đề xuất, cần chốt khi thiết kế vật lý); tỷ giá `DECIMAL(24,8)`; `FLOAT` và `DOUBLE` bị cấm cho tiền/tỷ giá. Giới hạn số lẻ theo mã tiền nằm ở validation Domain: VND 0; USD/EUR 2; tỷ giá tối đa 8.

**WF-DB-04 (MUST).** DDL viết trong migration có thứ tự `YYYYMMDDHHMM_description.sql`; migration đã áp dụng không sửa lại âm thầm; thay đổi schema bằng migration mới, kèm rollback/runbook khi cần.

**WF-DB-05 (MUST).** Mọi bảng dữ liệu thuộc người dùng phải có `user_id` hoặc có quan hệ bảo đảm truy ra owner; query bảo vệ lọc owner trên server. Không coi role Admin là quyền bypass xem tiền cá nhân.

**WF-DB-06 (MUST).** Ghi sổ nhiều bảng thực thi cùng transaction. Với ví chịu thay đổi số dư, sử dụng `SELECT ... FOR UPDATE` trong transaction; khóa các ví theo ID tăng dần khi chuyển nhiều ví để giảm nguy cơ deadlock.

**WF-DB-07 (MUST).** Unique key chống trùng idempotency `(user_id, idempotency_key)` và recurring `(subscription_id, due_date)`; lỗi trùng dùng 409 hoặc trả kết quả xử lý trước đó theo BR13.

**WF-DB-08 (MUST).** Không lưu plaintext password; chỉ `password_hash`; không dùng `ON DELETE CASCADE` với các bản ghi đã Posted có yêu cầu bất biến kế toán. Xóa mềm/trạng thái lưu trữ tuân theo chính sách dữ liệu.

**WF-DB-09 (SHOULD).** Chỉ dùng seed dữ liệu giả, không commit dữ liệu tài chính thật; backup/restore vận hành bằng script DevOps theo SRS Elaboration, không triển khai nút Admin backup/restore trên UI.

### Demo: schema SQL tối thiểu cho ledger (minh họa)

```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    currency CHAR(3) NOT NULL,
    balance_original DECIMAL(24,2) NOT NULL DEFAULT 0,
    is_archived BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_wallets_user_id (user_id),
    CONSTRAINT fk_wallets_user_id FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE idempotency_keys (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    idempotency_key CHAR(36) NOT NULL,
    request_hash CHAR(64) NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT uq_idempotency_user_key UNIQUE (user_id, idempotency_key),
    INDEX idx_idempotency_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Không phải migration đầy đủ:** Đoạn trên giả định bảng `users` tồn tại; còn thiếu transactions, ledger_accounts, ledger_entries, trạng thái xử lý idempotency, foreign keys giao dịch và các bảng module khác. Khi triển khai thật, phải thiết kế physical schema đầy đủ và kiểm tra thứ tự chạy migration. `wallets.balance_original` là **cache đối soát với sổ cái**, không phải nguồn dữ liệu độc lập có thể sửa tùy ý.

### Demo: query có kiểm tra owner và row lock

```php
$stmt = $pdo->prepare(
    'SELECT id, currency, balance_original
     FROM wallets
     WHERE id = :wallet_id AND user_id = :user_id AND is_archived = 0
     FOR UPDATE'
);
$stmt->execute(['wallet_id' => $walletId, 'user_id' => $currentUserId]);
$wallet = $stmt->fetch();
if ($wallet === false) {
    throw new NotFoundException('WALLET_NOT_FOUND');
}
```

**Quan trọng:** `FOR UPDATE` chỉ mang ý nghĩa locking khi đang ở trong transaction phù hợp và dùng InnoDB. Không chạy DDL trong transaction ghi sổ vì một số lệnh DDL của MySQL gây implicit commit.

# 9. Financial Coding Convention – các quy tắc trọng yếu

## 9.1. Bất biến tài chính

**WF-FIN-01 (MUST) [BR01].** Giao dịch `Posted` có tối thiểu 2 ledger entries gồm ít nhất một Debit và một Credit. Tổng `amount_base` ở 2 phía cân bằng chính xác. Không cộng trực tiếp `amount_original` thuộc hai currency khác nhau.

**WF-FIN-02 (MUST) [BR03].** Sử dụng chuỗi decimal/BCMath hoặc value object chuẩn; chuyển `amount`, `amountBase`, `rate` qua JSON ở dạng **string**, không gửi số tiền lớn bằng JSON numeric. Round Half-Up tại một nơi thống nhất, ghi chênh lệch làm tròn qua account riêng.

**WF-FIN-03 (MUST) [BR04].** Mọi FX transaction chụp snapshot rate hợp lệ `effective_at <= transaction_time`, tuổi tỷ giá không quá 24h. Snapshot sau commit không sửa, không tự cập nhật giá trị lịch sử khi IF02 thay đổi.

**WF-FIN-04 (MUST) [BR05].** Thu nhập, chi tiêu, chuyển ví, vay/cho vay, trả gốc/lãi và mua vàng phải có posting matrix và unit test trước khi merge.

**WF-FIN-05 (MUST) [BR06/14].** Không dùng `UPDATE`/`DELETE` trực tiếp vào Posted Ledger; sửa bằng chuỗi Reversal + Replacement trong 1 transaction. Draft không tính ví, ngân sách, dòng tiền, Net Worth.

**WF-FIN-06 (MUST) [BR12/13].** Posting phải đồng thời ghi Transaction header, Ledger Entries, cập nhật balance cache, Idempotency record và outbox/event bảo đảm nhất quán trong cùng transaction; nếu bất kỳ thao tác lỗi thì rollback.

**WF-FIN-07 (MUST) [BR10/16/17].** Goal contribution không tạo tiền; net worth = tài sản - nợ và chống trùng ví/tài sản vàng; trả nợ gốc giảm liability, lãi/phí mới là expense.

**WF-FIN-08 (MUST) [BR15].** Xuất ngoại tệ theo Book Value Base bình quân; tất toán toàn bộ nguyên tệ phải xuất hết số dư base còn lại, không để “rác” làm tròn.

## 9.2. Ma trận ghi sổ theo SRS

| Nghiệp vụ | Debit | Credit | Kiểm chứng |
|---|---|---|---|
| Mở ví với số dư 2.000.000 VND | Ví tài sản 2.000.000 | Vốn đầu kỳ 2.000.000 | Không tính vào thu nhập |
| Nhận lương 10.000.000 | Ví ngân hàng 10.000.000 | Thu nhập 10.000.000 | Income +10 triệu |
| Chi ăn uống 100.000 | Chi phí ăn uống 100.000 | Ví tài sản 100.000 | Expense +100.000 |
| Chuyển ví 1.000.000 | Ví đích 1.000.000 | Ví nguồn 1.000.000 | Income/Expense = 0 |
| Nhận vay 10.000.000 | Ví ngân hàng 10.000.000 | Nợ phải trả 10.000.000 | Net Worth không đổi |
| Trả nợ gốc 2tr + lãi 100k | Nợ phải trả 2tr; Chi phí lãi 100k | Ví ngân hàng 2,1tr | Expense chỉ tăng 100k |
| Mua vàng 8,5 triệu | Tài sản vàng 8,5 triệu | Ví ngân hàng 8,5 triệu | Net Worth không đổi lúc mua |
| Đảo chi tiêu 100.000 | Ví tài sản 100.000 | Chi phí ăn uống 100.000 | Giảm chi ròng, bảo lưu lịch sử |

*Điều kiện kỹ thuật:* Trong giao dịch ngoại tệ, bảng trên phải được mở rộng thêm amountOriginal, amountBase, currency, tỷ giá snapshot và xử lý chênh lệch như BR04/BR15; không coi bảng VND là thuật toán đa tiền tệ hoàn chỉnh.

## 9.3. Demo luồng Post Expense – SQ02

```text
1. HTTP POST /api/v1/transactions (type=EXPENSE)
2. Auth middleware xác thực JWT + tài khoản ACTIVE.
3. Controller validate amount string, currency, walletId, categoryId.
4. Service chuẩn hóa request + hash nội dung cho Idempotency-Key.
5. BEGIN TRANSACTION; tạo/khóa idempotency record.
6. SELECT wallet ... WHERE user_id=? FOR UPDATE.
7. Kiểm tra currency, số dư, category ownership, tỷ giá snapshot.
8. Tạo Transaction POSTED + Debit Expense + Credit Wallet.
9. Kiểm tra tổng debitBase = creditBase trước khi commit.
10. Cập nhật cache wallet balance và outbox notification events.
11. Lưu kết quả idempotency; COMMIT.
12. BudgetService tính chi ròng theo BR07/08; phản hồi kết quả.
```

**Ràng buộc chống sai lệch:** Nếu có thông báo ngân sách cần phát sau commit, phải lưu **outbox event** trong transaction (bước 10), rồi consumer sau commit mới phát notification và đánh dấu chống trùng. Cần tránh vừa commit rồi gọi dịch vụ khác cập nhật riêng không có cơ chế phục hồi; cảnh báo user có thể xử lý bất đồng bộ nhưng số dư và ledger phải nhất quán.

## 9.4. Idempotency và concurrency

- Request bắt buộc có UUID tại header `Idempotency-Key`; unique `(user_id, key)`.
- Tính `request_hash` từ payload chuẩn hóa bao gồm `type`, `walletId`, `amount`, `currency`, `categoryId`, `effectiveDate` và các trường ảnh hưởng nghiệp vụ; **không hash JSON raw thiếu chuẩn hóa** vì thứ tự field có thể khác.
- Nếu cùng key/cùng payload: trả response trước (hoặc trạng thái chờ đã định nghĩa); cùng key/khác payload: `409 IDEMPOTENCY_KEY_REUSED`.
- Timeout khi client chưa biết kết quả: retry cùng key, không đoán giao dịch “đã thất bại”.
- Khóa ở DB vẫn cần thiết để không có hai thao tác cùng trừ ví dẫn đến âm số dư; test bằng 2 request chạy đồng thời.
- Không thực hiện call tỷ giá, email, network I/O khi đang giữ row lock; lấy dữ liệu ngoài trước transaction và validate lại tính hợp lệ khi ghi.

# 10. REST API Contract, lỗi và validation

**WF-API-01 (MUST).** REST over HTTPS, JSON UTF-8, prefix `/api/v1`; endpoint dùng danh từ số nhiều. `GET` đọc; `POST` tạo/command; `PATCH` sửa thông tin cho phép thay đổi; `DELETE` chỉ với bản nháp hoặc entity được phép xóa. Không hỗ trợ HTTP `PUT` lên Posted Ledger.

**WF-API-02 (MUST).** Response chuẩn theo IF01 ở mọi trường hợp:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Số dư ví không đủ để ghi chi tiêu.",
    "fieldErrors": {}
  },
  "requestId": "req_20261013_0001"
}
```

**WF-API-03 (MUST).** Trạng thái HTTP: `200` thành công; `201` tạo; `400` cú pháp HTTP/JSON lỗi; `401` thiếu/sai token; `403` sai vai trò; `404` không tồn tại hoặc không sở hữu; `409` trùng/đụng độ/idempotency; `422` validation; `429` rate limit; `500` lỗi server. Không luôn trả HTTP 200 với `success:false`.

**WF-API-04 (MUST).** Validate toàn bộ dữ liệu **trên PHP server**: dạng chuỗi tiền, currency whitelist, kiểu ID, ngày/timezone, độ dài mô tả, email, quyền sở hữu wallet/category/loan. Validation JS chỉ cải thiện UX.

**WF-API-05 (MUST).** Với endpoint ghi sổ POST/reversal, `Idempotency-Key` bắt buộc; document behavior 201/200 khi replay/409 khi key khác request. Không dùng retry tự động cho POST nếu không có key cũ.

**WF-API-06 (SHOULD).** Thiết kế response có phân trang, sort/filter whitelist; không trả toàn bộ lịch sử ledger ra browser; dùng `limit` tối đa và ổn định thứ tự `createdAt,id`.

### Mẫu API endpoint mapping theo UC

| Endpoint đề xuất | Method | Nội dung | SRS |
|---|---|---|---|
| `/api/v1/auth/login` | POST | Đăng nhập JWT | FR01 / UC01 / SQ01 |
| `/api/v1/wallets` | GET, POST | Ví thuộc người dùng | FR02 / UC02 |
| `/api/v1/transactions` | GET, POST | Tra cứu, ghi thu/chi | FR03 / UC03 / SQ02 |
| `/api/v1/transactions/{id}/reversal` | POST | Đảo Posted | BR06 / UC03 / SQ03 |
| `/api/v1/transfers` | POST | Chuyển ví | BR05 / SQ03 |
| `/api/v1/budgets` | GET, POST, PATCH | Ngân sách tháng | FR05 / UC05 |
| `/api/v1/subscriptions` | GET, POST | Khoản định kỳ | FR06 / UC04 / SQ04 |
| `/api/v1/loans` | GET, POST | Khoản vay/cho vay | FR09 / UC06 / SQ05 |
| `/api/v1/loans/{id}/repayments` | POST | Trả nợ | FR09 / UC07 / SQ05 |
| `/api/v1/gold-assets` | GET, POST | Tài sản vàng | FR08 / UC08 / SQ06 |
| `/api/v1/dashboard` | GET | Net Worth, chỉ số | FR10 / UC10 / SQ06 |
| `/api/v1/reports/export` | GET | CSV an toàn | FR12 / UC11 / SQ07 |
| `/api/v1/admin/announcements` | POST | Broadcast | FR13 / UC13 / SQ08 |

*Các URL chỉ là đề xuất API Contract cho Construction, chưa phải endpoint đã tồn tại; cần đồng bộ `docs/api-contract.md` và sequence diagrams nếu nhóm đặt tên khác.*

### Demo DTO chi tiêu

```json
{
  "walletId": 25,
  "categoryId": 7,
  "type": "EXPENSE",
  "amount": "150000",
  "currency": "VND",
  "effectiveDate": "2026-10-13",
  "description": "Ăn trưa"
}
```

*Không cho client truyền `userId`, `balanceAfter`, `amountBase` như dữ liệu đáng tin cậy để tự ghi sổ. Các giá trị này phải được backend xác định/kiểm chứng; nếu có FX thì backend chọn snapshot tỷ giá.*

# 11. Security Convention & Privacy

**WF-SEC-01 (MUST) [NFR02/FR15].** Production dùng HTTPS TLS 1.3; HSTS khi cấu hình đúng; session/refresh cookie `HttpOnly`, `Secure`, `SameSite` phù hợp; xác minh JWT signature, issuer/audience nếu áp dụng, hạn tối đa 60 phút, revocation/session status.

**WF-SEC-02 (MUST).** Password dùng `password_hash()` (`PASSWORD_ARGON2ID` nếu runtime hỗ trợ; fallback bcrypt cost ≥ 12) và `password_verify()`. Không tự viết SHA256, MD5 hoặc lưu password dạng plain. Nếu dùng Argon2id thì cấu hình work factor phù hợp máy chủ và kiểm tra khi deploy.

**WF-SEC-03 (MUST).** Authentication và Authorization là hai kiểm tra riêng; mỗi API dữ liệu tài chính phải ràng buộc **owner**. Resource của User B khi User A biết ID phải trả 404; Admin chỉ xem thống kê hệ thống được phép, không được xem chi tiết sổ.

**WF-SEC-04 (MUST).** PDO parameterized queries; không nối raw input vào SQL. Các field sort/table không bind được bằng placeholder phải whitelist tại code.

**WF-SEC-05 (MUST).** Chống XSS bằng DOM `textContent` / PHP `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` khi có template HTML; cấu hình CSP phù hợp; tránh inline script.

**WF-SEC-06 (MUST).** Với refresh cookie hoặc cookie-auth mutation phải kiểm tra CSRF token và/hoặc Origin theo thiết kế an toàn; `SameSite` hỗ trợ nhưng không thay thế tất cả kiểm tra trong mọi tình huống. Access token qua Authorization header không tự động được browser đính kèm như cookie nhưng vẫn phải chống XSS.

**WF-SEC-07 (MUST).** Không commit `.env`, DB password, JWT private key, API key tỷ giá, file sao lưu, log người dùng; dùng `.env.example` chỉ chứa tên biến/mẫu giả.

**WF-SEC-08 (MUST).** Log không chứa mật khẩu, token, cookie, request body nhạy cảm, số tài khoản đầy đủ hay lịch sử tài chính cá nhân. `userId` trong audit log phải được kiểm soát truy cập, lưu giữ và xóa theo chính sách.

**WF-SEC-09 (MUST) [FR12].** Xuất CSV phải bảo vệ formula injection (ví dụ ô bắt đầu `=`, `+`, `-`, `@`, tab/CR/LF sau chuẩn hóa); xử lý quotes/delimiters/encoding, export test mở bằng spreadsheet; không dùng cách escape đơn giản chưa kiểm chứng làm duy nhất biện pháp bảo vệ.

**WF-SEC-10 (SHOULD).** Rate limit đăng nhập và API ghi, thông báo login sai chung, mật khẩu phải có yêu cầu tối thiểu hợp lý, quản trị thay đổi hệ thống ghi audit. Dùng `random_bytes()` hoặc thư viện vetted để tạo token/key.

### Demo password PHP

```php
<?php

declare(strict_types=1);

$algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
$options = $algorithm === PASSWORD_BCRYPT ? ['cost' => 12] : [];
$hash = password_hash($plainPassword, $algorithm, $options);

if (!password_verify($submittedPassword, $hash)) {
    throw new AuthenticationException('INVALID_CREDENTIALS');
}
```

*Đây là đoạn minh họa API hashing/verification; production cần xác định chính sách mật khẩu, rate limiting và cấu hình work factor Argon2id.*

### Demo security ownership test

```text
Given User A và User B đều có ví riêng.
When  User A gửi GET /api/v1/wallets/{walletIdOfB}.
Then  HTTP 404, success=false, không xuất nội dung ví B.
And   Audit ghi sự kiện mà không lộ số dư hoặc token.
```

# 12. Convention cho tác vụ nền và tích hợp ngoài

**WF-JOB-01 (MUST) [SYS01].** `scripts/sync-exchange-rates.php`: lấy tỷ giá, kiểm tra nguồn/effectiveAt/rate >0, lưu version mới; retry theo chính sách SRS (1, 5, 15 phút, sau đó mỗi giờ), cảnh báo khi stale >24h; không tự cập nhật Posted FX snapshots.

**WF-JOB-02 (MUST) [SYS02].** `scripts/process-recurring.php`: chỉ xử lý kỳ đến hạn đúng một lần bằng `UNIQUE(subscription_id, due_date)`; lùi ngày 29/30/31 về cuối tháng khi cần; không làm mất ngày neo gốc; auto-post chỉ khi bật. Không đủ tiền → draft + notification.

**WF-JOB-03 (MUST) [SYS03].** `scripts/remind-loans-snapshot.php`: gửi nhắc nợ trước kỳ hạn và tạo Net Worth snapshot theo lịch địa phương; tách hai command/entrypoint nếu lịch thực tế khác nhau (08:00 và 23:59:59).

**WF-JOB-04 (MUST).** Tác vụ nền dùng PHP CLI; không kích hoạt chỉ bằng người dùng mở web. Có idempotency, run identifier, lock/concurrency guard, retry giới hạn, log `traceId`; xử lý timezone khác nhau khi quét lịch.

**WF-JOB-05 (SHOULD).** Trong phát notification từ giao dịch ghi sổ, dùng outbox hoặc cơ chế phát có thể lặp mà không tạo duplicate; không gửi email/truy cập IF02 trong SQL transaction.

### Scheduler demo (Linux)

```cron
5 0 * * * /usr/bin/php /srv/wealthflow/scripts/sync-exchange-rates.php
*/5 * * * * /usr/bin/php /srv/wealthflow/scripts/process-recurring.php
0 8 * * * /usr/bin/php /srv/wealthflow/scripts/send-loan-reminders.php
59 23 * * * /usr/bin/php /srv/wealthflow/scripts/snapshot-net-worth.php
```

**Chú ý timezone:** Mẫu cron trên chạy theo timezone máy chủ; phải quy đổi/điều chỉnh theo đặc tả `effectiveAt`, timezone người dùng và thời gian VN. Ở Windows/XAMPP dùng **Windows Task Scheduler** tương đương. Các file `send-loan-reminders.php`/`snapshot-net-worth.php` là đề xuất tách SYS03 từ job tổng, không có sẵn trong source hiện tại.

# 13. Testing Convention và tiêu chí chất lượng

## 13.1. Các cấp độ test

| Loại | Nội dung | Công cụ chính | Ưu tiên |
|---|---|---|---|
| Unit test | Money, LedgerPolicy, BudgetPolicy, LoanCalculation, FX rounding | PHPUnit | P1 |
| Integration test | PDO, migration, multi-table commit/rollback, concurrency | PHPUnit + MySQL test DB | P1 |
| Feature/API test | Auth, ownership, CRUD, posting, reversal, error schema | PHPUnit / HTTP runner | P1 |
| UI test | HTML form, Fetch failure, responsive, accessibility, theme | Manual / Playwright | P1 |
| Security test | SQL injection, XSS, CSRF, brute force, token expiry, cross-user | Checklist + automated test | P1 |
| Nonfunctional | API p95, concurrency 50, FCP, backup/restore drill | Kịch bản riêng | P2 trước nghiệm thu |

**WF-TEST-01 (MUST) [NFR04.01].** Domain + Application Services phải đạt coverage **≥80%**; đánh giá thêm chất lượng case, không thay thế bằng con số coverage. Report coverage phải có thời điểm chạy và commit hash.

**WF-TEST-02 (MUST).** Mỗi BR quan trọng có test case bình thường, biên, bất hợp lệ và cạnh tranh đồng thời nếu liên quan: đa tiền tệ, rounding, reversal, idempotency, loan split, budget alert.

**WF-TEST-03 (MUST).** Test DB tách biệt DB phát triển/production; chạy migrations trên test DB; teardown/rollback dữ liệu. Không chạy test destructive trên DB người dùng thật.

**WF-TEST-04 (MUST).** CI chặn merge nếu fail PHP lint, code style, static analysis lỗi nghiêm trọng, PHPUnit, JS lint (nếu thiết lập), security scan hoặc test đặc thù đã được yêu cầu.

### Các trường hợp kiểm thử bắt buộc theo BR

| Test ID đề xuất | Tình huống | Kỳ vọng |
|---|---|---|
| WF-T01 | 100.000 VND ghi chi | Tổng Nợ = Có; wallet giảm 100.000 |
| WF-T02 | Cùng key + cùng payload gửi 2 lần | Một giao dịch Posted, response replay an toàn |
| WF-T03 | Cùng key + khác payload | 409 Conflict; không tạo mới |
| WF-T04 | Hai request đồng thời rút vượt tổng số dư | Không âm ví, có giao dịch bị từ chối |
| WF-T05 | Lỗi khi insert credit ledger | Rollback mọi bảng + cache |
| WF-T06 | Đảo giao dịch Posted | Bút toán đảo; record gốc vẫn bất biến |
| WF-T07 | Usage 79%, 80%, 100%, 105% | Normal/Warning/Exceeded; đúng ngưỡng |
| WF-T08 | Nhảy 70% → 105% | Chỉ thông báo Đỏ; đánh dấu Vàng |
| WF-T09 | Ngày neo 31/02/03 | Tháng 2 cuối tháng; tháng 3 về ngày 31 |
| WF-T10 | Nhận khoản vay 10 triệu | Net Worth không đổi; Income không tăng |
| WF-T11 | Trả gốc 2tr + lãi 100k | Expense chỉ tăng 100k |
| WF-T12 | User A truy cập wallet B | 404, không rò rỉ thông tin |
| WF-T13 | USD 100 đổi VND, snapshot FX | Lãi/lỗ tỷ giá hạch toán đúng, tỷ giá cũ không đổi |
| WF-T14 | Chuyển giữa 2 ví của cùng user | Không đổi Net Worth, Income, Expense |
| WF-T15 | CSV có giá trị mở đầu `=1+1` | Không tạo ô công thức có thể thực thi |
| WF-T16 | Logout/đổi mật khẩu/tài khoản Locked | Token phiên cũ bị từ chối |

### Demo PHPUnit – BudgetPolicy (đơn giản hóa)

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BudgetPolicyTest extends TestCase
{
    public function testWarningAtExactlyEightyPercent(): void
    {
        self::assertSame('WARNING', classifyBudgetUsage('800', '1000'));
    }

    public function testExceededAtExactlyHundredPercent(): void
    {
        self::assertSame('EXCEEDED', classifyBudgetUsage('1000', '1000'));
    }

    public function testNormalBelowEightyPercent(): void
    {
        self::assertSame('NORMAL', classifyBudgetUsage('790', '1000'));
    }
}
```

*Test minh họa giả định helper nằm trong scope/autoload phù hợp. Ứng dụng chính thức nên gọi class Domain cụ thể; test ngưỡng chưa đủ để xác minh chống thông báo trùng và hoàn/đảo chi tiêu.*

# 14. Git Convention, code review và CI

**WF-GIT-01 (MUST).** Không commit trực tiếp vào `main` (trừ trường hợp sửa khẩn cấp được phê duyệt). Các công việc dùng branch riêng, PR và tối thiểu 1 reviewer.

**WF-GIT-02 (MUST).** Branch/commit liên kết Use Case, FR hoặc BR. Commit dùng Conventional Commits; viết tiếng Anh ngắn, diễn đạt hành động.

```text
feature/uc03-post-expense
feature/fr09-loan-repayment
fix/br08-alert-threshold
docs/wf-conv-001

feat(transactions): post expense using double-entry ledger
fix(budgets): prevent duplicate exceeded alerts
test(loans): cover principal and interest split
```

**WF-GIT-03 (MUST).** Tối thiểu PR có: Purpose, SRS IDs, files modified, screenshots UI nếu có, cách chạy, test evidence, migration/risk, reviewer checklist.

**WF-GIT-04 (MUST).** Không merge code fail test, vượt phạm vi SRS không có CR/ADR, chứa secret, hoặc tạo SQL mutation với `Posted` trái BR06.

**WF-GIT-05 (SHOULD).** Khóa phiên bản gói (`composer.lock`, optional `package-lock.json`), không commit `vendor/`, `node_modules/`, uploads, logs hoặc bản `.env` thực.

### File `.editorconfig`

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true

[*.php]
indent_style = space
indent_size = 4

[*.{html,css,js,json,md,yml,yaml}]
indent_style = space
indent_size = 2

[*.sql]
indent_style = space
indent_size = 4
```

### File `.gitignore` (đề xuất)

```gitignore
/vendor/
/node_modules/
/.env
/.env.*
!/.env.example
/storage/logs/*
/storage/backups/
/coverage/
/.phpunit.cache/
/.idea/
.vscode/settings.local.json
```

### PR checklist để copy vào GitHub

```text
[ ] Có mã truy vết FR/UC/BR/SQ và mô tả thay đổi
[ ] Tuân thủ folder / naming / code format
[ ] Không có token, mật khẩu, dữ liệu cá nhân trong commit
[ ] Không có SQL injection/XSS/CSRF/ownership bypass
[ ] Logic tiền dùng Decimal + kiểm tra currency/rounding
[ ] Ghi sổ đúng ACID, row lock, idempotency, rollback
[ ] Có test normal / edge / invalid / concurrency (khi cần)
[ ] Cập nhật API contract, migration, ADR hoặc docs liên quan
[ ] UI chạy trên mobile và dark/light (nếu ảnh hưởng FE)
[ ] Local tests & CI đều PASS
```

# 15. Thiết lập môi trường Construction

## 15.1. Điều kiện máy phát triển

- PHP 8.2+; xác nhận `php -v` và `php -m` có `bcmath`, `pdo_mysql`, `openssl`, `mbstring` nếu thư viện yêu cầu.
- MySQL 8.0+; tạo riêng DB `wealthflow_dev` và `wealthflow_test`.
- Apache/XAMPP có route tới `public/`; Composer đã cài; Git hoạt động.
- Node.js chỉ cần nếu nhóm áp dụng ESLint/Prettier, không phải để chạy PHP backend.

## 15.2. Các lệnh khởi tạo (tham khảo cho Windows CMD/PowerShell)

```bash
php -v
php -m
composer --version
composer install
composer dump-autoload
php -l public/index.php
vendor/bin/phpunit
```

Với Windows CMD, nếu dùng PHP trong XAMPP nhưng chưa thêm PATH, chạy đường dẫn đầy đủ, ví dụ `F:\xampp\php\php.exe -v` (điều chỉnh theo máy). **Không** bắt đầu CMD bằng `&` như cú pháp PowerShell. Trong PowerShell, nếu đường dẫn có dấu cách thì dùng `& "F:\xampp\php\php.exe" -v`.

### Mẫu `.env.example`

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/wealthflow
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=wealthflow_dev
DB_USER=wealthflow_dev
DB_PASSWORD=change-me
APP_TIMEZONE=Asia/Ho_Chi_Minh
JWT_ISSUER=wealthflow-local
JWT_ACCESS_TTL_SECONDS=3600
FX_PROVIDER_URL=https://provider.example/api
```

*Cảnh báo:* Giá trị này là ví dụ, không phải thông số deployment thật. `JWT_ACCESS_TTL_SECONDS=3600` là mức tối đa theo NFR02; phát hành production cần cấu hình HTTPS, bí mật ký JWT, secure cookie và môi trường riêng. Không dùng `root` MySQL không mật khẩu cho production; user DB cấp quyền tối thiểu.

## 15.3. Definition of Ready / Definition of Done

**Một ticket sẵn sàng làm (DoR) khi:** có mã UC/FR, mô tả user flow, API contract/input-output, bảng liên quan, quy tắc BR áp dụng, expected test và thành viên phụ trách.

**Một ticket hoàn thành (DoD) khi:** code chạy, pass CI, PR review, test business/validation/security, DB migration chạy trên test DB, docs và trace cập nhật, giao diện responsive nếu có, không còn lỗi blocking. Đối với posting tài chính, DoD bắt buộc phải có chứng cứ DB rollback và concurrency.

# 16. Ma trận truy vết Coding Convention ↔ SRS

| Mã quy chuẩn | Yêu cầu SRS | Phân hệ thực thi | Bằng chứng nghiệm thu |
|---|---|---|---|
| WF-ARCH-01…06 | §2.2, §6.4 | Toàn backend/frontend | Tree, PR review, module boundaries |
| WF-GEN-01…05 | NFR04 | Tất cả module | Lint, style checks, code review |
| WF-HTML/CSS/JS | IF01, NFR05/06 | Frontend | Responsive, UI test, accessibility |
| WF-PHP/DB | NFR03/04, BR12 | Backend, MySQL | Integration test, migration check |
| WF-FIN-01…08 | BR01…06, BR10…17 | Wallets, Transactions, Loans, Wealth | Ledger tests, T01–T05, T10–T14 |
| WF-API-01…06 | IF01, FR01–15 | HTTP interface | API contract and error tests |
| WF-SEC-01…10 | BR11, FR15, NFR02 | Auth, middleware, all repositories | T12, T15, T16, pen-test checklist |
| WF-JOB-01…05 | SYS01–03, BR04/09 | Recurring/FX/Loans | Scheduler run logs, replay tests |
| WF-TEST-01…04 | NFR04, §6.2 | Tests + CI | Coverage report ≥80% |
| WF-GIT-01…05 | NFR04, Construction governance | Repository | PR evidence, branch controls |

### 16.1. Ví dụ truy vết một chức năng

```text
FR03 / UC03 / SQ02 / BR01 + BR03 + BR12 + BR13
    → Modules/Transactions/Application/PostExpenseService.php
    → Modules/Transactions/Domain/LedgerPolicy.php
    → Modules/Transactions/Infrastructure/PdoTransactionRepository.php
    → tests/Unit/LedgerPolicyTest.php
    → tests/Integration/PostExpenseTransactionTest.php
    → tests/Feature/PostExpenseApiTest.php
    → PR: feature/uc03-post-expense
```

### 16.2. Vai trò từng thành viên (mẫu phân chia cho nhóm 4 người)

| Vai trò | Trách nhiệm chính | Điều kiện phối hợp |
|---|---|---|
| Thành viên A | UI HTML/CSS, responsive, accessible form | Theo API Contract; mock dữ liệu có kiểm soát |
| Thành viên B | API Auth, middleware, Wallet, user isolation | Review security mọi route |
| Thành viên C | Domain ledger, Transaction, Budget, Loan | Review tài chính và DB transaction |
| Thành viên D | MySQL migration, report, background jobs, CI/tests | Review schema & integration |

Đây là **phân chia công việc minh họa**, không mặc định thành viên thực tế được chỉ định theo bảng. Các phần nhạy cảm (Auth, ledger, DB) phải có reviewer chéo; không để một người tự viết rồi tự duyệt.

# 17. Lộ trình áp dụng quy ước trong pha Construction

| Sprint/giai đoạn | Ưu tiên | Kết quả bàn giao tối thiểu |
|---|---|---|
| C0 – Construction foundation | Chốt ADR stack, repo, Composer, PHP autoload, MySQL migrations, lint/tests, API skeleton | App chạy local, endpoint health, CI pass |
| C1 – Core identity & wallet | Auth JWT/session, ownership, Wallet, opening balance | UC01, UC02, BR11 security tests |
| C2 – Ledger & money | Money, rates, transaction posting, reversal, idempotency, row lock | UC03, SQ02/SQ03, T01–T06 |
| C3 – Financial planning | Budget, Goals, Loans, Gold/Wealth | UC05–UC10, T07–T14 |
| C4 – Reports & jobs | Report CSV, scheduled recurring, FX updates, reminders, Admin features | SQ04–SQ08, T15–T16 |
| C5 – Hardening | 80% coverage, responsiveness, 50 concurrent test, p95 latency, RPO/RTO drill | Evidence pack sẵn sàng Transition |

Thứ tự mang tính thực thi đề xuất, không thay đổi mức ưu tiên P1/P2 của SRS. Nếu phải cắt giảm scope do thời gian, phải có change control, không âm thầm bỏ các business rules mang tính an toàn.

# 18. Checklist nghiệm thu Coding Convention trước khi nộp giảng viên

- [ ] Nhóm đã chấp thuận rõ stack (HTML/CSS/JS/PHP/MySQL/Composer/BCMath).
- [ ] Đã xác định ranh giới `public/`, `src/`, `database/`, `tests/`, `scripts/`.
- [ ] Quy tắc đặt tên/căn lề/comment nhất quán trong mọi file demo.
- [ ] Có ví dụ đúng và sai cho HTML, CSS, JS, PHP, SQL và API.
- [ ] Có ràng buộc riêng cho tiền tệ, Ledger, Reversal, ACID, Idempotency.
- [ ] JWT/session, phân quyền ownership và Admin privacy thống nhất SRS.
- [ ] Có rule cho background cron SYS01–03 và chống chạy trùng.
- [ ] Có phương pháp test, số đo coverage và quality gates.
- [ ] Mỗi module có trace FR/UC/BR/SQ/TC và PR evidence.
- [ ] Có tài liệu thiết lập môi trường và lệnh chạy ở Windows/XAMPP.
- [ ] Tất cả đoạn demo đã được đánh dấu rõ mức độ minh họa/chạy được.
- [ ] Không có secret, dữ liệu tài chính thật hoặc mô tả sai lệch phạm vi SRS.

# 19. Danh sách quyết định cần nhóm phê duyệt và ghi ADR

| ADR ID | Quyết định cần xác nhận | Đề xuất hiện tại |
|---|---|---|
| ADR-001 | Stack triển khai chính | Vanilla HTML/CSS/JS + PHP 8.2+ + MySQL 8.0+ |
| ADR-002 | Toolchain PHP | Composer, PSR-4, PSR-12, PHPUnit, PHPStan |
| ADR-003 | Auth/session | JWT access in memory; refresh token HttpOnly cookie + revocation |
| ADR-004 | Precision money | BCMath + DECIMAL; tập trung Half-Up, VND0/USD-EUR2/FX8 |
| ADR-005 | Commit transaction | Ledger append-only, balance cache, outbox, unique idempotency |
| ADR-006 | Các mốc triển khai | C0→C5 như mục 17 |
| ADR-007 | Phiên bản API | `/api/v1`, response envelope thống nhất |
| ADR-008 | Kiểm soát quality gate | PSR-12 + tests ≥80% Domain/Application + PR review |

**Nguyên tắc thay đổi:** Trưởng nhóm cập nhật ADR, người sửa cập nhật API contract/test và PR; thay đổi làm sai SRS phải đi theo change request trước khi code. Không dùng tài liệu Coding Convention để tự ý sửa quy tắc nghiệp vụ đã chốt.

# 20. Phụ lục – Nguồn chuẩn và tài liệu gốc

## 20.1. Tài liệu nhóm từ TailieuSE

1. `02_elaboration/SRS.md` – nguồn yêu cầu/BR/NFR/FR/UC và architectural decisions chính.
2. `01_inception/ Business-Modelling.md` – bài toán, stakeholder, logic phân hệ.
3. `00_concept/ideas.md` – mô tả mục tiêu nguyên thủy.
4. `02_elaboration/uml_modeling/usecase_diagram.md` – actor và Use Case.
5. `02_elaboration/uml_modeling/activity_diagram.md` – luồng hoạt động.
6. `02_elaboration/uml_modeling/sequence_diagram/sq01_auth_jwt.md` đến `sq08_admin_user_broadcast.md` – hợp đồng tương tác.
7. `NhomVozSRS.md` và các bản PDF/Backlog trong ZIP – chỉ đối chiếu khi cần, không ghi đè baseline mới.

## 20.2. Chuẩn kỹ thuật tham khảo

- PHP-FIG PSR-12: https://www.php-fig.org/psr/psr-12/
- PHP-FIG PSR-4: https://www.php-fig.org/psr/psr-4/
- PHP manual – BCMath: https://www.php.net/book.bc.php
- PHP manual – PDO transactions: https://www.php.net/manual/en/pdo.begintransaction.php
- PHP manual – password_hash: https://www.php.net/manual/en/function.password-hash.php
- MySQL 8.0 – DECIMAL exact-value: https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html
- MySQL 8.0 – InnoDB locking: https://dev.mysql.com/doc/refman/8.0/en/innodb-locks-set.html
- OWASP Session Management: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
- OWASP CSV Injection: https://owasp.org/www-community/attacks/CSV_Injection
- MDN – Fetch API: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API
- MDN – Web accessibility: https://developer.mozilla.org/en-US/docs/Web/Accessibility

---

**Kết luận:** Bộ quy ước này chuyển các yêu cầu WealthFlow từ pha Elaboration sang những nguyên tắc mã nguồn cụ thể cho HTML/CSS/JavaScript/PHP/MySQL, đồng thời thiết lập các quality gates có thể kiểm chứng trong Construction. Khi nhóm phê duyệt các ADR ở Mục 19, phiên bản này trở thành baseline review chung cho mã nguồn, tài liệu và kiểm thử.
