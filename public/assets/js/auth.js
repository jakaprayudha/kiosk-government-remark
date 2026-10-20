const keyboardToggle = document.querySelector('.keyboard-toggle');
const keyboard = document.querySelector('.touch-keyboard');
const formInputs = [...document.querySelectorAll('.auth-form input:not([type="hidden"])')];
let activeInput = null;
let shifted = false;
let symbolsMode = false;

function setCustomKeyboardMode(enabled) {
    formInputs.forEach((input) => {
        if (enabled) {
            if (!Object.hasOwn(input.dataset, 'nativeInputmode')) {
                input.dataset.nativeInputmode = input.getAttribute('inputmode') ?? '';
            }
            input.setAttribute('inputmode', 'none');
        } else if (Object.hasOwn(input.dataset, 'nativeInputmode')) {
            const originalInputmode = input.dataset.nativeInputmode;
            if (originalInputmode) {
                input.setAttribute('inputmode', originalInputmode);
            } else {
                input.removeAttribute('inputmode');
            }
            delete input.dataset.nativeInputmode;
        }
    });
}

function insertAtCursor(value) {
    if (!activeInput) return;
    const start = activeInput.selectionStart ?? activeInput.value.length;
    const end = activeInput.selectionEnd ?? start;
    activeInput.setRangeText(value, start, end, 'end');
    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
    activeInput.focus({ preventScroll: true });
}

function updateKeyLabels() {
    keyboard?.querySelectorAll('[data-character]').forEach((button) => {
        const character = button.dataset.character;
        const label = shifted ? character.toLocaleUpperCase('id') : character;
        button.textContent = label;
        button.setAttribute('aria-label', label);
    });
}

function addKeyRow(characters) {
    const row = document.createElement('div');
    row.className = 'keyboard-row';
    [...characters].forEach((character) => {
        const button = document.createElement('button');
        button.className = 'keyboard-key';
        button.type = 'button';
        button.dataset.character = character;
        button.textContent = shifted ? character.toLocaleUpperCase('id') : character;
        button.setAttribute('aria-label', character);
        button.addEventListener('pointerdown', (event) => event.preventDefault());
        button.addEventListener('click', () => insertAtCursor(shifted ? character.toLocaleUpperCase('id') : character));
        row.append(button);
    });
    keyboard.append(row);
}

function renderKeyboard() {
    if (!keyboard) return;
    keyboard.replaceChildren();
    const rows = symbolsMode
        ? ['1234567890', '!@#$%^&*()', '-_=+[]{}', ';:\'"`,./?\\|~']
        : ['qwertyuiop', 'asdfghjkl', 'zxcvbnm'];
    rows.forEach(addKeyRow);

    const controls = document.createElement('div');
    controls.className = 'keyboard-row keyboard-controls';
    [
        { label: shifted ? 'Shift aktif' : 'Shift', action: 'shift', className: 'keyboard-action' },
        { label: symbolsMode ? 'ABC' : '123 / simbol', action: 'symbols', className: 'keyboard-action keyboard-mode' },
        { label: 'Spasi', action: 'space', className: 'keyboard-space' },
        { label: '⌫', action: 'backspace', className: 'keyboard-action', aria: 'Hapus karakter' },
        { label: 'Selesai', action: 'done', className: 'keyboard-action keyboard-done' },
    ].forEach(({ label, action, className, aria }) => {
        const button = document.createElement('button');
        button.className = `keyboard-key ${className}`;
        button.type = 'button';
        button.textContent = label;
        button.setAttribute('aria-label', aria ?? label);
        button.addEventListener('pointerdown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            if (action === 'shift') {
                shifted = !shifted;
                updateKeyLabels();
                renderKeyboard();
            } else if (action === 'symbols') {
                symbolsMode = !symbolsMode;
                shifted = false;
                renderKeyboard();
            } else if (action === 'space') {
                insertAtCursor(' ');
            } else if (action === 'backspace' && activeInput) {
                const start = activeInput.selectionStart ?? activeInput.value.length;
                const end = activeInput.selectionEnd ?? start;
                const deleteStart = start === end ? Math.max(0, start - 1) : start;
                activeInput.setRangeText('', deleteStart, end, 'end');
                activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                activeInput.focus({ preventScroll: true });
            } else if (action === 'done') {
                keyboard.hidden = true;
                keyboardToggle?.setAttribute('aria-expanded', 'false');
                setCustomKeyboardMode(false);
                activeInput?.focus({ preventScroll: true });
            }
        });
        controls.append(button);
    });
    keyboard.append(controls);
}

formInputs.forEach((input) => {
    input.addEventListener('focus', () => {
        activeInput = input;
    });
});

keyboardToggle?.addEventListener('click', () => {
    const willOpen = keyboard.hidden;
    keyboard.hidden = !willOpen;
    keyboardToggle.setAttribute('aria-expanded', String(willOpen));
    setCustomKeyboardMode(willOpen);
    if (willOpen) {
        if (!activeInput) {
            activeInput = formInputs[0] ?? null;
        }
        activeInput?.focus({ preventScroll: true });
        renderKeyboard();
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const passwordInput = document.getElementById(button.dataset.passwordToggle);
        if (!(passwordInput instanceof HTMLInputElement)) return;
        const showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        button.textContent = showPassword ? 'Sembunyi' : 'Lihat';
        button.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
        passwordInput.focus({ preventScroll: true });
    });
});
