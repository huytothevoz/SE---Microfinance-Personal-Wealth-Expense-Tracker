import { authApi, errorMessage } from './services/auth-api.js';

const dialog = document.querySelector('#auth-dialog');
const tabs = [...document.querySelectorAll('[data-auth-tab]')];
const title = document.querySelector('#auth-title');
const description = document.querySelector('#auth-desc');
const nameField = document.querySelector('#register-name');
const nameInput = document.querySelector('#auth-name');
const emailField = document.querySelector('#auth-email-field');
const emailInput = document.querySelector('#auth-email');
const passwordField = document.querySelector('#auth-password-field');
const password = document.querySelector('#auth-password');
const otpInput = document.querySelector('#auth-reset-otp');
const otpField = document.querySelector('#reset-otp-field');
const resetEmailNote = document.querySelector('#auth-reset-email-note');
const resendOtpButton = document.querySelector('#auth-resend-otp');
let resendTimer = null;
let resendSeconds = 0;

function startResendCooldown() {
  clearInterval(resendTimer);
  resendSeconds = 60;
  resendOtpButton.disabled = true;
  resendOtpButton.textContent = `Gửi lại sau ${resendSeconds}s`;
  resendTimer = setInterval(() => {
    resendSeconds -= 1;
    if (resendSeconds <= 0) {
      clearInterval(resendTimer);
      resendTimer = null;
      resendOtpButton.disabled = false;
      resendOtpButton.textContent = 'Gửi lại mã OTP';
      return;
    }
    resendOtpButton.textContent = `Gửi lại sau ${resendSeconds}s`;
  }, 1000);
}

const menu = document.querySelector('#landing-menu');
const feedback = document.querySelector('#auth-feedback');
const submit = document.querySelector('.auth-submit');
const confirmation = document.querySelector('#auth-confirmation');
const confirmationField = document.querySelector('#confirmation-field');
const tabsContainer = document.querySelector('.auth-tabs');
const forgotLink = document.querySelector('#auth-forgot-link');
const backToLogin = document.querySelector('#auth-back-login');
let currentMode = 'login';

function setMode(mode) {
  if (currentMode !== mode && (mode === 'forgot' || mode === 'reset')) {
    password.value = '';
    confirmation.value = '';
    otpInput.value = '';
  }
  currentMode = mode;
  tabs.forEach(button => {
    const active = button.dataset.authTab === mode;
    button.classList.toggle('active', active);
    button.setAttribute('aria-selected', String(active));
  });
  const isRegister = mode === 'register';
  const isForgot = mode === 'forgot';
  const isReset = mode === 'reset';
  tabsContainer.hidden = isForgot || isReset;
  title.textContent = isRegister ? 'Tạo tài khoản WealthFlow'
    : isForgot ? 'Tìm lại mật khẩu'
      : isReset ? 'Đặt mật khẩu mới' : 'Đăng nhập tài khoản';
  description.textContent = isRegister ? 'Tạo tài khoản để bắt đầu quản lý tài chính của bạn.'
    : isForgot ? 'Nhập email đã đăng ký. Nếu tài khoản tồn tại, chúng tôi sẽ gửi mã OTP đặt lại mật khẩu.'
      : isReset ? 'Chọn mật khẩu mới có ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số.'
        : 'Đăng nhập để tiếp tục hoặc tạo tài khoản mới để bắt đầu.';
  nameField.hidden = !isRegister;
  nameInput.required = isRegister;
  emailField.hidden = isReset;
  emailInput.required = !isReset;
  passwordField.hidden = isForgot;
  password.required = !isForgot;
  password.autocomplete = isRegister || isReset ? 'new-password' : 'current-password';
  password.minLength = isRegister || isReset ? 8 : 1;
  document.querySelector('#register-extra').hidden = !isRegister;
  confirmationField.hidden = !isRegister && !isReset;
  confirmationField.querySelector('label').textContent = isReset ? 'Nhập lại mật khẩu mới' : 'Nhập lại mật khẩu';
  confirmation.required = isRegister || isReset;
  otpField.hidden = !isReset;
  otpInput.required = isReset;
  resetEmailNote.textContent = isReset
    ? `Mã có hiệu lực 10 phút. Kiểm tra hộp thư ${emailInput.value.trim()} và cả mục Spam.`
    : '';
  forgotLink.hidden = mode !== 'login';
  backToLogin.hidden = !isForgot && !isReset;
  submit.textContent = isRegister ? 'Đăng ký tài khoản →'
    : isForgot ? 'Gửi mã OTP'
      : isReset ? 'Lưu mật khẩu mới' : 'Đăng nhập →';
  feedback.textContent = '';
}

