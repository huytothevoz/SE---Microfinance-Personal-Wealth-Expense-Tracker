/**
 * WealthFlow Profile & Account Security Controller
 * Manages password changes and user profile security operations.
 */
import { authApi, errorMessage } from '../services/auth-api.js';

export function openPasswordForm({ dialog, toast } = {}) {
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (!targetDialog) return;

  const overline = document.querySelector('#modal-overline');
  if (overline) overline.textContent = 'BẢO MẬT TÀI KHOẢN';
  const title = document.querySelector('#modal-title');
  if (title) title.textContent = 'Đổi mật khẩu';
  const content = document.querySelector('#modal-content');
  if (content) {
    content.innerHTML = `<form id="password-form">
      <p class="form-hint">Mật khẩu mới cần ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số; tối đa 72 byte. Các phiên cũ trên mọi thiết bị sẽ được đăng xuất.</p>
      <div class="field"><label for="current-password">Mật khẩu hiện tại</label><input id="current-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
      <div class="field"><label for="new-password">Mật khẩu mới</label><input id="new-password" name="newPassword" type="password" autocomplete="new-password" minlength="8" required></div>
      <div class="field"><label for="confirm-password">Nhập lại mật khẩu mới</label><input id="confirm-password" name="passwordConfirmation" type="password" autocomplete="new-password" required></div>
      <p id="password-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Đổi mật khẩu</button></div>
    </form>`;

    const form = document.querySelector('#password-form');
    if (form) {
      form.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        const feedback = form.querySelector('#password-feedback');
        submit.disabled = true;
        feedback.textContent = 'Đang đổi mật khẩu…';
        try {
          const result = await authApi('change-password', Object.fromEntries(new FormData(form)));
          form.reset();
          targetDialog.close();
          if (toast) toast(result.message);
        } catch (error) {
          if (error.status === 401) location.replace('index.html?auth=login');
          feedback.textContent = errorMessage(error);
        } finally {
          submit.disabled = false;
        }
      });
    }
  }
  targetDialog.showModal();
}

export function openCloseAccountForm({ dialog, toast } = {}) {
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (!targetDialog) return;

  const overline = document.querySelector('#modal-overline');
  if (overline) overline.textContent = 'VÙNG NGUY HIỂM';
  const title = document.querySelector('#modal-title');
  if (title) title.textContent = 'Đóng tài khoản';
  const content = document.querySelector('#modal-content');
  if (!content) return;
  content.innerHTML = `<form id="close-account-form">
    <p class="form-hint">Tài khoản sẽ bị khóa và đăng xuất khỏi mọi thiết bị. Hồ sơ cùng dữ liệu tài chính được giữ lại phục vụ kiểm toán; bạn không thể tự đăng nhập lại sau khi đóng.</p>
    <div class="field"><label for="close-current-password">Mật khẩu hiện tại</label><input id="close-current-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
    <div class="field"><label for="close-confirmation">Nhập chính xác DONG TAI KHOAN</label><input id="close-confirmation" name="confirmation" type="text" autocomplete="off" required></div>
    <p id="close-account-feedback" role="status" aria-live="polite"></p>
    <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--danger">Đóng tài khoản</button></div>
  </form>`;

  const form = content.querySelector('#close-account-form');
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#close-account-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang xác nhận và đóng tài khoản…';
    try {
      const result = await authApi('close-account', Object.fromEntries(new FormData(form)));
      feedback.textContent = result.message;
      window.setTimeout(() => location.replace('index.html?auth=login&closed=1'), 900);
    } catch (error) {
      if (error.status === 401) location.replace('index.html?auth=login');
      feedback.textContent = errorMessage(error);
      submit.disabled = false;
    }
  });
  targetDialog.showModal();
}

export function handleSecurityAction(action, { dialog, toast } = {}) {
  if (action === 'password') {
    openPasswordForm({ dialog, toast });
    return true;
  }
  if (action === 'close-account') {
    openCloseAccountForm({ dialog, toast });
    return true;
  }
  return false;
}
