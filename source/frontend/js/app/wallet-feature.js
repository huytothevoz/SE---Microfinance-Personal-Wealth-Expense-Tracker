import { walletApi } from '../services/wallet-api.js';
import { icon, safe } from './ui-core.js';

const OPENING_BALANCES = [
  ['0', '0 đ'],
  ['50000', '50.000 đ'],
  ['100000', '100.000 đ'],
  ['200000', '200.000 đ'],
  ['500000', '500.000 đ'],
  ['1000000', '1.000.000 đ'],
  ['2000000', '2.000.000 đ'],
  ['5000000', '5.000.000 đ']
];

export function createWalletFeature({ state, dialog, toast, render }) {
  const show = (title, content) => {
    document.querySelector('#modal-overline').textContent = 'VÍ & TÀI KHOẢN';
    document.querySelector('#modal-title').textContent = title;
    document.querySelector('#modal-content').innerHTML = content;
    if (!dialog.open) dialog.showModal();
  };

  const walletByTrigger = trigger => state.walletItems.find(
    item => String(item.id) === String(trigger.dataset.walletId)
  );

  async function load() {
    state.walletLoading = true;
    state.walletError = '';
    state.walletRateWarning = '';
    render();
    try {
      const result = await walletApi.list('all');
      state.walletItems = result.wallets ?? [];
      try {
        const rateResult = await walletApi.listRates();
        state.walletRates = rateResult.rates ?? [];
      } catch (error) {
        state.walletRates = [];
        state.walletRateWarning = error.message;
      }
    } catch (error) {
      state.walletError = error.message;
      state.walletItems = [];
      state.walletRates = [];
    } finally {
      state.walletLoading = false;
      render();
    }
  }

  function configureOpeningBalanceForm() {
    const form = document.querySelector('#wallet-create-form');
    if (!form) return;
    const preset = form.elements.namedItem('openingPreset');
    const custom = form.elements.namedItem('customOpeningBalance');
    const presetField = form.querySelector('#wallet-opening-preset-field');
    const customField = form.querySelector('#wallet-opening-custom-field');
    const customLabel = form.querySelector('#wallet-opening-custom-label');
    const currency = form.elements.namedItem('currency');

    const sync = () => {
      const isVnd = currency.value === 'VND';
      if (!isVnd) preset.value = 'CUSTOM';
      presetField.hidden = !isVnd;
      const customSelected = !isVnd || preset.value === 'CUSTOM';
      customField.hidden = !customSelected;
      custom.disabled = !customSelected;
      custom.required = customSelected;
      custom.inputMode = isVnd ? 'numeric' : 'decimal';
      custom.pattern = isVnd
        ? '(?:[0-9]{1,16}|[0-9]{1,3}(?:\\.[0-9]{3})+|[0-9]{1,3}(?:,[0-9]{3})+)'
        : '[0-9]{1,16}(?:[.,][0-9]{1,2})?';
      custom.title = isVnd
        ? 'Nhập số nguyên, ví dụ 1250000, 1.250.000 hoặc 1,250,000.'
        : 'Nhập tối đa 2 số thập phân bằng dấu chấm hoặc dấu phẩy; không dùng dấu phân cách hàng nghìn.';
      customLabel.textContent = `Số dư tự nhập (${currency.value}) *`;
    };
    preset.addEventListener('change', sync);
    currency.addEventListener('change', sync);
    sync();
  }

  function openCreateForm() {
    const presets = OPENING_BALANCES
      .map(([value, label]) => `<option value="${value}">${label}</option>`)
      .join('');
    show('Tạo ví mới', `<form id="wallet-create-form"><div class="form-grid">
      <div class="field"><label for="wallet-name">Tên ví *</label><input id="wallet-name" name="name" maxlength="120" autocomplete="off" required></div>
      <div class="field"><label for="wallet-type">Loại ví *</label><select id="wallet-type" name="walletType" required><option value="CASH">Tiền mặt</option><option value="BANK">Ngân hàng</option><option value="E_WALLET">Ví điện tử</option><option value="SAVINGS">Tiết kiệm</option><option value="CREDIT">Thẻ tín dụng</option></select></div>
      <div class="field"><label for="wallet-currency">Đơn vị tiền tệ *</label><select id="wallet-currency" name="currency" required><option value="VND">VND</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
      <div class="field" id="wallet-opening-preset-field"><label for="wallet-opening-preset">Số dư đầu kỳ *</label><select id="wallet-opening-preset" name="openingPreset" required>${presets}<option value="CUSTOM">Tự nhập số tiền…</option></select></div>
      <div class="field form-grid-span" id="wallet-opening-custom-field" hidden><label id="wallet-opening-custom-label" for="wallet-opening-custom">Số dư tự nhập (VND) *</label><input id="wallet-opening-custom" type="text" name="customOpeningBalance" maxlength="24" inputmode="numeric" autocomplete="off" disabled></div>
      </div><p id="wallet-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" data-close-modal class="btn btn--outline">Hủy</button><button type="submit" class="btn btn--primary">Tạo ví</button></div></form>`);
    configureOpeningBalanceForm();
  }

  function openManageMenu(wallet) {
    show('Chỉnh sửa ví', `<div class="wallet-edit-menu">
      <button type="button" class="wallet-edit-option" data-action="wallet-rename" data-wallet-id="${Number(wallet.id)}">
        <span class="wallet-edit-option__icon">${icon('file')}</span><span><strong>Đổi tên ví</strong><small>Cập nhật tên hiển thị của ví hoặc tài khoản</small></span>
      </button>
      <button type="button" class="wallet-edit-option" data-action="wallet-adjust" data-wallet-id="${Number(wallet.id)}">
        <span class="wallet-edit-option__icon">${icon('arrows')}</span><span><strong>Thêm hoặc bớt tiền</strong><small>Ghi nhận điều chỉnh bằng bút toán tài chính</small></span>
      </button>
      <button type="button" class="wallet-edit-option wallet-edit-option--danger" data-action="wallet-archive" data-wallet-id="${Number(wallet.id)}">
        <span class="wallet-edit-option__icon">${icon('layers')}</span><span><strong>Lưu trữ ví</strong><small>Chỉ áp dụng khi số dư, tiền phân bổ và lịch định kỳ đều hợp lệ</small></span>
      </button>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>
    </div>`);
  }

  function handleAction(action, trigger) {
    if (action === 'wallet-retry') {
      void load();
      return true;
    }
    if (action === 'wallet-add-real') {
      openCreateForm();
      return true;
    }
    if (action === 'rate-refresh') {
      trigger.disabled = true;
      void walletApi.syncRates()
        .then(result => toast(`Đã cập nhật tỷ giá Vietcombank lúc ${result.effectiveAt}.`))
        .then(load)
        .catch(error => toast(error.message))
        .finally(() => { trigger.disabled = false; });
      return true;
    }

    const walletItemActions = ['wallet-manage', 'wallet-rename', 'wallet-adjust', 'wallet-archive', 'wallet-delete'];
    if (!walletItemActions.includes(action)) return false;

    const wallet = walletByTrigger(trigger);
    if (!wallet) {
      toast('Không tìm thấy ví. Vui lòng tải lại trang.');
      return true;
    }
    if (action === 'wallet-delete') {
      if (String(wallet.status) !== 'ARCHIVED') {
        toast('Phải lưu trữ ví trước khi xóa vĩnh viễn.');
        return true;
      }
      show('Xóa vĩnh viễn ví', `<form id="wallet-delete-form" data-wallet-id="${Number(wallet.id)}">
        <p class="form-hint"><strong>${safe(wallet.name)}</strong> sẽ bị xóa vĩnh viễn nếu chưa từng được sử dụng. Nếu đã có giao dịch, lịch định kỳ, khoản vay, vàng hoặc mục tiêu liên quan, hệ thống sẽ giữ ví lưu trữ để bảo toàn lịch sử.</p>
        <p id="wallet-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--danger">Xóa vĩnh viễn</button></div></form>`);
      return true;
    }
    if (String(wallet.status) !== 'ACTIVE') {
      toast('Ví không còn hoạt động. Vui lòng tải lại trang.');
      return true;
    }
    if (action === 'wallet-manage') {
      openManageMenu(wallet);
      return true;
    }
    if (action === 'wallet-rename') {
      show('Đổi tên ví', `<form id="wallet-edit-form" data-wallet-id="${Number(wallet.id)}">
        <div class="field"><label for="wallet-new-name">Tên ví *</label><input id="wallet-new-name" name="name" value="${safe(wallet.name)}" maxlength="120" autocomplete="off" required></div>
        <p id="wallet-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu thay đổi</button></div></form>`);
      return true;
    }
    if (action === 'wallet-adjust') {
      const currency = safe(wallet.currency);
      const isVnd = wallet.currency === 'VND';
      const amountPattern = isVnd
        ? '(?:[0-9]{1,16}|[0-9]{1,3}(?:\\.[0-9]{3})+|[0-9]{1,3}(?:,[0-9]{3})+)'
        : '[0-9]{1,16}(?:[.,][0-9]{1,2})?';
      const amountHelp = isVnd
        ? 'Số nguyên hoặc phân cách hàng nghìn hợp lệ.'
        : 'Tối đa 2 số thập phân; dùng dấu chấm hoặc dấu phẩy.';
      show('Thêm hoặc bớt tiền', `<form id="wallet-adjust-form" data-wallet-id="${Number(wallet.id)}">
        <div class="form-grid"><div class="field"><label for="wallet-adjust-direction">Thao tác *</label><select id="wallet-adjust-direction" name="direction" required><option value="INCREASE">Thêm tiền</option><option value="DECREASE">Bớt tiền</option></select></div>
        <div class="field"><label for="wallet-adjust-amount">Số tiền (${currency}) *</label><input id="wallet-adjust-amount" name="amount" type="text" maxlength="24" inputmode="${isVnd ? 'numeric' : 'decimal'}" pattern="${amountPattern}" title="${amountHelp}" autocomplete="off" required></div>
        <div class="field form-grid-span"><label for="wallet-adjust-note">Nội dung</label><input id="wallet-adjust-note" name="note" maxlength="200" autocomplete="off" placeholder="Ví dụ: Bổ sung tiền mặt đầu tháng"></div></div>
        <p id="wallet-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Ghi nhận</button></div></form>`);
      return true;
    }
    if (action === 'wallet-archive') {
      show('Xác nhận lưu trữ ví', `<form id="wallet-archive-form" data-wallet-id="${Number(wallet.id)}"><p class="form-hint">Chỉ lưu trữ được ví có số dư và số tiền phân bổ bằng 0, đồng thời không có lịch định kỳ đang hoạt động.</p>
        <p id="wallet-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu trữ ví</button></div></form>`);
      return true;
    }
    return false;
  }

  async function handleSubmit(form) {
    if (!['wallet-create-form', 'wallet-edit-form', 'wallet-adjust-form', 'wallet-archive-form', 'wallet-delete-form'].includes(form.id)) return false;
    const button = form.querySelector('[type="submit"]');
    const info = form.querySelector('#wallet-form-feedback');
    button.disabled = true;
    info.textContent = 'Đang ghi dữ liệu…';
    try {
      const data = Object.fromEntries(new FormData(form));
      let successMessage = 'Đã lưu thay đổi trên máy chủ.';
      if (form.id === 'wallet-create-form') {
        data.initialBalance = data.openingPreset === 'CUSTOM'
          ? data.customOpeningBalance
          : data.openingPreset;
        delete data.openingPreset;
        delete data.customOpeningBalance;
        await walletApi.create(data);
        successMessage = 'Đã tạo ví và ghi nhận số dư đầu kỳ.';
      } else if (form.id === 'wallet-edit-form') {
        await walletApi.update(form.dataset.walletId, data);
        successMessage = 'Đã cập nhật tên ví.';
      } else if (form.id === 'wallet-adjust-form') {
        await walletApi.adjust(form.dataset.walletId, data);
        successMessage = 'Đã điều chỉnh số dư và ghi sổ tài chính.';
      } else if (form.id === 'wallet-archive-form') {
        await walletApi.archive(form.dataset.walletId);
        successMessage = 'Đã lưu trữ ví.';
      } else if (form.id === 'wallet-delete-form') {
        await walletApi.remove(form.dataset.walletId);
        successMessage = 'Đã xóa vĩnh viễn ví chưa có lịch sử sử dụng.';
      }
      dialog.close();
      toast(successMessage);
      await load();
    } catch (error) {
      const fields = Object.values(error.fields || {});
      info.textContent = fields.length ? fields.join(' ') : error.message;
    } finally {
      button.disabled = false;
    }
    return true;
  }

  return { load, handleAction, handleSubmit };
}
