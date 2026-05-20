const menuToggle = document.querySelector('#menuToggle');
const sidebar = document.querySelector('#sidebar');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

requestAnimationFrame(() => {
    document.body.classList.add('is-loaded');
});

document.querySelectorAll('.main-content > *, .card, .stat-card, .shortcut-card, .notice-card, .hero-panel, .dashboard-hero, .page-intro, .studio-panel, .showcase-lane').forEach((item, index) => {
    item.style.setProperty('--reveal-index', String(index));
});

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        const clickedOutside = !sidebar.contains(target) && !menuToggle.contains(target);

        if (clickedOutside && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });
}

document.querySelectorAll('[data-fill-message]').forEach((chip) => {
    chip.addEventListener('click', () => {
        const targetId = chip.dataset.fillTarget || 'message';
        const textarea = document.querySelector(`#${targetId}`);

        if (!textarea) {
            return;
        }

        textarea.value = chip.dataset.fillMessage || '';
        textarea.focus();
        textarea.classList.remove('field-pop');
        void textarea.offsetWidth;
        textarea.classList.add('field-pop');
    });
});

const timerDisplay = document.querySelector('#timerDisplay');
const timerRing = document.querySelector('#timerRing');
const timerMode = document.querySelector('#timerMode');
const timerHint = document.querySelector('#timerHint');
const startTimer = document.querySelector('#startTimer');
const pauseTimer = document.querySelector('#pauseTimer');
const resetTimer = document.querySelector('#resetTimer');
const focusStreak = document.querySelector('#focusStreak');

if (timerDisplay && timerRing && timerMode && startTimer && pauseTimer && resetTimer) {
    const durations = {
        focus: 25 * 60,
        break: 5 * 60,
    };
    let mode = 'focus';
    let remaining = durations.focus;
    let intervalId = null;
    let completedSessions = Number(localStorage.getItem('studybuddy_focus_streak') || '0');

    const renderTimer = () => {
        const minutes = Math.floor(remaining / 60).toString().padStart(2, '0');
        const seconds = (remaining % 60).toString().padStart(2, '0');
        const duration = durations[mode];
        const progress = Math.round(((duration - remaining) / duration) * 100);

        timerDisplay.textContent = `${minutes}:${seconds}`;
        timerRing.style.setProperty('--timer-progress', `${progress}%`);
        timerMode.textContent = mode === 'focus' ? 'Focus' : 'Break';

        if (timerHint) {
            timerHint.textContent = mode === 'focus'
                ? 'Start kecil dulu. 25 menit cukup buat bikin progress nyata.'
                : 'Break 5 menit. Stretch, minum air, jauhkan layar sebentar.';
        }

        if (focusStreak) {
            focusStreak.textContent = String(completedSessions);
        }
    };

    const switchMode = () => {
        if (mode === 'focus') {
            completedSessions += 1;
            localStorage.setItem('studybuddy_focus_streak', String(completedSessions));
            mode = 'break';
        } else {
            mode = 'focus';
        }

        remaining = durations[mode];
        renderTimer();
    };

    const stopTimer = () => {
        window.clearInterval(intervalId);
        intervalId = null;
    };

    startTimer.addEventListener('click', () => {
        if (intervalId) {
            return;
        }

        startTimer.textContent = 'Running';
        intervalId = window.setInterval(() => {
            remaining -= 1;

            if (remaining <= 0) {
                switchMode();
            }

            renderTimer();
        }, 1000);
    });

    pauseTimer.addEventListener('click', () => {
        stopTimer();
        startTimer.textContent = 'Resume';
    });

    resetTimer.addEventListener('click', () => {
        stopTimer();
        mode = 'focus';
        remaining = durations.focus;
        startTimer.textContent = 'Start';
        renderTimer();
    });

    renderTimer();
}

document.querySelectorAll('a[href$=".php"]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const isModifiedClick = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
        const isSamePage = link.getAttribute('href') === window.location.pathname.split('/').pop();

        if (prefersReducedMotion || isModifiedClick || isSamePage) {
            return;
        }

        event.preventDefault();
        document.body.classList.add('is-leaving');

        window.setTimeout(() => {
            window.location.href = link.href;
        }, 160);
    });
});

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.dataset.originalText = submitButton.textContent || '';
            submitButton.textContent = 'Memproses...';
            submitButton.disabled = true;
        }
    });
});
