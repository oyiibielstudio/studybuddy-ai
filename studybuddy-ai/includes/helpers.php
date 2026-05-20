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

    $smallTalk = generateSmallTalkResponse($text);

    if ($smallTalk !== null) {
        return $smallTalk;
    }

    if (isVeryVagueMessage($text)) {
        return implode("\n", [
            'Hehe aku nangkep, tapi ceritamu masih tipis banget.',
            'Coba cerita santai aja: lagi kepikiran tugas apa, deadline kapan, atau badan lagi capek/semangat?',
            'Aku bakal bantu pecah jadi langkah kecil yang enak dikerjain.',
        ]);
    }

    $insight = analyzeStudyMessage($text);

    return composeAIResponse($insight);
}

function generateSmallTalkResponse(string $text): ?string
{
    $cleanText = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text);

    if (preg_match('/^(assalamualaikum|salam)$/u', $cleanText)) {
        return 'Waalaikumsalam. Halo, aku di sini. Lagi mau ngobrol santai dulu atau langsung bahas tugas yang lagi kepikiran?';
    }

    if (preg_match('/^(halo|hai|hei|hello|hi|helo|pagi|siang|sore|malam)(\s+(kak|buddy|studybuddy))?$/u', $cleanText)) {
        return implode("\n", [
            'Halo juga. Aku di sini, santai aja.',
            'Kamu nggak harus langsung produktif kok. Mau mulai dari ngobrol ringan juga boleh.',
            'Kalau mau, pilih vibe yang paling cocok:',
            '- "aku mau mulai tugas" kalau pengin ditemenin ngerapihin tugas',
            '- "aku mau cek mood" kalau pengin cerita perasaan dulu',
            '- "aku mau bikin rencana belajar" kalau pengin dibantu pelan-pelan',
        ]);
    }

    if (
        (preg_match('/^[wk]+$/u', $cleanText) && str_contains($cleanText, 'wk'))
        || preg_match('/^((ha)+|(he)+|(hi)+)$/u', $cleanText)
    ) {
        return 'Wkwk iya, santai dulu. Kalau udah siap, ceritain aja satu hal yang lagi paling ganggu fokusmu.';
    }

    if (preg_match('/\b(makasih|terima kasih|thanks|thank you|thx)\b/u', $cleanText)) {
        return 'Sama-sama. Pelan-pelan aja ya, progress kecil tetap progress. Kalau mau, kita bisa lanjut susun langkah berikutnya.';
    }

    if (preg_match('/^(oke|ok|siap|sip|gas|gaskeun)(\s+(deh|ya|yuk|aja))?$/u', $cleanText)) {
        return 'Siap, pelan-pelan aja. Kamu mau ditemenin ngobrol dulu, atau mau aku bantu pilih satu langkah kecil yang paling ringan?';
    }

    if (containsPhrase($cleanText, ['mau mulai tugas', 'mulai tugas', 'ngerjain tugas', 'kerjain tugas'])) {
        return implode("\n", [
            'Oke, kita mulai tugasnya tanpa mode panik ya.',
            'Ceritain tugasnya apa dan deadline kapan. Kalau belum tahu mulai dari mana, cukup tulis seadanya.',
            'Nanti aku bantu pilih langkah pertama yang paling ringan, bukan langsung nyuruh kamu beresin semuanya.',
        ]);
    }

    if (containsPhrase($cleanText, ['cek mood', 'mau cek mood', 'cerita mood', 'mood dulu'])) {
        return implode("\n", [
            'Boleh banget, kita cek mood dulu.',
            'Hari ini rasanya lebih dekat ke mana: senang, biasa aja, lelah, stres, atau sedih?',
            'Nggak perlu dijelasin rapi. Cerita berantakan juga gapapa.',
        ]);
    }

    if (containsPhrase($cleanText, ['bikin rencana belajar', 'buat rencana belajar', 'mau rencana belajar', 'study plan'])) {
        return implode("\n", [
            'Siap, kita bikin rencana belajar yang manusiawi.',
            'Kirim aja 3 hal: tugas/topik, deadline, dan energi kamu sekarang.',
            'Contoh santai: "web besok, database 3 hari lagi, aku capek". Nanti aku pecah jadi langkah kecil.',
        ]);
    }

    return null;
}

