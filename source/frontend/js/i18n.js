const LANGUAGE_KEY = 'wealthflow.language';
const translations = new Map(Object.entries({
  'Điều hướng chính': 'Main navigation',
  'Tính năng': 'Features',
  'Cách hoạt động': 'How it works',
  'Về WealthFlow': 'About WealthFlow',
  'Đăng nhập': 'Sign in',
  'Đăng ký': 'Sign up',
  'Bắt đầu miễn phí': 'Get started for free',
  'Mở menu': 'Open menu',
  'NỀN TẢNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN': 'PERSONAL FINANCE MANAGEMENT PLATFORM',
  'Nắm rõ dòng tiền.': 'Understand your cash flow.',
  'Xây dựng tương lai': 'Build your financial',
  'tài chính của bạn.': 'future.',
  'Không chỉ ghi lại thu chi. WealthFlow giúp bạn theo dõi toàn bộ tài sản, kiểm soát ngân sách, quản lý nợ và tiến gần hơn đến những mục tiêu quan trọng.': 'More than expense tracking. WealthFlow helps you monitor your assets, manage budgets and debt, and get closer to your financial goals.',
  'Khám phá WealthFlow': 'Explore WealthFlow',
  'Quản lý đa ví': 'Manage multiple wallets',
  'Theo dõi tài sản ròng': 'Track net worth',
  'Dữ liệu do bạn kiểm soát': 'Your data, under your control',
  'Tổng quan tài chính': 'Financial overview',
  'Tài sản ròng của bạn': 'Your net worth',
  'Biến động tài sản 6 tháng gần nhất': 'Net worth over the last 6 months',
  'Thu nhập': 'Income',
  'Chi tiêu': 'Expenses',
  'Tháng này': 'This month',
  '↑ Tháng này': '↑ This month',
  'Đang kiểm soát': 'Under control',
  '↘ Đang kiểm soát': '↘ Under control',
  'Thg 5': 'May',
  'Thg 6': 'June',
  'Thg 7': 'July',
  'Thg 8': 'August',
  'Thg 9': 'September',
  'Thg 10': 'October',
  'Mục tiêu tích lũy': 'Savings goal',
  'Đã đạt 75% mục tiêu': '75% of goal reached',
  'Sức khỏe tài chính': 'Financial health',
  'Đang cải thiện': 'Improving',
  'Thấu hiểu tài chính của bạn từ mọi góc nhìn': 'Understand your finances from every angle',
  'Dòng tiền': 'Cash flow',
  'Tài sản': 'Assets',
  'Mục tiêu': 'Goals',
  'Tăng trưởng': 'Growth',
  'TẤT CẢ TRONG MỘT NỀN TẢNG': 'EVERYTHING IN ONE PLATFORM',
  'Mọi công cụ tài chính': 'Every financial tool',
  'trong tầm tay bạn.': 'at your fingertips.',
  'Từ khoản chi nhỏ mỗi ngày đến bức tranh tài sản dài hạn, mọi thứ đều có vị trí rõ ràng trong WealthFlow.': 'From everyday expenses to your long-term financial picture, everything has its place in WealthFlow.',
  'TỔNG QUAN': 'OVERVIEW',
  'Dashboard tài sản ròng': 'Net worth dashboard',
  'Biết tổng tài sản, nghĩa vụ nợ, dòng tiền và xu hướng tài chính chỉ với một màn hình.': 'See your assets, liabilities, cash flow, and financial trends at a glance.',
  'Ví & giao dịch': 'Wallets & transactions',
  'Quản lý tiền mặt, ngân hàng, ví điện tử, tiết kiệm; ghi nhận thu, chi và chuyển ví nội bộ.': 'Manage cash, bank accounts, e-wallets, and savings; record income, expenses, and transfers.',
  'Ngân sách thông minh': 'Smart budgets',
  'Giám sát từng danh mục, nhắc sớm ở 80% và khi vượt 100% hạn mức.': 'Track each category with alerts at 80% and when you exceed your limit.',
  'Mục tiêu tiết kiệm': 'Savings goals',
  'Đặt mục tiêu, phân bổ khoản tích lũy và theo dõi tiến độ theo thời gian.': 'Set goals, allocate savings, and track your progress over time.',
  'Vay, nợ & tài sản vàng': 'Loans, debt & gold assets',
  'Quản lý dư nợ gốc, lãi/phí, hạn trả và giá trị vàng đang tích lũy.': 'Manage loan balances, interest and fees, due dates, and the value of your gold.',
  'Báo cáo & đối soát': 'Reports & reconciliation',
  'Phân tích Cash Flow, Saving Rate, kiểm tra sổ sách và xuất dữ liệu CSV.': 'Analyze cash flow and savings rate, reconcile records, and export CSV data.',
  'ĐƠN GIẢN, CÓ HỆ THỐNG': 'SIMPLE AND STRUCTURED',
  'Từng bước nhỏ.': 'Small steps.',
  'Thay đổi lớn.': 'Big change.',
  'WealthFlow được thiết kế để việc theo dõi tài chính không còn là một công việc phức tạp.': 'WealthFlow makes tracking your finances simple.',
  'Khám phá giao diện': 'Explore the app',
  'Thiết lập nguồn tiền': 'Set up your accounts',
  'Thêm ví tiền mặt, ngân hàng, tiết kiệm và xác định số dư đầu kỳ.': 'Add cash, bank, and savings accounts, then set opening balances.',
  'Theo dõi & lập kế hoạch': 'Track & plan',
  'Ghi thu chi, thiết lập hạn mức, giao dịch định kỳ và khoản vay.': 'Record income and expenses, set budgets, and manage recurring transactions and loans.',
  'Đánh giá & phát triển': 'Review & grow',
  'Quan sát tài sản ròng, tỷ lệ tiết kiệm và điều chỉnh mục tiêu phù hợp.': 'Track your net worth and savings rate, then adjust your goals.',
  'TỪ THEO DÕI ĐẾN LÀM CHỦ': 'FROM TRACKING TO TAKING CONTROL',
  'Tài chính tốt hơn': 'Better finances',
  'bắt đầu từ sự rõ ràng.': 'start with clarity.',
  'WealthFlow hướng đến sinh viên, người mới đi làm, freelancer và người muốn chủ động kiểm soát thu chi, nợ và tài sản tích lũy. Đây là công cụ ghi chép và phân tích tài chính, không phải dịch vụ ngân hàng hay cấp tín dụng.': 'WealthFlow is designed for students, professionals, freelancers, and anyone who wants to take control of their income, expenses, debt, and savings. It is a financial tracking and analysis tool, not a banking or lending service.',
  'Tạo tài khoản miễn phí': 'Create a free account',
  'BẮT ĐẦU TỪ HÔM NAY': 'GET STARTED TODAY',
  'Mỗi quyết định tốt là': 'Every good decision is',
  'một bước đến tự do tài chính.': 'a step toward financial freedom.',
  'Nhìn rõ hiện tại. Lập kế hoạch thông minh hơn cho ngày mai.': 'See where you are. Plan smarter for tomorrow.',
  'Bắt đầu với WealthFlow': 'Get started with WealthFlow',
  'Bắt đầu với WealthFlow →': 'Get started with WealthFlow →',
  'Phát triển bởi nhóm VOZ': 'Developed by the VOZ team',
  'CHÀO MỪNG ĐẾN WEALTHFLOW': 'WELCOME TO WEALTHFLOW',
  'Đăng nhập tài khoản': 'Sign in to your account',
  'Đăng nhập để tiếp tục hoặc tạo tài khoản mới để bắt đầu.': 'Sign in to continue, or create an account to get started.',
  'Họ và tên': 'Full name',
  'Mã OTP trong email': 'Email verification code',
  'Gửi lại mã OTP': 'Resend code',
  'Mật khẩu': 'Password',
  'Nhập mật khẩu': 'Enter your password',
  'Hiện mật khẩu': 'Show password',
  'Ẩn mật khẩu': 'Hide password',
  'Quên mật khẩu?': 'Forgot password?',
  'Nhập lại mật khẩu': 'Confirm password',
  'Tiền cơ sở': 'Base currency',
  'Ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số; tối đa 72 byte.': 'At least 8 characters, including uppercase, lowercase, and a number; maximum 72 bytes.',
  'Việt Nam đồng': 'Vietnamese dong',
  'Đô la Mỹ': 'US dollar',
  'Đăng nhập →': 'Sign in →',
  '← Quay lại đăng nhập': '← Back to sign in',
  'Tổng quan': 'Overview',
  'Giao dịch': 'Transactions',
  'Giao dịch thu chi': 'Income & expenses',
  'Ví & tài khoản': 'Wallets & accounts',
  'Danh mục thu chi': 'Income & expense categories',
  'Ngân sách': 'Budgets',
  'Giao dịch định kỳ': 'Recurring transactions',
  'Định kỳ': 'Recurring',
  'Khoản vay & nợ': 'Loans & debt',
  'Tài sản vàng': 'Gold assets',
  'Báo cáo tài chính': 'Financial reports',
  'Đối soát & xuất CSV': 'Reconciliation & CSV export',
  'Trung tâm thông báo': 'Notifications',
  'Thông báo': 'Notifications',
  'Cài đặt & bảo mật': 'Settings & security',
  'QUẢN LÝ TÀI CHÍNH': 'FINANCIAL MANAGEMENT',
  'TÀI SẢN & TÍCH LŨY': 'ASSETS & SAVINGS',
  'PHÂN TÍCH & QUẢN LÝ': 'ANALYSIS & MANAGEMENT',
  'QUẢN TRỊ HỆ THỐNG': 'SYSTEM ADMINISTRATION',
  'QUẢN TRỊ': 'ADMINISTRATION',
  'Quay về người dùng': 'Back to user view',
  'Khu vực quản trị': 'Admin area',
  'Không gian cá nhân': 'Personal workspace',
  'Tìm giao dịch nhanh...': 'Quick transaction search...',
  'Tìm giao dịch': 'Search transactions',
  'Xem thông báo': 'View notifications',
  'Mở hồ sơ cá nhân': 'Open profile',
  'Tài khoản': 'Account',
  'Hồ sơ cá nhân': 'Personal profile',
  'Mở thanh điều hướng': 'Open navigation',
  'Thao tác': 'Action',
  'Đóng': 'Close',
  'THÔNG BÁO BẢO TRÌ HỆ THỐNG': 'SYSTEM MAINTENANCE NOTICE',
  'Tôi đã hiểu': 'Got it',
  'Đang đồng bộ dữ liệu từ máy chủ…': 'Syncing data from the server…',
  'Dữ liệu tài sản ròng, tài sản, nợ, mục tiêu và dòng tiền được tổng hợp từ API của tài khoản đang đăng nhập.': 'Net worth, assets, debt, goals, and cash flow are summarized from the signed-in account.',
  'Đang tải Dashboard…': 'Loading dashboard…',
  'Giao diện SPA sẽ thay vùng này bằng kết quả xác thực từ <code>/api/analytics/net-worth</code>; không sử dụng số liệu minh họa.': 'The app will replace this area with verified data from <code>/api/analytics/net-worth</code>; sample figures are not used.',
  'Quản lý thu chi, chuyển ví và lịch sử bút toán': 'Manage income, expenses, transfers, and transaction history',
  'Mọi nguồn tiền của bạn tại một nơi': 'All your accounts in one place',
  'Tổ chức thu nhập và chi tiêu có hệ thống': 'Organize income and expenses',
  'Theo dõi hạn mức và nhận diện rủi ro bội chi': 'Track budgets and spot overspending risks',
  'Không bỏ lỡ khoản thanh toán hoặc thu nhập thường xuyên': 'Never miss a recurring payment or income',
  'Theo dõi gốc, lãi/phí và lịch trả nợ': 'Track principal, interest, fees, and repayment schedules',
  'Đánh giá tài sản tích lũy ngoài ví': 'Track assets held outside your wallets',
  'Theo dõi những khoản bạn đang để dành': 'Track what you are saving for',
  'Hiểu dòng tiền và tỷ lệ tiết kiệm': 'Understand your cash flow and savings rate',
  'Kiểm tra tính nhất quán và lưu trữ báo cáo': 'Check consistency and archive reports',
  'Theo dõi các cảnh báo và nhắc nhở quan trọng': 'Track important alerts and reminders',
  'Hồ sơ, tiền cơ sở, bảo mật phiên đăng nhập': 'Profile, base currency, and session security',
  'Thống kê vĩ mô, không bao gồm dữ liệu tài chính cá nhân': 'Platform-wide statistics; personal financial data is excluded',
  'Trạng thái tài khoản và quyền truy cập': 'Account status and access control',
  'Thiết lập danh mục mẫu cho người dùng mới': 'Configure default categories for new users',
  'Soạn và gửi broadcast announcement': 'Compose and send system announcements',
  'Tham số vận hành và nhật ký quản trị': 'Operational settings and admin logs',
  'Bức tranh tài chính trong một màn hình': 'Your financial picture at a glance',
  'Giao dịch đã ghi nhận không sửa hoặc xóa trực tiếp; thao tác điều chỉnh phải tạo bút toán đảo và giao dịch thay thế.': 'Posted transactions cannot be edited or deleted directly. Adjustments require a reversing entry and a replacement transaction.',
  'Lịch sử giao dịch': 'Transaction history',
  'giao dịch đã ghi nhận không sửa hoặc xóa trực tiếp; thao tác điều chỉnh phải tạo bút toán đảo và giao dịch thay thế.': 'posted transactions cannot be edited or deleted directly. Adjustments require a reversing entry and a replacement transaction.',
  'Hạn kế tiếp': 'Next due date',
  'Theo dõi các khoản thu, chi và chuyển ví': 'Track income, expenses, and transfers',
  'Ghi giao dịch': 'Add transaction',
  'Chuyển ví': 'Transfer',
  'Thu nhập tháng': 'Monthly income',
  'Chi tiêu tháng': 'Monthly expenses',
  'Chuyển nội bộ': 'Internal transfers',
  'Giao dịch nháp': 'Draft transactions',
  'Không bao gồm tiền nhận vay': 'Excludes loan proceeds',
  'Không bao gồm khoản trả nợ gốc': 'Excludes principal repayments',
  'Không tính thu / chi': 'Not counted as income or expenses',
  'Chưa vào báo cáo': 'Not included in reports',
  'Lọc theo loại giao dịch · Tất cả': 'Filter by transaction type · All',
  'Tìm tên, mã, danh mục...': 'Search name, code, category...',
  'Tìm kiếm giao dịch': 'Search transactions',
  'Xuất CSV': 'Export CSV',
  'Nguyên tắc ghi sổ:': 'Accounting principle:',
  'Thông tin tài khoản cá nhân, đơn vị tiền tệ cơ sở và quản lý phiên đăng nhập.': 'Personal account details, base currency, and session management.',
  'Thông tin đăng ký và ưu tiên hiển thị': 'Registration details and display preferences',
  'Chỉnh sửa': 'Edit',
  'Chỉnh sửa danh mục': 'Edit category',
  'Lưu thay đổi': 'Save changes',
  'Ghi chú (không bắt buộc)': 'Note (optional)',
  'Tên danh mục': 'Category name',
  'Loại danh mục': 'Category type',
  'Loại danh mục: Thu nhập': 'Category type: Income',
  'Loại danh mục: Chi tiêu': 'Category type: Expense',
  'Thêm mô tả hoặc lưu ý cho danh mục': 'Add a description or note for this category',
  'Thêm ghi chú': 'Add note',
  'Chỉnh sửa danh mục': 'Edit category',
  'Lưu thay đổi': 'Save changes',
  'Ghi chú (không bắt buộc)': 'Note (optional)',
  'Tên danh mục': 'Category name',
  'Loại danh mục': 'Category type',
  'Loại danh mục: Thu nhập': 'Category type: Income',
  'Loại danh mục: Chi tiêu': 'Category type: Expense',
  'Thêm mô tả hoặc lưu ý cho danh mục': 'Add a description or note for this category',
  'Email': 'Email',
  'Đã đăng ký': 'Registered',
  'Chi tiết': 'Details',
  'Ngôn ngữ': 'Language',
  'Tiếng Việt (UTF-8) · cấu trúc sẵn sàng mở rộng i18n': 'English (UTF-8) · language preference is shared across the website',
  'Tài khoản & bảo mật': 'Account & security',
  'Quản lý mật khẩu và phiên đăng nhập': 'Manage your password and sign-in sessions',
  'Đổi mật khẩu': 'Change password',
  'Xác nhận mật khẩu cũ, thu hồi phiên khác.': 'Confirm your current password and revoke other sessions.',
  'Thiết lập': 'Configure',
  'Phiên đăng nhập': 'Sign-in session',
  'Đăng xuất sẽ kết thúc ngay phiên đăng nhập hiện tại.': 'Signing out immediately ends your current session.',
  'Đang hoạt động': 'Active',
  'Yêu cầu xóa dữ liệu': 'Request data deletion',
  'Quản lý thông tin và quyền riêng tư của tài khoản.': 'Manage your account information and privacy.',
  'Xem quy trình': 'View process',
  'Đăng xuất': 'Sign out',
  'Kết thúc phiên và quay về trang đăng nhập.': 'End your session and return to the sign-in page.',
  'Quản lý tài chính chủ động, không chỉ ghi chép thu chi.': 'Take control of your finances, beyond simple expense tracking.',
  'Về trang giới thiệu →': 'About WealthFlow →',
  'VOZ · Ứng dụng quản lý tài chính': 'VOZ · Personal finance management',
  'Hiểu về WealthFlow': 'About WealthFlow',
  'Tạo tài khoản WealthFlow': 'Create a WealthFlow account',
  'Tìm lại mật khẩu': 'Recover your password',
  'Đặt mật khẩu mới': 'Set a new password',
  'Tạo tài khoản để bắt đầu quản lý tài chính của bạn.': 'Create an account to start managing your finances.',
  'Nhập email đã đăng ký. Nếu tài khoản tồn tại, chúng tôi sẽ gửi mã OTP đặt lại mật khẩu.': 'Enter your registered email. If the account exists, we will send a password reset code.',
  'Chọn mật khẩu mới có ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số.': 'Choose a new password with at least 8 characters, including uppercase, lowercase, and a number.',
  'Nhập lại mật khẩu mới': 'Confirm your new password',
  'Đăng ký tài khoản →': 'Create account →',
  'Gửi mã OTP': 'Send code',
  'Lưu mật khẩu mới': 'Save new password',
  'Mã có hiệu lực 10 phút. Kiểm tra hộp thư': 'Code expires in 10 minutes. Check your inbox',
  ' và cả mục Spam.': ' and spam folder.',
  'Gửi lại sau': 'Resend in',
  'Ít nhất 8 ký tự': 'At least 8 characters',
  'Mật khẩu xác nhận không trùng khớp.': 'Passwords do not match.',
  'Đang tạo tài khoản…': 'Creating account…',
  'Đang gửi yêu cầu…': 'Sending request…',
  'Đang cập nhật mật khẩu…': 'Updating password…',
  'Đang đăng nhập…': 'Signing in…',
  'Không tìm thấy dữ liệu': 'No data found',
  'Thử tải lại': 'Try again',
  'Vui lòng kiểm tra kết nối máy chủ.': 'Please check your server connection.',
  'Chưa tải được khoản vay': 'Could not load loans',
  'Chưa tải được tài sản vàng': 'Could not load gold assets',
  'Người dùng': 'Users',
  'Danh mục mặc định': 'Default categories',
  'Thông báo toàn hệ thống': 'System announcements',
  'Cấu hình & nhật ký': 'Settings & logs',
  'Tổng quan hệ thống': 'System overview',
  'Quản trị hệ thống': 'System administration',
  'Tổng quan người dùng, tham số hệ thống và các tác vụ vận hành.': 'Overview of users, system parameters, and operational tasks.',
  'Quản lý người dùng': 'User management',
  'Cấu hình hệ thống': 'System settings',
  'Thông báo hệ thống': 'System notifications',
  'Nội dung': 'Description',
  'Đang tải…': 'Loading…',
  'Tất cả': 'All',
  'Thêm mới': 'Add new',
  'Lưu': 'Save',
  'Hủy': 'Cancel',
  'Xóa': 'Delete',
  'Cập nhật': 'Update',
  'Trạng thái': 'Status',
  'Ngày cập nhật': 'Updated',
  'Tên': 'Name',
  'Số tiền': 'Amount',
  'Ngày': 'Date',
  'Danh mục': 'Category',
  'Ghi chú': 'Note',
  'Ví': 'Wallet',
  'Loại': 'Type',
  'Hành động': 'Actions',
  'Hoạt động': 'Active',
  'Đã lưu': 'Archived',
  'Đã hủy': 'Cancelled',
  'Chờ xử lý': 'Pending',
  'Thành công': 'Success',
  'Thất bại': 'Failed',
  'Không có dữ liệu': 'No data available',
  'Chưa có giao dịch': 'No transactions yet',
  'Chưa có ví': 'No wallets yet',
  'Chưa có thông báo': 'No notifications',
  'Chưa có dữ liệu': 'No data yet',
  'Vui lòng thử lại sau.': 'Please try again later.',
  'Máy chủ chưa được cấu hình API. Vui lòng chạy script cài đặt XAMPP.': 'The API server is not configured. Please run the XAMPP setup script.',
  'Tìm kiếm...': 'Search...',
  'Tìm kiếm': 'Search',
  'Tạo mới': 'Create',
  'Thêm': 'Add',
  'Đóng cửa sổ': 'Close dialog',
  'Ứng dụng • WealthFlow': 'App • WealthFlow',
  'WealthFlow — ứng dụng quản lý tài chính cá nhân.': 'WealthFlow — personal finance management app.',
  'WealthFlow – theo dõi thu chi, quản lý ví, ngân sách, khoản vay, vàng tích lũy và tài sản ròng trên một nền tảng.': 'WealthFlow — track income, expenses, wallets, budgets, loans, gold, and net worth in one place.',
  'Phát thông báo toàn hệ thống': 'Send system-wide announcement',
  'Gửi thông báo vận hành, bảo trì hoặc chính sách mới đến toàn bộ người dùng.': 'Send operational, maintenance, or policy announcements to all users.',
  'Lịch sử thông báo toàn hệ thống': 'System-wide announcement history',
  'Nhật ký các thông báo phát thanh đã gửi đến trung tâm thông báo của người dùng': 'Log of announcements sent to users’ notification centers',
  'Chỉ Admin được tạo broadcast. Thông báo được phát thanh tức thời tới tất cả người dùng đang hoạt động (ACTIVE) trong hệ thống với độ phức tạp tối ưu O(1).': 'Only admins can create announcements. Messages are delivered immediately to all active users with O(1) complexity.',
  'Khoản vay & nợ vi mô': 'Microloans & debt',
  'Theo dõi đi vay, cho vay, dư gốc, lãi/phí và lịch thanh toán.': 'Track borrowing, lending, principal balances, interest, fees, and payment schedules.',
  'Thêm khoản vay': 'Add loan',
  'Thêm khoản vay đầu tiên': 'Add your first loan',
  'Tổng dư nợ đi vay': 'Total borrowed principal',
  'Tổng phải thu cho vay': 'Total receivable',
  'Khoản đang hoạt động': 'Active loans',
  'Nghĩa vụ gốc còn phải trả': 'Outstanding principal to repay',
  'Tiền gốc còn được nhận lại': 'Principal still receivable',
  'Đi vay và cho vay chưa tất toán': 'Open borrowing and lending positions',
  'Danh sách khoản vay': 'Loan list',
  'Dư gốc, tiến độ và kỳ thanh toán kế tiếp': 'Principal, progress, and next payment date',
  'Đang tải khoản vay…': 'Loading loans…',
  'Đang đồng bộ dư gốc và lịch thanh toán từ máy chủ.': 'Syncing principal balances and payment schedules from the server.',
  'Bạn chưa có khoản vay hoặc khoản cho vay': 'You have no loans yet',
  'Thêm khoản mới để theo dõi dư gốc, lãi/phí và lịch thanh toán.': 'Add a loan to track principal, interest, fees, and payments.',
  'Dựa trên các giao dịch đã ghi sổ': 'Based on posted transactions',
  'Các danh mục có chi tiêu trong tháng': 'Categories with spending this month',
  'Đơn vị báo cáo': 'Reporting currency',
  'Đơn vị dùng trong các biểu đồ': 'Currency used in the charts',
  'Phân bổ theo giá trị hiện tại': 'Allocation by current value',
  '↻ Làm mới dữ liệu': '↻ Refresh data',
  'Chọn điều bạn muốn đạt được và theo dõi từng khoản đã để dành.': 'Choose a goal and track every contribution.',
  'Đang thực hiện': 'In progress',
  '0 mục tiêu': '0 goals',
  'Những kế hoạch đang để dành': 'Goals you are saving for',
  'Muốn đạt': 'Target amount',
  'Tổng số tiền của các mục tiêu': 'Total amount across goals',
  'Đã để dành': 'Saved so far',
  'Tiền đang được dành cho các mục tiêu': 'Money allocated to your goals',
  'Tiến độ chung': 'Overall progress',
  'Mức đã đạt so với kế hoạch': 'Progress toward your plan',
  'Ngân sách chi tiêu': 'Spending budgets',
  'Thiết lập hạn mức theo danh mục tháng, theo dõi chi tiêu ròng và cảnh báo 80% / 100%.': 'Set monthly category limits, track net spending, and get alerts at 80% and 100%.',
  'Tổng ngân sách': 'Total budget',
  '5 danh mục tháng 10': '5 categories in October',
  'Chi tiêu thuộc ngân sách': 'Budgeted spending',
  'Theo danh mục': 'By category',
  'Cảnh báo vàng': 'Warning alerts',
  '1 danh mục': '1 category',
  'Đạt từ 80% hạn mức': 'At 80% of the limit',
  'Vượt ngân sách': 'Over budget',
  'Đạt từ 100% hạn mức': 'At 100% of the limit',
  'Hạn mức theo danh mục': 'Limits by category',
  'Kỳ ngân sách: 10/2026': 'Budget period: 10/2026',
  'Tháng 10/2026': 'October 2026',
  'Ngưỡng cảnh báo': 'Alert thresholds',
  'Từ 80%': 'At 80%',
  'Ngân sách chuyển trạng thái cần chú ý.': 'The budget is marked for attention.',
  'Từ 100%': 'At 100%',
  'Cảnh báo vượt ngưỡng; ứng dụng không chặn giao dịch thật.': 'An over-limit alert is shown; the app does not block real transactions.',
  'Giữ ngân sách chính xác': 'Keep budgets accurate',
  'Ngân sách tính chi tiêu ròng, không tính chuyển ví nội bộ hoặc trả nợ gốc. Mỗi ngưỡng cảnh báo chỉ nên gửi thông báo một lần trong một kỳ ngân sách.': 'Budgets use net spending and exclude internal transfers and principal repayments. Each alert threshold should notify only once per budget period.',
  'Quản lý tiền mặt, tài khoản ngân hàng, ví điện tử và các sổ tiết kiệm.': 'Manage cash, bank accounts, e-wallets, and savings accounts.',
  'Lọc theo loại ví': 'Filter by wallet type',
  'Hiện ví lưu trữ': 'Show archived wallets',
  'Tổng số dư các ví': 'Total wallet balance',
  'Đối chiếu số dư ví': 'Reconcile wallet balances',
  'Chưa bao gồm tài sản vàng': 'Does not include gold assets',
  'Tổng giá trị quy đổi sang VND': 'Total value converted to VND',
  'Đã quy đổi các ví ngoại tệ theo tỷ giá mới nhất.': 'Foreign currency wallets use the latest exchange rates.',
  'Ví đang sử dụng': 'Active wallets',
  'Ví đã lưu trữ': 'Archived wallets',
  'Quy đổi tiền tệ': 'Currency conversion',
  'Tỷ giá tham khảo': 'Reference exchange rates',
  'VND (tiền cơ sở)': 'VND (base currency)',
  'USD → VND': 'USD → VND',
  'EUR → VND': 'EUR → VND',
  'Chưa có tỷ giá': 'No exchange rate available',
  'Cập nhật tỷ giá': 'Update exchange rates',
  'Ghi nhận tỷ giá': 'Rate source',
  'Chưa đồng bộ Vietcombank': 'Not synced with Vietcombank',
  'Tài sản vàng & tích lũy': 'Gold assets & savings',
  'Theo dõi vàng tích lũy theo chỉ và định giá thủ công từ dữ liệu bạn xác nhận.': 'Track gold holdings by tael and manually value them using data you confirm.',
  'Giá trị tài sản vàng': 'Gold asset value',
  'Số loại vàng': 'Gold types',
  'Các loại đang sở hữu': 'Types owned',
  'Tổng khối lượng': 'Total quantity',
  'Đơn vị chỉ vàng': 'Unit: tael',
  'Ngày định giá': 'Valuation date',
  'Theo lần định giá gần nhất': 'Based on the latest valuation',
  'Mua vàng từ ví sẽ chuyển giá trị từ tiền sang vàng bằng bút toán kép, không tạo thu nhập và không làm tài sản ròng tăng giả. Tài sản có sẵn được ghi nhận như số dư mở đầu.': 'Buying gold from a wallet transfers value from cash to gold using double-entry bookkeeping. It does not create income or artificially increase net worth. Existing assets are recorded as opening balances.',
  'Tổng người dùng': 'Total users',
  'Thông tin hệ thống': 'System information',
  'Tài khoản Active': 'Active accounts',
  'Không bao gồm tài khoản khóa': 'Excludes locked accounts',
  'Đăng ký mới tháng': 'New registrations this month',
  'Tài khoản Locked': 'Locked accounts',
  'Người dùng chủ động bật': 'Users who opted in',
  'Cần xử lý theo quy trình': 'Requires follow-up',
  'Hoạt động tài khoản': 'Account activity',
  'Thống kê người dùng theo tháng': 'Monthly user statistics',
  'Phân bố tiền cơ sở': 'Base currency distribution',
  'Thống kê tổng hợp, không có số dư': 'Aggregated statistics; no balances',
  'Bảo mật dữ liệu:': 'Data security:',
  'trang Admin không được cung cấp số dư, tài sản, khoản nợ hoặc lịch sử giao dịch tài chính của bất kỳ người dùng nào. Dữ liệu quản trị phải được tổng hợp và ẩn danh theo quyền được cấp.': 'The admin area does not expose user balances, assets, debts, or transaction history. Admin data must be aggregated and anonymized according to access permissions.',
  ' trang Admin không được cung cấp số dư, tài sản, khoản nợ hoặc lịch sử giao dịch tài chính của bất kỳ người dùng nào. Dữ liệu quản trị phải được tổng hợp và ẩn danh theo quyền được cấp.': ' the admin area does not expose user balances, assets, debts, or transaction history. Admin data must be aggregated and anonymized according to access permissions.',
  'Theo dõi danh sách tài khoản, trạng thái hoạt động và thực hiện khóa/mở khóa có kiểm toán.': 'Review accounts and their status, and lock or unlock accounts with an audit trail.',
  'Theo dõi mọi khoản tiền vào/ra, chuyển ví và kiểm soát tính toàn vẹn của bút toán.': 'Track income, expenses, transfers, and transaction integrity.',
  'Danh sách người dùng': 'User list',
  'Theo dõi trạng thái, tìm kiếm và khóa/mở khóa khi có căn cứ': 'Review status, search, and lock or unlock accounts when justified',
  'Ngày tạo': 'Created date',
  'Mọi quyết định khóa tài khoản cần có lý do và được lưu vào nhật ký quản trị. Không thể khóa tài khoản quản trị duy nhất.': 'Every account lock must have a reason and be recorded in the admin log. The only administrator account cannot be locked.',
  'Tham số hệ thống': 'System parameters',
  'Cấu hình ngưỡng ngân sách, tỷ lệ cảnh báo, thời hạn token và nhật ký kiểm toán quản trị.': 'Configure budget thresholds, alert ratios, token lifetime, and admin audit logs.',
  'Tham số vận hành': 'Operational parameters',
  'Quy chuẩn vận hành áp dụng toàn hệ thống': 'System-wide operating standards',
  'Ngưỡng cảnh báo chi tiêu ngân sách (Yellow Alert)': 'Budget spending alert threshold (yellow alert)',
  'Thời gian sống Token xác thực (JWT TTL)': 'Authentication token lifetime (JWT TTL)',
  '60 phút': '60 minutes',
  'Giới hạn số dòng đối soát xuất CSV': 'CSV reconciliation export row limit',
  '5.000 dòng': '5,000 rows',
  'Nhắc hạn khoản vay trước hạn': 'Loan due date reminder',
  'Trước hạn 3 ngày': '3 days before due date',
  'Admin Audit Log': 'Admin audit log',
  'Lịch sử thao tác quản trị được kiểm toán tự động': 'Admin actions are automatically audited',
  'Thời gian': 'Time',
  'Quản trị viên': 'Administrator',
  'Chi tiết thao tác': 'Action details',
  'Đang tải nhật ký kiểm toán…': 'Loading audit log…',
  'Tổng phải thu': 'Total receivable',
  'Ví khớp sổ': 'Reconciled wallets',
  'Ví đang theo dõi': 'Tracked wallets',
  'Thử lại': 'Retry',
  'Báo cáo chỉ hiển thị số liệu đã tải thành công từ API của tài khoản hiện tại.': 'Reports show only data successfully loaded from the current account API.',
  'Bao gồm ví USD': 'Include USD wallets',
  'Bộ danh mục chuẩn khởi tạo mặc định cho tài khoản người dùng mới.': 'Standard categories used as defaults for new accounts.',
  'Bộ danh mục mặc định': 'Default category set',
  'Các khoản định kỳ': 'Recurring items',
  'Cảnh báo ngân sách, nhắc nợ và broadcast từ hệ thống': 'System budget alerts, debt reminders, and announcements',
  'Cấu hình thông báo': 'Notification settings',
  'Cây danh mục phân loại chi tiêu, nguồn thu và phục vụ kiểm soát hạn mức ngân sách.': 'Categories organize expenses and income and help manage budget limits.',
  'Chế độ': 'Mode',
  'Chế độ mặc định': 'Default mode',
  'Chênh lệch': 'Difference',
  'Chỉnh sửa / lưu trữ': 'Edit / archive',
  'Chưa chạy phép đối soát:': 'Reconciliation has not been run:',
  'Có bảo vệ công thức': 'Formula-injection protection enabled',
  'CSV UTF-8 BOM': 'CSV with UTF-8 BOM',
  'Đã loại trừ bản nháp': 'Drafts excluded',
  'Đang theo dõi': 'Tracking',
  'Danh mục cá nhân': 'Personal categories',
  'Danh mục cá nhân có thể đổi tên, lưu trữ; danh mục mặc định do Admin quản lý riêng, không xem được dữ liệu tài chính người dùng.': 'Personal categories can be renamed or archived. Admin-managed defaults are separate and do not expose users’ financial data.',
  'Danh mục hệ thống': 'System categories',
  'Danh mục mặc định chỉ dùng làm mẫu khi tạo tài khoản mới. Không được tự ý làm thay đổi các danh mục hoặc giao dịch cá nhân đã tồn tại.': 'Default categories are templates for new accounts only. Existing personal categories and transactions are not changed.',
  'Dùng để phân loại, lọc giao dịch và lập ngân sách': 'Used to categorize and filter transactions and create budgets',
  'Giao dịch đã ghi nhận': 'Posted transactions',
  'Giao diện SPA sẽ thay vùng này bằng kết quả xác thực từ': 'The app will replace this area with verified data from',
  'Hỗ trợ nhắc tạo Draft / tự động ghi sổ (sẽ xử lý bằng PHP scheduler)': 'Supports draft reminders and automatic posting (handled by the PHP scheduler)',
  'Hộp thư thông báo': 'Notification inbox',
  'khi triển khai, PHP phải tính lại số dư từ ledger entries của từng ví, so sánh với số dư cache và phát hiện sai khác. Chỉ sử dụng kết quả đối soát do máy chủ xác nhận.': 'At runtime, PHP recalculates each wallet balance from ledger entries, compares it with the cached balance, and flags discrepancies. Only server-confirmed reconciliation results are used.',
  'Kiểm tra đối soát': 'Run reconciliation',
  'Lịch đang hoạt động': 'Active schedules',
  'Lịch định kỳ chạy theo ngày đã thiết lập; khi tháng không đủ ngày, hệ thống áp dụng ngày cuối tháng. Mỗi kỳ chỉ được ghi sổ một lần.': 'Recurring schedules run on the configured day. If a month is shorter, the last day is used. Each period is posted only once.',
  'Đối soát & xuất dữ liệu': 'Reconciliation & data export',
  'Dữ liệu định giá do bạn xác nhận': 'Valuation data confirmed by you',
  'Không dùng số liệu mẫu để thay thế khi máy chủ hoặc MySQL chưa sẵn sàng.': 'Sample data is never used as a fallback when the server or MySQL is unavailable.',
  'Khoản định kỳ': 'Recurring item',
  'Sắp đến hạn': 'Due soon',
  'Sẽ được khởi tạo cho người dùng mới': 'Created for new users',
  'Số dư ghi nhận': 'Recorded balance',
  'Số dư từ sổ cái': 'Ledger balance',
  'So khớp số dư ví với tổng đại số Nợ/Có sổ cái kép và xuất báo cáo CSV chống Formula Injection.': 'Reconcile wallet balances with double-entry ledger totals and export CSV reports protected against formula injection.',
  'So sánh số dư hiện tại với lịch sử bút toán': 'Compare current balances with ledger history',
  'Soạn thông báo': 'Compose announcement',
  'Tải Xuất CSV': 'Download CSV export',
  'Tạo bản nháp': 'Create draft',
  'Tạo broadcast': 'Create announcement',
  'Tạo danh mục': 'Create category',
  'Tạo lịch định kỳ': 'Create recurring schedule',
  'Tạo ngân sách': 'Create budget',
  'Tạo ví mới': 'Create wallet',
  'Điều chỉnh tham số': 'Adjust parameters',
  'Thêm danh mục mẫu': 'Add default category',
  'Thêm hoặc lấy tiền khỏi mục tiêu trong phần Giao dịch. Tiền vẫn thuộc số dư ví nên tổng tài sản không thay đổi.': 'Add or withdraw money from a goal in Transactions. The money remains in your wallet balance, so total assets do not change.',
  'Thêm mục tiêu': 'Add goal',
  'Thêm tài sản vàng': 'Add gold asset',
  'EUR · Euro': 'EUR · Euro',
  'USD · Đô la Mỹ': 'USD · US dollar',
  'VND · Việt Nam đồng': 'VND · Vietnamese dong',
  'Thiết lập đăng ký dịch vụ, tiền nhà, bảo hiểm lặp lại hàng tháng kèm quy tắc neo ngày.': 'Set up monthly subscriptions, rent, and insurance with consistent date rules.',
  'Thông báo & cảnh báo': 'Notifications & alerts',
  'Ngưỡng ngân sách và thời hạn khoản vay được dùng để gửi nhắc nhở phù hợp.': 'Budget thresholds and loan due dates are used to send relevant reminders.',
  'Tổng dư nợ đi vay': 'Total borrowed balance',
  'Trong tháng 10': 'In October',
  'Khác': 'Other',
  'Trạng thái đối chiếu': 'Reconciliation status',
  'Đang tải báo cáo…': 'Loading report…',
  'Tiêu đề thông báo': 'Announcement title',
  'Xem nhật ký Admin': 'View admin log',
  'Trung tâm thông báo tài chính, cảnh báo hạn mức ngân sách và nhắc nợ đến hạn.': 'Financial notifications, budget limit alerts, and upcoming debt reminders.',
  'Tự động ghi sổ': 'Automatic posting',
  'Xuất dữ liệu': 'Export data',
  '🔒 Mọi thao tác thêm/sửa/xóa danh mục mẫu, đổi tham số vận hành hoặc xử lý tài khoản đều được lưu vết kiểm toán bất biến.': '🔒 All changes to default categories, operating parameters, and accounts are recorded in an immutable audit log.',
  '💡 Thay đổi ngưỡng cảnh báo ngân sách sẽ cập nhật tức thời quy tắc phân loại rủi ro (Xanh/Vàng/Đỏ) cho toàn bộ người dùng khi ghi nhận giao dịch.': '💡 Changing budget alert thresholds immediately updates risk classification (green/yellow/red) for all users when transactions are recorded.',
  'Ứng dụng không chặn giao dịch thật.': 'The app does not block real transactions.',
  'Chuyển tiền': 'Transfer',
  'Vay và cho vay': 'Borrowing and lending',
  'Số dư': 'Balance',
  'Chưa đọc': 'Unread',
  'Đã đọc': 'Read',
  'Mở khóa': 'Unlock',
  'Khóa': 'Lock',
  'Xác nhận': 'Confirm',
  'Thêm giao dịch': 'Add transaction',
  'Sửa': 'Edit',
  'Lưu thay đổi': 'Save changes',
  'Không có khoản nào': 'No items',
  'Chưa có mục tiêu': 'No goals yet',
  'Chưa có ngân sách': 'No budgets yet',
  'Chưa có lịch định kỳ': 'No recurring schedules',
  'Chưa có khoản vay': 'No loans yet'
  ,'Dashboard chi tiêu': 'Expense dashboard'
  ,'Tổng chi tiêu tháng': 'Monthly expenses'
  ,'Số danh mục phát sinh': 'Active expense categories'
  ,'Chỉ giao dịch chi phí đã ghi sổ': 'Posted expense transactions only'
  ,'Tiền cơ sở': 'Base currency'
  ,'Mọi số liệu đã được backend quy đổi': 'All figures are converted by the backend'
  ,'Nguồn dữ liệu': 'Data source'
  ,'Không dùng dữ liệu mẫu hoặc localStorage': 'No sample data or localStorage'
  ,'Chi tiêu theo danh mục': 'Expenses by category'
  ,'Thu nhập và chi tiêu': 'Income and expenses'
  ,'Thu nhập': 'Income'
  ,'Biểu đồ cột · 6 tháng gần nhất': 'Bar chart · last 6 months'
  ,'Cơ cấu chi tiêu theo danh mục': 'Expense breakdown by category'
}));

