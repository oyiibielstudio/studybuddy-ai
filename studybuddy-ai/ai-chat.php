<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Curhat AI';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $message = trim($_POST['message'] ?? '');

        if ($message === '') {
            $errors[] = 'Isi curhat tidak boleh kosong.';
        }

        if (!$errors) {
            $response = generateAIResponse($message);

            $stmt = $pdo->prepare('INSERT INTO chat_logs (user_message, ai_response) VALUES (:user_message, :ai_response)');
            $stmt->execute([
                ':user_message' => $message,
                ':ai_response' => $response,
            ]);

            redirect('ai-chat.php');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM chat_logs WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        redirect('ai-chat.php');
    }

    if ($action === 'clear') {
        $pdo->exec('DELETE FROM chat_logs');
        redirect('ai-chat.php');
    }
}

$stmt = $pdo->prepare('SELECT id, user_message, ai_response, created_at FROM chat_logs ORDER BY created_at DESC, id DESC LIMIT 15');
$stmt->execute();
$chatLogs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="notice-card compact-note">
    <strong>Catatan penting</strong>
    <p>StudyBuddy AI bukan pengganti layanan konseling profesional. Aplikasi ini hanya memberikan dukungan ringan untuk membantu mahasiswa belajar lebih terarah.</p>
</section>

<section class="ai-coach-strip">
    <div>
        <span class="eyebrow">AI Coach Mode</span>
        <h2>Kasih konteks, StudyBuddy bikin langkahnya.</h2>
    </div>
    <div class="coach-signals" aria-label="Kemampuan AI lokal">
        <span>Deteksi deadline</span>
        <span>Energi belajar</span>
        <span>Prioritas tugas</span>
        <span>Plan 25 menit</span>
    </div>
</section>

<section class="page-grid">
    <article class="card form-card">
        <span class="eyebrow">Curhat ringan</span>
        <h2>Ngobrol sama StudyBuddy</h2>

        <?php if ($errors): ?>
            <div class="alert error"><?= e($errors[0]); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" novalidate>
            <input type="hidden" name="action" value="add">
            <label for="message">Pesan kamu</label>
            <textarea id="message" name="message" rows="7" placeholder="Contoh: Aku punya tugas web deadline besok, database 3 hari lagi, dan aku capek banget." required></textarea>

            <div class="prompt-chips" aria-label="Contoh curhat cepat">
                <button type="button" data-fill-message="Aku cape banget sama skripsi aku, revisi bab 3 belum selesai dan bimbingan minggu ini.">Cape skripsi</button>
                <button type="button" data-fill-message="Tugas web deadline besok, tugas database 3 hari lagi, dan aku lagi capek.">Deadline numpuk</button>
                <button type="button" data-fill-message="Aku ga tau harus mulai dari mana, tugas laporan numpuk dan deadline dekat.">Ga tau mulai</button>
                <button type="button" data-fill-message="Hari ini aku semangat banget, mau nyicil presentasi dan latihan 25 menit.">Lagi semangat</button>
            </div>

            <button class="button primary" type="submit">Kirim ke StudyBuddy</button>
        </form>
    </article>

    <article class="card chat-card">
        <div class="section-heading compact">
            <div>
                <span class="eyebrow">Riwayat chat</span>
                <h2>StudyBuddy Says</h2>
            </div>
            <?php if ($chatLogs): ?>
                <form method="post" onsubmit="return confirm('Clear semua chat?');">
                    <input type="hidden" name="action" value="clear">
                    <button class="button small danger" type="submit">Clear All</button>
                </form>
            <?php else: ?>
                <span class="badge">0 pesan</span>
            <?php endif; ?>
        </div>

        <?php if (!$chatLogs): ?>
            <div class="empty-state">
                <strong>Belum ada chat.</strong>
                <p>Tulis curhat ringan dulu. StudyBuddy akan bantu bikin langkah belajar yang lebih tenang.</p>
            </div>
        <?php else: ?>
            <div class="chat-list">
                <?php foreach ($chatLogs as $chat): ?>
                    <article class="chat-pair">
                        <div class="bubble user">
                            <span>Kamu</span>
                            <p><?= e($chat['user_message']); ?></p>
                        </div>
                        <div class="bubble ai">
                            <span>StudyBuddy AI</span>
                            <p><?= e($chat['ai_response']); ?></p>
                            <small><?= e(formatDateTime($chat['created_at'])); ?></small>
                        </div>
                        <form class="chat-actions" method="post" onsubmit="return confirm('Hapus chat ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $chat['id']; ?>">
                            <button class="button small danger" type="submit">Hapus chat</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
