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

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editingTour = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM tours WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editingTour = $stmt->fetch();
}

$tours = [];
try {
    $stmt = $pdo->query('SELECT id, code, title, slug, destination, duration_days, duration_nights, price_adult, status, created_at FROM tours ORDER BY created_at DESC, id DESC');
    $tours = $stmt->fetchAll();
} catch (Throwable $e) {
    $tours = [];
}

$formData = $editingTour ?: [
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

$isEditing = $editingTour !== false && $editingTour !== null;
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
</head>
<body class="bg-slate-100">
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quan tri tour</h1>
                <p class="text-sm text-slate-600">Them, sua, xoa va an/hien tour.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="bookings.php" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Dat tour</a>
                <a href="../public/index.php" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">Ve trang chu</a>
                <a href="logout.php" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm">Dang xuat</a>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="rounded-lg px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200' ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Danh sach tour</h2>
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
                            <th class="px-4 py-3 text-left">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tours)): ?>
                            <?php foreach ($tours as $tour): ?>
                                <?php $isPublished = $tour['status'] === $publishedStatus; ?>
                                <tr class="border-t border-slate-100">
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
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="tours.php?edit=<?= (int)$tour['id'] ?>" class="px-3 py-1 rounded bg-blue-600 text-white text-xs">Sua</a>

                                            <form method="post" onsubmit="return confirm('Ban chac chan muon <?= $isPublished ? 'an' : 'hien' ?> tour nay?');">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int)$tour['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= e($tour['status']) ?>">
                                                <button type="submit" class="px-3 py-1 rounded <?= $isPublished ? 'bg-amber-500 text-black' : 'bg-emerald-600 text-white' ?> text-xs">
                                                    <?= $isPublished ? 'An' : 'Hien' ?>
                                                </button>
                                            </form>

                                            <form method="post" onsubmit="return confirm('Ban chac chan muon xoa tour nay? Du lieu lien quan se bi xoa.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$tour['id'] ?>">
                                                <button type="submit" class="px-3 py-1 rounded bg-red-600 text-white text-xs">Xoa</button>
                                            </form>
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

        <section class="bg-white rounded-xl shadow">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800"><?= $isEditing ? 'Sua tour' : 'Them tour moi' ?></h2>
                <?php if ($isEditing): ?>
                    <a href="tours.php" class="text-sm text-slate-600 underline">Tao moi</a>
                <?php endif; ?>
            </div>

            <form method="post" class="p-4 grid md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'add' ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int)$formData['id'] ?>">
                <?php endif; ?>

                <label class="block">
                    <span class="text-sm text-slate-700">Ma tour</span>
                    <input name="code" value="<?= e($formData['code']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="VD: ST-001">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Ten tour *</span>
                    <input name="title" required value="<?= e($formData['title']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Ten tour">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Slug (de trong de tu tao)</span>
                    <input name="slug" value="<?= e($formData['slug']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="vd: tour-da-lat-3n2d">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Trang thai</span>
                    <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2">
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= e($status) ?>" <?= $formData['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Noi khoi hanh</span>
                    <input name="departure_location" value="<?= e($formData['departure_location']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="TP.HCM">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Diem den</span>
                    <input name="destination" value="<?= e($formData['destination']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Da Lat">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Phuong tien</span>
                    <input name="vehicle" value="<?= e($formData['vehicle']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Xe giuong nam">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">URL anh dai dien</span>
                    <input name="main_image" value="<?= e($formData['main_image']) ?>" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="assets/image/...">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">So ngay</span>
                    <input type="number" min="1" name="duration_days" value="<?= (int)$formData['duration_days'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">So dem</span>
                    <input type="number" min="0" name="duration_nights" value="<?= (int)$formData['duration_nights'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Gia nguoi lon</span>
                    <input type="number" min="0" step="0.01" name="price_adult" value="<?= (float)$formData['price_adult'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Gia tre em</span>
                    <input type="number" min="0" step="0.01" name="price_child" value="<?= (float)$formData['price_child'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Gia em be</span>
                    <input type="number" min="0" step="0.01" name="price_baby" value="<?= (float)$formData['price_baby'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">So khach toi da</span>
                    <input type="number" min="1" name="max_passengers" value="<?= (int)$formData['max_passengers'] ?>" class="mt-1 w-full border rounded-lg px-3 py-2">
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-slate-700">Tong quan</span>
                    <textarea name="overview" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Mo ta ngan"><?= e($formData['overview']) ?></textarea>
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-slate-700">Mo ta chi tiet</span>
                    <textarea name="description" rows="4" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Noi dung mo ta"><?= e($formData['description']) ?></textarea>
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-slate-700">Diem noi bat</span>
                    <textarea name="highlight" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Diem noi bat cua tour"><?= e($formData['highlight']) ?></textarea>
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-slate-700">Chinh sach</span>
                    <textarea name="policy" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Chinh sach hoan huy, tre em...\n"><?= e($formData['policy']) ?></textarea>
                </label>

                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold">
                        <?= $isEditing ? 'Cap nhat tour' : 'Them tour' ?>
                    </button>
                    <?php if ($isEditing): ?>
                        <a href="tours.php" class="px-5 py-2 rounded-lg border text-sm text-slate-700">Huy sua</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
