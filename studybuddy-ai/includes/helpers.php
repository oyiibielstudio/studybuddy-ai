<?php
declare(strict_types=1);

function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function currentPage(): string
{
    return basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

function isActive(string $page): string
{
    return currentPage() === $page ? 'active' : '';
}

function moodEmoji(?string $mood): string
{
    return match ($mood) {
        'Senang' => '&#128516;',
        'Biasa Saja' => '&#128528;',
        'Lelah' => '&#128564;',
        'Stres' => '&#128565;',
        'Sedih' => '&#128532;',
        default => '&#128578;',
    };
}

function moodMotivation(?string $mood): string
{
    return match ($mood) {
        'Senang' => 'Energi bagus hari ini. Gunakan untuk menyelesaikan satu hal penting dulu, lalu beri ruang untuk menikmati prosesnya.',
        'Biasa Saja' => 'Hari yang biasa tetap bisa produktif. Pilih satu target kecil dan mulai pelan-pelan.',
        'Lelah' => 'Tidak apa-apa untuk rehat sebentar. Coba istirahat 10 menit, minum air, lalu lanjutkan dari bagian paling ringan.',
        'Stres' => 'Tarik napas pelan, rapikan prioritas, lalu mulai dari langkah kecil yang paling mungkin dikerjakan sekarang.',
        'Sedih' => 'Terima perasaanmu dulu. Kalau siap, coba satu aktivitas belajar ringan supaya hari ini tetap punya kemajuan kecil.',
        default => 'Belum ada mood hari ini. Yuk mulai check-in sebentar agar StudyBuddy bisa memberi motivasi yang lebih pas.',
    };
}

function generateAIResponse(string $message): string
{
    $text = function_exists('mb_strtolower')
        ? mb_strtolower($message, 'UTF-8')
        : strtolower($message);

    $text = normalizeMessage($text);

    // Rule-based response dibuat akrab dengan bahasa chat mahasiswa dan tetap tanpa diagnosis.
    if (
        containsAny($text, ['skripsi', 'tesis', 'revisi', 'dosen pembimbing', 'bimbingan', 'sidang'])
        && containsAny($text, ['capek', 'cape', 'capai', 'lelah', 'burnout', 'tepar', 'drop'])
    ) {
        return 'Aduh, capek sama skripsi itu valid banget. Ambil jeda 10 menit dulu, lalu balik dengan target kecil: buka dokumen, pilih 1 bagian revisi, dan kerjakan 25 menit saja. Kamu tidak perlu menaklukkan semuanya sekali duduk.';
    }

    if (containsAny($text, ['skripsi', 'tesis', 'revisi', 'dosen pembimbing', 'bimbingan', 'sidang'])) {
        return 'Skripsi memang bisa terasa berat, apalagi kalau kepikiran terus. Coba pilih satu target mini dulu: buka file terakhir, tulis 3 poin yang mau direvisi, lalu kerjakan 25 menit. Fokusnya bukan selesai semua hari ini, tapi bikin satu langkah nyata.';
    }

    if (containsAny($text, ['capek', 'cape', 'capai', 'lelah', 'burnout', 'tepar', 'drop'])) {
        return 'Wajar kalau kamu merasa capek. Ambil jeda 10-15 menit dulu, minum air, jauhkan layar sebentar, lalu balik dengan target paling kecil. Belajar tetap jalan meski pelan.';
    }

    if (containsAny($text, ['tugas', 'deadline', 'numpuk'])) {
        return 'Kalau tugas terasa numpuk, jangan dilihat sebagai satu gunung besar. Urutkan deadline terdekat, pecah jadi langkah kecil, lalu mulai dari bagian pertama yang bisa selesai dalam 25 menit.';
    }

    if (containsAny($text, ['stres', 'tertekan'])) {
        return 'Aku dengar kamu lagi merasa berat. Tarik napas pelan beberapa kali, kasih diri kamu jeda sebentar, lalu mulai dari satu langkah kecil yang paling mungkin dilakukan sekarang.';
    }

    if (containsAny($text, ['malas', 'bingung', 'gatau', 'ga tau', 'nggak tau', 'tidak tau', 'tidak tahu', 'mulai dari mana'])) {
        return 'Kalau lagi bingung, mulai dari target super kecil dulu. Tulis 1 hal yang harus dibereskan, buka materinya, lalu timer 10-25 menit. Setelah mulai, biasanya arahnya lebih kebaca.';
    }

    if (containsAny($text, ['semangat', 'senang', 'happy', 'lega', 'bangga'])) {
        return 'Love that energy. Pakai momentum ini buat menyelesaikan satu prioritas utama, lalu kasih apresiasi kecil ke diri sendiri. Progress kecil tetap valid.';
    }

    return 'Makasih sudah cerita. Coba pilih satu hal yang paling mengganggu pikiranmu sekarang, lalu ubah jadi langkah kecil yang bisa dikerjakan 10 menit. Aku bantu kamu tetap pelan tapi terarah.';
}

function normalizeMessage(string $text): string
{
    $replacements = [
        'ngga' => 'nggak',
        'gak' => 'ga',
        'enggak' => 'nggak',
        'capee' => 'cape',
        'capekk' => 'capek',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

function generateStudyPlan(string $input): string
{
    $text = function_exists('mb_strtolower')
        ? mb_strtolower($input, 'UTF-8')
        : strtolower($input);

    $text = normalizeMessage($text);
    $isTired = containsAny($text, ['capek', 'cape', 'lelah', 'burnout', 'tepar', 'drop']);
    $isUrgent = containsAny($text, ['hari ini', 'malam ini', 'besok', 'deadline dekat', 'deadline mepet']);
    $hasManyTasks = containsAny($text, ['numpuk', 'banyak', 'beberapa', 'dan']);
    $mainTask = detectMainStudyTask($text);
    $secondTask = detectSecondaryStudyTask($text, $mainTask);

    $priority = $isUrgent
        ? "Prioritas hari ini: kerjakan {$mainTask} dulu karena deadline-nya paling dekat."
        : "Prioritas hari ini: mulai dari {$mainTask} supaya ada progress yang kelihatan.";

    $energyRule = $isTired
        ? 'Mode energi rendah: pakai 1 sesi fokus 25 menit, istirahat 5 menit, lalu evaluasi. Jangan paksa maraton.'
        : 'Mode fokus normal: pakai 2 sesi fokus 25 menit dengan jeda 5 menit di antaranya.';

    $secondStep = $secondTask
        ? "lanjut {$secondTask} dari bagian yang paling mudah dulu."
        : 'lanjutkan bagian paling mudah atau paling jelas dulu agar momentum tetap jalan.';

    if ($hasManyTasks && !$secondTask) {
        $secondStep = 'Karena tugasnya terasa banyak, tulis semua tugas dalam 3 menit lalu pilih satu yang paling dekat deadline-nya.';
    }

    return implode("\n", [
        $priority,
        $energyRule,
        'Rencana 45 menit:',
        '1. 5 menit: buka instruksi dan tandai bagian yang harus dikerjakan.',
        "2. 25 menit: fokus ke {$mainTask} tanpa pindah tab.",
        '3. 5 menit: istirahat, minum air, dan jauhkan layar sebentar.',
        "4. 10 menit: {$secondStep}",
        'Tiny win: sebelum berhenti, tulis next step paling kecil untuk sesi berikutnya.',
    ]);
}

function detectMainStudyTask(string $text): string
{
    if (preg_match('/tugas\s+([a-z0-9 ]+?)(?:\s+deadline|\s+\d+\s+hari|,|\.| dan aku| dan saya|$)/u', $text, $match)) {
        return 'tugas ' . trim($match[1]);
    }

    $topicMap = [
        'skripsi' => 'skripsi',
        'database' => 'tugas database',
        'web' => 'tugas web',
        'laporan' => 'laporan',
        'jurnal' => 'review jurnal',
        'presentasi' => 'materi presentasi',
    ];

    foreach ($topicMap as $keyword => $label) {
        if (str_contains($text, $keyword)) {
            return $label;
        }
    }

    return 'tugas paling dekat deadline-nya';
}

function detectSecondaryStudyTask(string $text, string $mainTask): ?string
{
    preg_match_all('/tugas\s+([a-z0-9 ]+?)(?:\s+deadline|\s+\d+\s+hari|,|\.| dan aku| dan saya|$)/u', $text, $matches);

    foreach ($matches[1] ?? [] as $task) {
        $label = 'tugas ' . trim($task);

        if ($label !== $mainTask) {
            return $label;
        }
    }

    if ($mainTask !== 'tugas database' && str_contains($text, 'database')) {
        return 'tugas database';
    }

    if ($mainTask !== 'tugas web' && str_contains($text, 'web')) {
        return 'tugas web';
    }

    return null;
}

function containsAny(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if (str_contains($text, $keyword)) {
            return true;
        }
    }

    return false;
}

function formatDateTime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('d M Y, H:i', $timestamp) : $value;
}

function formatDateOnly(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('d M Y', $timestamp) : $value;
}