const textOriginals = new WeakMap();
const attributeOriginals = new WeakMap();
let language = 'vi';
let titleOriginal = document.title;
let titleRendered = document.title;

function normalize(value) {
  return value.replace(/\s+/g, ' ').trim();
}

function translate(value) {
  const normalized = normalize(value);
  if (translations.has(normalized)) return translations.get(normalized);
  const resendMatch = normalized.match(/^Gửi lại sau (\d+)s$/);
  if (resendMatch) return `Resend in ${resendMatch[1]}s`;
  const monthMatch = normalized.match(/^Tháng (\d{4}-\d{2})$/);
  if (monthMatch) return `Month ${monthMatch[1]}`;
  const pieMonthMatch = normalized.match(/^Biểu đồ tròn · tháng (\d{4}-\d{2})$/);
  if (pieMonthMatch) return `Pie chart · ${pieMonthMatch[1]}`;
  return value;
}

function updateToggle(button) {
  const targetLanguage = language === 'vi' ? 'en' : 'vi';
  button.textContent = targetLanguage.toUpperCase();
  button.setAttribute('aria-label', targetLanguage === 'en' ? 'Switch language to English' : 'Chuyển ngôn ngữ sang tiếng Việt');
  button.title = targetLanguage === 'en' ? 'Switch to English' : 'Chuyển sang tiếng Việt';
}

