const textSizeToggle = document.querySelector('#text-size-toggle');
const dialog = document.querySelector('#idle-dialog');
const countdown = document.querySelector('#idle-countdown');
const continueButton = document.querySelector('#continue-session');
let lastActivity = Date.now();
let dialogOpenedAt = null;
let previousFocus = null;
const speechStatus = document.querySelector('#speech-status');
const speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
let pendingSpeech = null;

function announceMenu(link, event) {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();

    if (pendingSpeech) {
        pendingSpeech.cancelled = true;
        clearTimeout(pendingSpeech.timeout);
    }
    if (speechSupported) window.speechSynthesis.cancel();

    const request = { cancelled: false, timeout: null, completed: false };
    pendingSpeech = request;
    const navigate = () => {
        if (!request.cancelled) window.location.assign(link.href);
    };
    const failed = () => {
        if (request.cancelled || request.completed) return;
        request.completed = true;
        clearTimeout(request.timeout);
        if (speechSupported) window.speechSynthesis.cancel();
        if (speechStatus) speechStatus.textContent = 'Suara tidak tersedia di perangkat ini. Membuka menu tanpa suara.';
        request.timeout = setTimeout(navigate, 1200);
    };

    if (!speechSupported) {
        failed();
        return;
    }

    const utterance = new SpeechSynthesisUtterance(`Menu ${link.dataset.menuVoice.replaceAll('&', 'dan')}`);
    utterance.lang = 'id-ID';
    utterance.rate = 1;
    const voices = window.speechSynthesis.getVoices();
    const indonesianVoice = voices.find((voice) => /^id(-|_)/i.test(voice.lang) && voice.localService)
        ?? voices.find((voice) => /^id(-|_)/i.test(voice.lang));
    if (indonesianVoice) utterance.voice = indonesianVoice;
    if (speechStatus) speechStatus.textContent = `Membuka menu ${link.dataset.menuVoice}…`;
    utterance.onend = () => {
        if (request.cancelled || request.completed) return;
        request.completed = true;
        clearTimeout(request.timeout);
        navigate();
    };
    utterance.onerror = failed;
    request.timeout = setTimeout(failed, 6000);
    window.speechSynthesis.speak(utterance);
}

document.querySelectorAll('a[data-menu-voice]').forEach((link) => {
    link.addEventListener('click', (event) => announceMenu(link, event));
});

textSizeToggle?.addEventListener('click', () => {
    const enlarged = document.documentElement.classList.toggle('large-text');
    textSizeToggle.setAttribute('aria-pressed', String(enlarged));
});

function resumeSession() {
    lastActivity = Date.now();
    dialogOpenedAt = null;
    dialog?.close();
    previousFocus?.focus();
}

continueButton?.addEventListener('click', resumeSession);
dialog?.addEventListener('cancel', (event) => {
    event.preventDefault();
    resumeSession();
});

['pointerdown', 'keydown', 'input', 'scroll'].forEach((eventName) => {
    document.addEventListener(eventName, () => {
        if (!dialog?.open) lastActivity = Date.now();
    }, { passive: true, capture: true });
});

function updateKiosk() {
    const now = new Date();
    const clock = document.querySelector('#kiosk-clock');
    const date = document.querySelector('#kiosk-date');
    if (clock) {
        clock.textContent = `${new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(now).replaceAll('.', ':')} WIB`;
        clock.dateTime = now.toISOString();
    }
    if (date) {
        date.textContent = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'long', year: 'numeric' }).format(now);
    }

    if (dialog?.open && dialogOpenedAt !== null) {
        const remaining = Math.max(0, 20 - Math.floor((Date.now() - dialogOpenedAt) / 1000));
        if (countdown) countdown.textContent = String(remaining);
        if (remaining === 0) window.location.replace('kiosk.php');
    } else if (dialog && Date.now() - lastActivity >= 120000) {
        previousFocus = document.activeElement;
        dialogOpenedAt = Date.now();
        if (countdown) countdown.textContent = '20';
        dialog.showModal();
        continueButton?.focus();
    }
}

updateKiosk();
setInterval(updateKiosk, 1000);
