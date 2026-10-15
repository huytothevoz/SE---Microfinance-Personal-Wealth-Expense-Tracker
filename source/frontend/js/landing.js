import { authApi, errorMessage } from './auth-api.js';

const dialog = document.querySelector('#auth-dialog');
const tabs = [...document.querySelectorAll('[data-auth-tab]')];
const title = document.querySelector('#auth-title');
const nameField = document.querySelector('#register-name');
const nameInput = document.querySelector('#auth-name');
const password = document.querySelector('#auth-password');
const menu = document.querySelector('#landing-menu');
const feedback = document.querySelector('#auth-feedback');
const submit = document.querySelector('.auth-submit');
const confirmation = document.querySelector('#auth-confirmation');
let currentTab = 'login';
function setTab(tab) {
  currentTab = tab;
  tabs.forEach(button => {
    const active = button.dataset.authTab === tab;
    button.classList.toggle('active', active);
    button.setAttribute('aria-selected', String(active));
  });
  title.textContent = tab === 'register' ? 'Tạo tài khoản WealthFlow' : 'Đăng nhập tài khoản';
  nameField.hidden = tab !== 'register';
  nameInput.required = tab === 'register';
  password.autocomplete = tab === 'register' ? 'new-password' : 'current-password';
  password.minLength = tab === 'register' ? 8 : 1;
  document.querySelector('#register-extra').hidden = tab !== 'register';
  confirmation.required = tab === 'register';
  submit.textContent = tab === 'register' ? 'Đăng ký tài khoản →' : 'Đăng nhập →';
  feedback.textContent = '';
}
document.querySelectorAll('[data-open-auth]').forEach(button => button.addEventListener('click', () => {
  setTab(button.dataset.openAuth);
  dialog.showModal();
}));
document.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
dialog.addEventListener('click', event => {
  if (event.target === dialog) dialog.close();
});
tabs.forEach(tab => tab.addEventListener('click', () => setTab(tab.dataset.authTab)));
document.querySelector('#auth-form').addEventListener('submit', async event => {
  event.preventDefault();
  if (!event.currentTarget.reportValidity()) return;
  const tab = currentTab;
  const form = event.currentTarget;
  const data = {
    email: document.querySelector('#auth-email').value, password: password.value,
    fullName: nameInput.value, baseCurrency: document.querySelector('#auth-currency').value,
    passwordConfirmation: confirmation.value
  };
  submit.disabled = true;
  tabs.forEach(button => { button.disabled = true; });
  feedback.textContent = tab === 'register' ? 'Đang tạo tài khoản…' : 'Đang đăng nhập…';
  try {
    const result = await authApi(tab, data);
    if (tab === 'login') {
      form.reset();
      window.location.assign('app.html');
      return;
    }
    setTab('login');
    password.value = '';
    confirmation.value = '';
    const mailMessage = result.emailDelivery === 'sent' ? ' Email chào mừng đã được gửi; hãy kiểm tra cả thư rác.'
      : result.emailDelivery === 'preview' ? ' Email được lưu ở chế độ xem thử trên máy chủ.'
        : ' Email chào mừng đang chờ gửi. Bạn vẫn có thể đăng nhập ngay.';
    feedback.textContent = result.message + mailMessage;
    password.focus();
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
  document.querySelector('.landing-nav').classList.toggle('is-open', !expanded)
});
document.querySelectorAll('.landing-nav a').forEach(link => link.addEventListener('click', () => {
  document.querySelector('.landing-nav').classList.remove('is-open');
  menu.setAttribute('aria-expanded', 'false')
}));
const requestedTab = new URLSearchParams(location.search).get('auth');
if (['login', 'register'].includes(requestedTab)) {
  setTab(requestedTab);
  dialog.showModal();
}