function containsPhrase(string $text, array $phrases): bool
{
    foreach ($phrases as $phrase) {
        if (str_contains($text, $phrase)) {
            return true;
        }
    }

    return false;
}

function isVeryVagueMessage(string $text): bool
{
    $wordCount = str_word_count($text);
    $hasStudySignal = containsAny($text, [
        'tugas',
        'deadline',
        'skripsi',
        'belajar',
        'ujian',
        'uts',
        'uas',
        'web',
        'database',
        'laporan',
        'presentasi',
        'capek',
        'cape',
        'bingung',
        'stres',
    ]);

    return $wordCount <= 3 && !$hasStudySignal;
}

function analyzeStudyMessage(string $text): array
{
    $mainTask = detectMainStudyTask($text);
    $secondTask = detectSecondaryStudyTask($text, $mainTask);

    return [
        'main_task' => $mainTask,
        'second_task' => $secondTask,
        'deadline' => detectDeadlineLabel($text),
        'energy' => detectEnergyLevel($text),
        'tone' => detectUserTone($text),
        'workload' => containsAny($text, ['numpuk', 'banyak', 'keteteran', 'overload', 'semua', 'beberapa']) ? 'banyak' : 'normal',
        'confused' => containsAny($text, ['bingung', 'gatau', 'ga tau', 'nggak tau', 'tidak tau', 'tidak tahu', 'mulai dari mana', 'apa ya']),
        'positive' => containsAny($text, ['semangat', 'senang', 'happy', 'lega', 'bangga', 'mantap']),
        'is_skripsi' => containsAny($text, ['skripsi', 'tesis', 'revisi', 'dosen pembimbing', 'bimbingan', 'sidang']),
        'is_deadline' => containsAny($text, ['deadline', 'besok', 'hari ini', 'malam ini', 'mepet', 'dekat', '3 hari', '2 hari', 'minggu ini']),
    ];
}

function composeAIResponse(array $insight): string
{
    $mainTask = $insight['main_task'];
    $secondTask = $insight['second_task'];
    $deadline = $insight['deadline'];
    $energy = $insight['energy'];

    $opening = match ($insight['tone']) {
        'berat' => 'Aku nangkep ini lagi berat buat kamu, dan kamu tidak harus membereskan semuanya sekaligus.',
        'bingung' => 'Aku nangkep kamu lagi belum nemu titik mulai. Kita bikin jadi lebih kecil dan jelas.',
        'positif' => 'Wih, energinya lagi bagus. Kita pakai momentumnya biar jadi progress nyata.',
        default => 'Aku nangkep ceritamu. Kita ubah jadi langkah belajar yang bisa langsung dijalankan.',
    };

    $context = "Yang kebaca: fokus utama = {$mainTask}; deadline = {$deadline}; energi = {$energy}.";

    if ($insight['is_skripsi'] && $energy === 'rendah') {
        $priority = 'Prioritasnya bukan ngebut skripsi sampai habis, tapi buka dokumen dan pilih 1 bagian revisi paling kecil.';
    } elseif ($insight['is_deadline']) {
        $priority = "Prioritasnya: kerjakan {$mainTask} dulu karena sinyal deadline-nya paling kuat.";
    } elseif ($insight['confused']) {
        $priority = "Prioritasnya: bikin titik mulai. Jangan mikir semua bagian, cukup mulai dari 1 langkah paling jelas.";
    } else {
        $priority = "Prioritasnya: mulai dari {$mainTask}, lalu jaga ritme supaya tidak keburu mental penuh.";
    }

    $plan = $energy === 'rendah'
        ? [
            '1. 3 menit: rapikan meja/tab, buka file atau instruksi tugas.',
            "2. 15 menit: kerjakan bagian termudah dari {$mainTask}.",
            '3. 5 menit: istirahat beneran, minum air, jangan buka tugas baru.',
            '4. 10 menit: lanjutkan sedikit atau tulis next step untuk sesi berikutnya.',
        ]
        : [
            '1. 5 menit: tulis daftar bagian yang harus dikerjakan.',
            "2. 25 menit: fokus ke {$mainTask} tanpa pindah tab.",
            '3. 5 menit: break singkat.',
            '4. 25 menit: lanjutkan bagian berikutnya atau revisi hasil sesi pertama.',
        ];

    if ($secondTask) {
        $plan[] = "Bonus kalau masih ada tenaga: sentuh {$secondTask} selama 10 menit dari bagian paling gampang.";
    }

    $tinyWin = $insight['positive']
        ? 'Tiny win: selesaikan 1 bagian penting lalu kasih reward kecil. Momentum kayak gini sayang kalau cuma lewat.'
        : 'Tiny win: setelah 10 menit, cukup tanya "lanjut 10 menit lagi atau break dulu?" Bukan harus sempurna, yang penting bergerak.';

    $safety = 'Catatan kecil: aku bantu dukungan belajar ringan ya, bukan diagnosis atau instruksi medis.';

    return implode("\n", array_merge([
        "StudyBuddy Says:",
        $opening,
        $context,
        $priority,
        '',
        'Rencana paling masuk akal sekarang:',
    ], $plan, [
        '',
        $tinyWin,
        $safety,
    ]));
}

