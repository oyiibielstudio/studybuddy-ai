<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Dashboard';

$totalTodos = (int) $pdo->query('SELECT COUNT(*) FROM todos')->fetchColumn();
$pendingTodos = (int) $pdo->query("SELECT COUNT(*) FROM todos WHERE status = 'belum'")->fetchColumn();
$doneTodos = (int) $pdo->query("SELECT COUNT(*) FROM todos WHERE status = 'selesai'")->fetchColumn();
$progress = $totalTodos > 0 ? (int) round(($doneTodos / $totalTodos) * 100) : 0;
$lastMood = $pdo->query('SELECT mood, note, created_at FROM moods ORDER BY created_at DESC, id DESC LIMIT 1')->fetch();
$latestSchedule = $pdo->query('SELECT subject, study_time, target FROM schedules ORDER BY created_at DESC, id DESC LIMIT 1')->fetch();
$latestPlan = $pdo->query('SELECT user_input, plan_result, created_at FROM study_plans ORDER BY created_at DESC, id DESC LIMIT 1')->fetch();
$urgentTodos = (int) $pdo->query("SELECT COUNT(*) FROM todos WHERE status = 'belum' AND deadline IS NOT NULL AND date(deadline) <= date('now', '+1 day')")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<section class="dashboard-hero">
    <div class="hero-copy">
        <span class="eyebrow">Your cozy AI study companion</span>
        <h2>Hi, ready to study today?</h2>
        <p>StudyBuddy AI bantu kamu mengatur tugas, mood, jadwal belajar, dan motivasi dalam satu ruang belajar digital yang ringan.</p>
        <div class="hero-actions">
            <a class="button primary" href="#focusTimer">Start Focus Session</a>
            <a class="button" href="study-plan.php">Plan My Study</a>
        </div>
    </div>

    <div class="buddy-hero" aria-hidden="true">
        <div class="buddy-sparkle">&#10022;</div>
        <div class="buddy-head">
            <span></span>
            <span></span>
        </div>
        <div class="buddy-book">
            <span>AI</span>
            <span>Study</span>
        </div>
    </div>
</section>

<section class="stats-grid" aria-label="Ringkasan dashboard">
    <article class="stat-card progress-stat">
        <span>Task progress</span>
        <div class="progress-ring" style="--progress: <?= $progress; ?>%;">
            <strong><?= $progress; ?>%</strong>
        </div>
        <small><?= $doneTodos; ?> done, <?= $pendingTodos; ?> tasks left today</small>
    </article>

    <article class="stat-card">
        <span>Pending</span>
        <strong><?= $pendingTodos; ?></strong>
        <small><?= $urgentTodos; ?> urgent near deadline</small>
    </article>

    <article class="stat-card mood-stat">
        <span>Mood terakhir</span>
        <?php if ($lastMood): ?>
            <strong><?= moodEmoji($lastMood['mood']); ?> <?= e($lastMood['mood']); ?></strong>
            <small><?= e(formatDateTime($lastMood['created_at'])); ?></small>
        <?php else: ?>
            <strong>&#128578;</strong>
            <small>Belum check-in hari ini</small>
        <?php endif; ?>
    </article>

    <article class="stat-card">
        <span>Focus streak</span>
        <strong><span id="focusStreak">0</span>x</strong>
        <small>Sesi fokus di browser ini</small>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card focus-card" id="focusTimer">
        <div class="section-heading compact">
            <div>
                <span class="eyebrow">Pomodoro</span>
                <h2>25-min Focus Session</h2>
            </div>
            <span class="badge" id="timerMode">Focus</span>
        </div>

        <div class="timer-shell">
            <div class="timer-ring" id="timerRing" style="--timer-progress: 0;">
                <span id="timerDisplay">25:00</span>
            </div>
            <p id="timerHint">Start kecil dulu. 25 menit cukup buat bikin progress nyata.</p>
        </div>

        <div class="timer-controls">
            <button class="button primary" type="button" id="startTimer">Start</button>
            <button class="button" type="button" id="pauseTimer">Pause</button>
            <button class="button" type="button" id="resetTimer">Reset</button>
        </div>
    </article>

    <article class="card highlight-card">
        <span class="card-icon">&#10022;</span>
        <h2>Daily motivation</h2>
        <p><?= e(moodMotivation($lastMood['mood'] ?? null)); ?></p>
        <?php if (!$lastMood): ?>
            <a class="text-link" href="mood.php">Check-in mood sebentar</a>
        <?php endif; ?>
    </article>

    <article class="card">
        <span class="card-icon">&#9889;</span>
        <h2>Smart Plan preview</h2>
        <?php if ($latestPlan): ?>
            <p><strong><?= e($latestPlan['user_input']); ?></strong></p>
            <p><?= e(strtok($latestPlan['plan_result'], "\n") ?: $latestPlan['plan_result']); ?></p>
            <a class="text-link" href="study-plan.php">Buka full plan</a>
        <?php else: ?>
            <p>Belum ada smart plan. Ceritakan tugas dan deadline-mu, nanti StudyBuddy bantu pecah jadi sesi kecil.</p>
            <a class="text-link" href="study-plan.php">Plan My Study</a>
        <?php endif; ?>
    </article>

    <article class="card">
        <span class="card-icon">&#128214;</span>
        <h2>Next study plan</h2>
        <?php if ($latestSchedule): ?>
            <p><strong><?= e($latestSchedule['subject']); ?></strong></p>
            <p><?= e($latestSchedule['target'] ?: 'Tetapkan target kecil agar sesi belajar lebih jelas.'); ?></p>
            <small><?= e($latestSchedule['study_time'] ?: 'Waktu belum diatur'); ?></small>
        <?php else: ?>
            <p>Jadwal belajar masih kosong. Bikin satu sesi kecil dulu yuk.</p>
            <a class="text-link" href="schedule.php">Buat study plan manual</a>
        <?php endif; ?>
    </article>
</section>

<section class="card about-card">
    <div>
        <span class="eyebrow">About</span>
        <h2>Kenapa StudyBuddy AI?</h2>
    </div>
    <p>StudyBuddy AI membantu mahasiswa mengatur tugas, mood, jadwal belajar, smart study plan, dan curhat ringan tanpa API eksternal. Semua data tetap lokal di SQLite, pas untuk demo kampus yang gampang dijelaskan.</p>
</section>

<section>
    <div class="section-heading">
        <div>
            <span class="eyebrow">Shortcut</span>
            <h2>Mulai dari kebutuhanmu</h2>
        </div>
    </div>

    <div class="shortcut-grid">
        <a class="shortcut-card" href="todo.php">
            <span>&#9997;</span>
            <strong>Tambah Tugas</strong>
            <small>Catat deadline dan status.</small>
        </a>
        <a class="shortcut-card" href="mood.php">
            <span>&#128153;</span>
            <strong>Check-in Mood</strong>
            <small>Gimana perasaanmu sekarang?</small>
        </a>
        <a class="shortcut-card" href="study-plan.php">
            <span>&#9889;</span>
            <strong>Smart Study Plan</strong>
            <small>Bikin rencana belajar otomatis.</small>
        </a>
        <a class="shortcut-card" href="ai-chat.php">
            <span>&#128172;</span>
            <strong>Curhat AI</strong>
            <small>StudyBuddy Says, pelan tapi maju.</small>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
