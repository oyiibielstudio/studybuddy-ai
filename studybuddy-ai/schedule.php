<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Manual Study Plan';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $subject = trim($_POST['subject'] ?? '');
        $studyTime = trim($_POST['study_time'] ?? '');
        $target = trim($_POST['target'] ?? '');

        if ($subject === '') {
            $errors[] = 'Mata kuliah atau topik tidak boleh kosong.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO schedules (subject, study_time, target) VALUES (:subject, :study_time, :target)');
            $stmt->execute([
                ':subject' => $subject,
                ':study_time' => $studyTime !== '' ? $studyTime : null,
                ':target' => $target !== '' ? $target : null,
            ]);

            redirect('schedule.php');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM schedules WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        redirect('schedule.php');
    }
}

$stmt = $pdo->prepare('SELECT id, subject, study_time, target, created_at FROM schedules ORDER BY created_at DESC, id DESC');
$stmt->execute();
$schedules = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-intro intro-schedule">
    <div>
        <span class="eyebrow">Manual Study Plan</span>
        <h2>Susun sesi belajar yang realistis.</h2>
        <p>Kalau Smart Plan terasa terlalu otomatis, halaman ini jadi papan agenda manual buat topik, waktu, dan target kecil.</p>
    </div>
    <div class="intro-meter">
        <strong><?= count($schedules); ?></strong>
        <span>sesi tersimpan</span>
    </div>
</section>

<section class="page-grid">
    <article class="card form-card">
        <span class="eyebrow">Manual Plan</span>
        <h2>Rancang sesi belajar</h2>

        <?php if ($errors): ?>
            <div class="alert error"><?= e($errors[0]); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" novalidate>
            <input type="hidden" name="action" value="add">

            <label for="subject">Mata kuliah/topik</label>
            <input type="text" id="subject" name="subject" placeholder="Contoh: Struktur Data - Graph" required>

            <label for="study_time">Waktu belajar</label>
            <input type="text" id="study_time" name="study_time" placeholder="Contoh: Selasa, 19.00 - 20.30">

            <label for="target">Target belajar</label>
            <textarea id="target" name="target" rows="4" placeholder="Contoh: Pahami BFS dan DFS, lalu kerjakan 3 soal latihan."></textarea>

            <button class="button primary" type="submit">Simpan Sekarang</button>
        </form>
    </article>

    <article class="card">
        <div class="section-heading compact">
            <div>
                <span class="eyebrow">Study Plan</span>
                <h2>Sesi belajar tersimpan</h2>
            </div>
            <span class="badge"><?= count($schedules); ?> jadwal</span>
        </div>

        <?php if (!$schedules): ?>
            <div class="empty-state">
                <strong>Belum ada jadwal belajar.</strong>
                <p>Jadwal belajar masih kosong. Bikin satu sesi kecil dulu yuk.</p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($schedules as $schedule): ?>
                    <article class="timeline-item">
                        <div class="timeline-dot" aria-hidden="true"></div>
                        <div>
                            <h3><?= e($schedule['subject']); ?></h3>
                            <p><?= e($schedule['target'] ?: 'Target belum diisi.'); ?></p>
                            <small><?= e($schedule['study_time'] ?: 'Waktu belum diatur'); ?></small>
                        </div>
                        <form method="post" onsubmit="return confirm('Hapus jadwal ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $schedule['id']; ?>">
                            <button class="button small danger" type="submit">Hapus</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
