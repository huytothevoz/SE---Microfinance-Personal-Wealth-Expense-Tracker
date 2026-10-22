/** Fields for the planned business workflows. These forms never write to the database until an API handler is available. */
export const FIELDS = {
  'wallet-archive': {
    title: 'Quản lý & lưu trữ ví',
    desc: 'Chỉ lưu trữ ví số dư bằng 0',
    fields: [],
    note: 'Chỉ lưu trữ ví không còn số dư và không liên kết lịch định kỳ đang hoạt động. Lịch sử của ví được bảo toàn.'

  },
  'budget-manage': {
    title: 'Chỉnh sửa / lưu trữ ngân sách',
    desc: 'Quản lý hạn mức',
    fields: [
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Mua sắm',
          'Giải trí',
          'Di chuyển',
          'Hóa đơn'

        ]
      ],
      ['limit', 'Hạn mức mới (VND)', 'number'],
      ['status', 'Trạng thái', 'select', ['Hoạt động', 'Lưu trữ']]

    ]

  },
  'recurring-delete': {
    title: 'Xóa lịch định kỳ',
    desc: 'Xóa lịch và giữ dấu vết kỳ cũ',
    fields: [],
    note: 'Xóa chỉ ngừng phát sinh kỳ tiếp theo; các giao dịch đã ghi nhận vẫn phải giữ nguyên để phục vụ kiểm toán.'

  },
  'goal-complete': {
    title: 'Hoàn thành mục tiêu',
    desc: 'Đánh dấu đã hoàn thành',
    fields: [],
    note: 'Hoàn thành mục tiêu không làm tăng tài sản của người dùng.'

  },
  'goal-cancel': {
    title: 'Hủy mục tiêu tài chính',
    desc: 'Hủy nhưng giữ lịch sử phân bổ',
    fields: [],
    note: 'Các khoản tiền phân bổ không tự tạo tài sản mới và lịch sử phải được giữ để tra soát.'

  },
  'gold-liquidate': {
    title: 'Thanh lý / xóa tài sản vàng',
    desc: 'Ngừng theo dõi vàng tích lũy',
    fields: [],
    note: 'Việc thanh lý vàng cần ghi nhận cả biến động tài sản và dòng tiền.'

  },
  'rate-refresh': {
    title: 'Cập nhật tỷ giá',
    desc: 'Dự kiến đồng bộ qua',
    fields: [],
    note: 'Tỷ giá chỉ được dùng cho quy đổi khi đã xác định được nguồn và thời điểm cập nhật đáng tin cậy.'

  },
  'tx-add': {
    title: 'Ghi nhận giao dịch',
    desc: 'Tạo giao dịch Thu / Chi / Bản nháp',
    fields: [
      ['type', 'Loại giao dịch', 'select', ['Chi tiêu', 'Thu nhập']],
      ['amount', 'Số tiền (VND)', 'number'],
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Di chuyển',
          'Mua sắm',
          'Giải trí',
          'Hóa đơn',
          'Lương thưởng',
          'Thu nhập khác'

        ]

      ],
      ['wallet', 'Ví nguồn / ví nhận', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo', 'Tiết kiệm dài hạn']],
      ['date', 'Ngày giao dịch', 'date'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  transfer: {
    title: 'Chuyển giữa các ví',
    desc: 'Chuyển nội bộ không tính thu / chi',
    fields: [
      ['from', 'Từ ví', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo', 'Quỹ ngoại tệ']],
      ['to', 'Đến ví', 'select', ['MoMo', 'Vietcombank', 'Tiền mặt', 'Quỹ ngoại tệ']],
      ['amount', 'Số tiền nguồn', 'number'],
      ['receive', 'Số tiền đích nếu khác tiền tệ', 'number'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  'wallet-add': {
    title: 'Tạo ví / tài khoản',
    desc: 'Mở ví, ghi nhận số dư đầu kỳ',
    fields: [
      ['name', 'Tên ví', 'text'],
      [
        'type',
        'Loại ví',
        'select',
        [
          'Tiền mặt',
          'Ngân hàng',
          'Ví điện tử',
          'Tiết kiệm',
          'Thẻ tín dụng (ví nợ)'

        ]
      ],
      ['currency', 'Tiền tệ', 'select', ['VND', 'USD', 'EUR']],
      ['balance', 'Số dư đầu kỳ', 'number']

    ]

  },
  'category-add': {
    title: 'Tạo danh mục cá nhân',
    desc: 'Danh mục thu / chi',
    fields: [['name', 'Tên danh mục', 'text'], ['type', 'Loại danh mục', 'select', ['Chi tiêu', 'Thu nhập']]]

  },
  'category-edit': {
    title: 'Chỉnh sửa danh mục',
    desc: 'Quản lý danh mục cá nhân',
    fields: [['name', 'Tên danh mục mới', 'text'], ['state', 'Trạng thái', 'select', ['Đang hoạt động', 'Lưu trữ']]]

  },
  'budget-add': {
    title: 'Thiết lập ngân sách',
    desc: 'Hạn mức theo danh mục và tháng',
    fields: [
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Mua sắm',
          'Giải trí',
          'Di chuyển',
          'Hóa đơn'

        ]
      ],
      ['month', 'Tháng áp dụng', 'month'],
      ['amount', 'Hạn mức (VND)', 'number']

    ]

  },
  'recurring-add': {
    title: 'Lập lịch giao dịch định kỳ',
    desc: 'Nhắc bản nháp hoặc tự động ghi sổ',
    fields: [
      ['name', 'Tên khoản định kỳ', 'text'],
      ['amount', 'Số tiền', 'number'],
      ['category', 'Danh mục', 'select', ['Hóa đơn', 'Nhà ở', 'Thu nhập khác']],
      ['wallet', 'Ví liên quan', 'select', ['Vietcombank', 'MoMo', 'Tiền mặt']],
      ['day', 'Ngày trong tháng (1–31)', 'number'],
      ['mode', 'Chế độ', 'select', ['Nhắc tạo bản nháp', 'Tự động ghi sổ']]

    ]

  },
  'loan-add': {
    title: 'Thêm khoản vay / nợ',
    desc: 'Vay và cho vay vi mô',
    fields: [
      ['name', 'Tên khoản vay', 'text'],
      ['kind', 'Loại', 'select', ['Đi vay', 'Cho vay']],
      ['partner', 'Đối tác', 'text'],
      ['principal', 'Số tiền gốc', 'number'],
      ['interest', 'Lãi suất / phí', 'text'],
      ['date', 'Ngày đáo hạn', 'date']

    ]

  },
  'loan-pay': {
    title: 'Ghi nhận kỳ trả nợ',
    desc: 'Bắt buộc tách nợ gốc và lãi / phí',
    fields: [
      ['loan', 'Khoản vay', 'select', ['Vay mua laptop', 'Khoản vay học tập']],
      ['principal', 'Số tiền gốc trả', 'number'],
      ['interest', 'Tiền lãi / phí', 'number'],
      ['wallet', 'Ví thanh toán', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo']],
      ['date', 'Ngày thanh toán', 'date']

    ]

  },
  'gold-add': {
    title: 'Ghi nhận tài sản vàng',
    desc: 'Chỉ gồm vàng nhẫn 9999 và SJC',
    fields: [
      ['type', 'Loại vàng', 'select', ['Nhẫn tròn trơn 9999', 'Vàng miếng SJC']],
      ['weight', 'Khối lượng (chỉ)', 'number'],
      ['unit', 'Giá trị mỗi chỉ (VND)', 'number'],
      ['date', 'Ngày định giá', 'date']

    ]

  },
  'gold-revalue': {
    title: 'Cập nhật định giá vàng',
    desc: 'Lưu ngày định giá',
    fields: [
      ['type', 'Tài sản', 'select', ['Nhẫn tròn trơn 9999', 'Vàng miếng SJC']],
      ['unit', 'Thị giá hiện tại / chỉ', 'number'],
      ['date', 'Ngày định giá', 'date']

    ]

  },
  'goal-add': {
    title: 'Tạo mục tiêu tài chính',
    desc: 'Tiến độ và kế hoạch tích lũy',
    fields: [
      ['name', 'Tên mục tiêu', 'text'],
      ['target', 'Giá trị mục tiêu (VND)', 'number'],
      ['deadline', 'Hạn hoàn thành', 'date']

    ]

  },
  'goal-allocate': {
    title: 'Phân bổ vào mục tiêu',
    desc: 'Không làm tăng tổng tài sản',
    fields: [
      ['name', 'Mục tiêu', 'select', ['Quỹ khẩn cấp', 'Laptop mới', 'Du lịch cuối năm']],
      ['amount', 'Số tiền phân bổ', 'number'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  'broadcast-add': {
    title: 'Soạn thông báo hệ thống',
    desc: 'Gửi thông báo chung tới người dùng',
    fields: [
      ['title', 'Tiêu đề', 'text'],
      ['level', 'Mức ưu tiên', 'select', ['Thông tin', 'Cảnh báo', 'Khẩn cấp']],
      ['body', 'Nội dung', 'textarea']

    ]

  },
  'admin-category-add': {
    title: 'Danh mục hệ thống mặc định',
    desc: 'Dành cho người dùng mới',
    fields: [['name', 'Tên danh mục', 'text'], ['type', 'Loại', 'select', ['Chi tiêu', 'Thu nhập']]]

  },
  'admin-category-edit': {
    title: 'Điều chỉnh danh mục mặc định',
    desc: 'Không ảnh hưởng giao dịch cũ',
    fields: [['name', 'Tên danh mục', 'text']]

  },
  'admin-config': {
    title: 'Tham số hệ thống',
    desc: 'Chỉ dành cho quản trị viên',
    fields: [['budget', 'Ngưỡng cảnh báo (%)', 'number'], ['upload', 'Giới hạn dung lượng xuất (MB)', 'number']]

  },
  'profile-edit': { title: 'Cập nhật hồ sơ', desc: 'Hồ sơ người dùng', fields: [['name', 'Tên hiển thị', 'text']] },
  'password': {
    title: 'Đổi mật khẩu',
    desc: 'Bảo mật tài khoản',
    fields: [],
    note: 'Đổi mật khẩu sẽ đăng xuất các phiên cũ để bảo vệ tài khoản.'

  },
  'currency-setting': {
    title: 'Tiền cơ sở của tài khoản',
    desc: 'Cấu hình định dạng tiền',
    fields: [['currency', 'Tiền cơ sở', 'select', ['VND', 'USD', 'EUR']]]

  },
  'notification-settings': {
    title: 'Tùy chọn nhắc nhở',
    desc: 'Tùy chỉnh thông tin',
    fields: [
      ['budget', 'Cảnh báo ngân sách', 'select', ['Bật', 'Tắt']],
      ['loan', 'Nhắc khoản vay', 'select', ['Bật', 'Tắt']]
    ]

  },
  'account-delete': {
    title: 'Quyền quản lý dữ liệu cá nhân',
    desc: 'Quy trình yêu cầu xóa',
    fields: [],
    note: 'Yêu cầu xóa tài khoản cần xác nhận danh tính và tuân thủ chính sách lưu trữ dữ liệu.'

  },
  'admin-user-lock': {
    title: 'Khóa tài khoản người dùng',
    desc: 'Lý do khóa tài khoản là bắt buộc',
    fields: [['reason', 'Lý do khóa', 'textarea']]

  },
  'admin-user-unlock': {
    title: 'Mở khóa tài khoản',
    desc: 'Mở khóa và ghi nhận lịch sử thao tác',
    fields: [['reason', 'Ghi chú xử lý', 'textarea']]

  },
  'recurring-toggle': {
    title: 'Thay đổi trạng thái định kỳ',
    desc: 'Tạm dừng / kích hoạt lịch',
    fields: [],
    note: 'Lịch tạm dừng không tạo giao dịch mới cho đến khi được kích hoạt lại.'

  },
  'loan-view': {
    title: 'Lịch thanh toán',
    desc: 'Kỳ hạn gốc, lãi / phí',
    fields: [],
    note: 'Lịch thanh toán sẽ hiển thị kỳ hạn và phần gốc, lãi/phí phải trả.'

  },
  'admin-logs': {
    title: 'Admin Audit Log',
    desc: 'Tùy chỉnh thông tin',
    fields: [],
    note: 'Nhật ký quản trị phải bảo toàn dấu vết thao tác và không chứa dữ liệu tài chính cá nhân.'

  },


};
