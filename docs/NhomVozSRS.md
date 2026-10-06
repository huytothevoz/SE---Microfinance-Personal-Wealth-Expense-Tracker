# ĐẶC TẢ YÊU CẦU PHẦN MỀM - NHÓM VOZ

<!-- [Bản PDF để in và xem sơ đồ](NhomVozfinal_DaChinhSua.pdf) -->

TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG
KHOA CÔNG NGHỆ THÔNG TIN

**Microfinance Personal Wealth & Expense Tracker**

Ứng dụng quản lý tài chính cá nhân và tài chính vi mô

- **Môn học:** Công nghệ phần mềm
- **Nhóm / Lớp:** Voz / N3
- **Giảng viên:** Võ Thị Kim Anh
- **Thành viên:** Võ Trịnh Quốc Huy; Võ Văn Khôi Nguyên; Nguyễn Thành Trung; Phan Nguyễn Tuấn Kiệt


---

### Mục lục

| Phần | Nội dung / cách sử dụng |
| --- | --- |
| [1](#phan-1) | Mục đích, phạm vi, yêu cầu người dùng, thuật ngữ và giả định. |
| [2](#phan-2) | Ranh giới hệ thống, tác nhân, quy tắc nghiệp vụ và dữ liệu. |
| [3](#phan-3) | Danh mục và đặc tả chức năng bằng ngôn ngữ có cấu trúc. |
| [4](#phan-4) | Use case của tác nhân và các kịch bản xử lý nền. |
| [5](#phan-5) | NFR, lượng hóa chất lượng theo ISO/IEC 25010:2023. |
| [6](#phan-6) | Ma trận truy vết và tiêu chí nghiệm thu. |


---

<a id="phan-1"></a>

## 1. Giới thiệu và yêu cầu người dùng

### 1.1 Mục đích và phạm vi

Hệ thống giúp cá nhân, freelancer và hộ kinh doanh vi mô ghi nhận tài sản, nợ phải trả, thu chi, chuyển tiền giữa các ví của cùng một chủ sở hữu; kiểm soát ngân sách, theo dõi khoản định kỳ, mục tiêu và báo cáo..

### 1.2 Yêu cầu người dùng

| Mã | Nhu cầu và lý do | Phân rã |
| --- | --- | --- |
| UR01 | Tạo và sử dụng tài khoản an toàn; giữ dữ liệu riêng tư. | FR01, FR11, FR12 |
| UR02 | Quản lý ví và ghi thu/chi/chuyển tiền chính xác; sửa sai có dấu vết. | FR02, FR03, FR04 |
| UR03 | Theo dõi chi tiêu và khoản định kỳ để tránh vượt ngân sách, quên ghi nhận. | FR05, FR06 |
| UR04 | Theo dõi tiến độ tiết kiệm và tình hình dòng tiền. | FR07, FR08, FR13 |
| UR05 | Tra cứu, đối soát và xuất số liệu đã ghi nhận. | FR09 |
| UR06 | Nhập từ ảnh nhưng được kiểm tra số liệu trước khi ghi sổ. | FR14 |
| UR07 | Quản trị tài khoản, cấu hình và phục hồi dịch vụ khi sự cố. | FR10, FR11, FR12 |
| UR08 | Dùng hệ thống phản hồi nhanh, dễ thao tác, không mất hay lộ dữ liệu. | Toàn bộ NFR |


<a id="phan-2"></a>

## 2. Bối cảnh và quy tắc miền nghiệp vụ

### 2.1 Tác nhân

| Mã | Vai trò | Quyền / tương tác |
| --- | --- | --- |
| A01 | Khách / người dùng | Đăng ký, đăng nhập; sau xác thực chỉ thao tác dữ liệu của mình. |
| A02 | Quản trị viên | Quản lý trạng thái/nhóm quyền người dùng, cấu hình vận hành, xem nhật ký vận hành, backup/restore. Không mặc nhiên được xem sổ cá nhân. |
| A03 | Nhà cung cấp tỷ giá | Hệ thống bên ngoài trả dữ liệu tỷ giá qua giao diện IF02. Tên nhà cung cấp chưa chốt; dùng bộ dữ liệu giả lập để kiểm thử. |

### 2.2 Môi trường, giao diện và giới hạn

- **IF01 - Người dùng / API:** Web responsive; máy khách gửi JSON qua HTTPS. Thời điểm có múi giờ; tiền là chuỗi thập phân kèm mã tiền tệ. Phản hồi gồm mã kết quả, thông báo, lỗi từng trường và requestId; không trả stack trace. 401: chưa xác thực; 403: sai quyền vai trò; 404: đối tượng không tồn tại/không thuộc người dùng; 409: xung đột; 422: sai dữ liệu; 503: phụ thuộc chưa sẵn sàng.
- **IF02 - Tỷ giá:** Nhận cặp tiền, rate > 0, effectiveAt, fetchedAt và source. Từ chối thiếu trường, sai cặp hoặc thời điểm ở tương lai; lỗi không được ghi đè tỷ giá hợp lệ. Cấu hình nhà cung cấp là tham số triển khai, không gắn cứng trong SRS.
- **IF03 - Ảnh và báo cáo:** Ảnh JPEG/PNG tối đa 10 MB/ảnh [ĐX]; kiểm tra chữ ký định dạng. Xuất CSV UTF-8 theo FR09; ô văn bản có nguy cơ được trình bảng tính hiểu là công thức phải được trung hòa.
- **IF04 - Kho sao lưu:** Kho độc lập với ổ lưu dữ liệu chính; chỉ tài khoản vận hành có quyền đọc/ghi. Danh mục bản sao gồm mã, thời điểm dữ liệu, kích thước, checksum, phiên bản lược đồ và trạng thái kiểm chứng.
- **Giới hạn thiết kế:** Yêu cầu tính nguyên tử, bền vững, cô lập và nhất quán; không áp đặt microservices hay API Gateway. Kiến trúc nội bộ, công nghệ DB và ngôn ngữ lập trình thuộc thiết kế. [L]

### 2.3 Các chính sách làm rõ [ĐX]

Phiên bản đầu hỗ trợ VND, USD, EUR; múi giờ mặc định Asia/Ho_Chi_Minh, có thể đổi cho kỳ tương lai. Tiền cơ sở khóa sau bút toán đầu tiên. Ví chỉ gồm tài sản/nợ; danh mục ánh xạ sang tài khoản thu/chi. Không cho ví tài sản âm; tài khoản nợ được dùng khi chi tiêu vay nợ. Báo cáo theo giá trị ghi sổ, không tự đánh giá lại ngoại tệ theo giá thị trường.

### 2.4 Quy tắc nghiệp vụ dùng chung

| Mã | Quy tắc |
| --- | --- |
| BR01 | Mỗi giao dịch đã ghi sổ có ít nhất một dòng Nợ và một dòng Có; tổng DebitBase = tổng CreditBase chính xác theo đơn vị nhỏ nhất của tiền cơ sở. Không cộng trực tiếp 100 USD với 100 VND. [G/L] |
| BR02 | Sổ hỗ trợ đủ Asset, Liability, Equity, Income, Expense. Số dư tài sản = Nợ - Có; số dư nợ/vốn/thu = Có - Nợ; chi = Nợ - Có. A = L + E + (I - X), với E không gồm lợi nhuận kỳ hiện tại; khi kết chuyển, E bao gồm I - X. Số dư đầu kỳ có tài khoản vốn đối ứng. [L] |
| BR03 | Giá trị tiền dùng số thập phân chính xác, không dùng dấu phẩy động nhị phân. VND 0, USD/EUR 2 chữ số lẻ; tỷ giá tối đa 8 chữ số lẻ. Làm tròn half-up về đơn vị tiền cơ sở; chênh lệch làm tròn được ghi thành dòng riêng có dấu đúng, không sửa số liệu âm thầm. [ĐX] |
| BR04 | Tỷ giá dùng bản hợp lệ mới nhất có effectiveAt ≤ thời điểm giao dịch, tuổi ≤ 24 giờ. Giao dịch lùi ngày dùng tỷ giá lịch sử tương ứng; thiếu/hết hạn thì giữ nháp và không ghi sổ. Snapshot tỷ giá bất biến sau khi ghi sổ. [G/L/ĐX] |
| BR05 | Thu: Nợ ví tài sản/Có thu nhập. Chi: Nợ chi phí/Có ví tài sản hoặc nợ phải trả. Chuyển nội bộ: Nợ ví đích/Có ví nguồn; không tính là thu/chi. Không chuyển vào chính ví nguồn; cả hai thuộc cùng chủ. [L] |
| BR06 | Giao dịch Posted không sửa/xóa vật lý. Đảo giao dịch tạo bộ dòng ngược dấu Nợ/Có với đúng số tiền và snapshot cũ; chỉ đảo một lần. Sửa sai = đảo + giao dịch thay thế, phải liên kết bản gốc. Draft được sửa/xóa. [G/L] |
| BR07 | Ngân sách theo danh mục chi, theo tháng trong múi giờ người dùng, hạn mức > 0 và tính bằng tiền cơ sở. Tổng chi là số ròng sau bút toán đảo, theo ngày hiệu lực; chuyển ví/số dư đầu kỳ không được tính. Một ngân sách hoạt động cho mỗi danh mục/tháng. [G/L/ĐX] |
| BR08 | Usage = chi ròng / hạn mức × 100. < 80%: bình thường; từ 80% đến < 100%: vàng; ≥ 100%: đỏ. Chỉ một thông báo mỗi mức/ngân sách/kỳ; nhảy từ dưới 80 lên ≥ 100 chỉ gửi đỏ và đánh dấu vàng đã vượt. Đảo giao dịch cập nhật trạng thái, không xóa thông báo lịch sử. [G/L] |
| BR09 | Mỗi kỳ định kỳ chỉ có một giao dịch/nháp với khóa duy nhất (subscriptionId, dueDate). Tự động ghi sổ phải được người dùng bật rõ ràng; mặc định tạo nháp và nhắc. Ngày 29-31 không tồn tại thì dùng ngày cuối tháng, giữ ngày neo gốc cho kỳ tiếp theo. [G/L/ĐX] |
| BR10 | Mục tiêu là phân bổ theo dõi, không làm tăng tài sản hay tự tạo thu nhập. CurrentAmount là tổng các khoản phân bổ còn hiệu lực; TargetAmount > 0; Progress = CurrentAmount/TargetAmount × 100. Thanh tiến độ tối đa 100%, số thực tế vẫn hiển thị. [L/ĐX] |
| BR11 | Mọi bản ghi nghiệp vụ thuộc một chủ sở hữu. Quyền được kiểm tra ở máy chủ kể cả truy vấn danh sách, xuất tệp, truy cập ảnh và tác vụ nền. Vai trò quản trị không cấp quyền đọc tài chính cá nhân. [G/L] |
| BR12 | Toàn bộ giao dịch, bút toán, thay đổi số dư và sự kiện cần xử lý tiếp phải được lưu nguyên tử. Ghi sổ thất bại thì không có tác động tài chính; lỗi cảnh báo sau commit được thử lại và không đảo ngược giao dịch đã thành công. [G/L] |
| BR13 | Cùng người dùng + idempotency key + nội dung trả lại cùng kết quả; cùng khóa khác nội dung bị từ chối. Bản ghi khóa của giao dịch đã ghi sổ tồn tại cùng giao dịch. Khóa cạnh tranh phải ngăn ghi trùng và chi vượt số dư. [ĐX] |
| BR14 | Giao dịch nháp không tính vào số dư/báo cáo. Báo cáo gồm giao dịch đã ghi sổ và các dòng đảo, tránh loại bản gốc rồi trừ thêm lần nữa. Lưu cả thời điểm nghiệp vụ và thời điểm hệ thống ghi nhận. [L] |
| BR15 | Giá trị sổ ngoại tệ [ĐX]: phần tăng số dư ví định giá theo tỷ giá thời điểm nghiệp vụ; phần giảm dùng giá trị sổ bình quân trước ghi nhận (BookBase/BalanceOriginal). Giảm toàn bộ thì lấy đúng toàn bộ BookBase để không còn dư làm tròn. Chênh giữa giá trị đối ứng theo tỷ giá giao dịch và giá trị sổ ghi riêng vào lãi/lỗ tỷ giá. Lưu cả tỷ giá thị trường và tỷ lệ ghi sổ khi khác nhau. Thứ tự tính bình quân là thứ tự commit; lùi ngày không tính lại giá vốn các giao dịch đã ghi. Đảo giữ nguyên giá trị gốc nhưng bị từ chối nếu làm số dư nguyên tệ/giá trị sổ ví âm hoặc nguyên tệ = 0 mà giá trị sổ khác 0; khi đó cần quy trình điều chỉnh mở rộng ngoài phiên bản này. |

### 2.5 Dữ liệu logic và trạng thái

Đây là mô hình dữ liệu khái niệm phục vụ yêu cầu, không phải lược đồ SQL bắt buộc. Mỗi đối tượng có mã định danh ổn định, chủ sở hữu khi phù hợp, thời điểm tạo và phiên bản để phát hiện cập nhật đồng thời.

| Đối tượng | Dữ liệu tối thiểu / quan hệ |
| --- | --- |
| User | Email duy nhất, passwordHash, role, status, baseCurrency, timeZone. Một User có nhiều Wallet, Category, Transaction, Budget, Subscription, Goal. |
| Wallet / LedgerAccount | Wallet: tên, Asset/Liability, currency, status; mỗi ví ánh xạ một LedgerAccount. Sổ còn có Equity/Income/Expense và tài khoản chênh lệch. Balance suy ra từ sổ, hoặc là giá trị lưu đệm phải đối soát được. |
| Transaction / LedgerEntry | Transaction: loại, ngày hiệu lực, mô tả, trạng thái, nguồn, idempotencyKey, originalId. Có từ 2 LedgerEntry khi Posted; mỗi dòng có accountId, debit/credit, amount, currency, amountBase, tỷ giá thị trường và tỷ lệ ghi sổ theo BR15 nếu khác. |
| CurrencyRate | Cặp tiền, rate, effectiveAt, fetchedAt, source; nhiều phiên bản theo thời gian, không thay đổi snapshot của giao dịch cũ. |
| Category / Budget | Category: tên, loại thu/chi, tài khoản sổ liên kết, trạng thái. Budget: categoryId, tháng, múi giờ kỳ, hạn mức tiền cơ sở; các mức 80/100 cố định. |
| Subscription / Occurrence | Ví, danh mục, số tiền, tiền tệ, chu kỳ, ngày neo, dueDate tiếp theo, chế độ, trạng thái; occurrence giữ dueDate, kết quả, transactionId và lỗi. |
| Goal / Contribution | Mục tiêu: tên, đích tiền cơ sở, hạn chót, trạng thái. Contribution: số tiền, ngày, nguồn tham chiếu tùy chọn và trạng thái; tổng hợp thành CurrentAmount. |
| Notification / Audit / Backup | Thông báo có khóa chống trùng và readAt; audit có actor, hành động, thời điểm, mã đối tượng/kết quả; backup có manifest/checksum/trạng thái. Ảnh thuộc chủ sở hữu và gắn với nháp hoặc giao dịch. |



---

<a id="phan-3"></a>

## 3. Đặc tả yêu cầu chức năng

FR là điều hệ thống phải làm; UC là một hoặc nhiều cách tác nhân sử dụng các chức năng đó. Không có quy tắc một FR bắt buộc tương ứng một UC. FR03, FR04 và FR12 được nhiều UC dùng chung; các tác vụ theo lịch được đặc tả bằng SYS. Cấu trúc dưới đây áp dụng Sommerville §4.3.2 và bổ sung thuộc tính truy vết theo định hướng SRS [S1, S2].

Trong mỗi phiếu, các câu FRnn.xx là yêu cầu bắt buộc. Mọi bước kiểm tra thất bại phải giữ nguyên dữ liệu đã commit, trừ nhật ký và trạng thái lỗi được nêu rõ. Tiêu chí TC-Fnn bao phủ toàn bộ các câu con của FRnn; mục 6 quy định cách tách thành các ca thử cụ thể.

### FR01 - Định danh và phiên truy cập

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR01; UC01, UC02, UC03; BR11.
- **Chức năng và đầu vào / nguồn:** Nhận email, mật khẩu, tên hiển thị, tiền cơ sở từ người dùng đăng ký; thông tin đăng nhập/đổi mật khẩu từ chủ tài khoản.
- **Đầu ra / đích nhận:** Kết quả đăng ký hoặc phiên xác thực gửi về người dùng; hồ sơ và thông tin kiểm chứng mật khẩu lưu trong hệ thống.
- **Tiền điều kiện:** Đăng ký: chưa có email này. Đăng nhập: tài khoản tồn tại và hoạt động. Đổi mật khẩu: có phiên và biết mật khẩu hiện tại.
- **Dữ liệu cần có / phụ thuộc:** Kho người dùng, chính sách mật khẩu NFR02, trạng thái khóa và cấu hình hồ sơ FR11.

**FR01.01** Hệ thống phải tạo duy nhất một tài khoản cho email đã trim và chuẩn hóa không phân biệt hoa thường khi đầu vào hợp lệ.

**FR01.02** Hệ thống phải chỉ cấp phiên cho thông tin xác thực đúng và tài khoản hoạt động; đăng nhập thất bại trả thông báo chung, không phân biệt email không tồn tại hay sai mật khẩu.

**FR01.03** Hệ thống phải vô hiệu hóa phiên khi đăng xuất; phiên hết hạn hoặc bị thu hồi không thể gọi tài nguyên được bảo vệ.

**FR01.04** Hệ thống phải đổi mật khẩu sau khi xác minh mật khẩu cũ và thu hồi mọi phiên cũ; hồ sơ cá nhân được phép xem và sửa tên hiển thị.

- **Ngoại lệ:** Email trùng/sai định dạng hoặc mật khẩu không đạt: từ chối, không tạo bản ghi dở dang. Tài khoản khóa: từ chối truy cập. Không có luồng “quên mật khẩu qua email” trong phạm vi này.
- **Hậu điều kiện:** Tài khoản/phiên mới hợp lệ hoặc mật khẩu được thay; các lần thất bại không thay đổi dữ liệu tài chính.
- **Tác động phụ:** Ghi nhật ký bảo mật, không ghi mật khẩu hay token thô.
- **Kiểm chứng TC-F01:** Thử email hoa/thường trùng; đúng/sai mật khẩu; tài khoản khóa; token hết hạn/đăng xuất; đổi mật khẩu và dùng lại phiên cũ. Kết quả phải đúng từng yêu cầu.

### FR02 - Quản lý ví, ghi nhận và điều chỉnh giao dịch

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR02; UC04-UC09; BR01-BR06, BR11-BR14.
- **Chức năng và đầu vào / nguồn:** Người dùng tạo/đổi tên/lưu trữ ví hoặc nhập loại thu/chi/chuyển ví, ví nguồn/đích, danh mục, số tiền, ngày hiệu lực, mô tả và khóa yêu cầu; bản nháp có thể đến từ FR06 hoặc FR14.
- **Đầu ra / đích nhận:** Ví và số dư đầu kỳ, hoặc mã giao dịch, trạng thái, số dư mới và chi tiết sổ trả cho người dùng; sự kiện cập nhật số liệu gửi xử lý nền.
- **Tiền điều kiện:** Phiên hợp lệ và sở hữu dữ liệu. Với giao dịch: ví/danh mục hoạt động, đủ số dư ví tài sản; ngày hiệu lực không ở tương lai.
- **Dữ liệu cần có / phụ thuộc:** Tiền cơ sở và danh mục FR11; sổ FR03; tỷ giá FR04; phân quyền FR12; tài khoản Equity đối ứng cho số dư đầu kỳ.

**FR02.01** Hệ thống phải lưu, đọc, sửa và xóa nháp của chủ sở hữu mà không tác động số dư.

**FR02.02** Hệ thống phải ghi sổ giao dịch thu/chi hợp lệ với số tiền > 0, danh mục đúng loại và tiền tệ phù hợp ví.

**FR02.03** Hệ thống phải ghi chuyển tiền giữa hai ví khác nhau cùng chủ sở hữu; chuyển khác tiền yêu cầu số tiền nguồn và số tiền đích được xác nhận, định giá cả hai theo snapshot.

**FR02.04** Hệ thống phải từ chối sửa/xóa giao dịch đã ghi sổ và cho phép tạo bút toán đảo; yêu cầu “sửa giao dịch” phải ghi đảo và bản thay thế nguyên tử.

**FR02.05** Hệ thống phải áp dụng BR13 cho tạo, đảo và thay thế; yêu cầu lặp không được tạo thêm bút toán.

**FR02.06** Hệ thống phải cho tạo ví tài sản/nợ và tài khoản sổ liên kết; số dư đầu kỳ lớn hơn 0 phải được ghi với vốn đối ứng trong cùng thao tác nguyên tử.

**FR02.07** Hệ thống phải liệt kê và cho đổi tên ví của chủ sở hữu; loại và tiền tệ bị khóa khi ví đã có bút toán.

**FR02.08** Hệ thống phải cho lưu trữ ví có số dư bằng 0 nếu không còn lịch định kỳ hoạt động; ví lưu trữ không nhận giao dịch mới nhưng vẫn xuất hiện trong lịch sử.

- **Ngoại lệ:** Thiếu tỷ giá: giữ nháp hoặc không tạo ví có số dư đầu kỳ. Số tiền/số dư/tên ví sai, ví lưu trữ, danh mục sai hoặc ví còn số dư/lịch hoạt động khi lưu trữ: từ chối và nêu lỗi. Lỗi ghi sổ: rollback toàn bộ.
- **Hậu điều kiện:** Ví được tạo cùng bút toán mở sổ cân bằng hoặc giao dịch được ghi đúng một lần. Nháp không đổi sổ; ví lưu trữ không mất lịch sử; đảo không được làm ví tài sản âm.
- **Tác động phụ:** Audit; cập nhật ngân sách/dashboard; đổi tên ví không xóa bút toán mở sổ.
- **Kiểm chứng TC-F02:** Mở ví tài sản 1.000.000 và ví nợ 500.000: tài sản ròng 500.000, thu/chi bằng 0. Sau đó thử thu, chi, chuyển, lưu trữ ví còn tiền, gửi lặp cùng khóa và đảo hai lần.

### FR03 - Sổ kế toán kép và tính nguyên tử

- **Nguồn / ưu tiên / truy vết:** [G/L]; P1; UR02; UC04-UC09, SYS02; BR01-BR06, BR12-BR14.
- **Chức năng và đầu vào / nguồn:** Nhận đề nghị ghi sổ đã kiểm tra từ chức năng giao dịch, mở ví hoặc tác vụ định kỳ; gồm các tài khoản và giá trị gốc/quy đổi.
- **Đầu ra / đích nhận:** Tập bút toán cân bằng, số dư hoặc mã lỗi trả về chức năng gọi; dữ liệu lưu bền vững trong sổ.
- **Tiền điều kiện:** Chủ sở hữu và loại tài khoản hợp lệ; các số tiền/tỷ giá đã xác định; chưa xử lý khóa nghiệp vụ này.
- **Dữ liệu cần có / phụ thuộc:** Hệ tài khoản đủ năm loại; đơn vị tiền cơ sở; quy tắc làm tròn; bản ghi sự kiện tiếp nối.

**FR03.01** Hệ thống phải tạo các dòng Nợ/Có theo loại giao dịch và kiểm tra cân bằng bằng amountBase cho từng giao dịch trước commit.

**FR03.02** Hệ thống phải commit hoặc rollback đồng thời giao dịch, các dòng sổ, số dư lưu đệm nếu có và sự kiện cần xử lý sau đó.

**FR03.03** Hệ thống phải tính số dư từ các dòng đã ghi sổ và phát hiện chênh lệch với số dư lưu đệm khi đối soát.

**FR03.04** Hệ thống phải giữ bất biến bút toán đã ghi; khi đảo phải giữ tham chiếu gốc, tài khoản, số tiền và tỷ giá ban đầu.

- **Ngoại lệ:** Thiếu dòng đối ứng, sai chủ sở hữu, tổng không cân bằng hoặc lỗi lưu: rollback, ghi lỗi vận hành. Khi không xác định chắc giao dịch đã commit hay chưa, tra bằng khóa cũ, không gửi khóa mới.
- **Hậu điều kiện:** Không tồn tại giao dịch đã ghi sổ thiếu dòng, mất cân bằng hoặc có số dư cập nhật một phần.
- **Tác động phụ:** Audit của commit thành công; log lỗi tách khỏi phần dữ liệu tài chính rollback.
- **Kiểm chứng TC-F03:** Chèn lỗi trước/sau từng bước ghi; kiểm tra tổng Nợ/Có và số dư; thử 100 yêu cầu cạnh tranh trên một ví. Không có ghi dở dang, âm trái quy tắc hoặc trùng khóa.

### FR04 - Tiền tệ và tỷ giá

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR02; UC04-UC09, UC15-UC18; SYS01; BR03, BR04, BR15.
- **Chức năng và đầu vào / nguồn:** Nhận cặp tiền/thời điểm từ nghiệp vụ; tỷ giá từ A03 qua IF02; cấu hình tiền tệ từ quản trị.
- **Đầu ra / đích nhận:** Snapshot tỷ giá và giá trị tiền cơ sở gửi sổ/báo cáo; danh sách tỷ giá có nguồn, tuổi dữ liệu cho quản trị.
- **Tiền điều kiện:** Cặp tiền được hỗ trợ; số tiền dương; tiền cơ sở đã chọn.
- **Dữ liệu cần có / phụ thuộc:** Dữ liệu tỷ giá lịch sử; lịch cập nhật ít nhất hàng ngày [G]; chính sách tuổi 24 giờ [ĐX].

**FR04.01** Hệ thống phải nhập và lưu tỷ giá hợp lệ theo phiên bản, cùng effectiveAt, fetchedAt và nguồn; cùng tiền cơ sở dùng rate = 1.

**FR04.02** Hệ thống phải chọn tỷ giá thị trường theo BR04 để quy đổi giá trị giao dịch; amountBase của dòng ví phải dùng tỷ lệ ghi sổ theo BR15, lưu đủ snapshot thị trường và tỷ lệ ghi sổ.

**FR04.03** Hệ thống phải ghi chênh lệch giữa giá trị sổ ví và các dòng đối ứng vào tài khoản lãi/lỗ tỷ giá hoặc làm tròn thích hợp để tổng giá trị cơ sở cân bằng; ví hết nguyên tệ phải hết giá trị sổ.

**FR04.04** Hệ thống phải giữ nguyên giá trị giao dịch lịch sử khi tỷ giá mới đến; trình bày báo cáo là giá trị ghi sổ và tách chênh lệch tỷ giá khỏi dòng tiền thu/chi thực.

- **Ngoại lệ:** Nguồn tỷ giá lỗi: giữ phiên bản cũ; nếu không còn bản đủ mới cho giao dịch thì không ghi sổ ngoại tệ. Giao dịch nội tệ vẫn hoạt động.
- **Hậu điều kiện:** Chỉ dữ liệu tỷ giá hợp lệ được công bố; lịch sử đã ghi sổ không bị đổi.
- **Tác động phụ:** Nhật ký đồng bộ và cảnh báo vận hành khi quá hạn; không phát sinh giao dịch chỉ vì tỷ giá đổi.
- **Kiểm chứng TC-F04:** Nhận 100 USD ở 25.000 cho giá trị 2.500.000 VND. Xuất toàn bộ lấy 2.490.000: lỗ 10.000; lấy 2.600.000: lãi 100.000, ví USD còn 0 cả hai đơn vị. Thử lịch sử, thiếu/hết hạn tỷ giá và làm tròn bình quân.

### FR05 - Ngân sách và cảnh báo

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR03; UC11, UC12; SYS03; BR07, BR08.
- **Chức năng và đầu vào / nguồn:** Người dùng gửi danh mục chi, tháng và hạn mức; sự kiện giao dịch đã commit kích hoạt tính lại.
- **Đầu ra / đích nhận:** Ngân sách, số đã chi/còn lại, tỷ lệ và thông báo trong ứng dụng đến người dùng.
- **Tiền điều kiện:** Danh mục chi thuộc người dùng; hạn mức > 0; không trùng danh mục/tháng.
- **Dữ liệu cần có / phụ thuộc:** Sổ giao dịch đã ghi, múi giờ kỳ, thông báo chống trùng.

**FR05.01** Hệ thống phải cho chủ sở hữu tạo, xem, sửa hạn mức và lưu trữ ngân sách; không cho sửa danh mục/tháng của ngân sách đã có chi, phải tạo ngân sách khác [ĐX].

**FR05.02** Hệ thống phải tính chi ròng và mức sử dụng theo BR07 sau giao dịch mới, đảo giao dịch hoặc đổi hạn mức.

**FR05.03** Hệ thống phải phát thông báo theo BR08, lưu mức/ngân sách/kỳ và thời điểm; sự kiện phát lại không sinh thông báo trùng.

**FR05.04** Hệ thống phải hiển thị danh sách thông báo của chủ sở hữu, cho xem chi tiết và đánh dấu đã đọc.

- **Ngoại lệ:** Không có ngân sách: bỏ qua cảnh báo. Xử lý thông báo lỗi: lưu trạng thái chờ và thử lại; không thay đổi kết quả ghi sổ đã thành công.
- **Hậu điều kiện:** Số liệu phản ánh sổ trong thời hạn NFR04; thông báo cũ giữ lại dù chi giảm sau đảo.
- **Tác động phụ:** Cập nhật dashboard và readAt; không gửi email/SMS trong phiên bản này [ĐX].
- **Kiểm chứng TC-F05:** Hạn mức 1.000.000: thử 799.999, 800.000, 999.999, 1.000.000; thử nhảy thẳng lên đỏ, sự kiện lặp và đảo giao dịch. Mức màu/thông báo đúng, không trùng.

### FR06 - Khoản ghi nhận định kỳ

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR03; UC13; SYS02; BR09, BR12, BR13.
- **Chức năng và đầu vào / nguồn:** Người dùng nhập tên, ví thanh toán, danh mục chi, số tiền, chu kỳ tháng/năm, ngày neo và chế độ nháp/tự ghi sổ [ĐX].
- **Đầu ra / đích nhận:** Lịch định kỳ và trạng thái từng lần đến hạn; giao dịch/nháp và thông báo trả chủ sở hữu.
- **Tiền điều kiện:** Ví, danh mục hoạt động; số tiền > 0; tiền tệ trùng ví; người dùng đồng ý khi bật tự ghi sổ.
- **Dữ liệu cần có / phụ thuộc:** Lịch theo múi giờ người dùng; FR02-FR04, FR12; bảng occurrence với khóa BR09.

**FR06.01** Hệ thống phải cho chủ sở hữu tạo, xem, sửa kỳ tương lai, tạm dừng, tiếp tục hoặc hủy lịch; lịch sử các kỳ đã xử lý không được sửa.

**FR06.02** Hệ thống phải quét khoản đến hạn và các kỳ còn thiếu sau gián đoạn; mỗi kỳ chỉ tạo một occurrence.

**FR06.03** Hệ thống phải tạo nháp và nhắc người dùng ở chế độ mặc định; ở chế độ tự ghi sổ phải dùng cùng kiểm tra và sổ kép như giao dịch thủ công.

**FR06.04** Hệ thống phải xác định kỳ tiếp theo theo ngày neo, theo dõi kỳ thất bại để thử lại và không tạo bút toán trùng.

- **Ngoại lệ:** Thiếu tiền/tỷ giá hoặc ví bị lưu trữ: giữ occurrence chờ xử lý, thông báo người dùng, không ghi sổ. Khi tiếp tục lịch đã tạm dừng, các kỳ nằm trong khoảng dừng được bỏ qua [ĐX].
- **Hậu điều kiện:** Kỳ được đánh dấu thành công chỉ sau khi nháp hoặc giao dịch đã được lưu; ngày quét kế tiếp tách khỏi các occurrence còn lỗi.
- **Tác động phụ:** Audit, thông báo; chế độ tự ghi sổ có thể phát sinh cảnh báo ngân sách. Không thực hiện thanh toán ngân hàng.
- **Kiểm chứng TC-F06:** Thử ngày 31/01 → 28/02 → 31/03 trong năm không nhuận; chạy lại hai tiến trình cùng kỳ; thử mất dịch vụ qua hai kỳ, thiếu tiền và lịch tạm dừng.

### FR07 - Mục tiêu tài chính

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P2; UR04; UC14; BR10.
- **Chức năng và đầu vào / nguồn:** Tên, TargetAmount, TargetDate từ người dùng; các khoản phân bổ/tăng giảm tiến độ do chủ sở hữu nhập.
- **Đầu ra / đích nhận:** Mục tiêu, tổng đã phân bổ, tỷ lệ và số còn thiếu hiển thị cho người dùng/dashboard.
- **Tiền điều kiện:** Người dùng đã đăng nhập; đích > 0; thời hạn không trước ngày tạo.
- **Dữ liệu cần có / phụ thuộc:** Tiền cơ sở; danh sách Goal và Contribution; không suy diễn đóng góp từ chuyển ví.

**FR07.01** Hệ thống phải cho chủ sở hữu tạo, xem, sửa tên/đích/thời hạn, hủy và lưu trữ mục tiêu; không xóa lịch sử đóng góp.

**FR07.02** Hệ thống phải cho thêm khoản phân bổ dương và hủy khoản phân bổ đã nhập sai, có ngày và ghi chú.

**FR07.03** Hệ thống phải tính CurrentAmount và Progress theo BR10; hiển thị Đạt khi số hiện tại ≥ đích, Quá hạn khi hết ngày đích mà chưa đạt, còn lại Đang thực hiện.

- **Ngoại lệ:** Đích ≤ 0, ngày không hợp lệ, hủy đóng góp hai lần: từ chối. Số phân bổ là theo dõi thủ công, giao diện phải nêu rõ không phải số dư khả dụng.
- **Hậu điều kiện:** Tiến độ được tính lại nhất quán; dữ liệu sổ không đổi.
- **Tác động phụ:** Audit thay đổi; cập nhật dashboard.
- **Kiểm chứng TC-F07:** Đích 10.000.000, phân bổ 2.000.000 và 3.000.000 cho 50%; hủy khoản đầu còn 30%; vượt đích hiển thị số thực nhưng thanh tối đa 100%.

### FR08 - Dashboard tài chính

- **Nguồn / ưu tiên / truy vết:** [G/L]; P2; UR04; UC15; BR02, BR14.
- **Chức năng và đầu vào / nguồn:** Khoảng ngày, ví và chủ sở hữu từ yêu cầu xem tổng quan.
- **Đầu ra / đích nhận:** Tài sản ròng, tổng thu/chi, cơ cấu chi, xu hướng số dư, ngân sách và mục tiêu trên giao diện.
- **Tiền điều kiện:** Phiên hợp lệ; khoảng ngày bắt đầu ≤ kết thúc.
- **Dữ liệu cần có / phụ thuộc:** Sổ FR03, ngân sách FR05, mục tiêu FR07, dòng tiền FR13; thời điểm snapshot.

**FR08.01** Hệ thống phải hiển thị tài sản ròng = tổng tài sản - tổng nợ tại cuối khoảng ngày theo giá trị ghi sổ.

**FR08.02** Hệ thống phải hiển thị thu, chi, cơ cấu và xu hướng từ cùng bộ lọc và cùng mốc dữ liệu; thu/chi dùng định nghĩa FR13.

**FR08.03** Hệ thống phải hiển thị trạng thái ngân sách, tiến độ mục tiêu và thời điểm cập nhật; khi chưa có dữ liệu phải hiển thị giá trị 0 và hướng dẫn nhập giao dịch.

- **Ngoại lệ:** Bộ lọc sai: báo trường lỗi. Tính tổng hợp chậm: hiển thị dữ liệu gần nhất có mốc thời gian hoặc trạng thái đang cập nhật, không trình bày như dữ liệu mới.
- **Hậu điều kiện:** Chỉ dữ liệu của chủ sở hữu được hiển thị; không thay đổi số dư.
- **Tác động phụ:** Không có tác động nghiệp vụ.
- **Kiểm chứng TC-F08:** So từng thẻ và biểu đồ với bộ sổ chuẩn; thử dữ liệu rỗng, đổi khoảng ngày và người dùng khác. Tài sản ròng không tăng khi chuyển giữa hai ví.

### FR09 - Tra cứu, đối soát và xuất báo cáo

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1 tra cứu/đối soát, P2 xuất; UR05; UC17, UC18; BR01, BR11, BR14.
- **Chức năng và đầu vào / nguồn:** Khoảng ngày, ví, loại, trạng thái và từ khóa mô tả do người dùng nhập; lệnh đối soát trên dữ liệu của mình.
- **Đầu ra / đích nhận:** Danh sách phân trang, chi tiết dòng sổ, kết quả đối soát và tệp CSV tải về máy người dùng.
- **Tiền điều kiện:** Có phiên hợp lệ và quyền với tập dữ liệu; khoảng ngày hợp lệ.
- **Dữ liệu cần có / phụ thuộc:** Sổ, snapshot tỷ giá và liên kết đảo/thay thế; số dư ví; IF03.

**FR09.01** Hệ thống phải cho lọc/lập trang lịch sử và xem từng giao dịch với amount, currency, amountBase, tỷ giá, các dòng Nợ/Có và liên kết điều chỉnh.

**FR09.02** Hệ thống phải đối chiếu tổng Nợ/Có và số dư tính từ sổ với số dư ví lưu đệm nếu có, trả Đúng hoặc danh sách chênh lệch; không tự sửa sổ.

**FR09.03** Hệ thống phải xuất CSV UTF-8 theo đúng bộ lọc, gồm transactionId, effectiveAt, type, status, wallet, category, amount, currency, amountBase, rate, originalId và mô tả; số tiền dùng dấu chấm thập phân và không có phân cách hàng nghìn.

**FR09.04** Hệ thống phải bảo vệ tải tệp bằng quyền của người yêu cầu và giữ cùng snapshot trong suốt quá trình xuất; giới hạn 100.000 giao dịch/tệp [ĐX].

- **Ngoại lệ:** Không có kết quả: danh sách rỗng/CSV chỉ header. Vượt giới hạn: yêu cầu thu hẹp khoảng ngày. Lỗi xuất: thông báo thất bại, không trả tệp một phần như hoàn chỉnh.
- **Hậu điều kiện:** Báo cáo khớp bộ lọc và số dòng; chỉ đọc, không đổi sổ.
- **Tác động phụ:** Ghi audit xuất/đối soát, không lưu nội dung tài chính vào log vận hành.
- **Kiểm chứng TC-F09:** So 100 dòng đã biết với CSV và tổng sổ; thử mô tả chứa dấu phẩy, xuống dòng, tiếng Việt và ký tự công thức; thử tải tệp bằng người khác; tạo chênh lệch lưu đệm và kiểm tra phát hiện.

### FR10 - Sao lưu và khôi phục

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR07; UC21, UC22; SYS04; BR01, BR11, BR12.
- **Chức năng và đầu vào / nguồn:** Lệnh sao lưu/khôi phục từ Admin hoặc lịch sao lưu; mã backup và lý do khôi phục.
- **Đầu ra / đích nhận:** Bản sao có manifest/checksum; trạng thái job, biên bản kiểm tra và kết quả phục hồi cho Admin.
- **Tiền điều kiện:** Admin có quyền vận hành, xác thực lại trước restore; backup đích có thể truy cập; restore dùng cửa sổ bảo trì có chặn ghi.
- **Dữ liệu cần có / phụ thuộc:** DB nhất quán, ảnh/đính kèm, cấu hình cần phục hồi, khóa giải mã lưu riêng, IF04.

**FR10.01** Hệ thống phải tạo bản sao nhất quán tối thiểu hằng ngày và theo yêu cầu; lưu đủ dữ liệu để phục hồi quan hệ, ảnh, sổ và các occurrence định kỳ.

**FR10.02** Hệ thống phải kiểm tra checksum/manifest, lưu trạng thái và cho Admin liệt kê bản sao cùng thời điểm dữ liệu.

**FR10.03** Hệ thống phải khôi phục vào môi trường tách biệt trước, kiểm tra quyền sở hữu, số lượng bản ghi, quan hệ và cân bằng sổ rồi mới cho chuyển phục vụ.

**FR10.04** Hệ thống phải lưu bản trước phục hồi, chỉ mở ghi sau kiểm tra thành công và giữ audit vận hành ngoài tập dữ liệu bị phục hồi.

- **Ngoại lệ:** Bản sao hỏng, thiếu khóa hoặc sai phiên bản lược đồ: từ chối restore. Phục hồi/kiểm tra lỗi: giữ hệ thống bảo trì, không công bố dữ liệu lỗi, cho quay về bản trước đó.
- **Hậu điều kiện:** Thành công: dữ liệu tại mốc backup được phục hồi, phiên cũ bị thu hồi; thất bại không làm mất bản đang có.
- **Tác động phụ:** Gián đoạn có kiểm soát khi phục hồi; log và thông báo vận hành; lịch nền chỉ tiếp tục sau đối soát để tránh ghi trùng.
- **Kiểm chứng TC-F10:** Restore một backup có giao dịch đa tiền, ảnh và kỳ định kỳ; so checksum/số dòng/số dư; thử backup hỏng và lỗi giữa restore; đo RPO/RTO theo NFR04.

### FR11 - Danh mục, cấu hình và quản trị tài khoản

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P1; UR01, UR07; UC03, UC10, UC19, UC20; BR04, BR07, BR11.
- **Chức năng và đầu vào / nguồn:** Tên/loại danh mục từ người dùng; tiền cơ sở, múi giờ từ hồ sơ; tham số vận hành hoặc lệnh khóa/mở khóa/đổi vai trò kèm lý do từ Admin.
- **Đầu ra / đích nhận:** Danh mục, cấu hình phiên bản mới, trạng thái/quyền tài khoản và nhật ký vận hành đến người có quyền.
- **Tiền điều kiện:** Quyền đúng phạm vi; danh mục thuộc chủ sở hữu; giá trị cấu hình hợp lệ; Admin xác thực lại khi đổi vai trò.
- **Dữ liệu cần có / phụ thuộc:** Danh mục và tài khoản thu/chi; cờ đã có bút toán; lịch vận hành; User/role, phiên FR01, phân quyền FR12 và audit.

**FR11.01** Hệ thống phải cho người dùng tạo, đổi tên và lưu trữ danh mục thu/chi; danh mục đã được dùng không được đổi loại hoặc xóa lịch sử.

**FR11.02** Hệ thống phải cho chọn tiền cơ sở trước giao dịch đầu tiên và từ chối thay đổi sau đó; thay đổi múi giờ chỉ áp dụng cho kỳ ngân sách/định kỳ tương lai.

**FR11.03** Hệ thống phải cho Admin sửa các tham số vận hành hợp lệ, ghi người đổi và phiên bản; thay đổi lỗi phải giữ nguyên cấu hình cũ.

**FR11.04** Hệ thống phải cho Admin xem trạng thái tài khoản, khóa/mở khóa và thay đổi giữa User/Admin với lý do bắt buộc; không cung cấp mật khẩu.

**FR11.05** Hệ thống phải thu hồi quyền/phiên khi tài khoản bị khóa hoặc đổi vai trò và tạm ngừng ghi sổ tự động của tài khoản bị khóa.

**FR11.06** Hệ thống phải cho Admin xem kết quả tác vụ nền, lỗi, lịch sử cấu hình, backup/restore và thay đổi quyền; không mặc định hiển thị chi tiết tài chính cá nhân.

- **Ngoại lệ:** Tên danh mục trùng, tham số làm vi phạm RPO, sai quyền, thiếu lý do, tự vô hiệu hóa Admin cuối cùng hoặc xung đột cập nhật: từ chối và giữ trạng thái cũ.
- **Hậu điều kiện:** Danh mục/cấu hình có hiệu lực rõ ràng; trạng thái/quyền áp dụng từ yêu cầu tiếp theo; lịch sử của tài khoản bị khóa vẫn được giữ.
- **Tác động phụ:** Audit bắt buộc; thay đổi cấu hình có thể lên lịch chạy tiếp theo; ứng dụng không được xóa audit.
- **Kiểm chứng TC-F11:** Thử danh mục đã có giao dịch, đổi tiền cơ sở sau ghi sổ, tham số lịch sai, khóa tài khoản đang đăng nhập, User gọi quản trị và vô hiệu hóa Admin cuối cùng.

### FR12 - Kiểm soát truy cập API

- **Nguồn / ưu tiên / truy vết:** [G/L]; P1; UR01; mọi UC có xác thực, SYS01-SYS04; BR11.
- **Chức năng và đầu vào / nguồn:** Token/phiên, vai trò, tài nguyên và hành động từ mọi yêu cầu được bảo vệ; danh tính dịch vụ của tác vụ nền.
- **Đầu ra / đích nhận:** Cho phép hoặc từ chối thực hiện, trả mã lỗi IF01; ghi sự kiện bảo mật khi cần.
- **Tiền điều kiện:** Đường dẫn công khai duy nhất là đăng ký/đăng nhập và tài nguyên giao diện công khai; các đường còn lại được bảo vệ.
- **Dữ liệu cần có / phụ thuộc:** Phiên FR01; quyền/trạng thái FR11; quan hệ sở hữu trên tất cả đối tượng.

**FR12.01** Hệ thống phải kiểm chứng tính hợp lệ và hiệu lực phiên/token trước xử lý nghiệp vụ; danh tính lấy từ phiên đã kiểm chứng, không tin userId do máy khách gửi.

**FR12.02** Hệ thống phải kiểm tra quyền vai trò và quyền sở hữu trên từng đối tượng, danh sách, tệp xuất và ảnh; mọi đối tượng liên quan trong một lệnh đều phải cùng chủ sở hữu.

**FR12.03** Hệ thống phải áp dụng quyền tối thiểu cho tác vụ nền, kiểm tra trạng thái chủ tài khoản trước ghi sổ tự động và từ chối mặc định khi không xác định được quyền.

- **Ngoại lệ:** Chưa xác thực: 401; thiếu quyền vai trò: 403; đối tượng không sở hữu: 404, không tiết lộ tồn tại; tài khoản bị khóa: thu hồi truy cập.
- **Hậu điều kiện:** Lệnh bị từ chối không đọc/làm thay đổi dữ liệu tài chính của chủ khác.
- **Tác động phụ:** Audit bảo mật theo NFR02, không ghi token thô.
- **Kiểm chứng TC-F12:** Ma trận Guest/UserA/UserB/Admin/Service × các hành động trên tài nguyên; thử sửa userId, ID đoán được, URL tải trực tiếp, token giả/hết hạn và đối tượng con thuộc UserB.

### FR13 - Phân tích dòng tiền

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P2; UR04; UC16; BR05, BR14.
- **Chức năng và đầu vào / nguồn:** Khoảng ngày, mức nhóm ngày/tuần/tháng/quý/năm và bộ lọc ví từ người dùng.
- **Đầu ra / đích nhận:** Chuỗi số liệu thu, chi, dòng tiền ròng và bảng số liệu đối chiếu.
- **Tiền điều kiện:** Phiên hợp lệ; khoảng ngày có thứ tự; dữ liệu được tính cùng một snapshot.
- **Dữ liệu cần có / phụ thuộc:** Giao dịch đã ghi sổ; snapshot tiền cơ sở; múi giờ; tuần bắt đầu thứ Hai [ĐX].

**FR13.01** Hệ thống phải tổng hợp thu và chi theo ngày hiệu lực trong kỳ được chọn, bao gồm tác động của bút toán đảo tương ứng.

**FR13.02** Hệ thống phải tính NetCashFlow = TotalIncome - TotalExpense và loại chuyển ví nội bộ, số dư đầu kỳ, chênh lệch tỷ giá/làm tròn khỏi thu/chi.

**FR13.03** Hệ thống phải trả cả các kỳ không có giao dịch với giá trị 0; số trên biểu đồ phải bằng bảng chi tiết cùng bộ lọc.

- **Ngoại lệ:** Khoảng ngày sai: từ chối. Không có dữ liệu: trả dãy 0; dữ liệu chưa cập nhật phải kèm mốc thời gian.
- **Hậu điều kiện:** Số liệu chỉ đọc và có thể đối chiếu với sổ; không đồng nhất tài sản ròng với dòng tiền ròng.
- **Tác động phụ:** Không có tác động nghiệp vụ.
- **Kiểm chứng TC-F13:** Thu 5.000.000, chi 2.000.000, chuyển 1.000.000 cho dòng tiền 3.000.000. Thử ranh giới 00:00, cuối tháng/quý/năm, tuần có giao dịch đảo và kỳ rỗng.

### FR14 - Nhập giao dịch từ ảnh

- **Nguồn / ưu tiên / truy vết:** [G/L/ĐX]; P2; UR06; UC09; BR11-BR13.
- **Chức năng và đầu vào / nguồn:** Ảnh hóa đơn/biên nhận JPEG/PNG do người dùng tải qua IF03; dữ liệu sửa/xác nhận sau nhận dạng.
- **Đầu ra / đích nhận:** Bản nháp gồm số tiền, ngày, mô tả và tiền tệ nếu đọc được; giao dịch sau khi người dùng xác nhận.
- **Tiền điều kiện:** Phiên hợp lệ; ảnh đúng định dạng/kích thước; chủ sở hữu chọn ví và danh mục.
- **Dữ liệu cần có / phụ thuộc:** Bộ nhận dạng nội bộ; kiểm tra FR02-FR04; lưu ảnh theo quyền FR12.

**FR14.01** Hệ thống phải kiểm tra tệp tải lên và trích xuất các trường nhận dạng được thành nháp; đánh dấu trường thiếu/không chắc chắn.

**FR14.02** Hệ thống phải cho người dùng sửa mọi trường và chủ động xác nhận trước khi ghi sổ; không được tự hạch toán trực tiếp từ kết quả OCR.

**FR14.03** Hệ thống phải dùng cùng đường kiểm tra/ghi sổ như nhập tay và liên kết ảnh với giao dịch; cảnh báo nếu cùng chủ tải ảnh có cùng checksum, cho quyết định bỏ hoặc tiếp tục.

- **Ngoại lệ:** Ảnh mờ/không đọc được: báo nhận dạng không thành công và cho nhập tay. Tệp giả hoặc quá lớn: từ chối. Người dùng hủy: không có bút toán; nháp/ảnh chưa dùng tuân theo NFR02.
- **Hậu điều kiện:** Chỉ nội dung người dùng xác nhận mới được ghi; nháp giữ nguyên khi không hợp lệ.
- **Tác động phụ:** Lưu ảnh riêng tư và nhật ký nhập; không cam kết OCR chính xác tuyệt đối.
- **Kiểm chứng TC-F14:** Bộ 30 ảnh rõ/mờ, tiếng Việt, thiếu ngày/số tiền, tệp giả: kiểm tra trường nhận dạng, sửa lại và xác nhận. Không ảnh nào tự phát sinh Posted trước xác nhận.

---

<a id="phan-4"></a>

## 4. Mô hình tương tác và use case

Bộ UC dưới đây được đánh số lại, không giữ số cũ khi ý nghĩa đã thay đổi. UC04 cũ “Hạch toán” chuyển thành FR03; UC05 cũ “Quy đổi” chuyển thành FR04/SYS01; UC07 cũ “Kiểm tra hạn mức” chuyển thành SYS03. Những xử lý nội bộ này có đặc tả đầy đủ nhưng không có tác nhân giả “System/Worker”. [S1 §5.2.1]

Sơ đồ được chia theo nhóm để đọc được. Đường liền không có mũi tên biểu thị liên kết tác nhân - use case, không biểu thị thứ tự thực hiện. Đăng nhập là tiền điều kiện của UC được bảo vệ; không gắn «include Đăng nhập» cho mọi UC. Bản này không cần quan hệ include/extend: hành vi sổ kép và tỷ giá được tham chiếu bằng FR dùng chung, còn các nhánh nhập từ ảnh/sửa sai được mô tả trong kịch bản. [S4]

### 4.1 Danh mục use case theo tác nhân

| Mã | Use case | Tác nhân |
| --- | --- | --- |
| UC01 | Đăng ký | A01 - Người dùng |
| UC02 | Quản lý phiên và mật khẩu | A01 - Người dùng |
| UC03 | Cập nhật hồ sơ | A01 - Người dùng |
| UC04 | Quản lý ví | A01 - Người dùng |
| UC05 | Ghi thu nhập | A01 - Người dùng |
| UC06 | Ghi chi tiêu | A01 - Người dùng |
| UC07 | Chuyển giữa các ví | A01 - Người dùng |
| UC08 | Đảo / thay thế giao dịch | A01 - Người dùng |
| UC09 | Nhập giao dịch từ ảnh | A01 - Người dùng |
| UC10 | Quản lý danh mục | A01 - Người dùng |
| UC11 | Thiết lập ngân sách | A01 - Người dùng |
| UC12 | Xem cảnh báo | A01 - Người dùng |
| UC13 | Quản lý khoản định kỳ | A01 - Người dùng |
| UC14 | Theo dõi mục tiêu | A01 - Người dùng |
| UC15 | Xem tổng quan | A01 - Người dùng |
| UC16 | Phân tích dòng tiền | A01 - Người dùng |
| UC17 | Tra cứu và đối soát sổ | A01 - Người dùng |
| UC18 | Xuất báo cáo | A01 - Người dùng |
| UC19 | Quản trị tài khoản | A02 - Quản trị viên |
| UC20 | Cấu hình vận hành | A02 - Quản trị viên |
| UC21 | Tạo / kiểm tra sao lưu | A02 - Quản trị viên |
| UC22 | Khôi phục dữ liệu | A02 - Quản trị viên |

A01 ở trạng thái khách khi đăng ký/đăng nhập; các chức năng khác yêu cầu phiên hợp lệ. A02 chỉ có quyền vận hành đã chỉ định. A03 cung cấp tỷ giá cho SYS01 qua IF02. Worker và System là thành phần nội bộ, không phải tác nhân. Sơ đồ được giữ trong [bản PDF](NhomVozfinal_DaChinhSua.pdf).

### 4.2 Đặc tả use case

Quy tắc chung C-UC: trừ đăng ký/đăng nhập, cần phiên hợp lệ và quyền FR12; lỗi xác thực kết thúc không đổi nghiệp vụ. Mọi biểu mẫu giữ dữ liệu đã nhập khi báo lỗi trường; hủy trước xác nhận không phát sinh thay đổi. Mỗi thao tác sửa có kiểm tra phiên bản; người thắng được commit, người còn lại nhận xung đột để tải lại, không ghi đè âm thầm. Các ngoại lệ và tiêu chí chi tiết của FR liên kết vẫn có hiệu lực. Đây là quy tắc được kế thừa, không phải bỏ qua ngoại lệ.

#### UC01 - Đăng ký

- **Tác nhân / liên kết:** A01; FR01.01; NFR02; kiểm chứng TC-U01.
- **Kích thích / tiền điều kiện:** Khách chọn Đăng ký. Chưa có tài khoản cho email.
- **Dữ liệu:** Email, mật khẩu, tên, tiền cơ sở.

**Luồng chính:**

1. Khách nhập thông tin.
2. Hệ thống kiểm tra dữ liệu và tính duy nhất email.
3. Hệ thống lưu tài khoản và cấu hình ban đầu.
4. Hệ thống thông báo thành công, cho chuyển sang đăng nhập.

- **Luồng thay thế / lỗi:** Email trùng hoặc dữ liệu sai: thông báo phù hợp và yêu cầu sửa; lỗi lưu: không tạo tài khoản một phần.
- **Kết thúc:** Có một tài khoản mới; chưa có ví/giao dịch và chưa tự động có phiên.
- **Hoạt động đồng thời:** Hai yêu cầu cùng email: chỉ một thành công.

#### UC02 - Quản lý phiên và mật khẩu

- **Tác nhân / liên kết:** A01; FR01.02-.04; FR12; kiểm chứng TC-U02.
- **Kích thích / tiền điều kiện:** Người dùng chọn Đăng nhập. Tài khoản hoạt động.
- **Dữ liệu:** Email, mật khẩu; mật khẩu cũ/mới khi đổi.

**Luồng chính:**

1. Người dùng gửi thông tin xác thực.
2. Hệ thống kiểm tra mật khẩu, trạng thái và giới hạn thử.
3. Hệ thống cấp phiên.
4. Người dùng truy cập tổng quan.

- **Luồng thay thế / lỗi:** Thông tin sai: thông báo chung. Nhánh đăng xuất: thu hồi phiên, về trang đăng nhập. Nhánh đổi mật khẩu: kiểm tra mật khẩu cũ, lưu mật khẩu mới và kết thúc các phiên cũ; yêu cầu đăng nhập lại.
- **Kết thúc:** Có phiên hợp lệ khi đăng nhập; không còn phiên dùng được sau đăng xuất/đổi mật khẩu.
- **Hoạt động đồng thời:** Khóa tài khoản hoặc đổi vai trò làm phiên mất hiệu lực theo FR11.

#### UC03 - Cập nhật hồ sơ

- **Tác nhân / liên kết:** A01; FR01.04; FR11.02; kiểm chứng TC-U03.
- **Kích thích / tiền điều kiện:** Người dùng chọn Hồ sơ. Đã đăng nhập.
- **Dữ liệu:** Tên hiển thị, múi giờ; tiền cơ sở khi chưa ghi sổ.

**Luồng chính:**

1. Hệ thống hiển thị hồ sơ hiện tại.
2. Người dùng sửa và lưu.
3. Hệ thống kiểm tra giới hạn đổi tiền cơ sở/múi giờ.
4. Hệ thống lưu và hiển thị giá trị có hiệu lực.

- **Luồng thay thế / lỗi:** Đã có bút toán: khóa tiền cơ sở và giải thích. Múi giờ sai: giữ giá trị cũ.
- **Kết thúc:** Hồ sơ được cập nhật; giao dịch và kỳ đã tồn tại không bị tính lại.
- **Hoạt động đồng thời:** Nếu giao dịch đầu tiên commit trước lệnh đổi tiền cơ sở, lệnh đổi phải bị từ chối.

#### UC04 - Quản lý ví

- **Tác nhân / liên kết:** A01; FR02.06-FR02.08; FR03, FR04; kiểm chứng TC-U04.
- **Kích thích / tiền điều kiện:** Người dùng chọn Thêm ví. Tiền cơ sở đã chọn.
- **Dữ liệu:** Tên, loại, tiền tệ, số dư và ngày mở.

**Luồng chính:**

1. Người dùng nhập thông tin.
2. Hệ thống kiểm tra dữ liệu, tỷ giá khi cần.
3. Hệ thống tạo ví và ghi đối ứng vốn nếu có số dư đầu kỳ.
4. Hệ thống trả ví cùng số dư.

- **Luồng thay thế / lỗi:** Thiếu tỷ giá/lỗi sổ: không tạo ví. Nhánh đổi tên: kiểm tra rồi lưu. Nhánh lưu trữ: chỉ thực hiện khi số dư 0 và không có lịch hoạt động.
- **Kết thúc:** Ví hợp lệ và sổ cân bằng; hoặc ví đổi tên/lưu trữ có lịch sử giữ nguyên.
- **Hoạt động đồng thời:** Giao dịch mới cạnh tranh với lưu trữ phải được tuần tự hóa để không ghi vào ví đã đóng.

#### UC05 - Ghi thu nhập

- **Tác nhân / liên kết:** A01; FR02.01,.02,.05; FR03, FR04; kiểm chứng TC-U05.
- **Kích thích / tiền điều kiện:** Người dùng chọn Thu nhập. Có ví tài sản và danh mục thu hoạt động.
- **Dữ liệu:** Ví nhận, số tiền/tiền tệ, danh mục thu, ngày, mô tả.

**Luồng chính:**

1. Người dùng nhập và chọn ghi sổ.
2. Hệ thống kiểm tra quyền, dữ liệu, tỷ giá.
3. Hệ thống ghi Nợ ví/Có thu nguyên tử.
4. Hệ thống trả mã giao dịch và số dư mới.

- **Luồng thay thế / lỗi:** Lưu nháp: chỉ lưu Draft. Sai số tiền/danh mục hoặc thiếu tỷ giá: yêu cầu sửa, không ghi sổ.
- **Kết thúc:** Thu nhập đã ghi đúng một lần; tài sản tăng theo số tiền.
- **Hoạt động đồng thời:** Gửi lại cùng khóa nhận lại cùng kết quả; cảnh báo/tổng hợp có thể cập nhật sau commit.

#### UC06 - Ghi chi tiêu

- **Tác nhân / liên kết:** A01; FR02.01,.02,.05; FR03-FR05; kiểm chứng TC-U06.
- **Kích thích / tiền điều kiện:** Người dùng chọn Chi tiêu. Có ví tài sản đủ tiền hoặc ví nợ và danh mục chi.
- **Dữ liệu:** Ví, số tiền/tiền tệ, danh mục chi, ngày, mô tả.

**Luồng chính:**

1. Người dùng nhập và xác nhận.
2. Hệ thống kiểm tra dữ liệu và khả năng chi.
3. Hệ thống ghi Nợ chi/Có ví theo sổ kép.
4. Hệ thống trả kết quả; ngân sách được cập nhật từ sự kiện.

- **Luồng thay thế / lỗi:** Thiếu tiền: từ chối với số dư hiện có. Lỗi sổ: rollback. Lỗi cảnh báo sau commit: giao dịch vẫn thành công, thông báo được thử lại.
- **Kết thúc:** Số dư/chi phí đúng và có sự kiện ngân sách; hoặc không có tác động tài chính.
- **Hoạt động đồng thời:** Hai khoản chi cạnh tranh không được cùng dựa vào số dư cũ để làm ví tài sản âm.

#### UC07 - Chuyển giữa các ví

- **Tác nhân / liên kết:** A01; FR02.03,.05; FR03, FR04; kiểm chứng TC-U07.
- **Kích thích / tiền điều kiện:** Người dùng chọn Chuyển ví. Có hai ví cùng chủ đang hoạt động.
- **Dữ liệu:** Ví nguồn/đích, số tiền đi/đến, ngày và mô tả.

**Luồng chính:**

1. Người dùng chọn ví và số tiền.
2. Hệ thống hiển thị quy đổi, nguồn tỷ giá và chênh lệch nếu có.
3. Người dùng xác nhận.
4. Hệ thống ghi cả hai vế cùng đối ứng chênh lệch nếu cần trong một commit.

- **Luồng thay thế / lỗi:** Cùng ví, thiếu tiền, thiếu tỷ giá hoặc ví khác chủ: từ chối. Bản này không có phí chuyển riêng; nếu có phí người dùng ghi khoản chi tách biệt [ĐX].
- **Kết thúc:** Nguồn/đích cập nhật cùng lúc; chuyển nội bộ không tính thành thu/chi.
- **Hoạt động đồng thời:** Khóa nghiệp vụ và kiểm tra số dư chống ghi trùng/chi vượt số dư.

#### UC08 - Đảo / thay thế giao dịch

- **Tác nhân / liên kết:** A01; FR02.04,.05; FR03.04; kiểm chứng TC-U08.
- **Kích thích / tiền điều kiện:** Người dùng chọn Đảo hoặc Sửa sai ở chi tiết giao dịch. Bản gốc Posted, chưa đảo và không phải bút toán đảo.
- **Dữ liệu:** Mã gốc, lý do; thông tin thay thế nếu sửa sai.

**Luồng chính:**

1. Hệ thống hiển thị tác động và yêu cầu xác nhận.
2. Người dùng xác nhận lý do/nội dung thay thế.
3. Hệ thống kiểm tra trạng thái và số dư sau thao tác.
4. Hệ thống tạo đảo và bản thay thế nếu có, lưu liên kết và commit nguyên tử.

- **Luồng thay thế / lỗi:** Đã đảo, thao tác làm tài sản âm hoặc bản thay thế không hợp lệ: từ chối toàn bộ. Người dùng hủy: không đổi sổ.
- **Kết thúc:** Gốc được đánh dấu Reversed; lịch sử còn đủ; ngân sách/báo cáo tính cả dòng gốc và đảo.
- **Hoạt động đồng thời:** Hai yêu cầu đảo cùng gốc: chỉ một thành công; lặp cùng khóa trả kết quả cũ.

#### UC09 - Nhập giao dịch từ ảnh

- **Tác nhân / liên kết:** A01; FR14; FR02-FR04; kiểm chứng TC-U09.
- **Kích thích / tiền điều kiện:** Người dùng chọn Nhập từ ảnh. Ảnh đúng IF03.
- **Dữ liệu:** Ảnh và trường giao dịch đã được người dùng sửa/xác nhận.

**Luồng chính:**

1. Người dùng tải ảnh.
2. Hệ thống nhận dạng và trình bày bản nháp.
3. Người dùng kiểm tra, bổ sung ví/danh mục và sửa trường sai.
4. Người dùng xác nhận; hệ thống ghi sổ theo FR02 và hiển thị kết quả.

- **Luồng thay thế / lỗi:** Ảnh không đọc được: chuyển sang nhập tay. Ảnh trùng: cảnh báo. Thiếu trường: giữ nháp. Hủy trước xác nhận: không ghi sổ.
- **Kết thúc:** Giao dịch phản ánh dữ liệu đã xác nhận, gắn ảnh đúng chủ; hoặc chỉ có nháp.
- **Hoạt động đồng thời:** Nhấn xác nhận nhiều lần sử dụng cùng khóa nghiệp vụ.

#### UC10 - Quản lý danh mục

- **Tác nhân / liên kết:** A01; FR11.01; kiểm chứng TC-U10.
- **Kích thích / tiền điều kiện:** Người dùng chọn Danh mục. Đã đăng nhập.
- **Dữ liệu:** Tên và loại thu/chi.

**Luồng chính:**

1. Hệ thống hiển thị danh mục.
2. Người dùng chọn tạo hoặc sửa tên.
3. Hệ thống kiểm tra tên/loại và lưu.
4. Hệ thống hiển thị danh mục đã cập nhật.

- **Luồng thay thế / lỗi:** Nhánh lưu trữ: ngừng dùng cho giao dịch mới, giữ lịch sử. Đổi loại danh mục đã dùng hoặc tên trùng: từ chối.
- **Kết thúc:** Danh mục hợp lệ; phân loại các giao dịch cũ được bảo toàn.
- **Hoạt động đồng thời:** Giao dịch đang tạo phải kiểm tra lại danh mục còn hoạt động khi commit.

#### UC11 - Thiết lập ngân sách

- **Tác nhân / liên kết:** A01; FR05.01,.02; kiểm chứng TC-U11.
- **Kích thích / tiền điều kiện:** Người dùng chọn Ngân sách. Danh mục chi hoạt động.
- **Dữ liệu:** Danh mục, tháng, hạn mức tiền cơ sở.

**Luồng chính:**

1. Người dùng nhập và lưu ngân sách.
2. Hệ thống kiểm tra trùng kỳ/hạn mức.
3. Hệ thống lưu và tính chi hiện có trong kỳ.
4. Hệ thống hiển thị tỷ lệ, số còn lại và phát cảnh báo nếu cần.

- **Luồng thay thế / lỗi:** Sửa hạn mức: tính lại và áp dụng chống trùng thông báo. Lưu trữ: ngừng cảnh báo mới. Hạn mức 0 hoặc trùng kỳ: từ chối.
- **Kết thúc:** Ngân sách duy nhất có số sử dụng đúng; không thay đổi giao dịch.
- **Hoạt động đồng thời:** Giao dịch phát sinh trong lúc tạo ngân sách phải được tính trong lần cập nhật kế tiếp.

#### UC12 - Xem cảnh báo

- **Tác nhân / liên kết:** A01; FR05.03,.04; kiểm chứng TC-U12.
- **Kích thích / tiền điều kiện:** Người dùng mở Thông báo. Có phiên hợp lệ; có thể chưa có thông báo.
- **Dữ liệu:** Bộ lọc đã/chưa đọc; mã thông báo.

**Luồng chính:**

1. Hệ thống liệt kê cảnh báo thuộc chủ sở hữu.
2. Người dùng mở chi tiết.
3. Hệ thống hiển thị mức, kỳ và ngân sách liên quan.
4. Người dùng đánh dấu đã đọc.

- **Luồng thay thế / lỗi:** Danh sách rỗng: báo chưa có thông báo. Ngân sách đã lưu trữ: vẫn xem nội dung lịch sử. ID người khác: không trả dữ liệu.
- **Kết thúc:** readAt được cập nhật; số liệu sổ không đổi.
- **Hoạt động đồng thời:** Thông báo mới có thể đến trong khi đọc; đánh dấu cũ không đánh dấu nhầm thông báo mới.

#### UC13 - Quản lý khoản định kỳ

- **Tác nhân / liên kết:** A01; FR06; SYS02; kiểm chứng TC-U13.
- **Kích thích / tiền điều kiện:** Người dùng mở Khoản định kỳ. Ví/danh mục chi hoạt động.
- **Dữ liệu:** Tên, số tiền, chu kỳ, ngày neo, ví, danh mục, chế độ.

**Luồng chính:**

1. Người dùng tạo lịch với mặc định nháp.
2. Hệ thống hiển thị ngày đến hạn kế tiếp.
3. Nếu bật tự ghi sổ, người dùng xác nhận rõ.
4. Hệ thống lưu lịch và hiển thị các kỳ đã xử lý.

- **Luồng thay thế / lỗi:** Sửa áp dụng từ kỳ chưa xử lý; tạm dừng/hủy không xóa giao dịch cũ. Xem kỳ lỗi cho phép thử lại cùng occurrence sau khi sửa nguyên nhân.
- **Kết thúc:** Lịch được tạo/cập nhật; không phát sinh thanh toán ngân hàng.
- **Hoạt động đồng thời:** Nếu kỳ đã được SYS02 nhận xử lý, thay đổi lịch chỉ tác động kỳ tiếp theo và giao diện phải thông báo.

#### UC14 - Theo dõi mục tiêu

- **Tác nhân / liên kết:** A01; FR07; kiểm chứng TC-U14.
- **Kích thích / tiền điều kiện:** Người dùng chọn Mục tiêu. Đã đăng nhập.
- **Dữ liệu:** Tên, số đích, hạn chót; khoản phân bổ.

**Luồng chính:**

1. Người dùng tạo mục tiêu.
2. Hệ thống kiểm tra rồi lưu.
3. Người dùng thêm khoản phân bổ.
4. Hệ thống cập nhật số hiện tại và tiến độ.

- **Luồng thay thế / lỗi:** Sửa mục tiêu/hủy khoản phân bổ: tính lại. Lưu trữ/hủy mục tiêu: giữ lịch sử. Số tiền hoặc ngày sai: không lưu.
- **Kết thúc:** Tiến độ đúng theo phân bổ; số dư tài sản không thay đổi.
- **Hoạt động đồng thời:** Cập nhật đồng thời phải dùng phiên bản; hủy cùng khoản chỉ có một lần.

#### UC15 - Xem tổng quan

- **Tác nhân / liên kết:** A01; FR08; kiểm chứng TC-U15.
- **Kích thích / tiền điều kiện:** Người dùng mở Tổng quan. Đã đăng nhập.
- **Dữ liệu:** Khoảng ngày và ví tùy chọn.

**Luồng chính:**

1. Hệ thống áp dụng bộ lọc mặc định tháng hiện tại.
2. Hệ thống lấy số liệu cùng snapshot.
3. Hệ thống hiển thị các chỉ số, biểu đồ, ngân sách, mục tiêu và thời điểm cập nhật.
4. Người dùng đổi bộ lọc để xem lại.

- **Luồng thay thế / lỗi:** Chưa có giao dịch: số 0 và hướng dẫn nhập. Lỗi tổng hợp: thông báo và cho thử lại; dữ liệu cũ có nhãn thời điểm.
- **Kết thúc:** Các chỉ số đọc được, khớp cùng bộ lọc; không sửa dữ liệu.
- **Hoạt động đồng thời:** Giao dịch đang ghi sẽ xuất hiện trong mốc cập nhật tiếp theo, không làm thẻ và biểu đồ dùng hai mốc khác nhau.

#### UC16 - Phân tích dòng tiền

- **Tác nhân / liên kết:** A01; FR13; kiểm chứng TC-U16.
- **Kích thích / tiền điều kiện:** Người dùng mở Dòng tiền. Đã đăng nhập.
- **Dữ liệu:** Khoảng ngày, ví, mức nhóm thời gian.

**Luồng chính:**

1. Người dùng chọn bộ lọc.
2. Hệ thống kiểm tra khoảng ngày.
3. Hệ thống tổng hợp thu/chi theo FR13.
4. Hệ thống trình biểu đồ và bảng số liệu.

- **Luồng thay thế / lỗi:** Khoảng sai: yêu cầu sửa. Kỳ rỗng: ghi 0. Chuyển ví không được thêm vào cả hai cột thu/chi.
- **Kết thúc:** Thu - chi bằng dòng tiền ròng của cùng kỳ.
- **Hoạt động đồng thời:** Truy vấn đọc cùng snapshot; số liệu cập nhật tiếp theo không trộn vào kết quả hiện tại.

#### UC17 - Tra cứu và đối soát sổ

- **Tác nhân / liên kết:** A01; FR09.01,.02; kiểm chứng TC-U17.
- **Kích thích / tiền điều kiện:** Người dùng mở Lịch sử / Sổ. Đã đăng nhập.
- **Dữ liệu:** Bộ lọc; mã giao dịch; lệnh đối soát.

**Luồng chính:**

1. Người dùng tìm giao dịch theo bộ lọc.
2. Hệ thống trả trang kết quả.
3. Người dùng mở chi tiết và xem các dòng sổ/liên kết đảo.
4. Nếu chọn đối soát, hệ thống trả kết quả khớp hoặc chênh lệch.

- **Luồng thay thế / lỗi:** Không có dữ liệu: danh sách rỗng. Có chênh lệch: hiển thị cụ thể và ghi lỗi vận hành, không tự sửa.
- **Kết thúc:** Lịch sử và bằng chứng đối soát chỉ đọc, đúng chủ sở hữu.
- **Hoạt động đồng thời:** Đối soát lấy mốc nhất quán để không báo sai khi có giao dịch mới.

#### UC18 - Xuất báo cáo

- **Tác nhân / liên kết:** A01; FR09.03,.04; kiểm chứng TC-U18.
- **Kích thích / tiền điều kiện:** Người dùng chọn Xuất báo cáo. Bộ lọc hợp lệ, trong giới hạn xuất.
- **Dữ liệu:** Bộ lọc và định dạng CSV.

**Luồng chính:**

1. Hệ thống xác nhận phạm vi xuất.
2. Người dùng yêu cầu tạo tệp.
3. Hệ thống lấy snapshot, tạo CSV đầy đủ và kiểm tra số dòng.
4. Người dùng tải tệp qua phiên hợp lệ.

- **Luồng thay thế / lỗi:** Vượt giới hạn: yêu cầu thu hẹp. Lỗi xuất: không tải tệp dở. Phiên hết hạn trước tải: yêu cầu đăng nhập và kiểm tra lại quyền.
- **Kết thúc:** Tệp đúng bộ lọc và chỉ người có quyền được tải; sổ không đổi.
- **Hoạt động đồng thời:** Giao dịch mới sau mốc xuất không được chen vào tệp đang tạo.

#### UC19 - Quản trị tài khoản

- **Tác nhân / liên kết:** A02; FR11.04-FR11.06; FR12; kiểm chứng TC-U19.
- **Kích thích / tiền điều kiện:** Admin mở Quản trị tài khoản. Có quyền quản trị.
- **Dữ liệu:** Mã tài khoản, trạng thái/vai trò mới, lý do.

**Luồng chính:**

1. Admin tìm và chọn tài khoản.
2. Hệ thống hiển thị trạng thái và quyền hiện tại.
3. Admin chọn thay đổi, nhập lý do và xác nhận lại khi đổi vai trò.
4. Hệ thống áp dụng thay đổi, thu hồi phiên liên quan và ghi audit.

- **Luồng thay thế / lỗi:** Admin cuối cùng, sai quyền, thiếu lý do hoặc xung đột: từ chối. Nhánh xem log: đọc hoạt động vận hành theo bộ lọc, không đọc sổ cá nhân.
- **Kết thúc:** Quyền/trạng thái cập nhật nhất quán, có người thực hiện và lý do.
- **Hoạt động đồng thời:** Các lệnh nghiệp vụ mới phải thấy trạng thái khóa; lệnh đã commit trước khóa được giữ.

#### UC20 - Cấu hình vận hành

- **Tác nhân / liên kết:** A02; FR11.03; kiểm chứng TC-U20.
- **Kích thích / tiền điều kiện:** Admin chọn Cấu hình vận hành. Có quyền cấu hình.
- **Dữ liệu:** Nguồn tỷ giá, lịch tác vụ, tham số backup và phiên bản cấu hình.

**Luồng chính:**

1. Hệ thống hiển thị tham số hiện hành.
2. Admin sửa và lưu.
3. Hệ thống kiểm tra giới hạn đã đặc tả.
4. Hệ thống công bố phiên bản mới và ghi audit.

- **Luồng thay thế / lỗi:** Tham số sai hoặc gây vi phạm RPO: từ chối toàn bộ. Cấu hình bí mật chỉ cho thay mới, không hiển thị giá trị gốc.
- **Kết thúc:** Có một phiên bản hiệu lực; job đang chạy không dùng cấu hình nửa cũ nửa mới.
- **Hoạt động đồng thời:** Job hiện tại giữ phiên bản đã nhận; job tiếp theo nhận cấu hình mới.

#### UC21 - Tạo / kiểm tra sao lưu

- **Tác nhân / liên kết:** A02; FR10.01,.02; kiểm chứng TC-U21.
- **Kích thích / tiền điều kiện:** Admin chọn Tạo/kiểm tra backup. Kho sao lưu hoạt động.
- **Dữ liệu:** Phạm vi toàn bộ dữ liệu, mã job hoặc mã backup cần kiểm tra.

**Luồng chính:**

1. Admin yêu cầu sao lưu.
2. Hệ thống tạo snapshot nhất quán và sao chép dữ liệu cần phục hồi.
3. Hệ thống tính checksum, tạo manifest và kiểm tra kết quả.
4. Hệ thống hiển thị trạng thái và thời điểm dữ liệu.

- **Luồng thay thế / lỗi:** Kho đầy/mất kết nối: đánh dấu thất bại, giữ bản thành công trước đó. Nhánh xem/kiểm tra: chỉ hiển thị bản sao có trạng thái xác định.
- **Kết thúc:** Có backup được kiểm chứng hoặc job thất bại rõ nguyên nhân.
- **Hoạt động đồng thời:** Giao dịch có thể tiếp tục khi snapshot hỗ trợ; chỉ một job sao lưu toàn phần hoạt động tại một thời điểm [ĐX].

#### UC22 - Khôi phục dữ liệu

- **Tác nhân / liên kết:** A02; FR10.03,.04; kiểm chứng TC-U22.
- **Kích thích / tiền điều kiện:** Admin chọn Khôi phục dữ liệu. Có backup, quyền và xác thực lại; cửa sổ bảo trì.
- **Dữ liệu:** Mã backup, mốc phục hồi, lý do và xác nhận tác động dữ liệu.

**Luồng chính:**

1. Hệ thống hiển thị mốc dữ liệu sẽ phục hồi và phần mới hơn có thể mất.
2. Admin xác nhận; hệ thống chặn ghi và lưu bản trước phục hồi.
3. Hệ thống kiểm tra backup, phục hồi tách biệt và đối soát.
4. Khi tất cả kiểm tra đạt, hệ thống chuyển phục vụ, thu hồi phiên cũ và ghi biên bản.

- **Luồng thay thế / lỗi:** Hủy trước xác nhận: không làm gì. Backup hỏng hoặc đối soát lỗi: không công bố, giữ bảo trì và cho quay lại bản trước phục hồi.
- **Kết thúc:** Dữ liệu phục hồi hợp lệ hoặc bản trước phục hồi được giữ; audit khôi phục còn nguyên.
- **Hoạt động đồng thời:** Mọi ghi sổ và SYS02/SYS03 bị dừng trong thời gian chuyển dữ liệu; chỉ tiếp tục sau đối soát.

### 4.3 Kịch bản xử lý nền

Các kịch bản này có cùng trường mô tả như đặc tả chức năng, nhưng kích hoạt bởi lịch hoặc sự kiện. Không thay thế bằng use case có tác nhân là chính hệ thống. Ngoại lệ chung: job có mã, trạng thái, số lần thử và lỗi; sau khởi động lại xử lý lại mục chưa hoàn tất, không tạo tác động tài chính trùng.

| Mã / liên kết | Kích thích, dữ liệu và xử lý | Kết quả / lỗi |
| --- | --- | --- |
| SYS01<br>FR04; A03; IF02 | Tối thiểu mỗi ngày và khi khởi động: lấy tỷ giá từ A03; kiểm tra cặp tiền, giá trị, thời điểm; lưu phiên bản rồi công bố. Không thay snapshot cũ. | Thành công có tỷ giá mới; thất bại giữ bản cũ, ghi lỗi và thử lại sau 1, 5, 15 phút, sau đó mỗi giờ [ĐX]. Quá tuổi cho phép thì chặn ghi sổ ngoại tệ, không chặn nội tệ. |
| SYS02<br>FR06, FR02-FR04 | Quét mỗi 5 phút [ĐX] các dueDate ≤ thời điểm hiện tại chưa xử lý của lịch hoạt động. Kiểm tra người dùng/phiên bản lịch; nhận khóa occurrence; tạo nháp hoặc ghi sổ; cập nhật kết quả kỳ. | Mỗi kỳ một kết quả; lỗi nghiệp vụ giữ chờ và báo người dùng; lỗi tạm thời thử lại cùng khóa. Khi hệ thống gián đoạn, quét bù cả kỳ cũ. Không quét các kỳ trong khoảng Paused. |
| SYS03<br>FR05 | Sự kiện commit/đảo giao dịch hoặc thay hạn mức: tính lại chi của danh mục/kỳ, xác định màu theo BR08; lưu thông báo với khóa chống trùng. Quét đối soát mỗi giờ để phát hiện sự kiện bỏ sót [ĐX]. | Lỗi thử lại cùng khóa; giao dịch đã commit giữ nguyên. Chỉ chủ sở hữu thấy thông báo. Trạng thái đọc và lịch sử cảnh báo không bị đặt lại khi tính lại. |
| SYS04<br>FR10 | Lịch sao lưu mỗi 12 giờ [ĐX nhằm bảo đảm RPO < 24h] hoặc yêu cầu UC21: chụp mốc nhất quán, sao chép, kiểm checksum/manifest và lưu kết quả. | Thất bại cảnh báo Admin và thử lại sau 15 phút; không xóa bản thành công cuối. Giữ 30 ngày [ĐX]; chỉ luân chuyển bản cũ khi có bản mới đạt kiểm tra. |

### 4.4 Ví dụ sổ và kiểm tra tính nhất quán

| Tình huống | Nợ | Có | Kết quả |
| --- | --- | --- | --- |
| Mở ví 1.000.000 VND | Ví tài sản 1.000.000 | Vốn đầu kỳ 1.000.000 | Thu/chi = 0 |
| Thu 500.000 VND | Ví tài sản 500.000 | Thu nhập 500.000 | Tài sản tăng |
| Chi 200.000 VND | Chi phí 200.000 | Ví tài sản 200.000 | Tài sản giảm |
| Chuyển 100.000 VND | Ví đích 100.000 | Ví nguồn 100.000 | Không tạo thu/chi |
| Đảo khoản chi 200.000 | Ví tài sản 200.000 | Chi phí 200.000 | Chi ròng giảm |
| Chuyển 100 USD, giá ghi sổ giao dịch 2.500.000 VND, nhận 2.490.000 VND | Ví VND 2.490.000; chênh lệch tỷ giá 10.000 | Ví USD: 100 USD / 2.500.000 VND | Cân bằng theo VND; chênh lệch trình riêng |

Ví dụ cuối giả định giá trị sổ của 100 USD là 2.500.000 VND. Theo BR15, nếu đổi toàn bộ lấy 2.600.000 VND thì ghi Nợ ví VND 2.600.000, Có ví USD 2.500.000 và Có lãi tỷ giá 100.000; ví USD hết cả nguyên tệ lẫn giá trị cơ sở. Hệ thống không lập báo cáo kế toán pháp định và không tự đánh giá lại số dư theo thị trường khi chưa có giao dịch. Giá trị sổ không nhất thiết bằng số dư nguyên tệ nhân tỷ giá mới nhất.

---

<a id="phan-5"></a>

## 5. Yêu cầu phi chức năng và lượng hóa ISO/IEC 25010

Tài liệu giữ đúng **7 NFR của nguồn**. ISO/IEC 25010:2023 được dùng để phân loại và làm rõ tiêu chí đo; không tạo thêm mã NFR mới. Các ngưỡng ghi **[G]** được kế thừa từ tài liệu gốc; ngưỡng ghi **[ĐX]** là đề xuất cần nhóm/giảng viên chốt trước khi nghiệm thu.

### 5.1 Điều kiện đo chung

- **E1 - Môi trường hiệu năng [ĐX]:** máy ứng dụng 4 vCPU/8 GB RAM, DB 4 vCPU/8 GB RAM, SSD, độ trễ mạng nội bộ không quá 5 ms.
- **D1 - Dữ liệu [ĐX]:** 1.000 người dùng, 5.000 ví, 1.000.000 giao dịch và ít nhất 2.000.000 dòng sổ; có giao dịch đa tiền, đảo, nháp và kỳ trống.
- **L1 - Tải [ĐX]:** 100 người dùng ảo, tổng 20 yêu cầu/giây; làm nóng 10 phút, đo 30 phút, chạy 3 lần. Mỗi lần phải đạt riêng.
- **Quy tắc đo:** p95 là phân vị 95, không dùng thời gian trung bình để thay thế. Chỉ tiêu chưa chạy phải ghi “Chưa kiểm chứng”, không tự ghi “Đạt”.

### NFR01 - Hiệu năng

- **ISO/IEC 25010:** Performance efficiency - time behaviour, resource utilization, capacity; Compatibility - co-existence.
- **Yêu cầu:** API tạo thu/chi/chuyển hợp lệ phải có p95 dưới 200 ms [G]; dashboard phải hiển thị đầy đủ trong p95 dưới 2 giây [G]. Sau khi nhận gói tỷ giá hợp lệ, hệ thống phải công bố phiên bản mới trong p95 dưới 30 giây [G/L]. Ngân sách, cảnh báo và dashboard phải phản ánh giao dịch đã commit trong p95 không quá 5 giây, mọi mẫu không quá 30 giây [ĐX].
- **Tải và tài nguyên [ĐX]:** trong E1/D1/L1, lỗi phía hệ thống dưới 1%; p95 CPU ứng dụng và DB không quá 80%; bộ nhớ mỗi máy không quá 80%; backup chạy nền không làm NFR01 mất ngưỡng.
- **Kiểm chứng TC-N01:** chạy L1 ba lần; lưu độ trễ từng API, thời gian vẽ xong dashboard, độ trễ cập nhật sau commit, CPU/RAM/IO và số lỗi.
- **Áp dụng:** FR02-FR10, FR13; SYS01-SYS04.

### NFR02 - Bảo mật

- **ISO/IEC 25010:** Security - confidentiality, integrity, authenticity, accountability, resistance.
- **Xác thực [G/L/ĐX]:** không lưu mật khẩu dạng rõ; dùng hàm băm mật khẩu có salt riêng. Mật khẩu dài 12-128 ký tự; sau 5 lần sai trong 15 phút phải hạn chế thử thêm 15 phút; phiên không hoạt động quá 30 phút hoặc tổng tuổi 12 giờ phải hết hạn.
- **Phân quyền [G/L]:** 100% trường hợp trái quyền trong ma trận Guest/UserA/UserB/Admin/Service phải bị từ chối; không lộ bản ghi, ảnh hoặc CSV của người khác. Danh tính phải lấy từ phiên đã kiểm chứng, không tin userId do máy khách gửi.
- **Bảo vệ dữ liệu [G/L/ĐX]:** truyền dữ liệu bằng TLS 1.2 trở lên; DB, ảnh và backup được mã hóa khi lưu; log không chứa mật khẩu, token hoặc nội dung tài chính chi tiết. Các hành động đăng nhập thất bại, ghi/đảo giao dịch, đổi quyền/cấu hình, xuất và restore phải có audit với actorId, requestId, hành động, đối tượng, thời điểm và kết quả.
- **Đầu vào:** các mẫu SQL injection, XSS, tệp sai chữ ký, tệp quá 10 MB và CSV formula injection phải bị xử lý an toàn.
- **Kiểm chứng TC-N02:** kiểm kho mật khẩu/log; thử token giả/hết hạn, ID của người khác, đường tải tệp, HTTP, đầu vào độc hại và đối chiếu audit.
- **Áp dụng:** FR01, FR02, FR09-FR12, FR14; mọi UC được bảo vệ.

### NFR03 - Tính toàn vẹn dữ liệu

- **ISO/IEC 25010:** Functional suitability - functional correctness; Reliability - faultlessness; Safety - fail safe và risk identification.
- **Yêu cầu [G/L]:** 100% giao dịch Posted phải có tổng DebitBase bằng CreditBase chính xác tới đơn vị tiền nhỏ nhất; không có bút toán mồ côi, số dư cập nhật một phần, ghi trùng hoặc dữ liệu sai chủ sở hữu.
- **Nguyên tử:** giao dịch, dòng sổ, số dư lưu đệm và sự kiện hậu xử lý phải commit hoặc rollback cùng nhau. Yêu cầu lặp cùng khóa/nội dung chỉ tạo một giao dịch; cùng khóa khác nội dung phải bị từ chối.
- **Đa tiền:** dùng số thập phân chính xác và snapshot tỷ giá; ví hết nguyên tệ phải hết giá trị sổ; chênh lệch được ghi vào tài khoản lãi/lỗ tỷ giá hoặc làm tròn.
- **Phòng sai sót:** OCR không được tự ghi sổ; tự ghi định kỳ và restore phải có xác nhận/kiểm soát như FR tương ứng; dữ liệu thiếu hoặc không hợp lệ không được commit.
- **Kiểm chứng TC-N03:** tối thiểu 1.000 ca biên/đa tiền/đảo, 10.000 giao dịch sinh ngẫu nhiên có kết quả đối chiếu độc lập; chèn lỗi trước/sau commit; thử 100 nhóm yêu cầu cạnh tranh.
- **Áp dụng:** FR02-FR10, FR13-FR14; BR01-BR15.

### NFR04 - Độ tin cậy và sao lưu

- **ISO/IEC 25010:** Reliability - availability, fault tolerance, recoverability.
- **Sẵn sàng [ĐX]:** trong cửa sổ 30 ngày, đăng nhập, ghi giao dịch nội tệ và tra cứu phải đạt availability ít nhất 99,5%. Probe chạy mỗi phút; phút đạt khi cả ba thao tác thành công trong 5 giây.
- **Cô lập lỗi [G/L]:** lỗi worker tỷ giá, cảnh báo hoặc định kỳ không làm hỏng luồng giao dịch nội tệ. Sau khi phục hồi, tối đa 1.000 sự kiện tồn phải xử lý xong trong 10 phút và không tạo trùng [ĐX].
- **Sao lưu [G/L]:** RPO dưới 24 giờ, RTO dưới 1 giờ; tạo backup tối thiểu mỗi 12 giờ [ĐX], kiểm checksum/manifest từng bản và diễn tập restore hàng tháng. Backup hỏng không được thay thế bản hợp lệ cuối cùng.
- **Kiểm chứng TC-N04:** ngắt từng worker trong 30 phút, tiếp tục tải nghiệp vụ rồi đối chiếu sổ; thực hiện ít nhất 3 lần restore D1, gồm một lần backup mới nhất hỏng; đo RPO/RTO thực tế.
- **Áp dụng:** FR02-FR06, FR09-FR10; SYS01-SYS04.

### NFR05 - Khả năng kiểm thử

- **ISO/IEC 25010:** Maintainability - testability; Functional suitability - completeness.
- **Yêu cầu [G/L]:** có unit test, integration test và API test; line coverage phần mã nghiệp vụ tối thiểu 80%. Coverage không thay thế các test bắt buộc về sổ kép, rollback, phân quyền, chống trùng, tỷ giá, cảnh báo và restore.
- **Khả năng tái lập [ĐX]:** test tỷ giá lịch sử, ngày định kỳ, lỗi ghi sổ và restore phải chạy được với đồng hồ/dữ liệu/phụ thuộc giả lập; cùng seed và cấu hình phải cho cùng kết quả trong 10 lần chạy.
- **Bao phủ đặc tả:** 100% FRnn.xx có ít nhất một ca hợp lệ và một ca lỗi/biên phù hợp; mọi UC01-UC22 và SYS01-SYS04 có kiểm chứng đầu ra.
- **Kiểm chứng TC-N05:** kiểm báo cáo CI/coverage, ma trận FR-UC-SYS-test và chạy lại các bộ test quan trọng 10 lần.
- **Áp dụng:** toàn bộ FR, UC và SYS.

### NFR06 - Khả năng sử dụng

- **ISO/IEC 25010:** Interaction capability - learnability, operability, user error protection, inclusivity, self-descriptiveness; Compatibility trên trình duyệt.
- **Dễ học [G/L/ĐX]:** sau hướng dẫn tối đa 10 phút, ít nhất 9/10 người dùng mới phải nhập được một khoản chi hợp lệ trong tối đa 60 giây và không cần trợ giúp. Từ dashboard đến xác nhận giao dịch tối đa 3 bước màn hình.
- **Phòng lỗi:** lỗi trường bắt buộc, số tiền hoặc ngày phải chỉ ra trường và cách sửa, đồng thời giữ các trường đúng đã nhập. Đảo giao dịch, bật tự ghi sổ và restore phải hiển thị tác động và yêu cầu xác nhận rõ.
- **Responsive và tiếp cận [G/L/ĐX]:** các luồng người dùng hoạt động ở chiều rộng 360, 768 và 1440 px; hoàn thành được bằng bàn phím; có focus nhìn thấy, nhãn trường, tương phản chữ thường tối thiểu 4,5:1; cảnh báo không chỉ dựa vào màu.
- **Tương thích [ĐX]:** hỗ trợ hai phiên bản ổn định gần nhất của Chrome, Edge, Firefox và Safari tại ngày chốt kiểm thử.
- **Kiểm chứng TC-N06:** thử với 10 người mới; chạy checklist bàn phím, trình đọc màn hình, kích thước màn hình và ma trận trình duyệt; ghi phiên bản cụ thể.
- **Áp dụng:** FR01-FR14; UC01-UC22.

### NFR07 - Khả năng bảo trì

- **ISO/IEC 25010:** Maintainability - modularity, analysability, modifiability; Flexibility - adaptability, installability, scalability.
- **Cô lập thành phần [G/L]:** thay bộ kết nối tỷ giá bằng bộ giả lập cùng IF02 không được yêu cầu sửa quy tắc sổ; TC-F02, TC-F03 và TC-F04 vẫn phải đạt.
- **Phân tích lỗi [ĐX]:** 100% lỗi API và tác vụ nền có requestId/jobId liên kết tới thời điểm, thành phần và mã lỗi; kỹ sư phải xác định chức năng lỗi trong tối đa 15 phút đối với năm lỗi giả lập chuẩn.
- **Cài đặt [ĐX]:** từ gói phát hành và hướng dẫn, một người vận hành phải cài mới trên E1 sạch trong tối đa 60 phút và chạy đạt kiểm tra đăng nhập, ghi sổ và backup; đổi URL tỷ giá/kho backup không cần sửa mã nguồn.
- **Mở rộng [ĐX]:** khi tăng máy ứng dụng lên 8 vCPU/16 GB RAM, hệ thống phải chịu 30 yêu cầu/giây với 150 người dùng ảo và vẫn đạt ngưỡng thời gian của NFR01.
- **Kiểm chứng TC-N07:** chèn năm lỗi chuẩn và đo thời gian chẩn đoán; thay bộ kết nối tỷ giá; cài mới hai lần; chạy tải mở rộng ba lần.
- **Áp dụng:** toàn hệ thống, đặc biệt FR03, FR04, FR10-FR12 và SYS01-SYS04.

### 5.2 Yêu cầu tổ chức và ràng buộc bên ngoài

| Mã | Yêu cầu |
| --- | --- |
| ORG01 | Mỗi thay đổi yêu cầu phải ghi người đề xuất, lý do, mã ảnh hưởng, phiên bản, tác động đến FR/UC/NFR/test và người chấp thuận. |
| ORG02 | Mỗi đợt nghiệm thu phải lưu cấu hình, dữ liệu thử, script và kết quả; tiêu chí chưa đo phải ghi “Chưa kiểm chứng”. |
| EXT01 | Hai nguồn đầu vào chưa nêu quy định pháp lý cụ thể. Trước khi dùng dữ liệu thật, nhóm phải xác định thị trường và chuyển nghĩa vụ áp dụng thành yêu cầu có nguồn. |

---

<a id="phan-6"></a>

## 6. Truy vết và nghiệm thu

### 6.1 Ma trận FR - kịch bản - quy tắc - chất lượng

Một ô FRnn.* bao gồm tất cả FRnn.xx ở mục 3. Liên kết áp dụng hai chiều: từ FR tìm UC/SYS/test trong hàng; từ UC/SYS/test tìm các hàng chứa mã tương ứng. FR12 áp dụng xuyên suốt các giao diện được bảo vệ; không tạo thêm use case “kiểm tra token” để lấp ma trận.

| FR | UC / SYS | Quy tắc | NFR tiêu biểu | Bộ thử |
| --- | --- | --- | --- | --- |
| FR01 | UC01-UC03 | BR11 | NFR02, NFR06 | TC-F01 |
| FR02 | UC04-UC09; SYS02 | BR01-BR06, BR11-BR14 | NFR01-NFR06 | TC-F02 |
| FR03 | UC04-UC09; SYS02 | BR01-BR06, BR12-BR15 | NFR01, NFR03-NFR05, NFR07 | TC-F03 |
| FR04 | UC04-UC09, UC15-UC18; SYS01 | BR03, BR04, BR15 | NFR01, NFR03-NFR05, NFR07 | TC-F04 |
| FR05 | UC11,UC12; SYS03 | BR07, BR08 | NFR01, NFR03-NFR06 | TC-F05 |
| FR06 | UC13; SYS02 | BR09, BR12, BR13 | NFR03-NFR07 | TC-F06 |
| FR07 | UC14 | BR10 | NFR02, NFR03, NFR06 | TC-F07 |
| FR08 | UC15 | BR02, BR14 | NFR01, NFR03, NFR06 | TC-F08 |
| FR09 | UC17,UC18 | BR01, BR11, BR14 | NFR01-NFR06 | TC-F09 |
| FR10 | UC21,UC22; SYS04 | BR01, BR11, BR12 | NFR02-NFR05, NFR07 | TC-F10 |
| FR11 | UC03,UC10,UC19,UC20 | BR04, BR07, BR11 | NFR02, NFR04-NFR07 | TC-F11 |
| FR12 | Mọi UC có xác thực; SYS01-SYS04 | BR11 | NFR02-NFR05, NFR07 | TC-F12 |
| FR13 | UC16; UC15 dùng số tổng hợp | BR05, BR14 | NFR01, NFR03, NFR06 | TC-F13 |
| FR14 | UC09 | BR11-BR13 | NFR02, NFR03, NFR05, NFR06 | TC-F14 |

Bảng chỉ liệt kê các NFR01-NFR07 có tác động trực tiếp; mục 5 là nguồn quy định phạm vi và phép đo đầy đủ.

### 6.2 Quy tắc thiết kế kiểm thử

Với từng FRnn.xx, tạo mã TC-Fnn.xx-P cho hành vi hợp lệ và TC-Fnn.xx-E cho lỗi/biên. Đầu vào lấy từ trường “đầu vào/nguồn”; điều kiện trước từ phiếu FR; kết quả kỳ vọng phải kiểm cả đầu ra, trạng thái dữ liệu và tác động phụ. Các ví dụ TC-Fnn trong phiếu là bộ tình huống tối thiểu, không thay cho kiểm thử từng câu con.

TC-U01-TC-U22 chạy xuyên suốt luồng chính và các nhánh của từng UC. TC-S01-TC-S04 kiểm từng SYS với ba nhánh: thành công, lỗi phụ thuộc và khởi động lại/chạy lặp. TC-N01-TC-N07 dùng đúng phép đo mục 5. Mọi bộ thử bảo vệ dữ liệu chạy lại sau thay đổi sổ, quyền hoặc restore.

| Mã thử mẫu | Thiết lập / kích thích | Kết quả kỳ vọng |
| --- | --- | --- |
| TC-F02.05-P | Gửi 10 lệnh tạo thu 100.000 VND cùng chủ và cùng khóa/nội dung. | Một transactionId, một tập dòng sổ, số dư chỉ tăng 100.000. |
| TC-F02.05-E | Gửi lại khóa đã dùng nhưng đổi số tiền thành 200.000. | 409; sổ giữ nguyên; không tạo giao dịch thứ hai. |
| TC-F03.02-E | Chèn lỗi sau dòng sổ đầu tiên, trước commit của khoản chi. | Không có khoản chi Posted/dòng mồ côi/số dư dở; log lỗi có requestId. |
| TC-F05.03-P | Ngân sách 1.000.000; chi từ 700.000 lên 1.100.000 rồi gửi lại sự kiện. | Đỏ; chỉ một cảnh báo đỏ; không thêm vàng và không nhân bản cảnh báo. |
| TC-F06.04-E | Hai worker quét cùng kỳ, một bị timeout sau commit. | Một occurrence và tối đa một giao dịch; thử lại dùng mã cũ. |
| TC-F12.02-E | UserA yêu cầu ảnh/CSV/chi tiết giao dịch của UserB bằng ID thật. | 404 hoặc từ chối quyền theo IF01; không lộ nội dung, không đổi dữ liệu. |
| TC-S04-E | Backup mới nhất hỏng checksum, còn bản trước đó. | Không công nhận bản hỏng; restore bản đủ điều kiện và đo RPO/RTO thực tế. |
| TC-U09-E | OCR đọc 500.000 thành 5.000.000; người dùng sửa trước xác nhận. | Chỉ ghi 500.000 sau xác nhận; trước đó không có bút toán. |

### 6.3 Điều kiện nghiệm thu sản phẩm

- Tất cả yêu cầu P1 và P2 trong phạm vi đã được nhóm/giảng viên thống nhất có kết quả kiểm chứng; các mục chưa thống nhất hoặc chưa đo phải được nêu rõ, không tự đánh dấu đạt.
- 100% yêu cầu đã chốt có test liên kết; mọi ca sổ kép, rollback, đảo, chống trùng, phân quyền và khôi phục phải đạt. Không còn lỗi gây sai số dư, mất dữ liệu, lộ dữ liệu hoặc chặn luồng nghiệp vụ chính.
- Các chỉ tiêu NFR đạt trong môi trường đã ghi. Chỉ tiêu availability cần đủ cửa sổ quan sát; không suy ra 30 ngày sẵn sàng từ một lần demo. Coverage đạt NFR05 nhưng không thay thế nghiệm thu chức năng.
- Người dùng đại diện thực hiện các tình huống nhập chi, chuyển ví, sửa sai, vượt ngân sách, khoản định kỳ, xem báo cáo và nhập ảnh; ghi nhận được mục tiêu/đầu ra như đặc tả.
- Lưu phiên bản tài liệu, mã nguồn, dữ liệu thử, script, cấu hình và biên bản kết quả để lặp lại; thay đổi phạm vi phải cập nhật ma trận trước khi nghiệm thu.

### 6.4 Danh sách quyết định cần chốt trước triển khai

Những điểm sau đã có phương án mặc định trong bản này, nên đặc tả không bị bỏ trống; trạng thái của chúng vẫn là [ĐX]. Nhóm có thể thay đổi qua ORG02 mà không sửa các nguyên tắc phân biệt FR/UC/NFR.

| Quyết định | Phương án đang dùng / ảnh hưởng |
| --- | --- |
| Phạm vi sản phẩm | Web responsive; không ngân hàng thật, không native, không dự báo AI. Mục 1.1. |
| Chính sách tiền/sổ | VND/USD/EUR; khóa tiền cơ sở; quy tắc BR03/BR04 và phương pháp giá trị ghi sổ. Cần xác nhận yêu cầu đa tiền của đề bài gốc. |
| Khoản định kỳ | Tháng/năm; mỗi 5 phút; mặc định nháp, chủ động bật tự ghi sổ. FR06/SYS02. |
| Mục tiêu tài chính | Phân bổ thủ công, không làm đổi tài sản. FR07/BR10. |
| Ngân sách | Theo danh mục/tháng; ngưỡng cố định 80/100, một lần mỗi mức/kỳ. FR05. |
| Dữ liệu và vận hành | Chọn nhà cung cấp tỷ giá, cấu hình hàm băm, kho backup; chốt thời gian lưu dữ liệu và chính sách phục hồi. |
| Khả thi NFR | Chốt E1/D1/L1, p95, availability, tải mở rộng và số người thử với thời gian/nguồn lực đồ án; chưa có phép đo thực tế. |
| Nguồn đề bài | Đối chiếu Product Backlog/US/BM gốc khi có; nếu khác bản PDF, cập nhật phiên bản và nêu nguồn quyết định. |



