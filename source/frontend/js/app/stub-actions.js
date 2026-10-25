/**
 * WealthFlow Stub Actions & Mock Form Controller
 * Manages placeholder forms and underdeveloped mock actions.
 */
import { FIELDS } from './forms.js';
import { safe } from './ui-core.js?v=wealth-ui-2';

export function fieldHTML([name, label, type, options]) {
  const required = !['note', 'receive', 'interest'].includes(name);
  const constraint = required ? 'required' : '';
  let control;
  if (type === 'select') {
    control = `<select id="field-${safe(name)}" name="${safe(name)}" ${constraint}>${options.map(o => `<option>${safe(o)}</option>`).join('')}</select>`;
  } else if (type === 'textarea') {
    control = `<textarea id="field-${safe(name)}" name="${safe(name)}" rows="3" ${constraint} placeholder="Nhập ${safe(label.toLowerCase())}..."></textarea>`;
  } else {
    control = `<input id="field-${safe(name)}" name="${safe(name)}" type="${type}" ${type === 'number' ? 'min="0" step="any"' : ''} ${constraint} placeholder="${type === 'number' ? '0' : safe(label)}">`;
  }
  return `
    <div class="field">
      <label for="field-${safe(name)}">${safe(label)}${required ? ' *' : ''}</label>
      ${control}
    </div>
  `;
}

export function openStubAction(action, { dialog, toast } = {}) {
  const def = FIELDS[action];
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (!def) {
    if (toast) toast('Tính năng này đang được phát triển.');
    return;
  }
  const overline = document.querySelector('#modal-overline');
  if (overline) overline.textContent = def.desc;
  const title = document.querySelector('#modal-title');
  if (title) title.textContent = def.title;
  const content = document.querySelector('#modal-content');
  if (content) {
    content.innerHTML = `
      <p class="form-hint">
        ${safe(def.note || 'Chức năng lưu đang được phát triển. Dữ liệu nhập sẽ không được ghi nhận.')}
      </p>
      ${def.fields.length ? `
        <form id="action-form">
          <div class="form-grid">
            ${def.fields.map(fieldHTML).join('')}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button>
            <button type="submit" class="btn btn--primary btn-sm">Kiểm tra biểu mẫu</button>
          </div>
        </form>
      ` : `
        <div class="modal-footer">
          <button type="button" class="btn btn--primary btn-sm" data-close-modal>Đóng</button>
        </div>
      `}
    `;
  }
  if (targetDialog) targetDialog.showModal();
}

export function handleStubSubmit(event, { dialog, toast } = {}) {
  if (event.target.id !== 'action-form') return false;
  event.preventDefault();
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (targetDialog) targetDialog.close();
  if (toast) toast('Tính năng lưu chưa khả dụng. Không có thay đổi nào được thực hiện.');
  return true;
}

export function handleStubAction(action, { dialog, toast } = {}) {
  if (action === 'reversal') {
    const targetDialog = dialog || document.querySelector('#action-dialog');
    if (targetDialog) targetDialog.close();
    if (toast) toast('Chức năng đảo giao dịch đang được phát triển. Giao dịch gốc không bị thay đổi.');
    return true;
  }
  openStubAction(action, { dialog, toast });
  return true;
}
