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

const buddyHero = document.querySelector('#buddyHero');
const buddyEmotionBadge = document.querySelector('#buddyEmotionBadge');
const buddyEmotionCopy = document.querySelector('#buddyEmotionCopy');
const emotionChips = document.querySelectorAll('[data-emotion-chip]');
const buddyEmotions = [
    {
        name: 'joy',
        copy: 'StudyBuddy lagi happy: siap nemenin kamu mulai pelan-pelan.',
    },
    {
        name: 'anger',
        copy: 'Anger vibe: bukan marah ke kamu, dia lagi galak sama deadline yang suka nyerobot tenangmu.',
    },
    {
        name: 'fear',
        copy: 'Fear vibe: dia juga kadang deg-degan, jadi kita kecilkan langkahnya biar aman.',
    },
    {
        name: 'disgust',
        copy: 'Disgust vibe: dia ilfeel sama tugas yang numpuk, tapi tetap bantu pilah satu-satu.',
    },
    {
        name: 'sadness',
        copy: 'Sadness vibe: pelan dulu. Hari berat tetap boleh punya progress kecil.',
    },
    {
        name: 'envy',
        copy: 'Envy vibe: iri dikit sama orang yang sudah selesai, lalu balik fokus ke ritme sendiri.',
    },
    {
        name: 'embarrassment',
        copy: 'Embarrassment vibe: malu-malu tapi tetap muncul buat nemenin kamu mulai lagi.',
    },
    {
        name: 'anxiety',
        copy: 'Anxiety vibe: napas dulu, satu tab saja, satu langkah saja.',
    },
    {
        name: 'ennui',
        copy: 'Ennui vibe: lagi flat, jadi targetnya dibuat super ringan biar tetap nyaman.',
    },
];
let buddyEmotionIndex = 0;

const setBuddyEmotion = (emotionName, burst = false) => {
    if (!buddyHero) {
        return;
    }

    const emotion = buddyEmotions.find((item) => item.name === emotionName) || buddyEmotions[0];
    buddyHero.dataset.emotion = emotion.name;

    if (buddyEmotionBadge) {
        buddyEmotionBadge.textContent = emotion.name;
    }

    if (buddyEmotionCopy) {
        buddyEmotionCopy.textContent = emotion.copy;
    }

    emotionChips.forEach((chip) => {
        chip.classList.toggle('active', chip.dataset.emotionChip === emotion.name);
    });

    if (burst) {
        buddyHero.classList.remove('is-excited');
        void buddyHero.offsetWidth;
        buddyHero.classList.add('is-excited');

        window.setTimeout(() => {
            buddyHero.classList.remove('is-excited');
        }, 980);
    }
};

if (buddyHero) {
    setBuddyEmotion('joy');

    buddyHero.addEventListener('click', () => {
        buddyEmotionIndex = (buddyEmotionIndex + 1) % buddyEmotions.length;
        setBuddyEmotion(buddyEmotions[buddyEmotionIndex].name, true);
    });

    window.setInterval(() => {
        if (document.hidden) {
            return;
        }

        buddyEmotionIndex = (buddyEmotionIndex + 1) % buddyEmotions.length;
        setBuddyEmotion(buddyEmotions[buddyEmotionIndex].name, false);
    }, 5200);
}

emotionChips.forEach((chip) => {
    chip.addEventListener('click', () => {
        const emotionName = chip.dataset.emotionChip || 'joy';
        const nextIndex = buddyEmotions.findIndex((item) => item.name === emotionName);

        buddyEmotionIndex = nextIndex >= 0 ? nextIndex : 0;
        setBuddyEmotion(emotionName, true);
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

document.querySelectorAll('.button, .shortcut-card, .showcase-lane').forEach((element) => {
    element.addEventListener('pointerdown', () => {
        element.classList.remove('is-pressing');
        void element.offsetWidth;
        element.classList.add('is-pressing');
    });

    element.addEventListener('animationend', () => {
        element.classList.remove('is-pressing');
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
