const toggle = document.querySelector('.password-toggle');
const password = document.querySelector('#password');
if (toggle && password) {
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Sembunyi' : 'Tunjuk';
        toggle.setAttribute('aria-pressed', String(show));
    });
}
