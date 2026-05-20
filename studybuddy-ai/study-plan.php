<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Smart Study Plan';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'generate';

    if ($action === 'generate') {
        $studyInput = trim($_POST['study_input'] ?? '');

        if ($studyInput === '') {
            $errors[] = 'Ceritakan dulu tugas, deadline, dan energimu hari ini.';
        }

        if (!$errors) {
            $plan = generateStudyPlan($studyInput);

            $stmt = $pdo->prepare('INSERT INTO study_plans (user_input, plan_result) VALUES (:user_input, :plan_result)');
            $stmt->execute([
                ':user_input' => $studyInput,
                ':plan_result' => $plan,
            ]);

            redirect('study-plan.php');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM study_plans WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        redirect('study-plan.php');
    }

    if ($action === 'clear') {
        $pdo->exec('DELETE FROM study_plans');
        redirect('study-plan.php');
    }
}

$stmt = $pdo->prepare('SELECT id, user_input, plan_result, created_at FROM study_plans ORDER BY created_at DESC, id DESC LIMIT 8');
$stmt->execute();
$plans = $stmt->fetchAll();
$latestPlan = $plans[0] ?? null;

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-panel planner-hero">
    <div>
        <span class="eyebrow">Smart Study Plan Generator</span>
        <h2>Drop your chaos, get a calm plan.</h2>
        <p>Tulis tugas, deadline, dan kondisi energimu. StudyBuddy akan bikin rencana belajar yang pintar, tapi tetap manusiawi dan tidak maksa.</p>
        <div class="hero-badges" aria-label="Sinyal yang dibaca StudyBuddy">
            <span>deadline</span>
            <span>energy level</span>
            <span>tiny win</span>
        </div>
        <div class="hero-actions">
            <a class="button primary" href="#planForm">Plan My Study</a>
            <a class="button" href="index.php#focusTimer">Start Focus Session</a>
        </div>
    </div>
    <div class="ai-buddy-card" aria-hidden="true">
        <div class="buddy-face">SB</div>
        <span>Plan mode</span>
    </div>
</section>

<section class="page-grid">
    <article class="card form-card" id="planForm">
        <span class="eyebrow">AI-ish planner</span>
        <h2>Ceritakan kondisi belajarmu</h2>

        <?php if ($errors): ?>
            <div class="alert error"><?= e($errors[0]); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" novalidate>
            <input type="hidden" name="action" value="generate">

            <label for="study_input">Tugas, deadline, dan energi hari ini</label>
            <textarea id="study_input" name="study_input" rows="7" placeholder="Contoh: Aku punya tugas web deadline besok, tugas database 3 hari lagi, dan aku lagi capek." required></textarea>

            <div class="prompt-chips" aria-label="Contoh study plan cepat">
                <button type="button" data-fill-target="study_input" data-fill-message="Aku punya tugas web deadline besok, tugas database 3 hari lagi, dan aku lagi capek.">Web + database</button>
                <button type="button" data-fill-target="study_input" data-fill-message="Skripsi revisi bab 3, bimbingan minggu ini, aku bingung mulai dari mana.">Skripsi mode</button>
                <button type="button" data-fill-target="study_input" data-fill-message="Deadline presentasi hari ini dan tugas laporan numpuk.">Deadline mode</button>
            </div>

            <button class="button primary" type="submit">Generate Study Plan</button>
        </form>
    </article>

    <article class="card highlight-card">
        <span class="card-icon">&#9889;</span>
        <h2>Latest Plan</h2>

        <?php if ($latestPlan): ?>
            <div class="plan-result">
                <p class="plan-prompt"><?= e($latestPlan['user_input']); ?></p>
                <div class="studybuddy-says"><?= nl2br(e($latestPlan['plan_result'])); ?></div>
                <small><?= e(formatDateTime($latestPlan['created_at'])); ?></small>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <strong>Belum ada study plan.</strong>
                <p>Tulis kondisi belajarmu dulu, nanti StudyBuddy bantu pecah jadi langkah kecil.</p>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="card">
    <div class="section-heading compact">
        <div>
            <span class="eyebrow">History</span>
            <h2>Study plan terbaru</h2>
        </div>
        <?php if ($plans): ?>
            <form method="post" onsubmit="return confirm('Clear semua study plan?');">
                <input type="hidden" name="action" value="clear">
                <button class="button small danger" type="submit">Clear All</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$plans): ?>
        <div class="empty-state">
            <strong>Study plan masih kosong.</strong>
            <p>Mulai dari satu cerita kecil: tugas apa yang paling bikin kepikiran?</p>
        </div>
    <?php else: ?>
        <div class="plan-list">
            <?php foreach ($plans as $plan): ?>
                <article class="plan-item">
                    <div>
                        <span class="badge">AI Plan</span>
                        <h3><?= e($plan['user_input']); ?></h3>
                        <p><?= nl2br(e($plan['plan_result'])); ?></p>
                        <small><?= e(formatDateTime($plan['created_at'])); ?></small>
                    </div>
                    <form method="post" onsubmit="return confirm('Hapus study plan ini?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $plan['id']; ?>">
                        <button class="button small danger" type="submit">Hapus</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