function processText(node) {
  const previous = textOriginals.get(node);
  const original = previous && node.nodeValue === previous.rendered ? previous.original : node.nodeValue;
  const translated = language === 'en' ? translate(original) : original;
  textOriginals.set(node, { original, rendered: translated });
  if (node.nodeValue !== translated) node.nodeValue = translated;
}

function processAttribute(element, name) {
  const values = attributeOriginals.get(element) || new Map();
  const previous = values.get(name);
  const current = element.getAttribute(name) || '';
  const original = previous && current === previous.rendered ? previous.original : current;
  const translated = language === 'en' ? translate(original) : original;
  values.set(name, { original, rendered: translated });
  attributeOriginals.set(element, values);
  if (current !== translated) element.setAttribute(name, translated);
}

function processNode(node) {
  if (node.nodeType === Node.TEXT_NODE) {
    if (!node.parentElement?.closest('[data-language-toggle]')) processText(node);
    return;
  }
  if (node.nodeType !== Node.ELEMENT_NODE || node.matches('[data-language-toggle]')) return;
  for (const name of ['placeholder', 'title', 'aria-label', 'aria-description']) {
    if (node.hasAttribute(name)) processAttribute(node, name);
  }
  node.childNodes.forEach(processNode);
}

function updateLanguage(languageValue) {
  language = languageValue === 'en' ? 'en' : 'vi';
  document.documentElement.lang = language === 'en' ? 'en' : 'vi';
  if (document.title !== titleRendered) titleOriginal = document.title;
  titleRendered = language === 'en' ? translate(titleOriginal) : titleOriginal;
  document.title = titleRendered;
  document.querySelectorAll('[data-language-toggle]').forEach(updateToggle);
  updateLanguageSettings();
  processNode(document.body);
  document.dispatchEvent(new CustomEvent('wealthflow:languagechange', { detail: { language } }));
}