document.querySelectorAll('[data-open-auth]').forEach(button => button.addEventListener('click', () => {
  setMode(button.dataset.openAuth);
  dialog.showModal();
}));

document.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());

dialog.addEventListener('click', event => {
  if (event.target === dialog) dialog.close();
});

tabs.forEach(tab => tab.addEventListener('click', () => setMode(tab.dataset.authTab)));

forgotLink.addEventListener('click', () => {
  setMode('forgot');
  emailInput.focus();
});

backToLogin.addEventListener('click', () => setMode('login'));

resendOtpButton.addEventListener('click', async () => {
  if (resendOtpButton.disabled || currentMode !== 'reset') return;
  resendOtpButton.disabled = true;
  feedback.textContent = 'Đang yêu cầu gửi lại mã OTP…';
  try {
    const result = await authApi('password/forgot', { email: emailInput.value.trim() });
    otpInput.value = '';
    feedback.textContent = result.message;
    startResendCooldown();
    otpInput.focus();
  } catch (error) {
    feedback.textContent = errorMessage(error);
    resendOtpButton.disabled = false;
  }
});

document.querySelector('#auth-form').addEventListener('submit', async event => {
  event.preventDefault();
  if (!event.currentTarget.reportValidity()) return;
  const mode = currentMode;
  const form = event.currentTarget;
  const data = {
    email: emailInput.value,
    password: password.value,
    fullName: nameInput.value,
    baseCurrency: document.querySelector('#auth-currency').value,
    passwordConfirmation: confirmation.value
  };
  submit.disabled = true;
  tabs.forEach(button => { button.disabled = true; });
  feedback.textContent = mode === 'register' ? 'Đang tạo tài khoản…'
    : mode === 'forgot' ? 'Đang gửi yêu cầu…'
      : mode === 'reset' ? 'Đang cập nhật mật khẩu…' : 'Đang đăng nhập…';
  try {
    const route = mode === 'forgot' ? 'password/forgot'
      : mode === 'reset' ? 'password/reset' : mode;
    const payload = mode === 'reset' ? {
      email: emailInput.value,
      otp: otpInput.value,
      newPassword: password.value,
      passwordConfirmation: confirmation.value
    } : mode === 'forgot' ? { email: emailInput.value } : data;
    const result = await authApi(route, payload);
    if (mode === 'login') {
      form.reset();
      window.location.assign('app.html');
      return;
    }
    if (mode === 'register') {
      setMode('login');
      password.value = '';
      confirmation.value = '';
      const mailMessage = result.emailDelivery === 'sent' ? ' Email chào mừng đã được gửi; hãy kiểm tra cả thư rác.'
        : result.emailDelivery === 'preview' ? ' Email được lưu ở chế độ xem thử trên máy chủ.'
          : ' Email chào mừng đang chờ gửi. Bạn vẫn có thể đăng nhập ngay.';
      feedback.textContent = result.message + mailMessage;
      password.focus();
    } else if (mode === 'forgot') {
      setMode('reset');
      feedback.textContent = result.message;
      startResendCooldown();
      otpInput.focus();
    } else {
      form.reset();
      setMode('login');
      feedback.textContent = result.message;
      emailInput.focus();
    }
  } catch (error) {
    feedback.textContent = errorMessage(error);
  } finally {
    submit.disabled = false;
    tabs.forEach(button => { button.disabled = false; });
  }
});

menu.addEventListener('click', () => {
  const expanded = menu.getAttribute('aria-expanded') === 'true';
  menu.setAttribute('aria-expanded', String(!expanded));
  document.querySelector('.landing-nav').classList.toggle('is-open', !expanded);
});

document.querySelectorAll('.landing-nav a').forEach(link => link.addEventListener('click', () => {
  document.querySelector('.landing-nav').classList.remove('is-open');
  menu.setAttribute('aria-expanded', 'false');
}));

const requestedTab = new URLSearchParams(location.search).get('auth');
if (['login', 'register'].includes(requestedTab)) {
  setMode(requestedTab);
  dialog.showModal();
}
