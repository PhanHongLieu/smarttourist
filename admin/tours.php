<?php
require_once __DIR__ . '/auth.php';
adminRequireLogin();

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage(string $message, string $type = 'success'): void
{
    header('Location: tours.php?msg=' . urlencode($message) . '&type=' . urlencode($type));
    exit;
}

function normalizeSlug(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim((string)$text, '-');
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function getTourStatusOptions(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM tours LIKE 'status'");
        $row = $stmt->fetch();
        if (!$row || empty($row['Type'])) {
            return ['PUBLISHED', 'HIDDEN'];
        }

        if (!preg_match("/enum\\((.*)\\)/i", $row['Type'], $matches)) {
            return ['PUBLISHED', 'HIDDEN'];
        }

        $rawValues = str_getcsv($matches[1], ',', "'");
        $values = [];
        foreach ($rawValues as $value) {
            $value = trim($value);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return !empty($values) ? $values : ['PUBLISHED', 'HIDDEN'];
    } catch (Throwable $e) {
        return ['PUBLISHED', 'HIDDEN'];
    }
}

function resolvePublishedStatus(array $statusOptions): string
{
    foreach (['PUBLISHED', 'ACTIVE', 'VISIBLE'] as $candidate) {
        if (in_array($candidate, $statusOptions, true)) {
            return $candidate;
        }
    }
    return $statusOptions[0] ?? 'PUBLISHED';
}

function resolveHiddenStatus(array $statusOptions, string $publishedStatus): string
{
    foreach (['HIDDEN', 'DRAFT', 'INACTIVE', 'UNPUBLISHED'] as $candidate) {
        if (in_array($candidate, $statusOptions, true)) {
            return $candidate;
        }
    }

    foreach ($statusOptions as $status) {
        if ($status !== $publishedStatus) {
            return $status;
        }
    }

    return $publishedStatus;
}

function uniqueSlug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string
{
    $baseSlug = $baseSlug !== '' ? $baseSlug : 'tour';
    $slug = $baseSlug;
    $index = 1;

    while (true) {
        $sql = 'SELECT COUNT(*) FROM tours WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $index;
        $index++;
    }
}

function tourPayloadFromPost(array $postData): array
{
    return [
        'code' => trim((string)($postData['code'] ?? '')),
        'title' => trim((string)($postData['title'] ?? '')),
        'slug' => trim((string)($postData['slug'] ?? '')),
        'overview' => trim((string)($postData['overview'] ?? '')),
        'description' => trim((string)($postData['description'] ?? '')),
        'departure_location' => trim((string)($postData['departure_location'] ?? '')),
        'destination' => trim((string)($postData['destination'] ?? '')),
        'vehicle' => trim((string)($postData['vehicle'] ?? '')),
        'main_image' => trim((string)($postData['main_image'] ?? '')),
        'highlight' => trim((string)($postData['highlight'] ?? '')),
        'policy' => trim((string)($postData['policy'] ?? '')),
        'duration_days' => max(1, (int)($postData['duration_days'] ?? 1)),
        'duration_nights' => max(0, (int)($postData['duration_nights'] ?? 0)),
        'price_adult' => max(0, (float)($postData['price_adult'] ?? 0)),
        'price_child' => max(0, (float)($postData['price_child'] ?? 0)),
        'price_baby' => max(0, (float)($postData['price_baby'] ?? 0)),
        'max_passengers' => max(1, (int)($postData['max_passengers'] ?? 1)),
    ];
}

$statusOptions = getTourStatusOptions($pdo);
$publishedStatus = resolvePublishedStatus($statusOptions);
$hiddenStatus = resolveHiddenStatus($statusOptions, $publishedStatus);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $payload = tourPayloadFromPost($_POST);
            if ($payload['title'] === '') {
                redirectWithMessage('Vui long nhap ten tour.', 'error');
            }

            $status = (string)($_POST['status'] ?? $publishedStatus);
            if (!in_array($status, $statusOptions, true)) {
                $status = $publishedStatus;
            }

            $baseSlug = normalizeSlug($payload['slug'] !== '' ? $payload['slug'] : $payload['title']);
            $payload['slug'] = uniqueSlug($pdo, $baseSlug);

            $sql = "INSERT INTO tours (
                code, title, slug, overview, description,
                departure_location, destination, vehicle, main_image,
                highlight, policy, duration_days, duration_nights,
                price_adult, price_child, price_baby, max_passengers, status
            ) VALUES (
                :code, :title, :slug, :overview, :description,
                :departure_location, :destination, :vehicle, :main_image,
                :highlight, :policy, :duration_days, :duration_nights,
                :price_adult, :price_child, :price_baby, :max_passengers, :status
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'code' => $payload['code'],
                'title' => $payload['title'],
                'slug' => $payload['slug'],
                'overview' => $payload['overview'],
                'description' => $payload['description'],
                'departure_location' => $payload['departure_location'],
                'destination' => $payload['destination'],
                'vehicle' => $payload['vehicle'],
                'main_image' => $payload['main_image'],
                'highlight' => $payload['highlight'],
                'policy' => $payload['policy'],
                'duration_days' => $payload['duration_days'],
                'duration_nights' => $payload['duration_nights'],
                'price_adult' => $payload['price_adult'],
                'price_child' => $payload['price_child'],
                'price_baby' => $payload['price_baby'],
                'max_passengers' => $payload['max_passengers'],
                'status' => $status,
            ]);

            redirectWithMessage('Them tour thanh cong.');
        }

        if ($action === 'update') {
            $tourId = (int)($_POST['id'] ?? 0);
            if ($tourId <= 0) {
                redirectWithMessage('Khong tim thay tour can sua.', 'error');
            }

            $payload = tourPayloadFromPost($_POST);
            if ($payload['title'] === '') {
                redirectWithMessage('Vui long nhap ten tour.', 'error');
            }

            $status = (string)($_POST['status'] ?? $publishedStatus);
            if (!in_array($status, $statusOptions, true)) {
                $status = $publishedStatus;
            }

            $baseSlug = normalizeSlug($payload['slug'] !== '' ? $payload['slug'] : $payload['title']);
            $payload['slug'] = uniqueSlug($pdo, $baseSlug, $tourId);

            $sql = "UPDATE tours SET
                code = :code,
                title = :title,
                slug = :slug,
                overview = :overview,
                description = :description,
                departure_location = :departure_location,
                destination = :destination,
                vehicle = :vehicle,
                main_image = :main_image,
                highlight = :highlight,
                policy = :policy,
                duration_days = :duration_days,
                duration_nights = :duration_nights,
                price_adult = :price_adult,
                price_child = :price_child,
                price_baby = :price_baby,
                max_passengers = :max_passengers,
                status = :status
            WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id' => $tourId,
                'code' => $payload['code'],
                'title' => $payload['title'],
                'slug' => $payload['slug'],
                'overview' => $payload['overview'],
                'description' => $payload['description'],
                'departure_location' => $payload['departure_location'],
                'destination' => $payload['destination'],
                'vehicle' => $payload['vehicle'],
                'main_image' => $payload['main_image'],
                'highlight' => $payload['highlight'],
                'policy' => $payload['policy'],
                'duration_days' => $payload['duration_days'],
                'duration_nights' => $payload['duration_nights'],
                'price_adult' => $payload['price_adult'],
                'price_child' => $payload['price_child'],
                'price_baby' => $payload['price_baby'],
                'max_passengers' => $payload['max_passengers'],
                'status' => $status,
            ]);

            redirectWithMessage('Cap nhat tour thanh cong.');
        }

        if ($action === 'delete') {
            $tourId = (int)($_POST['id'] ?? 0);
            if ($tourId <= 0) {
                redirectWithMessage('Khong tim thay tour can xoa.', 'error');
            }

            $relatedTables = [
                'tour_images',
                'tour_schedule',
                'tour_departure_dates',
                'tour_includes',
                'tour_excludes',
                'tour_policy',
                'bookings',
            ];

            $pdo->beginTransaction();
            foreach ($relatedTables as $table) {
                if (tableExists($pdo, $table)) {
                    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE tour_id = :tour_id");
                    $stmt->execute(['tour_id' => $tourId]);
                }
            }

            $stmt = $pdo->prepare('DELETE FROM tours WHERE id = :id');
            $stmt->execute(['id' => $tourId]);
            $pdo->commit();

            redirectWithMessage('Da xoa tour.');
        }

        if ($action === 'toggle') {
            $tourId = (int)($_POST['id'] ?? 0);
            $currentStatus = (string)($_POST['current_status'] ?? '');
            if ($tourId <= 0) {
                redirectWithMessage('Khong tim thay tour can cap nhat trang thai.', 'error');
            }

            $newStatus = ($currentStatus === $publishedStatus) ? $hiddenStatus : $publishedStatus;
            $stmt = $pdo->prepare('UPDATE tours SET status = :status WHERE id = :id');
            $stmt->execute([
                'status' => $newStatus,
                'id' => $tourId,
            ]);

            $message = $newStatus === $publishedStatus ? 'Da hien tour.' : 'Da an tour.';
            redirectWithMessage($message);
        }

        redirectWithMessage('Hanh dong khong hop le.', 'error');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirectWithMessage('Khong the xu ly yeu cau: ' . $e->getMessage(), 'error');
    }
}

