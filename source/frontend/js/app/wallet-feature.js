import { walletApi } from '../services/wallet-api.js';
import { safe } from './ui-core.js';

export function createWalletFeature({ state, dialog, toast, render }) {
  const show = (title, form) => {
    document.querySelector('#modal-overline').textContent = 'VÍ & TÀI KHOẢN';
    document.querySelector('#modal-title').textContent = title;
    document.querySelector('#modal-content').innerHTML = form;
    dialog.showModal();
  };
  async function load() {
    state.walletLoading = true; state.walletError = ''; render();
    try {
      const result = await walletApi.list('all');
      state.walletItems = result.wallets ?? [];
    } catch (e) { state.walletError = e.message; state.walletItems = []; }
    finally { state.walletLoading = false; render(); }
  }
  function handleAction(action, trigger) {
    if (action==='wallet-retry') { void load(); return true; }
    if (action==='wallet-add-real') {
      show('Tạo ví mới', `<form id="wallet-create-form"><div class="form-grid">
        <div class="field"><label for="wallet-name">Tên ví</label><input id="wallet-name" name="name" maxlength="120" required></div>
        <div class="field"><label for="wallet-type">Loại ví</label><select id="wallet-type" name="walletType" required><option value="CASH">Tiền mặt</option><option value="BANK">Ngân hàng</option><option value="E_WALLET">Ví điện tử</option><option value="SAVINGS">Tiết kiệm</option><option value="CREDIT">Thẻ tín dụng</option></select></div>
        <div class="field"><label for="wallet-currency">Đơn vị tiền tệ</label><select id="wallet-currency" name="currency"><option value="VND">VND</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
        <div class="field"><label for="wallet-opening">Số dư đầu kỳ</label><input id="wallet-opening" type="number" name="initialBalance" min="0" step="0.01" value="0" required></div>
        </div><p class="form-hint">Số dư đầu kỳ lớn hơn 0 hiện chỉ được hỗ trợ đối với ví VND không phải thẻ tín dụng, để đảm bảo ghi sổ chính xác.</p>
        <p id="wallet-form-feedback" role="status"></p><div class="modal-footer"><button type="button" data-close-modal class="btn btn--outline">Hủy</button><button type="submit" class="btn btn--primary">Tạo ví</button></div></form>`);
      return true;
    }
    if (action==='wallet-edit') {
      const w = state.walletItems.find(x=>String(x.id)===String(trigger.dataset.walletId));
      if (!w || w.status !== 'ACTIVE') return true;
      show('Quản lý ví', `<form id="wallet-edit-form" data-wallet-id="${Number(w.id)}">
        <div class="field"><label for="wallet-new-name">Tên ví</label><input id="wallet-new-name" name="name" value="${safe(w.name)}" maxlength="120" required></div>
        <p id="wallet-form-feedback" role="status"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu</button><button type="button" class="btn btn--outline" data-action="wallet-archive-real" data-wallet-id="${Number(w.id)}">Lưu trữ ví</button></div></form>`);
      return true;
    }
    if (action==='wallet-archive-real') {
      const w = state.walletItems.find(x=>String(x.id)===String(trigger.dataset.walletId));
      if (!w) return true;
      show('Xác nhận lưu trữ ví', `<form id="wallet-archive-form" data-wallet-id="${Number(w.id)}"><p>Chỉ lưu trữ được ví có số dư bằng 0 và không có lịch định kỳ hoạt động.</p>
        <p id="wallet-form-feedback" role="status"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu trữ</button></div></form>`);
      return true;
    }
    return false;
  }
  async function handleSubmit(form) {
    if (!['wallet-create-form','wallet-edit-form','wallet-archive-form'].includes(form.id)) return false;
    const button = form.querySelector('[type="submit"]'), info = form.querySelector('#wallet-form-feedback');
    button.disabled=true; info.textContent='Đang ghi dữ liệu…';
    try {
      if (form.id==='wallet-create-form') await walletApi.create(Object.fromEntries(new FormData(form)));
      if (form.id==='wallet-edit-form') await walletApi.update(form.dataset.walletId, Object.fromEntries(new FormData(form)));
      if (form.id==='wallet-archive-form') await walletApi.archive(form.dataset.walletId);
      dialog.close(); toast('Đã lưu trên máy chủ.'); await load();
    } catch(e) { info.textContent=e.message; }
    finally { button.disabled=false; }
    return true;
  }
  return {load,handleAction,handleSubmit};
}
