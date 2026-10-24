import os
import sys

fr_metadata = [
    {
        'id': 'FR01',
        'dir': 'fr01_identity_access',
        'name': 'Định danh, xác thực và quản lý phiên truy cập',
        'sprint': 'S1 (19/10 - 25/10/2026)',
        'owner': 'Võ Trịnh Quốc Huy',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC01 (Đăng ký, Đăng nhập, Quản lý phiên)',
        'br': 'BR11 (Kiểm soát truy cập và bảo vệ dữ liệu cá nhân)',
        'components': [
            'source/backend/src/Modules/Auth/AuthController.php',
            'source/backend/src/Modules/Auth/AuthService.php',
            'source/backend/src/Modules/Auth/UserDAO.php',
            'source/backend/src/Core/TokenService.php',
            'source/backend/tests/mail_smoke_test.php'
        ],
        'status': 'PARTIAL (Đạt 7/7 Smoke Mail OTP, chưa có Unit Test tự động cho Auth Controller trong run_tests.php)',
        'tests': [
            ('TC-F01.01', 'Đăng ký tài khoản hợp lệ', 'Email chuẩn hóa, password mạnh, tiền cơ sở VND', 'Tạo User ACTIVE, gán bộ danh mục mặc định, 201 Created', 'PASS (Manual/Smoke)'),
            ('TC-F01.02', 'Đăng ký trùng email', 'Email đã tồn tại trong DB', 'Từ chối với 409 Conflict, không tạo bản ghi mới', 'PASS (Logic AuthService)'),
            ('TC-F01.03', 'Đăng nhập thành công', 'Email và mật khẩu chính xác', 'Cấp cặp Token JWT (Access 15p, Refresh 7 ngày), 200 OK', 'PASS (Manual)'),
            ('TC-F01.04', 'Đăng nhập sai mật khẩu/email', 'Email không tồn tại hoặc sai password', 'Chống User Enumeration: trả 401 Unauthorized chung', 'PASS (Logic AuthService)'),
            ('TC-F01.05', 'Đổi mật khẩu & thu hồi phiên', 'Mật khẩu cũ đúng, mật khẩu mới hợp lệ', 'Cập nhật password hash, tăng token_version làm hết hạn mọi phiên cũ', 'PASS (Logic AuthService)'),
            ('TC-F01.06', 'Rate limit chống brute-force', 'Đăng nhập sai quá 5 lần trong 1 phút', 'Chặn tạm thời với 429 Too Many Requests', 'PENDING (Cần test suite tự động)'),
            ('TC-F01.07', 'Xác thực OTP và đặt lại mật khẩu', 'Nhập mã OTP đúng trong vòng 10 phút', 'Đổi mật khẩu thành công, vô hiệu hóa mã OTP sau 1 lần dùng', 'PASS (7/7 mail_smoke_test.php)')
        ],
        'defects': [
            ('2026-10-17 14:00', 'AuthService.php & TokenService.php', 'Namespace cũ WealthFlow\\Auth gây lỗi class not found sau refactor', 'FIXED: Gom TokenService về WealthFlow\\Core và chuyển AuthService về WealthFlow\\Modules\\Auth.'),
            ('2026-10-18 10:30', 'bin/send-pending-emails.php', 'Không gửi được mail thật khi máy dev không có App Password Gmail hoặc ngắt mạng', 'FIXED: Thêm cơ chế fallback lưu file .eml nội bộ khi SE_MAIL_TRANSPORT=log.'),
            ('2026-10-22 08:30', 'source/backend/tests/run_tests.php', 'Chưa tích hợp nhóm kiểm thử tự động cho AuthService và UserDAO vào test runner chính', 'OPEN (Defect DF-01): Cần viết thêm 7 assertions cho Auth trong run_tests.php.')
        ]
    },
    {
        'id': 'FR02',
        'dir': 'fr02_wallet_management',
        'name': 'Quản lý tài khoản thanh toán và ví tiền',
        'sprint': 'S2 (26/10 - 01/11/2026)',
        'owner': 'Võ Văn Khôi Nguyên',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC02 (Quản lý ví và số dư)',
        'br': 'BR01, BR02, BR03, BR11',
        'components': [
            'source/backend/src/Modules/Wallets/WalletController.php',
            'source/backend/src/Modules/Wallets/WalletService.php',
            'source/backend/src/Modules/Wallets/WalletDAO.php',
            'source/frontend/js/app/wallets.js',
            'source/frontend/js/app/wallet-feature.js'
        ],
        'status': 'PASS (Đạt 24/24 assertions trong run_tests.php, API nối frontend thật)',
        'tests': [
            ('TC-F02.01', 'Tạo ví mới kèm số dư đầu kỳ', 'Tên ví, loại ví, số dư đầu kỳ 2.000.000 VND', 'Tạo ví, sinh bút toán đối ứng Vốn chủ (Equity), số dư cập nhật 2tr', 'PASS'),
            ('TC-F02.01a', 'Từ chối trùng tên ví cùng user', 'Tên ví trùng với ví đang có của user', 'Trả lỗi 409 DUPLICATE_WALLET_NAME', 'PASS'),
            ('TC-F02.01b', 'Idempotent tạo ví', 'Gửi 2 lần cùng Idempotency-Key và payload', 'Trả kết quả ví ban đầu, không tạo ví thứ 2', 'PASS'),
            ('TC-F02.01c', 'Tái sử dụng Idempotency-Key đổi payload', 'Cùng key nhưng đổi tên hoặc số tiền', 'Từ chối 409 Conflict', 'PASS'),
            ('TC-F02.02', 'Kiểm tra bút toán đầu kỳ', 'Tạo ví số dư > 0', 'Sinh 2 dòng ledger_entries cân bằng tuyệt đối', 'PASS'),
            ('TC-F02.03', 'Đổi tên ví theo phạm vi user', 'Đổi tên ví của chính mình', 'Tên ví cập nhật, user khác không bị ảnh hưởng', 'PASS'),
            ('TC-F02.04', 'Từ chối số dư đầu kỳ âm', 'Initial balance = -500.000', 'Báo lỗi 422/400 Validation Error', 'PASS'),
            ('TC-F02.05', 'Mở ví ngoại tệ khi chưa có tỷ giá', 'Mở ví USD kèm số dư khi tỷ giá chưa verify', 'Từ chối mở số dư dương khi chưa có rate snapshot', 'PASS'),
            ('TC-F02.06', 'Từ chối lưu trữ ví còn tiền', 'Ví còn 100.000 VND yêu cầu archive', 'Từ chối lưu trữ, yêu cầu đưa số dư về 0 trước', 'PASS'),
            ('TC-F02.07', 'Lưu trữ ví rỗng thành công', 'Ví số dư = 0, không có recurring', 'Chuyển trạng thái ví sang ARCHIVED', 'PASS'),
            ('TC-F02.07a', 'Xóa vĩnh viễn ví chưa có giao dịch', 'Ví lưu trữ chưa từng phát sinh bút toán sổ cái', 'Xóa vật lý bản ghi ví thành công', 'PASS'),
            ('TC-F02.07b', 'Cấm xóa ví đang ACTIVE', 'Yêu cầu xóa ví đang ACTIVE', 'Bắt buộc phải lưu trữ (ARCHIVED) trước khi xóa', 'PASS'),
            ('TC-F02.08', 'Cách ly danh sách ví giữa các user', 'User 2 lấy danh sách ví của User 1', 'Chỉ trả danh sách ví thuộc sở hữu của User 2', 'PASS'),
            ('TC-F02.14', 'Bảo toàn ví đã có lịch sử sổ cái', 'Yêu cầu xóa ví đã có bút toán', 'Giữ nguyên trạng thái ARCHIVED, không xóa để bảo toàn lịch sử', 'PASS')
        ],
        'defects': [
            ('2026-10-16 22:15', 'WalletDAO.php::renameWallet', 'Không kiểm tra điều kiện user_id khi đổi tên ví dẫn đến nguy cơ IDOR', 'FIXED: Thêm điều kiện AND user_id = :userId vào câu lệnh UPDATE.'),
            ('2026-10-18 14:20', 'WalletService.php::archiveWallet', 'Cho phép lưu trữ ví khi số dư vẫn còn > 0 vi phạm BR01', 'FIXED: Kiểm tra bccomp(balance, 0) == 0 và kiểm tra recurring ACTIVE trước khi archive.'),
            ('2026-10-21 16:00', 'WalletService.php::validateAmount', 'Input số tiền VND có chứa phần thập phân hoặc dấu phân cách hỗn hợp gây lỗi tính toán', 'FIXED: Thêm regex kiểm tra định dạng VND và ngoại tệ nghiêm ngặt (TC-F02.05c/d/e).')
        ]
    },
    {
        'id': 'FR03',
        'dir': 'fr03_double_entry_ledger',
        'name': 'Quản lý giao dịch tài chính và sổ kế toán kép',
        'sprint': 'S2 (26/10 - 01/11/2026)',
        'owner': 'Võ Văn Khôi Nguyên',
        'reviewer': 'Võ Trịnh Quốc Huy',
        'uc': 'UC03 (Ghi nhận thu chi, chuyển ví, đảo bút toán)',
        'br': 'BR01, BR02, BR03, BR04, BR05, BR06, BR12, BR13, BR14, BR15',
        'components': [
            'source/backend/src/Modules/Transactions/TransactionController.php',
            'source/backend/src/Modules/Transactions/LedgerService.php',
            'source/backend/src/Modules/Transactions/LedgerDAO.php',
            'source/backend/src/Modules/Transactions/CategoryDAO.php'
        ],
        'status': 'PASS (Đạt 18/18 assertions trong run_tests.php, sổ cái kép chuẩn kế toán)',
        'tests': [
            ('TC-F03.01.1', 'Tạo giao dịch chi tiêu', 'Chi 150.000 VND từ ví tiền mặt', 'Giao dịch tạo thành công trạng thái POSTED', 'PASS'),
            ('TC-F03.01.2', 'Kiểm tra 2 dòng sổ cái đối ứng', 'Chi tiêu 150k', 'Sinh chính xác 1 dòng DEBIT Chi phí, 1 dòng CREDIT Ví', 'PASS'),
            ('TC-F03.01.3', 'Cân bằng nợ có tuyệt đối', 'Tổng debit base và credit base', 'sum(DebitBase) == sum(CreditBase) chính xác đến từng xu', 'PASS'),
            ('TC-F03.01.4', 'Khấu trừ số dư ví', 'Chi 150.000 VND', 'Số dư ví bị trừ chính xác 150.000 VND', 'PASS'),
            ('TC-F03.02', 'Từ chối chi tiêu vượt số dư', 'Ví có 100k, chi 500k', 'Ném ngoại lệ 422 INSUFFICIENT_FUNDS, rollback toàn bộ', 'PASS'),
            ('TC-F03.03.1', 'Chuyển tiền giữa hai ví nội bộ', 'Chuyển 500k từ Ví A sang Ví B', 'Tạo giao dịch TRANSFER trạng thái POSTED', 'PASS'),
            ('TC-F03.03.2', 'Số dư ví nguồn giảm', 'Chuyển 500k', 'Ví nguồn giảm đúng 500.000 VND', 'PASS'),
            ('TC-F03.03.3', 'Số dư ví đích tăng', 'Chuyển 500k', 'Ví đích tăng đúng 500.000 VND', 'PASS'),
            ('TC-F03.04', 'Từ chối chuyển vào chính ví nguồn', 'fromWalletId == toWalletId', 'Báo lỗi 400 SAME_WALLET_TRANSFER', 'PASS'),
            ('TC-F03.05.1', 'Đảo bút toán (Reversal)', 'Đảo giao dịch chi 150k đã POSTED', 'Giao dịch gốc chuyển trạng thái REVERSED', 'PASS'),
            ('TC-F03.05.2', 'Tạo giao dịch đảo đối ứng', 'Đảo giao dịch', 'Sinh giao dịch REVERSAL liên kết original_id', 'PASS'),
            ('TC-F03.05.3', 'Đổi chiều Nợ Có hoàn trả số dư', 'Bút toán đảo', 'Hoán đổi DEBIT/CREDIT, số dư ví được phục hồi lại +150.000 VND', 'PASS'),
            ('TC-F03.06', 'Từ chối đảo giao dịch đã đảo', 'Cố tình gọi đảo lần 2', 'Ném lỗi 422 ALREADY_REVERSED', 'PASS'),
            ('TC-F03.07', 'Idempotency chống trừ tiền trùng', 'Gửi lặp lại cùng Idempotency-Key', 'Trả lại kết quả đã cache, không trừ tiền lần 2', 'PASS'),
            ('TC-F03.09', 'Rollback an toàn khi giao dịch lỗi', 'Giả lập lỗi giữa chừng khi post ledger', 'Toàn bộ transaction bị rollback, không có bản ghi mồ côi', 'PASS')
        ],
        'defects': [
            ('2026-10-17 15:30', 'Modules/Transactions/', 'Tồn tại class TransactionRepository cũ trùng lặp với LedgerDAO', 'FIXED: Đã xóa TransactionRepository và CategoryRepository, chuyển toàn bộ vào LedgerDAO và CategoryDAO.'),
            ('2026-10-17 16:45', 'LedgerService.php::reverseTransaction', 'Giao dịch đã REVERSED vẫn cho phép đảo tiếp gây sai lệch số dư ví', 'FIXED: Kiểm tra trạng thái status == POSTED trước khi thực hiện đảo bút toán.'),
            ('2026-10-18 11:00', 'LedgerService.php::postTransaction', 'Khi mạng lag người dùng ấn lưu nhiều lần sinh nhiều giao dịch trùng', 'FIXED: Lưu idempotency_keys và payload_hash để trả cache tức thì.')
        ]
    },
    {
        'id': 'FR04',
        'dir': 'fr04_multi_currency_fx',
        'name': 'Đa tiền tệ và tỷ giá hối đoái thời điểm (Snapshot)',
        'sprint': 'S2 (26/10 - 01/11/2026)',
        'owner': 'Võ Văn Khôi Nguyên',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC02, UC03 (Giao dịch ngoại tệ, snapshot tỷ giá)',
        'br': 'BR03, BR04, BR15',
        'components': [
            'source/backend/src/Modules/Transactions/ExchangeRateService.php',
            'source/backend/src/Modules/Transactions/VietcombankRateProvider.php',
            'source/database/021_exchange_rates.sql'
        ],
        'status': 'PASS (Đạt 4/4 assertions trong run_tests.php, snapshot bất biến)',
        'tests': [
            ('TC-F04.01.1', 'Lấy tỷ giá hiệu lực USD/VND', 'Truy vấn tỷ giá USD tại thời điểm hiện tại', 'Trả về tỷ giá hiệu lực > 0', 'PASS'),
            ('TC-F04.01.2', 'Lưu snapshot tỷ giá bất biến', 'Thực hiện giao dịch bằng USD', 'fx_rate_snapshot lưu cố định trên bản ghi giao dịch', 'PASS'),
            ('TC-F04.01.3', 'Tính tiền cơ sở bằng BCMath', 'Số tiền 100 USD * tỷ giá snapshot', 'amount_base tính toán chính xác scale 8, không sai số float', 'PASS'),
            ('TC-F04.02', 'Đồng bộ tỷ giá Vietcombank', 'Gọi đồng bộ bảng tỷ giá', 'Lưu đầy đủ tỷ giá trực tiếp, nghịch đảo và tỷ giá chéo EUR/USD', 'PASS')
        ],
        'defects': [
            ('2026-10-17 11:20', 'LedgerService.php::postTransaction', 'Báo cáo tháng trước bị đổi số liệu khi tỷ giá hôm nay biến động', 'FIXED: Áp dụng BR04 snapshot tỷ giá thời điểm, cấm tính toán lại giao dịch quá khứ.'),
            ('2026-10-20 09:30', 'VietcombankRateProvider.php', 'Thiếu tỷ giá chéo giữa các ngoại tệ (EUR -> USD)', 'FIXED: Cài đặt công thức tính tỷ giá chéo qua đồng tiền VND trung gian.')
        ]
    },
    {
        'id': 'FR05',
        'dir': 'fr05_expense_budgeting',
        'name': 'Thiết lập và kiểm soát ngân sách chi tiêu',
        'sprint': 'S2 (26/10 - 01/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Võ Văn Khôi Nguyên',
        'uc': 'UC05 (Thiết lập ngân sách và nhận cảnh báo)',
        'br': 'BR07, BR08',
        'components': [
            'source/backend/src/Modules/Budgets/BudgetController.php',
            'source/backend/src/Modules/Budgets/BudgetService.php',
            'source/backend/src/Modules/Budgets/BudgetDAO.php',
            'source/backend/src/Modules/Notifications/NotificationService.php'
        ],
        'status': 'PASS (Đạt 11/11 assertions trong run_tests.php, xử lý hoàn hảo ngưỡng 80/100%)',
        'tests': [
            ('TC-F05.01.1', 'Tạo ngân sách tháng theo danh mục', 'Ngân sách Ăn uống hạn mức 2.000.000 VND cho tháng hiện tại', 'Tạo bản ghi budget thành công', 'PASS'),
            ('TC-F05.02.1', 'Kích hoạt cảnh báo Vàng (>= 80%)', 'Chi tiêu đạt 1.650.000 VND (82.5%)', 'Sinh thông báo Cảnh báo Vàng gửi vào Notification Center', 'PASS'),
            ('TC-F05.02.2', 'Kích hoạt cảnh báo Đỏ (>= 100%)', 'Chi tiêu đạt 2.050.000 VND (102.5%)', 'Sinh thông báo Cảnh báo Đỏ bội chi', 'PASS'),
            ('TC-F05.02.3', 'Chống gửi cảnh báo trùng lặp', 'Chi tiếp trong cùng ngưỡng đã báo', 'Không sinh thêm thông báo rác cùng ngưỡng', 'PASS'),
            ('TC-12.1', 'Nhảy vọt từ <80% lên >=100% (Jump-over-yellow)', 'Chi một khoản lớn từ 50% vọt lên 105%', 'Bắn ngay thông báo ĐỎ lập tức', 'PASS'),
            ('TC-12.2', 'Ngăn bắn bù thông báo Vàng trễ', 'Sau khi đã vượt 100%, chi tiêu giảm về 85%', 'Không bắn thông báo Vàng vô lý', 'PASS'),
            ('TC-13', 'Loại trừ chuyển ví/trả nợ khỏi ngân sách', 'Chuyển ví nội bộ và trả nợ gốc', 'Net spend ngân sách không bị tăng sai theo BR07', 'PASS'),
            ('TC-F05.03.1', 'Cập nhật hạn mức ngân sách', 'Tăng hạn mức lên 3.000.000 VND', 'Tỷ lệ % chi tiêu tự động tính lại về màu Xanh', 'PASS'),
            ('TC-F05.03.2', 'Lưu trữ (Archive) ngân sách', 'Archive ngân sách cũ', 'Ẩn khỏi danh sách active nhưng giữ lịch sử', 'PASS'),
            ('TC-F05.04.1', 'Đánh dấu đã đọc thông báo', 'User đánh dấu read thông báo ngân sách', 'unreadCount giảm chính xác, lọc unreadOnly hoạt động đúng', 'PASS')
        ],
        'defects': [
            ('2026-10-18 16:00', 'BudgetService.php::calculateNetSpend', 'Chuyển tiền giữa 2 ví bị tính vào chi tiêu làm nhảy cảnh báo ngân sách sai', 'FIXED: Bổ sung điều kiện loại trừ type = TRANSFER và status = REVERSED theo BR07.'),
            ('2026-10-21 14:15', 'BudgetService.php::checkThresholds', 'Chi tiêu nhảy vọt qua ngưỡng 80% lên 100% bỏ sót cảnh báo đỏ', 'FIXED: Cài đặt quy tắc BR08 jump-over-yellow: ưu tiên kích hoạt cảnh báo Đỏ trước và đánh dấu đã gửi Vàng.')
        ]
    },
    {
        'id': 'FR06',
        'dir': 'fr06_recurring_transactions',
        'name': 'Xử lý giao dịch định kỳ',
        'sprint': 'S2 (26/10 - 01/11/2026)',
        'owner': 'Võ Văn Khôi Nguyên',
        'reviewer': 'Võ Trịnh Quốc Huy',
        'uc': 'UC04, SYS02 (Định kỳ tự động & Lập lịch nền)',
        'br': 'BR09, BR12, BR13',
        'components': [
            'source/backend/src/Modules/Recurring/RecurringController.php',
            'source/backend/src/Modules/Recurring/RecurringService.php',
            'source/backend/src/Modules/Recurring/RecurringRuleDAO.php',
            'source/backend/src/Modules/Recurring/RecurringWorker.php',
            'source/backend/bin/process_recurring.php'
        ],
        'status': 'PASS (Đạt 9/9 assertions trong run_tests.php, neo ngày 31 chính xác)',
        'tests': [
            ('TC-F06.01.1', 'Kẹp ngày neo 31 vào tháng 2', 'Lịch định kỳ ngày 31 chạy vào tháng 2', 'Kẹp về ngày 28/02 (hoặc 29/02 năm nhuận)', 'PASS'),
            ('TC-F06.01.2', 'Phục hồi ngày neo 31 vào tháng 3', 'Kỳ tiếp theo sau tháng 2', 'Tự động phục hồi ngày đến hạn về ngày 31/03 theo BR09', 'PASS'),
            ('TC-F06.01.3', 'Kẹp ngày neo 31 vào tháng 4 (30 ngày)', 'Kỳ tiếp theo tháng 4', 'Kẹp về ngày 30/04 chính xác', 'PASS'),
            ('TC-F06.02.1', 'Tạo lịch định kỳ AUTO_POST', 'Hóa đơn tiền mạng ngày 05 hàng tháng', 'Tạo lịch chế độ tự động hạch toán', 'PASS'),
            ('TC-F06.02.2', 'Tạo lịch định kỳ DRAFT_REMINDER', 'Tiền thuê nhà ngày 01 hàng tháng', 'Tạo lịch chế độ nhắc nhở phê duyệt', 'PASS'),
            ('TC-F06.03.1', 'Worker xử lý lịch đến hạn', 'Kích hoạt worker lúc 00:00 UTC', 'Xử lý đầy đủ các lịch có next_due_date <= now', 'PASS'),
            ('TC-F06.03.2', 'Hạch toán tự động lịch AUTO_POST', 'Lịch AUTO_POST đến hạn', 'Tự động sinh giao dịch POSTED và trừ ví', 'PASS'),
            ('TC-F06.03.3', 'Tạo nhắc nhở lịch DRAFT_REMINDER', 'Lịch DRAFT_REMINDER đến hạn', 'Tạo giao dịch nháp và gửi notification nhắc user', 'PASS'),
            ('TC-F06.03.4', 'Tịnh tiến next_due_date chu kỳ mới', 'Sau khi xử lý thành công', 'Ngày tới hạn tăng lên chu kỳ tiếp theo chính xác', 'PASS')
        ],
        'defects': [
            ('2026-10-17 17:00', 'RecurringService.php::calculateNextDueDate', 'Cộng 1 tháng vào ngày 31/01 trong PHP sinh ngày 03/03 làm mất kỳ tháng 2', 'FIXED: Cài đặt thuật toán date clamping: neo anchor_day = 31 và lấy min(anchor_day, daysInMonth).'),
            ('2026-10-18 09:45', 'RecurringWorker.php', 'Chạy lặp lại worker nhiều lần sinh giao dịch kép', 'FIXED: Thêm bảng recurring_executions để khóa chu kỳ xử lý.')
        ]
    },
    {
        'id': 'FR07',
        'dir': 'fr07_savings_goals',
        'name': 'Quản lý mục tiêu tích lũy tài chính',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC09 (Mục tiêu tài chính và hũ tiết kiệm)',
        'br': 'BR10, BR15',
        'components': [
            'source/backend/src/Modules/Goals/GoalController.php',
            'source/backend/src/Modules/Goals/GoalService.php',
            'source/backend/src/Modules/Goals/FinancialGoalDAO.php',
            'source/database/022_financial_goals.sql',
            'source/database/023_goal_allocations.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F07.01', 'Tạo mục tiêu tiết kiệm mới', 'Tên: Quỹ khẩn cấp, Hạn mức: 30tr, Hạn chót: 31/12/2026', 'Tạo goal trạng thái IN_PROGRESS, current_amount = 0', 'PENDING'),
            ('TC-F07.02', 'Nạp tiền vào quỹ mục tiêu', 'Trích 5.000.000 VND từ ví tiền mặt vào goal', 'Số tiền tích lũy tăng lên 5tr, tiến độ đạt 16.7%', 'PENDING'),
            ('TC-F07.03', 'Cơ chế phong tỏa số dư ví (BR15 Earmarking)', 'Ví có 10tr, đã nạp goal 5tr', 'Số dư khả dụng = 5tr; chi tiêu 6tr bị từ chối 422', 'PENDING'),
            ('TC-F07.04', 'Rút tiền từ goal hoàn trả về ví', 'Rút 2.000.000 VND từ goal về lại ví', 'Tiền goal giảm còn 3tr, số dư khả dụng của ví tăng lại', 'PENDING'),
            ('TC-F07.05', 'Đạt 100% mục tiêu tiết kiệm', 'Nạp đủ 30.000.000 VND', 'Trạng thái chuyển sang COMPLETED, sinh notification chúc mừng', 'PENDING'),
            ('TC-F07.06', 'Bảo toàn tài sản ròng (BR10)', 'Nạp tiền vào goal', 'Tổng tài sản Net Worth không đổi (chỉ là phân bổ nội bộ)', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Goals/GoalService.php', 'File GoalService.php và FinancialGoalDAO.php mới là khung rỗng 10 dòng', 'OPEN (Defect DF-07): Cần hiện thực hóa nghiệp vụ phân bổ hũ tiết kiệm và phong tỏa số dư ví.')
        ]
    },
    {
        'id': 'FR08',
        'dir': 'fr08_personal_wealth_gold',
        'name': 'Quản lý tài sản tích lũy ngoài ví (Vàng) và Tài sản ròng',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC08 (Quản lý vàng và tài sản tích lũy)',
        'br': 'BR02, BR14, BR16',
        'components': [
            'source/backend/src/Modules/Wealth/GoldController.php',
            'source/backend/src/Modules/Wealth/GoldAssetService.php',
            'source/backend/src/Modules/Wealth/GoldHoldingDAO.php',
            'source/database/019_gold_assets.sql',
            'source/database/020_gold_transactions.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F08.01', 'Ghi nhận mua vàng nhẫn 9999', 'Mua 2 chỉ giá 8.000.000 VND/chỉ, thanh toán từ Ví Techcombank', 'Tạo lô vàng 2 chỉ, ví trừ 16.000.000 VND', 'PENDING'),
            ('TC-F08.02', 'Tính giá vốn bình quân gia quyền WAC (BR14)', 'Mua thêm 1 chỉ giá 8.300.000 VND', 'Tổng 3 chỉ, Giá vốn bình quân WAC = 8.100.000 VND/chỉ', 'PENDING'),
            ('TC-F08.03', 'Bán vàng và hạch toán lãi vốn', 'Bán 1 chỉ lúc giá 8.500.000 VND', 'Ví nhận +8.5tr, hạch toán lãi vốn +400k vào income', 'PENDING'),
            ('TC-F08.04', 'Từ chối bán vượt số lượng vàng có', 'Tồn kho 2 chỉ, nhập bán 3 chỉ', 'Ném lỗi 422 INSUFFICIENT_GOLD_HOLDING', 'PENDING'),
            ('TC-F08.05', 'Định giá danh mục vàng theo thị trường', 'Cập nhật giá thị trường ngày hôm nay 8.4tr/chỉ', 'Giá trị thị trường = 2 chỉ * 8.4tr = 16.8tr', 'PENDING'),
            ('TC-F08.06', 'Tất toán toàn bộ danh mục vàng', 'Bán toàn bộ số lượng còn lại', 'Số lượng về 0, giá trị vốn tồn kho xuất sạch về 0', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Wealth/GoldAssetService.php', 'Chưa có code thuật toán tính giá vốn WAC và trích tiền ví', 'OPEN (Defect DF-08): Cần viết logic mua bán vàng và hạch toán lãi/lỗ vốn.')
        ]
    },
    {
        'id': 'FR09',
        'dir': 'fr09_microfinance_loans',
        'name': 'Quản lý khoản vay và nghĩa vụ nợ vi mô (Microfinance Tracking)',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC06, UC07, SYS03 (Vay nợ vi mô & Lịch trả nợ)',
        'br': 'BR05, BR10, BR17',
        'components': [
            'source/backend/src/Modules/Loans/LoanController.php',
            'source/backend/src/Modules/Loans/LoanService.php',
            'source/backend/src/Modules/Loans/LoanDAO.php',
            'source/database/016_micro_loans.sql',
            'source/database/017_loan_repayment_schedules.sql',
            'source/database/018_loan_payments.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F09.01', 'Tạo khoản đi vay (Borrowing)', 'Vay 10.000.000 VND kỳ hạn 6 tháng, giải ngân vào Ví tiền mặt', 'Tạo nợ phải trả 10tr, ví +10tr, KHÔNG tính là thu nhập', 'PENDING'),
            ('TC-F09.02', 'Tạo khoản cho vay (Lending)', 'Cho bạn vay 5.000.000 VND từ Ví ngân hàng', 'Tạo nợ phải thu 5tr, ví -5tr, KHÔNG tính là chi phí sinh hoạt', 'PENDING'),
            ('TC-F09.03', 'Sinh lịch phân kỳ nợ Amortization', 'Vay 10tr trả góp 5 kỳ đều nhau', 'Tự động sinh 5 bản ghi repayment_schedules với ngày đến hạn', 'PENDING'),
            ('TC-F09.04', 'Thanh toán trả nợ từng kỳ (BR05, BR17)', 'Trả kỳ 1: Gốc 2tr + Lãi 100k', 'Ví trừ 2.1tr, Dư nợ gốc giảm 2tr, Chỉ 100k lãi tính vào Chi phí', 'PENDING'),
            ('TC-F09.05', 'Ngăn tiền trả gốc đội chi tiêu ngân sách (BR07)', 'Kiểm tra ngân sách tháng khi trả nợ', 'Phần tiền trả gốc 2tr không bị cộng dồn vào ngân sách chi tiêu', 'PENDING'),
            ('TC-F09.06', 'Từ chối trả nợ gốc vượt dư nợ', 'Dư nợ còn 4tr, nhập trả gốc 5tr', 'Báo lỗi 422 REPAYMENT_EXCEEDS_PRINCIPAL', 'PENDING'),
            ('TC-F09.07', 'Tất toán khoản vay trước hạn', 'Trả toàn bộ số nợ gốc còn lại', 'Khoản nợ chuyển trạng thái SETTLED', 'PENDING'),
            ('TC-F09.08', 'Cảnh báo khoản vay sắp đến hạn', 'Trước 3 ngày đến hạn kỳ thanh toán', 'Tự động sinh notification nhắc nhở người dùng', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Loans/LoanService.php', 'Chưa có code bóc tách nợ gốc vs tiền lãi theo BR05/BR17', 'OPEN (Defect DF-09): Cần lập trình Amortization schedule và hạch toán trả nợ.')
        ]
    },
    {
        'id': 'FR10',
        'dir': 'fr10_net_worth_analytics',
        'name': 'Dashboard sức khỏe tài chính và Tổng quan tài sản ròng',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Võ Trịnh Quốc Huy',
        'uc': 'UC10, SYS03 (Tài sản ròng & Điểm sức khỏe)',
        'br': 'BR16, BR18',
        'components': [
            'source/backend/src/Modules/Wealth/AnalyticsDashboardController.php',
            'source/backend/src/Modules/Wealth/NetWorthAnalyticsService.php',
            'source/backend/src/Modules/Wealth/NetWorthSnapshotDAO.php',
            'source/backend/bin/daily_snapshot.php',
            'source/database/024_net_worth_snapshots.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F10.01', 'Tính phương trình Tài sản ròng (BR16)', 'Ví: 10tr, Vàng: 16tr, Nợ vay: 6tr', 'Net Worth = (10tr + 16tr) - 6tr = 20.000.000 VND', 'PENDING'),
            ('TC-F10.02', 'Chụp ảnh snapshot tài sản ròng (SYS03)', 'Chạy cron lúc 23:59:59', 'Ghi bản ghi vào net_worth_snapshots, không đếm trùng', 'PENDING'),
            ('TC-F10.03', 'Tính điểm sức khỏe tài chính (BR18)', 'Tỷ lệ nợ/tài sản và đệm thanh khoản', 'Tính điểm sức khỏe trên thang 100, phòng thủ chia cho 0', 'PENDING'),
            ('TC-F10.04', 'Biểu đồ tăng trưởng tài sản theo thời gian', 'Lấy lịch sử 30 ngày', 'Trả chuỗi thời gian snapshot để vẽ biểu đồ Line Chart', 'PENDING'),
            ('TC-F10.05', 'Đồng bộ biến động tức thời', 'Vừa chi tiêu 200k', 'Net Worth trên Dashboard giảm tương ứng 200k', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Wealth/NetWorthAnalyticsService.php', 'Chưa có code tính phương trình Net Worth và snapshot', 'OPEN (Defect DF-10): Cần hiện thực hóa công thức Assets - Liabilities.')
        ]
    },
    {
        'id': 'FR11',
        'dir': 'fr11_cash_flow_saving_rate',
        'name': 'Phân tích dòng tiền và Tỷ lệ tiết kiệm',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Phạm Nguyễn Tuấn Kiệt',
        'reviewer': 'Võ Trịnh Quốc Huy',
        'uc': 'UC10 (Phân tích dòng tiền, Saving Rate)',
        'br': 'BR18',
        'components': [
            'source/backend/src/Modules/Wealth/NetWorthAnalyticsService.php',
            'source/backend/src/Modules/Reports/ReportController.php'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F11.01', 'Tính Dòng tiền thuần (Net Cash Flow)', 'Thu nhập: 15tr, Chi tiêu: 10tr', 'Net Cash Flow = +5.000.000 VND', 'PENDING'),
            ('TC-F11.02', 'Tính Tỷ lệ tiết kiệm (Saving Rate)', 'Thu nhập: 15tr, Chi tiêu: 9tr', 'Saving Rate = (15tr - 9tr) / 15tr = 40.0%', 'PENDING'),
            ('TC-F11.03', 'Phòng thủ chia cho 0 khi Income = 0', 'Tháng không có thu nhập, chi 2tr', 'Cash Flow = -2tr, Saving Rate trả về null / N/A, không crash', 'PENDING'),
            ('TC-F11.04', 'Loại trừ chuyển ví và đảo bút toán', 'Phát sinh transfer 5tr và reversal 1tr', 'Không bị tính sai vào Cash Flow', 'PENDING'),
            ('TC-F11.05', 'Cơ cấu chi tiêu theo danh mục', 'Lấy phân bổ chi tiêu tháng', 'Trả mảng tỷ trọng % theo từng danh mục Ăn uống, Thuê nhà...', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Wealth/NetWorthAnalyticsService.php', 'Chưa cài đặt phép tính Saving Rate và phòng thủ chia cho 0', 'OPEN (Defect DF-11): Cần viết logic tính toán Cash Flow và Saving Rate.')
        ]
    },
    {
        'id': 'FR12',
        'dir': 'fr12_reconciliation_csv_export',
        'name': 'Tra cứu lịch sử, đối soát và xuất tệp CSV',
        'sprint': 'S3 (02/11 - 08/11/2026)',
        'owner': 'Võ Văn Khôi Nguyên',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC11 (Đối soát số dư sổ cái và xuất CSV)',
        'br': 'BR01, BR11, BR14',
        'components': [
            'source/backend/src/Modules/Reports/ReportController.php',
            'source/backend/src/Modules/Reports/ReconciliationService.php',
            'source/backend/src/Modules/Reports/ExportService.php',
            'source/backend/src/Modules/Reports/ReportDAO.php',
            'source/database/028_export_audit_logs.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 3)',
        'tests': [
            ('TC-F12.01', 'Đối soát số dư ví với Sổ cái kế toán', 'So sánh cached_balance ví với sum(Debit - Credit) sổ cái', 'Khớp hoàn toàn 100%, trạng thái MATCHED', 'PENDING'),
            ('TC-F12.02', 'Phát hiện sai lệch số dư sổ cái', 'Giả lập cached_balance bị sửa thủ công', 'Báo động LEDGER_MISMATCH, chỉ ra số tiền chênh lệch', 'PENDING'),
            ('TC-F12.03', 'Xuất file CSV giao dịch', 'Xuất giao dịch theo khoảng ngày', 'Sinh file CSV với các cột Ngày, Loại, Ví, Danh mục, Tiền', 'PENDING'),
            ('TC-F12.04', 'Khử độc Formula Injection trong CSV', 'Giao dịch có tên =cmd|\' /C calc\'!A0', 'Tự động chèn dấu nháy đơn \' phía trước, trung hòa công thức', 'PENDING'),
            ('TC-F12.05', 'Hỗ trợ UTF-8 BOM tiếng Việt', 'Mở file CSV bằng Microsoft Excel', 'Hiển thị chuẩn tiếng Việt có dấu, không vỡ font chữ', 'PENDING'),
            ('TC-F12.06', 'Bảo vệ quyền riêng tư xuất dữ liệu', 'User A xuất CSV', 'Chỉ chứa giao dịch của User A, không lộ dữ liệu User B', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Reports/ReconciliationService.php & ExportService.php', 'File placeholder 10 dòng, UI đang dùng dữ liệu mô phỏng', 'OPEN (Defect DF-12): Cần viết hàm đối soát SQL thật và bộ xuất CSV chống Formula Injection.')
        ]
    },
    {
        'id': 'FR13',
        'dir': 'fr13_system_config_broadcast',
        'name': 'Cấu hình hệ thống và Phát thông báo người dùng (Admin)',
        'sprint': 'S4 (09/11 - 15/11/2026)',
        'owner': 'Võ Trịnh Quốc Huy',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC12 (Cấu hình tham số & Broadcast)',
        'br': 'BR07, BR08, BR11',
        'components': [
            'source/backend/src/Modules/Admin/AdminController.php',
            'source/backend/src/Modules/Admin/AdminService.php',
            'source/backend/src/Modules/Admin/AdminDAO.php',
            'source/database/026_system_config.sql',
            'source/database/025_notifications.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 4)',
        'tests': [
            ('TC-F13.01', 'Admin cập nhật tham số hệ thống', 'Đổi tham số default_currency hoặc max_upload_size', 'Cập nhật bảng system_config, hệ thống áp dụng ngay', 'PENDING'),
            ('TC-F13.02', 'Phát thông báo Broadcast toàn hệ thống', 'Admin gửi thông báo bảo trì hệ thống', 'Phân phối tới 100% người dùng bằng Batch Fan-out', 'PENDING'),
            ('TC-F13.03', 'User nhận thông báo broadcast', 'User vào Notification Center', 'Hiển thị thông báo broadcast của Admin', 'PENDING'),
            ('TC-F13.04', 'Từ chối quyền User gọi API Admin', 'User thường gọi POST /admin/broadcast', 'Từ chối với mã lỗi 403 FORBIDDEN', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Admin/AdminService.php', 'Chưa có code phát thông báo hàng loạt qua Batch Fan-out', 'OPEN (Defect DF-13): Cần viết câu truy vấn INSERT ... SELECT phân phối thông báo.')
        ]
    },
    {
        'id': 'FR14',
        'dir': 'fr14_admin_user_management',
        'name': 'Quản trị tài khoản người dùng và Dashboard thống kê vĩ mô (Admin)',
        'sprint': 'S4 (09/11 - 15/11/2026)',
        'owner': 'Võ Trịnh Quốc Huy',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'UC13 (Quản trị người dùng & Giám sát vĩ mô)',
        'br': 'BR11',
        'components': [
            'source/backend/src/Modules/Admin/AdminController.php',
            'source/backend/src/Modules/Admin/AdminService.php',
            'source/backend/src/Modules/Admin/AdminDAO.php',
            'source/backend/src/Modules/Admin/AuditLogDAO.php',
            'source/database/027_admin_audit_logs.sql'
        ],
        'status': 'PENDING (CSDL đã tạo, code Backend & Test đang là placeholder cần viết trong Sprint 4)',
        'tests': [
            ('TC-F14.01', 'Khóa tài khoản người dùng vi phạm', 'Admin chọn khóa User spam', 'Đổi trạng thái sang LOCKED, thu hồi toàn bộ token phiên', 'PENDING'),
            ('TC-F14.02', 'Chặn khóa Admin duy nhất (CANNOT_LOCK_LAST_ADMIN)', 'Admin duy nhất cố tình tự khóa chính mình', 'Từ chối 409 CANNOT_LOCK_LAST_ADMIN để bảo vệ hệ thống', 'PENDING'),
            ('TC-F14.03', 'Mở khóa tài khoản người dùng', 'Admin mở khóa tài khoản bị LOCKED', 'User chuyển sang ACTIVE và đăng nhập lại được', 'PENDING'),
            ('TC-F14.04', 'Bảo vệ quyền riêng tư số dư cá nhân (BR11)', 'Admin tra cứu danh sách người dùng', 'TUYỆT ĐỐI KHÔNG xem được số dư ví, thu chi cá nhân của user', 'PENDING'),
            ('TC-F14.05', 'Ghi nhật ký kiểm toán quản trị (Audit Log)', 'Admin thực hiện khóa user hoặc đổi config', 'Ghi lại admin_id, action, target_user, timestamp vào audit log', 'PENDING'),
            ('TC-F14.06', 'Xem Dashboard vĩ mô', 'Xem tổng user, số giao dịch toàn hệ thống', 'Trả về số liệu thống kê tổng quát không lộ dữ liệu cá nhân', 'PENDING')
        ],
        'defects': [
            ('2026-10-22 08:30', 'Modules/Admin/AdminService.php', 'Chưa có code ràng buộc CANNOT_LOCK_LAST_ADMIN và audit logging', 'OPEN (Defect DF-14): Cần hiện thực hóa quản trị user và ghi audit log.')
        ]
    },
    {
        'id': 'FR15',
        'dir': 'fr15_api_security_isolation',
        'name': 'Kiểm soát truy cập API và bảo mật dữ liệu đa người dùng',
        'sprint': 'S1 & S4 (Bảo mật xuyên suốt)',
        'owner': 'Võ Trịnh Quốc Huy',
        'reviewer': 'Nguyễn Thành Trung',
        'uc': 'Toàn bộ 13 Use Cases (Chính sách bảo mật cô lập)',
        'br': 'BR11, BR13, NFR02, NFR03, NFR07',
        'components': [
            'source/backend/src/Core/AuthContext.php',
            'source/backend/src/Core/ApiException.php',
            'source/backend/public/index.php'
        ],
        'status': 'PARTIAL (Đã có AuthContext lấy userId, chưa có nhóm test tự động IDOR trong run_tests.php)',
        'tests': [
            ('TC-F15.01', 'Chặn IDOR truy cập ví người khác', 'User A gọi API lấy chi tiết ví của User B', 'Từ chối 403 Forbidden hoặc 404 Not Found', 'PASS (Đã có TC-F02.08)'),
            ('TC-F15.02', 'Chặn IDOR thao tác giao dịch người khác', 'User A gọi API đảo bút toán giao dịch của User B', 'Từ chối 403/404, bảo toàn dữ liệu của User B', 'PENDING'),
            ('TC-F15.03', 'Từ chối Token giả mạo hoặc hết hạn', 'Gửi request với token sai secret key', 'Từ chối 401 Unauthorized', 'PASS (Core TokenService)'),
            ('TC-F15.04', 'Chống tấn công SQL Injection', 'Input tham số chứa chuỗi độc hại', '100% câu truy vấn sử dụng Prepared Statements an toàn', 'PASS (Kiến trúc PDO)')
        ],
        'defects': [
            ('2026-10-22 08:30', 'source/backend/tests/run_tests.php', 'Chưa gom các kịch bản kiểm tra bảo mật cô lập thành 1 Group Test riêng', 'OPEN (Defect DF-15): Cần bổ sung GROUP riêng cho Security & Isolation trong runner.')
        ]
    }
]

base_dir = os.path.join(os.path.dirname(__file__), '..', 'source', 'tests')
base_dir = os.path.abspath(base_dir)

for item in fr_metadata:
    folder_path = os.path.join(base_dir, item['dir'])
    os.makedirs(folder_path, exist_ok=True)
    report_file = os.path.join(folder_path, 'TEST_REPORT.md')
    
    fr_id = item['id']
    fr_name = item['name']
    fr_sprint = item['sprint']
    fr_owner = item['owner']
    fr_reviewer = item['reviewer']
    fr_uc = item['uc']
    fr_br = item['br']
    fr_status = item['status']

    lines = []
    lines.append(f"# Báo Cáo Kiểm Thử: {fr_id} - {fr_name}\n")
    lines.append("## 1. Thông Tin Chung")
    lines.append(f"* **Mã yêu cầu chức năng:** `{fr_id}`")
    lines.append(f"* **Sprint xây dựng:** `{fr_sprint}`")
    lines.append(f"* **Người phụ trách (Developer):** {fr_owner}")
    lines.append(f"* **Người duyệt (Reviewer):** {fr_reviewer}")
    lines.append(f"* **Use Case liên quan:** {fr_uc}")
    lines.append(f"* **Quy tắc nghiệp vụ (BR):** {fr_br}")
    lines.append(f"* **Trạng thái kiểm thử:** **{fr_status}**\n")
    
    lines.append('### Thành phần mã nguồn liên quan')
    for comp in item['components']:
        fname = os.path.basename(comp)
        lines.append(f'- [{fname}](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/{comp})')
    lines.append('\n---\n')
    
    lines.append('## 2. Danh Mục Kịch Bản Kiểm Thử (Test Cases)')
    lines.append('| Mã Test Case | Kịch bản kiểm thử | Dữ liệu đầu vào & Ràng buộc | Kết quả kỳ vọng | Trạng thái hiện tại |')
    lines.append('| :---: | :--- | :--- | :--- | :---: |')
    for tc in item['tests']:
        lines.append(f'| **{tc[0]}** | {tc[1]} | {tc[2]} | {tc[3]} | `{tc[4]}` |')
    lines.append('\n---\n')
    
    lines.append('## 3. Lịch Sử Lỗi & Ghi Nhận Defect (Defect Log)')
    lines.append('| Thời điểm | Vị trí phát sinh (File / Hàm) | Mô tả lỗi / Thiếu sót | Hướng xử lý / Khắc phục |')
    lines.append('| :---: | :--- | :--- | :--- |')
    for df in item['defects']:
        lines.append(f'| `{df[0]}` | `{df[1]}` | {df[2]} | {df[3]} |')
    lines.append('\n---\n')
    
    lines.append('## 4. Điều Kiện Hoàn Thành & Nghiệm Thu (Definition of Done - DoD)')
    lines.append('1. [ ] Toàn bộ test cases trong bảng mục 2 được tích hợp vào bộ test tự động `source/backend/tests/run_tests.php` và đạt **100% PASS**.')
    lines.append('2. [ ] Không có lỗi cú pháp (`php -l`), tuân thủ chuẩn PSR-12 và `WealthFlow_Coding_Convention.md`.')
    lines.append('3. [ ] 100% câu truy vấn dùng Prepared Statement, kiểm soát phân quyền theo `userId` từ token phiên.')
    lines.append('4. [ ] Giao diện người dùng (Frontend) kết nối thành công với API thực tế qua `http-client.js`, loại bỏ hoàn toàn dữ liệu mock.')
    lines.append('5. [ ] Có bằng chứng chạy test thực tế (ảnh chụp terminal / log kết quả) trước khi merge vào nhánh `main`.\n')
    
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines))
    print(f'Generated: {report_file}')

# Generate README.md in source/tests/
readme_path = os.path.join(base_dir, 'README.md')
readme_lines = [
    '# Danh Mục Kiểm Thử Toàn Diện Hệ Thống WealthFlow (Pha Construction)',
    '',
    'Thư mục này quản lý toàn bộ kế hoạch, kịch bản kiểm thử và nhật ký lỗi (Defect Log) cho **15 nhóm Yêu cầu Chức năng (FR01 - FR15)** theo chuẩn tiến trình RUP/UP, được phân bổ theo 4 Sprints xây dựng.',
    '',
    '## Cấu Trúc Thư Mục Kiểm Thử Theo 15 FR',
    '',
    '| Nhóm FR | Tên chức năng | Sprint | Số Test Cases | Trạng thái hiện tại | Báo cáo chi tiết |',
    '| :---: | :--- | :---: | :---: | :---: | :---: |',
    '| **FR01** | Định danh, xác thực & quản lý phiên | S1 | 7 | Đạt 7 smoke mail | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr01_identity_access/TEST_REPORT.md) |',
    '| **FR02** | Quản lý ví & số dư đầu kỳ | S2 | 14 | **PASS (24 tests)** | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr02_wallet_management/TEST_REPORT.md) |',
    '| **FR03** | Giao dịch thu chi & sổ cái kép | S2 | 15 | **PASS (18 tests)** | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr03_double_entry_ledger/TEST_REPORT.md) |',
    '| **FR04** | Đa tiền tệ & tỷ giá Vietcombank | S2 | 4 | **PASS (4 tests)** | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr04_multi_currency_fx/TEST_REPORT.md) |',
    '| **FR05** | Ngân sách chi tiêu & cảnh báo | S2 | 10 | **PASS (11 tests)** | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr05_expense_budgeting/TEST_REPORT.md) |',
    '| **FR06** | Xử lý giao dịch định kỳ | S2 | 9 | **PASS (9 tests)** | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr06_recurring_transactions/TEST_REPORT.md) |',
    '| **FR07** | Mục tiêu tích lũy tài chính | S3 | 6 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr07_savings_goals/TEST_REPORT.md) |',
    '| **FR08** | Vàng tích lũy & giá vốn WAC | S3 | 6 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr08_personal_wealth_gold/TEST_REPORT.md) |',
    '| **FR09** | Khoản vay & nợ vi mô | S3 | 8 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr09_microfinance_loans/TEST_REPORT.md) |',
    '| **FR10** | Net Worth & Dashboard sức khỏe | S3 | 5 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr10_net_worth_analytics/TEST_REPORT.md) |',
    '| **FR11** | Dòng tiền & Saving Rate | S3 | 5 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr11_cash_flow_saving_rate/TEST_REPORT.md) |',
    '| **FR12** | Đối soát sổ cái & xuất CSV | S3 | 6 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr12_reconciliation_csv_export/TEST_REPORT.md) |',
    '| **FR13** | Cấu hình & Broadcast Admin | S4 | 4 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr13_system_config_broadcast/TEST_REPORT.md) |',
    '| **FR14** | Quản trị User & Audit Log | S4 | 6 | PENDING | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr14_admin_user_management/TEST_REPORT.md) |',
    '| **FR15** | Bảo mật API & Cô lập dữ liệu | S1/S4 | 4 | PARTIAL | [TEST_REPORT.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr15_api_security_isolation/TEST_REPORT.md) |',
    '',
    '## Cách Chạy Bộ Test Tự Động',
    '```bash',
    '# Chạy test suite chính (71 tests PASS)',
    'php source/backend/tests/run_tests.php',
    '',
    '# Chạy kiểm thử email/OTP queue (7 tests PASS)',
    'php source/backend/tests/mail_smoke_test.php',
    '```'
]
with open(readme_path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(readme_lines))
print(f'Generated: {readme_path}')