$tours = [];
try {
    $stmt = $pdo->query('SELECT id, code, title, slug, overview, description, departure_location, destination, vehicle, main_image, highlight, policy, duration_days, duration_nights, price_adult, price_child, price_baby, max_passengers, status, created_at FROM tours ORDER BY created_at DESC, id DESC');
    $tours = $stmt->fetchAll();
} catch (Throwable $e) {
    $tours = [];
}

$formDefaults = [
    'id' => 0,
    'code' => '',
    'title' => '',
    'slug' => '',
    'overview' => '',
    'description' => '',
    'departure_location' => '',
    'destination' => '',
    'vehicle' => '',
    'main_image' => '',
    'highlight' => '',
    'policy' => '',
    'duration_days' => 1,
    'duration_nights' => 0,
    'price_adult' => 0,
    'price_child' => 0,
    'price_baby' => 0,
    'max_passengers' => 1,
    'status' => $publishedStatus,
];

$totalTours = count($tours);
$visibleTours = 0;
foreach ($tours as $tour) {
    if (($tour['status'] ?? '') === $publishedStatus) {
        $visibleTours++;
    }
}
$hiddenTours = $totalTours - $visibleTours;

$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? 'success';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quan tri tour | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --brand: #0f172a;
            --accent: #0284c7;
            --soft: #e0f2fe;
        }

        body {
            background: radial-gradient(circle at top left, #f0f9ff 0%, #f8fafc 45%, #eef2ff 100%);
        }

        .panel {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(6px);
        }
    </style>
</head>
<body class="min-h-screen text-slate-800">
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-sky-700 font-semibold">SmartTourist Admin</p>
                <h1 class="text-3xl font-bold text-slate-900 mt-1">Quan tri tour</h1>
                <p class="text-sm text-slate-600 mt-1">Them, sua, xoa va an/hien tour voi thao tac nhanh.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="btnAddTour" class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700">+ Them tour</button>
                <a href="bookings.php" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Dat tour</a>
                <a href="/index.php" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800">Ve trang chu</a>
                <a href="logout.php" class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Dang xuat</a>
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="panel rounded-2xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tong so tour</p>
                <p class="text-3xl font-bold mt-1 text-slate-900"><?= (int)$totalTours ?></p>
            </article>
            <article class="panel rounded-2xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tour dang hien</p>
                <p class="text-3xl font-bold mt-1 text-emerald-700"><?= (int)$visibleTours ?></p>
            </article>
            <article class="panel rounded-2xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tour dang an</p>
                <p class="text-3xl font-bold mt-1 text-amber-700"><?= (int)$hiddenTours ?></p>
            </article>
        </section>

        <?php if ($message !== ''): ?>
            <div id="flashToast" class="fixed top-5 right-5 z-50 rounded-xl px-4 py-3 text-sm shadow-lg <?= $messageType === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200' ?>">
                <div class="flex items-start gap-3">
                    <p><?= e($message) ?></p>
                    <button type="button" id="closeToast" class="text-xs font-semibold opacity-70 hover:opacity-100">Dong</button>
                </div>
            </div>
        <?php endif; ?>

        <section class="panel rounded-2xl shadow-lg overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-slate-900">Danh sach tour</h2>
                <p class="text-xs text-slate-500">Nhap sua nhanh bang modal, khong can chuyen trang.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Code</th>
                            <th class="px-4 py-3 text-left">Ten tour</th>
                            <th class="px-4 py-3 text-left">Diem den</th>
                            <th class="px-4 py-3 text-left">Thoi gian</th>
                            <th class="px-4 py-3 text-left">Gia nguoi lon</th>
                            <th class="px-4 py-3 text-left">Trang thai</th>
                            <th class="px-4 py-3 text-right">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tours)): ?>
                            <?php foreach ($tours as $tour): ?>
                                <?php $isPublished = $tour['status'] === $publishedStatus; ?>
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3"><?= (int)$tour['id'] ?></td>
                                    <td class="px-4 py-3"><?= e($tour['code']) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800"><?= e($tour['title']) ?></div>
                                        <div class="text-xs text-slate-500">Slug: <?= e($tour['slug']) ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= e($tour['destination']) ?></td>
                                    <td class="px-4 py-3"><?= (int)$tour['duration_days'] ?>N<?= (int)$tour['duration_nights'] ?>D</td>
                                    <td class="px-4 py-3"><?= number_format((float)$tour['price_adult']) ?> đ</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $isPublished ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                            <?= e($tour['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <?php $tourJson = e(json_encode($tour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" class="btnEdit px-3 py-1.5 rounded-lg bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700" data-tour="<?= $tourJson ?>">Chinh sua</button>
                                            <button type="button" class="btnToggle px-3 py-1.5 rounded-lg text-xs font-semibold <?= $isPublished ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' ?>" data-id="<?= (int)$tour['id'] ?>" data-current-status="<?= e($tour['status']) ?>" data-title="<?= e($tour['title']) ?>" data-mode="<?= $isPublished ? 'hide' : 'show' ?>">
                                                <?= $isPublished ? 'An' : 'Hien' ?>
                                            </button>
                                            <button type="button" class="btnDelete px-3 py-1.5 rounded-lg bg-rose-100 text-rose-700 text-xs font-semibold hover:bg-rose-200" data-id="<?= (int)$tour['id'] ?>" data-title="<?= e($tour['title']) ?>">Xoa</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Chua co tour nao.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <form id="actionForm" method="post" class="hidden">
        <input type="hidden" name="action" id="actionFormAction" value="">
        <input type="hidden" name="id" id="actionFormId" value="">
        <input type="hidden" name="current_status" id="actionFormCurrentStatus" value="">
    </form>

    <div id="tourModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/60" id="tourModalBackdrop"></div>
        <div class="relative max-w-5xl mx-auto my-6 px-4">
            <div class="panel rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 id="tourModalTitle" class="text-xl font-semibold text-slate-900">Them tour moi</h3>
                    <button type="button" id="tourModalClose" class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">Dong</button>
                </div>
                <form id="tourForm" method="post" class="p-4 overflow-y-auto">
                    <input type="hidden" name="action" id="tourFormAction" value="add">
                    <input type="hidden" name="id" id="tourFormId" value="0">

                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-slate-700">Ma tour</span>
                            <input name="code" id="f_code" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="VD: ST-001">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Ten tour *</span>
                            <input name="title" id="f_title" required class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Ten tour">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Slug (de trong de tu tao)</span>
                            <input name="slug" id="f_slug" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="vd: tour-da-lat-3n2d">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Trang thai</span>
                            <select name="status" id="f_status" class="mt-1 w-full border rounded-lg px-3 py-2">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= e($status) ?>"><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Noi khoi hanh</span>
                            <input name="departure_location" id="f_departure_location" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="TP.HCM">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Diem den</span>
                            <input name="destination" id="f_destination" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Da Lat">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Phuong tien</span>
                            <input name="vehicle" id="f_vehicle" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Xe giuong nam">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">URL anh dai dien</span>
                            <input name="main_image" id="f_main_image" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="assets/image/...">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">So ngay</span>
                            <input type="number" min="1" name="duration_days" id="f_duration_days" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (int)$formDefaults['duration_days'] ?>">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">So dem</span>
                            <input type="number" min="0" name="duration_nights" id="f_duration_nights" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (int)$formDefaults['duration_nights'] ?>">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Gia nguoi lon</span>
                            <input type="number" min="0" step="0.01" name="price_adult" id="f_price_adult" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (float)$formDefaults['price_adult'] ?>">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Gia tre em</span>
                            <input type="number" min="0" step="0.01" name="price_child" id="f_price_child" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (float)$formDefaults['price_child'] ?>">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">Gia em be</span>
                            <input type="number" min="0" step="0.01" name="price_baby" id="f_price_baby" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (float)$formDefaults['price_baby'] ?>">
                        </label>

                        <label class="block">
                            <span class="text-sm text-slate-700">So khach toi da</span>
                            <input type="number" min="1" name="max_passengers" id="f_max_passengers" class="mt-1 w-full border rounded-lg px-3 py-2" value="<?= (int)$formDefaults['max_passengers'] ?>">
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm text-slate-700">Tong quan</span>
                            <textarea name="overview" id="f_overview" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Mo ta ngan"></textarea>
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm text-slate-700">Mo ta chi tiet</span>
                            <textarea name="description" id="f_description" rows="4" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Noi dung mo ta"></textarea>
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm text-slate-700">Diem noi bat</span>
                            <textarea name="highlight" id="f_highlight" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Diem noi bat cua tour"></textarea>
                        </label>

                        <label class="block md:col-span-2">
                            <span class="text-sm text-slate-700">Chinh sach</span>
                            <textarea name="policy" id="f_policy" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Chinh sach hoan huy, tre em..."></textarea>
                        </label>
                    </div>

                    <div class="sticky bottom-0 bg-white border-t border-slate-200 pt-4 mt-6 flex items-center justify-end gap-3">
                        <button type="button" id="tourFormCancel" class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">Huy</button>
                        <button type="submit" id="tourFormSubmit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Luu tour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/60" id="confirmBackdrop"></div>
        <div class="relative max-w-md mx-auto mt-40 px-4">
            <div class="panel rounded-2xl shadow-2xl p-5">
                <h3 id="confirmTitle" class="text-lg font-semibold text-slate-900">Xac nhan thao tac</h3>
                <p id="confirmMessage" class="text-sm text-slate-600 mt-2">Ban co chac chan muon tiep tuc?</p>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" id="confirmCancel" class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">Huy</button>
                    <button type="button" id="confirmAccept" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Xac nhan</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const defaults = <?= json_encode($formDefaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        const tourModal = document.getElementById('tourModal');
        const tourModalTitle = document.getElementById('tourModalTitle');
        const tourFormAction = document.getElementById('tourFormAction');
        const tourFormId = document.getElementById('tourFormId');
        const tourFormSubmit = document.getElementById('tourFormSubmit');

        function setField(id, value) {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = value ?? '';
        }

        function openTourModal(mode, data) {
            const source = data || defaults;
            const isEdit = mode === 'edit';

            tourModalTitle.textContent = isEdit ? 'Chinh sua tour' : 'Them tour moi';
            tourFormAction.value = isEdit ? 'update' : 'add';
            tourFormId.value = isEdit ? (source.id || 0) : 0;
            tourFormSubmit.textContent = isEdit ? 'Cap nhat tour' : 'Them tour';

            setField('f_code', source.code);
            setField('f_title', source.title);
            setField('f_slug', source.slug);
            setField('f_overview', source.overview);
            setField('f_description', source.description);
            setField('f_departure_location', source.departure_location);
            setField('f_destination', source.destination);
            setField('f_vehicle', source.vehicle);
            setField('f_main_image', source.main_image);
            setField('f_highlight', source.highlight);
            setField('f_policy', source.policy);
            setField('f_duration_days', source.duration_days || 1);
            setField('f_duration_nights', source.duration_nights || 0);
            setField('f_price_adult', source.price_adult || 0);
            setField('f_price_child', source.price_child || 0);
            setField('f_price_baby', source.price_baby || 0);
            setField('f_max_passengers', source.max_passengers || 1);
            setField('f_status', source.status || defaults.status);

            tourModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeTourModal() {
            tourModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.getElementById('btnAddTour')?.addEventListener('click', () => openTourModal('add', defaults));
        document.getElementById('tourModalClose')?.addEventListener('click', closeTourModal);
        document.getElementById('tourModalBackdrop')?.addEventListener('click', closeTourModal);
        document.getElementById('tourFormCancel')?.addEventListener('click', closeTourModal);

        document.querySelectorAll('.btnEdit').forEach((btn) => {
            btn.addEventListener('click', () => {
                try {
                    const data = JSON.parse(btn.dataset.tour || '{}');
                    openTourModal('edit', data);
                } catch (e) {
                    openTourModal('edit', defaults);
                }
            });
        });

        const actionForm = document.getElementById('actionForm');
        const actionFormAction = document.getElementById('actionFormAction');
        const actionFormId = document.getElementById('actionFormId');
        const actionFormCurrentStatus = document.getElementById('actionFormCurrentStatus');
        const confirmModal = document.getElementById('confirmModal');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmAccept = document.getElementById('confirmAccept');
        const confirmCancel = document.getElementById('confirmCancel');
        const confirmBackdrop = document.getElementById('confirmBackdrop');

        function openConfirm(title, message, onConfirm, variant) {
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmAccept.className = 'px-4 py-2 rounded-lg text-white text-sm font-semibold';
            if (variant === 'danger') {
                confirmAccept.classList.add('bg-rose-600', 'hover:bg-rose-700');
            } else if (variant === 'warning') {
                confirmAccept.classList.add('bg-amber-600', 'hover:bg-amber-700');
            } else {
                confirmAccept.classList.add('bg-slate-900', 'hover:bg-slate-800');
            }

            confirmModal.classList.remove('hidden');

            const handler = () => {
                closeConfirm();
                onConfirm();
            };

            confirmAccept.onclick = handler;
        }

        function closeConfirm() {
            confirmModal.classList.add('hidden');
            confirmAccept.onclick = null;
        }

        confirmCancel?.addEventListener('click', closeConfirm);
        confirmBackdrop?.addEventListener('click', closeConfirm);

        document.querySelectorAll('.btnToggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id || '0';
                const currentStatus = btn.dataset.currentStatus || '';
                const title = btn.dataset.title || 'tour nay';
                const mode = btn.dataset.mode || 'hide';
                const actionLabel = mode === 'hide' ? 'an' : 'hien';

                openConfirm(
                    'Xac nhan thay doi trang thai',
                    `Ban co chac chan muon ${actionLabel} "${title}"?`,
                    () => {
                        actionFormAction.value = 'toggle';
                        actionFormId.value = id;
                        actionFormCurrentStatus.value = currentStatus;
                        actionForm.submit();
                    },
                    'warning'
                );
            });
        });

        document.querySelectorAll('.btnDelete').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id || '0';
                const title = btn.dataset.title || 'tour nay';

                openConfirm(
                    'Xoa tour',
                    `Ban co chac chan muon xoa "${title}"? Du lieu lien quan se bi xoa.`,
                    () => {
                        actionFormAction.value = 'delete';
                        actionFormId.value = id;
                        actionFormCurrentStatus.value = '';
                        actionForm.submit();
                    },
                    'danger'
                );
            });
        });

        const toast = document.getElementById('flashToast');
        const closeToast = document.getElementById('closeToast');
        if (toast) {
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 5000);
        }
        closeToast?.addEventListener('click', () => {
            toast?.classList.add('hidden');
        });
    </script>
</body>
</html>
