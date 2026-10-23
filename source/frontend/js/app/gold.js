/** WealthFlow gold holdings backed only by authenticated PHP APIs. */
import { loadView } from './view-loader.js';
import { gold, wallets, dataStatus } from '../data/live-data.js?v=wealth-ui-2';
import { GoldApi } from '../services/gold-api.js';
import { money, safe, formatMoneyInput, moneyInputValue } from './ui-core.js?v=wealth-ui-2';

const byId = id => gold.find(asset => String(asset.id) === String(id));
const localDate = () => new Date().toLocaleDateString('en-CA');
const dateText = value => value ? new Intl.DateTimeFormat('vi-VN').format(new Date(`${value}T00:00:00`)) : '—';
const walletOptions = () => wallets.filter(wallet =>
  String(wallet.status || 'ACTIVE').toUpperCase() === 'ACTIVE'
  && String(wallet.type || wallet.walletType || '').toUpperCase() !== 'CREDIT'
  && String(wallet.currency || '').toUpperCase() === 'VND'
);

function cards() {
  const state = dataStatus.gold?.state;
  if (state === 'loading' || state === 'idle') return '<div class="empty-state"><strong>Đang tải tài sản vàng…</strong></div>';
  if (state !== 'ready') return `<div class="empty-state"><strong>Chưa tải được tài sản vàng</strong><p>${safe(dataStatus.gold?.message || 'Vui lòng kiểm tra kết nối máy chủ.')}</p><button type="button" class="btn btn--outline btn-sm" data-page="gold">Thử lại</button></div>`;
  if (!gold.length) return '<div class="empty-state"><strong>Bạn chưa có tài sản vàng</strong><p>Ghi nhận vàng đang sở hữu hoặc mua vàng từ một ví VND.</p></div>';
  return gold.map(asset => `<article class="gold-card">
    <div class="status-row"><span class="goal-emoji" aria-hidden="true">◇</span><span class="badge blue">Đang sở hữu</span></div>
    <div><h3>${safe(asset.name)}</h3><p>Số lượng: ${safe(Number(asset.quantityChi).toLocaleString('vi-VN', { maximumFractionDigits: 4 }))} chỉ</p></div>
    <div><span class="description">Giá trị định giá</span><div class="loan-amount">${money(Number(asset.currentValue))}</div><p>${money(Number(asset.valuationPerChi))} / chỉ · ${safe(dateText(asset.valuationDate))}</p></div>
    <div class="goal-card-actions"><button type="button" class="btn btn--outline btn-sm" data-action="gold:detail:${safe(asset.id)}">Lịch sử</button><button type="button" class="btn btn--outline btn-sm" data-action="gold:revalue:${safe(asset.id)}">Định giá</button><button type="button" class="btn btn--primary btn-sm" data-action="gold:liquidate:${safe(asset.id)}">Thanh lý</button></div>
  </article>`).join('');
}

export async function renderGoldPage() {
  const rawHtml = await loadView('gold');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const ready = dataStatus.gold?.state === 'ready';
  const held = ready ? gold : [];
  const totalValue = held.reduce((sum, asset) => sum + Number(asset.currentValue || 0), 0);
  const totalWeight = held.reduce((sum, asset) => sum + Number(asset.quantityChi || 0), 0);
  const latestDate = held.map(asset => asset.valuationDate).filter(Boolean).sort().at(-1);
  temp.querySelector('#gold-total-value').textContent = ready ? money(totalValue) : '—';
  temp.querySelector('#gold-types-count').textContent = ready ? `${held.length} loại` : '—';
  temp.querySelector('#gold-total-weight').textContent = ready ? `${totalWeight.toLocaleString('vi-VN', { maximumFractionDigits: 4 })} chỉ` : '—';
  temp.querySelector('#gold-valuation-date').textContent = ready ? dateText(latestDate) : '—';
  temp.querySelector('#gold-card-list').innerHTML = cards();

  return temp.innerHTML;
}

function setModal(dialog, title, html) {
  document.querySelector('#modal-overline').textContent = '';
  document.querySelector('#modal-title').textContent = title;
  document.querySelector('#modal-content').innerHTML = html;
  dialog.showModal();
}

function selectWallet(name, label, optional = false) {
  const options = walletOptions();
  return `<div class="field"><label for="gold-${name}">${safe(label)}</label><select id="gold-${name}" name="${safe(name)}" ${optional ? '' : 'required'}>${optional ? '<option value="">Tài sản đã sở hữu — không trừ ví</option>' : ''}${options.map(wallet => `<option value="${safe(wallet.id)}">${safe(wallet.name)} · ${money(Number(wallet.balance))}</option>`).join('')}</select></div>`;
}

function bind(form, request, context, success) {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('[type=submit]');
    const feedback = form.querySelector('[role=status]');
    const data = Object.fromEntries(new FormData(form));
    for (const key of ['pricePerChi']) data[key] = moneyInputValue(data[key]);
    if (data.walletId) data.walletId = Number(data.walletId); else delete data.walletId;
    button.disabled = true;
    feedback.textContent = 'Đang lưu và đối chiếu số liệu…';
    try {
      await request(data);
      context.dialog.close();
      await context.reload();
      context.toast(success);
    } catch (error) {
      feedback.textContent = error.message;
      button.disabled = false;
    }
  });
}