function updateLanguageSettings() {
  const languageLabel = document.querySelector('#app-language-label');
  const languageCode = document.querySelector('#app-language-code');
  if (languageLabel) languageLabel.textContent = language === 'en' ? 'English (UTF-8) · shared across the website' : 'Tiếng Việt (UTF-8) · đồng bộ trên toàn website';
  if (languageCode) languageCode.textContent = language === 'en' ? 'en-US' : 'vi-VN';
}

function readSavedLanguage() {
  try {
    return localStorage.getItem(LANGUAGE_KEY) === 'en' ? 'en' : 'vi';
  } catch (error) {
    console.warn('Could not read the saved language preference.', error);
    return 'vi';
  }
}

function saveLanguage(value) {
  try {
    localStorage.setItem(LANGUAGE_KEY, value);
  } catch (error) {
    console.warn('Could not save the language preference.', error);
  }
}

document.querySelectorAll('[data-language-toggle]').forEach(button => {
  button.addEventListener('click', () => {
    const nextLanguage = language === 'vi' ? 'en' : 'vi';
    saveLanguage(nextLanguage);
    updateLanguage(nextLanguage);
  });
});

updateLanguage(readSavedLanguage());
const observer = new MutationObserver(records => {
  for (const record of records) {
    if (record.type === 'characterData') processText(record.target);
    else if (record.type === 'attributes') processAttribute(record.target, record.attributeName);
    else record.addedNodes.forEach(processNode);
  }
  updateLanguageSettings();
});
observer.observe(document.body, {
  subtree: true,
  childList: true,
  characterData: true,
  attributes: true,
  attributeFilter: ['placeholder', 'title', 'aria-label', 'aria-description']
});
window.addEventListener('storage', event => {
  if (event.key === LANGUAGE_KEY) updateLanguage(event.newValue);
});
