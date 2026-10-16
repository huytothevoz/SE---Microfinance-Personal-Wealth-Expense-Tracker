import { safe } from './ui-core.js';
import { walletApi } from '../services/wallet-api.js';

const TYPE_OPTIONS = [
  ['CASH', 'Tiền mặt'],
  ['BANK', 'Ngân hàng'],
  ['E_WALLET', 'Ví điện tử'],
  ['SAVINGS', 'Tiết kiệm']
];
const ICON_OPTIONS = [
  ['cash', 'Tiền mặt'],
  ['bank', 'Ngân hàng'],
  ['wallet', 'Ví điện tử'],
  ['savings', 'Tiết kiệm']
];
const COLOR_OPTIONS = [
  ['blue', 'Xanh dương'],
  ['green', 'Xanh lá'],
  ['orange', 'Cam'],
  ['slate', 'Xám']
];

const options = (items, selected = '') => items.map(([value, label]) =>
  `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`
).join('');

export function createWalletFeature({ state, dialog, toast, render }) {
  async function load(forceRateSync = false) {
    if (state.walletLoading) return;
    state.walletLoading = true;
    state.walletError = '';
    state.walletRateWarning = '';
    render();
    try {
      if (forceRateSync || !state.walletRatesSynced) {
        try {
          await walletApi.syncRates();
        } catch (error) {
          state.walletRateWarning = error.message;
        } finally {
          state.walletRatesSynced = true;
        }
      }
      const result = await walletApi.list('all');
      state.walletItems = result.wallets;
      state.walletSummary = result.summary;
    } catch (error) {
      if (error.status === 401) location.replace('index.html?auth=login');
      state.walletError = error.message;
    } finally {
      state.walletLoading = false;
      render();
    }
  }

  function showDialog(overline, title, content) {
    document.querySelector('#modal-overline').textContent = overline;
    document.querySelector('#modal-title').textContent = title;
    document.querySelector('#modal-content').innerHTML = content;
    dialog.showModal();
  }

  function openCreate() {
    showDialog('FR02 · TẠO VÍ', 'Tạo ví / tài khoản', `<form id="wallet-create-form">
      <div class="form-grid">
        <div class="field"><label for="wallet-name">Tên ví *</label><input id="wallet-name" name="name" maxlength="120" required autocomplete="off"></div>
        <div class="field"><label for="wallet-type">Loại ví *</label><select id="wallet-type" name="type" required>${options(TYPE_OPTIONS)}</select></div>
        <div class="field"><label for="wallet-currency">Tiền tệ *</label><select id="wallet-currency" name="currency" required>${options([['VND', 'VND'], ['USD', 'USD'], ['EUR', 'EUR']])}</select></div>
        <div class="field"><label for="wallet-opening-balance">Số dư đầu kỳ *</label><input id="wallet-opening-balance" name="openingBalance" type="number" min="0" step="0.01" value="0" required></div>
        <div class="field"><label for="wallet-icon">Biểu tượng</label><select id="wallet-icon" name="iconKey">${options(ICON_OPTIONS)}</select></div>
        <div class="field"><label for="wallet-color">Màu hiển thị</label><select id="wallet-color" name="colorKey">${options(COLOR_OPTIONS)}</select></div>
      </div>
      <p class="form-hint">Số dư đầu kỳ lớn hơn 0 được ghi nhận bằng bút toán đối ứng Vốn chủ sở hữu.</p>
      <p id="wallet-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary btn-sm">Tạo ví</button></div>
    </form>`);
  }

  function openEdit(wallet) {
    showDialog('FR02 · CẬP NHẬT VÍ', 'Chỉnh sửa ví', `<form id="wallet-edit-form" data-wallet-id="${wallet.id}">
      <div class="form-grid">
        <div class="field"><label for="wallet-edit-name">Tên ví *</label><input id="wallet-edit-name" name="name" maxlength="120" value="${safe(wallet.name)}" required autocomplete="off"></div>
        <div class="field"><label for="wallet-edit-icon">Biểu tượng</label><select id="wallet-edit-icon" name="iconKey">${options(ICON_OPTIONS, wallet.iconKey)}</select></div>
        <div class="field"><label for="wallet-edit-color">Màu hiển thị</label><select id="wallet-edit-color" name="colorKey">${options(COLOR_OPTIONS, wallet.colorKey)}</select></div>
      </div>
      <p class="form-hint">Loại ví, tiền tệ và số dư không thể chỉnh sửa trực tiếp để bảo toàn sổ tài chính.</p>
      <p id="wallet-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary btn-sm">Lưu thay đổi</button></div>
    </form>`);
  }

  function openAdjustment(wallet) {
    const step = wallet.currency === 'VND' ? '1' : '0.01';
    showDialog('FR02 · ĐIỀU CHỈNH SỐ DƯ', 'Thêm hoặc bớt tiền', `<form id="wallet-adjust-form" data-wallet-id="${wallet.id}">
      <p class="form-hint">Mỗi điều chỉnh tạo một giao dịch và bút toán đối ứng; lịch sử không bị ghi đè.</p>
      <div class="form-grid">
        <div class="field"><label for="wallet-adjust-direction">Thao tác *</label><select id="wallet-adjust-direction" name="direction" required><option value="INCREASE">Thêm tiền</option><option value="DECREASE">Bớt tiền</option></select></div>
        <div class="field"><label for="wallet-adjust-amount">Số tiền (${safe(wallet.currency)}) *</label><input id="wallet-adjust-amount" name="amount" type="number" min="${step}" step="${step}" required></div>
        <div class="field form-grid-span"><label for="wallet-adjust-note">Nội dung</label><input id="wallet-adjust-note" name="note" maxlength="200" autocomplete="off" placeholder="Ví dụ: Đối soát số dư thực tế"></div>
      </div>
      <p id="wallet-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary btn-sm">Ghi nhận</button></div>
    </form>`);
  }

  function openArchive(wallet) {
    showDialog('FR02 · LƯU TRỮ VÍ', 'Lưu trữ ví', `<form id="wallet-archive-form" data-wallet-id="${wallet.id}">
      <p class="description">Lưu trữ “${safe(wallet.name)}”? Ví sẽ được ẩn khỏi danh sách sử dụng nhưng lịch sử tài chính vẫn được bảo toàn.</p>
      <p class="form-hint">Chỉ thực hiện được khi số dư bằng 0 và không còn giao dịch định kỳ hoạt động.</p>
      <p id="wallet-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary btn-sm">Lưu trữ ví</button></div>
    </form>`);
  }

  function openPermanentDelete(wallet) {
    showDialog('FR02 · XÓA VĨNH VIỄN', 'Xóa vĩnh viễn ví', `<form id="wallet-delete-form" data-wallet-id="${wallet.id}">
      <p class="description">Xóa vĩnh viễn “${safe(wallet.name)}” khỏi danh sách ví? Thao tác này không thể hoàn tác.</p>
      <p class="form-hint">Lịch sử giao dịch và báo cáo tài chính liên quan vẫn được giữ nguyên.</p>
      <p id="wallet-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button><button type="submit" class="btn btn--danger btn-sm">Xóa vĩnh viễn</button></div>
    </form>`);
  }

  function handleAction(action, trigger) {
    if (action === 'wallet-add-real') {
      openCreate();
      return true;
    }
    if (action === 'wallet-retry') {
      load();
      return true;
    }
    if (action === 'wallet-rates-refresh') {
      load(true);
      return true;
    }
    if (!['wallet-adjust', 'wallet-edit', 'wallet-archive-real', 'wallet-delete-permanent'].includes(action)) return false;
    const wallet = state.walletItems.find(item => item.id === Number(trigger.dataset.walletId));
    if (!wallet) return true;
    if (action === 'wallet-adjust') openAdjustment(wallet);
    else if (action === 'wallet-edit') openEdit(wallet);
    else if (action === 'wallet-delete-permanent') openPermanentDelete(wallet);
    else openArchive(wallet);
    return true;
  }

  async function handleSubmit(form) {
    if (!['wallet-create-form', 'wallet-adjust-form', 'wallet-edit-form', 'wallet-archive-form', 'wallet-delete-form'].includes(form.id)) return false;
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#wallet-form-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang xử lý…';
    try {
      if (form.id === 'wallet-create-form') {
        await walletApi.create(Object.fromEntries(new FormData(form)));
        toast('Đã tạo ví và ghi nhận số dư đầu kỳ.');
      } else if (form.id === 'wallet-adjust-form') {
        await walletApi.adjust(form.dataset.walletId, Object.fromEntries(new FormData(form)));
        toast('Đã ghi nhận điều chỉnh số dư.');
      } else if (form.id === 'wallet-edit-form') {
        await walletApi.update(form.dataset.walletId, Object.fromEntries(new FormData(form)));
        toast('Đã cập nhật ví.');
      } else if (form.id === 'wallet-archive-form') {
        await walletApi.archive(form.dataset.walletId);
        toast('Đã lưu trữ ví.');
      } else {
        await walletApi.deletePermanently(form.dataset.walletId);
        toast('Đã xóa ví; lịch sử tài chính vẫn được giữ nguyên.');
      }
      dialog.close();
      await load();
    } catch (error) {
      if (error.status === 401) location.replace('index.html?auth=login');
      const fields = Object.values(error.fields || {});
      feedback.textContent = fields.length ? fields.join(' ') : error.message;
    } finally {
      submit.disabled = false;
    }
    return true;
  }

  return { load, handleAction, handleSubmit };
}
