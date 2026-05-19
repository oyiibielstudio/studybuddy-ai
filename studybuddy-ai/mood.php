<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Mood Tracker';
$errors = [];
$allowedMoods = ['Senang', 'Biasa Saja', 'Lelah', 'Stres', 'Sedih'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mood = trim($_POST['mood'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($mood === '' || !in_array($mood, $allowedMoods, true)) {
        $errors[] = 'Pilih mood yang tersedia.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO moods (mood, note) VALUES (:mood, :note)');
        $stmt->execute([
            ':mood' => $mood,
            ':note' => $note !== '' ? $note : null,
        ]);

        redirect('mood.php');
    }
}

$stmt = $pdo->prepare('SELECT id, mood, note, created_at FROM moods ORDER BY created_at DESC, id DESC LIMIT 12');
$stmt->execute();
$moods = $stmt->fetchAll();
$latestMood = $moods[0] ?? null;

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-grid">
    <article class="card form-card">
        <span class="eyebrow">Check-in Mood</span>
        <h2>Gimana perasaanmu sekarang?</h2>

        <?php if ($errors): ?>
            <div class="alert error"><?= e($errors[0]); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" novalidate>
            <span class="form-label">Pilih mood</span>
            <div class="mood-choice-grid">
                <?php foreach ($allowedMoods as $option): ?>
                    <label class="mood-choice">
                        <input type="radio" name="mood" value="<?= e($option); ?>" required>
                        <span><?= moodEmoji($option); ?></span>
                        <strong><?= e($option); ?></strong>
                    </label>
                <?php endforeach; ?>
            </div>

            <label for="note">Catatan singkat</label>
            <textarea id="note" name="note" rows="4" placeholder="Contoh: Hari ini banyak praktikum, tapi masih bisa lanjut pelan-pelan."></textarea>

            <button class="button primary" type="submit">Simpan Sekarang</button>
        </form>
    </article>

    <article class="card highlight-card">
        <span class="card-icon"><?= moodEmoji($latestMood['mood'] ?? null); ?></span>
        <h2>Motivasi sederhana</h2>
        <p><?= e(moodMotivation($latestMood['mood'] ?? null)); ?></p>
        <?php if (!$latestMood): ?>
            <p class="muted">Belum ada mood hari ini. Yuk mulai check-in sebentar.</p>
        <?php endif; ?>
    </article>
</section>

<section class="notice-card compact-note">
    <strong>Note kecil</strong>
    <p>StudyBuddy AI memberikan dukungan belajar ringan dan bukan pengganti konseling profesional.</p>
</section>

<section class="card">
    <div class="section-heading compact">
        <div>
            <span class="eyebrow">Riwayat</span>
            <h2>Mood terbaru</h2>
        </div>
        <span class="badge"><?= count($moods); ?> catatan</span>
    </div>

    <?php if (!$moods): ?>
        <div class="empty-state">
            <strong>Belum ada riwayat mood.</strong>
            <p>Belum check-in hari ini. Gimana perasaanmu sekarang?</p>
        </div>
    <?php else: ?>
        <div class="mood-grid">
            <?php foreach ($moods as $item): ?>
                <article class="mood-card">
                    <span class="mood-emoji"><?= moodEmoji($item['mood']); ?></span>
                    <div>
                        <h3><?= e($item['mood']); ?></h3>
                        <small><?= e(formatDateTime($item['created_at'])); ?></small>
                        <p><?= e($item['note'] ?: moodMotivation($item['mood'])); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