function openCreate(context) {
  setModal(context.dialog, 'Thêm tài sản vàng', `<form id="gold-form"><p class="form-hint">Chọn ví để ghi nhận một giao dịch mua thực tế. Nếu đây là vàng đã sở hữu từ trước, để trống ví để ghi nhận số dư mở đầu.</p><div class="form-grid">
    <div class="field"><label for="gold-type">Loại vàng</label><select id="gold-type" name="goldType" required><option value="RING_9999">Vàng nhẫn 9999</option><option value="SJC_BAR">Vàng miếng SJC</option><option value="GOLD_24K">Vàng 24K</option></select></div>
    <div class="field"><label for="gold-quantity">Số lượng (chỉ)</label><input id="gold-quantity" name="quantityChi" type="text" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,4})?" required></div>
    <div class="field"><label for="gold-price">Giá mua mỗi chỉ (VND)</label><input id="gold-price" name="pricePerChi" type="text" inputmode="numeric" data-money-input required></div>
    ${selectWallet('walletId', 'Ví thanh toán', true)}
    <div class="field"><label for="gold-date">Ngày mua/ghi nhận</label><input id="gold-date" name="transactionDate" type="date" max="${localDate()}" value="${localDate()}" required></div>
  </div><p role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu tài sản</button></div></form>`);
  bind(document.querySelector('#gold-form'), data => GoldApi.createGoldTransaction({ ...data, actionType: 'BUY' }), context, 'Đã ghi nhận tài sản vàng.');
}

function openRevalue(asset, context) {
  setModal(context.dialog, `Định giá ${asset.name}`, `<form id="gold-form"><p class="form-hint">Định giá không làm thay đổi số lượng vàng hoặc số dư ví; đây là giá trị thị trường do bạn xác nhận.</p><div class="form-grid"><div class="field"><label for="gold-price">Giá trị mỗi chỉ (VND)</label><input id="gold-price" name="pricePerChi" type="text" inputmode="numeric" data-money-input value="${safe(formatMoneyInput(asset.valuationPerChi))}" required></div><div class="field"><label for="gold-date">Ngày định giá</label><input id="gold-date" name="valuationDate" type="date" max="${localDate()}" value="${localDate()}" required></div></div><p role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Cập nhật định giá</button></div></form>`);
  bind(document.querySelector('#gold-form'), data => GoldApi.revalueGoldAsset(asset.id, data), context, 'Đã cập nhật định giá vàng.');
}

function openLiquidate(asset, context) {
  if (!walletOptions().length) {
    context.toast('Bạn cần một ví tài sản VND đang hoạt động để nhận tiền thanh lý.');
    return;
  }
  setModal(context.dialog, `Thanh lý ${asset.name}`, `<form id="gold-form"><p class="form-hint">Toàn bộ ${safe(Number(asset.quantityChi).toLocaleString('vi-VN', { maximumFractionDigits: 4 }))} chỉ sẽ được bán. Tiền thu về được cộng vào ví và chênh lệch với giá vốn được ghi nhận riêng.</p><div class="form-grid"><div class="field"><label for="gold-price">Giá bán mỗi chỉ (VND)</label><input id="gold-price" name="pricePerChi" type="text" inputmode="numeric" data-money-input value="${safe(formatMoneyInput(asset.valuationPerChi))}" required></div>${selectWallet('walletId', 'Ví nhận tiền')}<div class="field"><label for="gold-date">Ngày thanh lý</label><input id="gold-date" name="transactionDate" type="date" max="${localDate()}" value="${localDate()}" required></div></div><p role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--danger">Xác nhận thanh lý</button></div></form>`);
  bind(document.querySelector('#gold-form'), data => GoldApi.liquidateGoldAsset(asset.id, data), context, 'Đã thanh lý tài sản vàng.');
}

async function openDetail(asset, context) {
  setModal(context.dialog, `Lịch sử ${asset.name}`, '<div class="empty-state">Đang tải lịch sử…</div>');
  try {
    const result = await GoldApi.getGoldAsset(asset.id);
    const detail = result.asset || result;
    const rows = detail.transactions || [];
    document.querySelector('#modal-content').innerHTML = `<div class="goal-detail-summary"><div><small>Số lượng hiện tại</small><strong>${safe(Number(detail.quantityChi).toLocaleString('vi-VN', { maximumFractionDigits: 4 }))} chỉ</strong></div><div><small>Giá trị hiện tại</small><strong>${money(Number(detail.currentValue))}</strong></div><div><small>Tổng giá vốn</small><strong>${money(Number(detail.totalCostBasis))}</strong></div><div><small>Giá vốn bình quân</small><strong>${money(Number(detail.weightedAverageCost))}/chỉ</strong></div></div><div class="goal-history"><h3>Lịch sử biến động</h3>${rows.length ? rows.map(row => `<div class="goal-history-row"><span><strong>${row.type === 'BUY' ? 'Mua/ghi nhận' : row.type === 'SELL' ? 'Thanh lý' : 'Định giá lại'}</strong><small>${safe(row.walletName)} · ${safe(dateText(row.occurredOn))}</small></span><b>${money(Number(row.totalAmount))}</b></div>`).join('') : '<p class="form-hint">Chưa có lịch sử.</p>'}</div><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
  } catch (error) {
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">${safe(error.message)}</p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
  }
}

export function handleGoldAction(action, context) {
  if (!action.startsWith('gold:')) return false;
  const [, verb, id] = action.split(':');
  if (verb === 'create') {
    if (dataStatus.gold?.state !== 'ready') context.toast('Chưa kết nối được API tài sản vàng.'); else openCreate(context);
    return true;
  }
  const asset = byId(id);
  if (!asset) {
    context.toast('Không tìm thấy tài sản vàng này.');
    return true;
  }
  if (verb === 'detail') void openDetail(asset, context);
  else if (verb === 'revalue') openRevalue(asset, context);
  else if (verb === 'liquidate') openLiquidate(asset, context);
  else context.toast('Thao tác tài sản vàng không hợp lệ.');
  return true;
}