function detectDeadlineLabel(string $text): string
{
    if (containsAny($text, ['hari ini', 'malam ini'])) {
        return 'hari ini';
    }

    if (str_contains($text, 'besok')) {
        return 'besok';
    }

    if (preg_match('/(\d+)\s*hari\s+lagi/u', $text, $match)) {
        return $match[1] . ' hari lagi';
    }

    if (containsAny($text, ['minggu ini', 'pekan ini'])) {
        return 'minggu ini';
    }

    if (containsAny($text, ['deadline', 'mepet', 'dekat'])) {
        return 'dekat';
    }

    return 'belum jelas';
}

function detectEnergyLevel(string $text): string
{
    if (containsAny($text, ['capek', 'cape', 'capai', 'lelah', 'burnout', 'tepar', 'drop', 'ngantuk'])) {
        return 'rendah';
    }

    if (containsAny($text, ['semangat', 'fresh', 'siap', 'happy', 'senang'])) {
        return 'bagus';
    }

    return 'sedang';
}

function detectUserTone(string $text): string
{
    if (containsAny($text, ['stres', 'tertekan', 'panik', 'takut', 'cemas', 'overthinking', 'capek', 'cape', 'lelah'])) {
        return 'berat';
    }

    if (containsAny($text, ['bingung', 'gatau', 'ga tau', 'nggak tau', 'tidak tahu', 'mulai dari mana', 'apa ya'])) {
        return 'bingung';
    }

    if (containsAny($text, ['semangat', 'senang', 'happy', 'lega', 'bangga'])) {
        return 'positif';
    }

    return 'netral';
}

function normalizeMessage(string $text): string
{
    $patterns = [
        '/\bngga\b/u' => 'nggak',
        '/\benggak\b/u' => 'nggak',
        '/\bgak\b/u' => 'ga',
        '/\bcapee+\b/u' => 'cape',
        '/\bcapekk+\b/u' => 'capek',
    ];

    return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
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
    if (preg_match('/tugas\s+([a-z0-9 ]+?)(?:\s+numpuk|\s+banyak|\s+deadline|\s+\d+\s+hari|,|\.| dan aku| dan saya|$)/u', $text, $match)) {
        return 'tugas ' . cleanTaskLabel($match[1]);
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
    preg_match_all('/tugas\s+([a-z0-9 ]+?)(?:\s+numpuk|\s+banyak|\s+deadline|\s+\d+\s+hari|,|\.| dan aku| dan saya|$)/u', $text, $matches);

    foreach ($matches[1] ?? [] as $task) {
        $label = 'tugas ' . cleanTaskLabel($task);

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

function cleanTaskLabel(string $task): string
{
    $task = trim($task);
    $task = preg_replace('/\b(numpuk|banyak|mepet|dekat|lagi|dan)\b/u', '', $task) ?? $task;
    $task = trim(preg_replace('/\s+/u', ' ', $task) ?? $task);

    return $task !== '' ? $task : 'paling dekat deadline-nya';
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
