<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'To-do';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        if ($title === '') {
            $errors[] = 'Nama tugas tidak boleh kosong.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO todos (title, course, deadline) VALUES (:title, :course, :deadline)');
            $stmt->execute([
                ':title' => $title,
                ':course' => $course !== '' ? $course : null,
                ':deadline' => $deadline !== '' ? $deadline : null,
            ]);

            redirect('todo.php');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            // Status dibalik dari nilai di database agar tidak bergantung pada input tersembunyi.
            $stmt = $pdo->prepare("
                UPDATE todos
                SET status = CASE WHEN status = 'selesai' THEN 'belum' ELSE 'selesai' END
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
        }

        redirect('todo.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM todos WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        redirect('todo.php');
    }
}

$stmt = $pdo->prepare('SELECT id, title, course, deadline, status, created_at FROM todos ORDER BY deadline IS NULL, deadline ASC, created_at DESC');
$stmt->execute();
$todos = $stmt->fetchAll();
$doneCount = count(array_filter($todos, fn (array $todo): bool => $todo['status'] === 'selesai'));
$todoProgress = count($todos) > 0 ? (int) round(($doneCount / count($todos)) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-intro intro-todo">
    <div>
        <span class="eyebrow">Task Flow</span>
        <h2>Bikin tugas terlihat lebih kecil.</h2>
        <p>Catat dulu, baru rapikan. StudyBuddy bantu kamu melihat mana yang pending, done, dan urgent tanpa bikin kepala makin penuh.</p>
    </div>
    <div class="intro-meter">
        <strong><?= $todoProgress; ?>%</strong>
        <span>progress tugas</span>
    </div>
</section>

<section class="page-grid">
    <article class="card form-card">
        <span class="eyebrow">Tambah Tugas</span>
        <h2>Tambah satu tugas dulu</h2>

        <?php if ($errors): ?>
            <div class="alert error"><?= e($errors[0]); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form" novalidate>
            <input type="hidden" name="action" value="add">

            <label for="title">Nama tugas</label>
            <input type="text" id="title" name="title" placeholder="Contoh: Review jurnal AI" required>

            <label for="course">Mata kuliah</label>
            <input type="text" id="course" name="course" placeholder="Contoh: Kecerdasan Buatan">

            <label for="deadline">Deadline</label>
            <input type="date" id="deadline" name="deadline">

            <button class="button primary" type="submit">Simpan Sekarang</button>
        </form>
    </article>

    <article class="card">
        <div class="section-heading compact">
            <div>
                <span class="eyebrow">Task board</span>
                <h2>Prioritas hari ini</h2>
            </div>
            <span class="badge"><?= count($todos); ?> tugas</span>
        </div>

        <div class="mini-progress" aria-label="Progress tugas">
            <span style="width: <?= $todoProgress; ?>%;"></span>
        </div>
        <p class="muted progress-copy"><?= $doneCount; ?> done, <?= count($todos) - $doneCount; ?> tasks left today</p>

        <?php if (!$todos): ?>
            <div class="empty-state">
                <strong>Belum ada tugas.</strong>
                <p>Yuk tambah satu dulu biar harimu lebih terarah &#10022;</p>
            </div>
        <?php else: ?>
            <div class="item-list">
                <?php foreach ($todos as $todo): ?>
                    <?php
                    $deadlineTime = $todo['deadline'] ? strtotime($todo['deadline']) : false;
                    $isUrgent = $todo['status'] === 'belum' && $deadlineTime && $deadlineTime <= strtotime('+1 day');
                    ?>
                    <div class="task-item <?= $todo['status'] === 'selesai' ? 'done' : ''; ?>">
                        <div>
                            <span class="status-pill <?= e($todo['status']); ?>"><?= $todo['status'] === 'selesai' ? 'Done' : 'Pending'; ?></span>
                            <?php if ($isUrgent): ?>
                                <span class="status-pill urgent">Urgent</span>
                            <?php endif; ?>
                            <h3><?= e($todo['title']); ?></h3>
                            <p><?= e($todo['course'] ?: 'Mata kuliah belum diisi'); ?></p>
                            <small>Deadline: <?= e(formatDateOnly($todo['deadline'])); ?></small>
                        </div>
                        <div class="row-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $todo['id']; ?>">
                                <button class="button small" type="submit">
                                    <?= $todo['status'] === 'selesai' ? 'Tandai belum' : 'Tandai selesai'; ?>
                                </button>
                            </form>
                            <form method="post" onsubmit="return confirm('Hapus tugas ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $todo['id']; ?>">
                                <button class="button small danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
