<?php
require_once __DIR__ . '/auth.php';
adminRequireLogin();

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
    $stmt->execute(['column_name' => $column]);
    return (bool)$stmt->fetch();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $table]);
    return (bool)$stmt->fetchColumn();
}

function ensureBookingPaymentColumns(PDO $pdo): void
{
    $ddlMap = [
        'payment_method' => "ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(20) NULL AFTER total_amount",
        'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) NULL AFTER payment_method",
        'payment_reference' => "ALTER TABLE bookings ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status",
        'payment_trans_id' => "ALTER TABLE bookings ADD COLUMN payment_trans_id VARCHAR(100) NULL AFTER payment_reference",
        'payment_message' => "ALTER TABLE bookings ADD COLUMN payment_message VARCHAR(255) NULL AFTER payment_trans_id",
        'paid_at' => "ALTER TABLE bookings ADD COLUMN paid_at DATETIME NULL AFTER payment_message",
    ];

    foreach ($ddlMap as $column => $ddl) {
        if (!tableHasColumn($pdo, 'bookings', $column)) {
            try {
                $pdo->exec($ddl);
            } catch (Throwable $e) {
                // Ignore migration errors if DB permission is limited.
            }
        }
    }
}

function buildQuery(array $overrides = [], array $exclude = []): string
{
    $params = $_GET;
    foreach ($exclude as $k) {
        unset($params[$k]);
    }
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return http_build_query($params);
}

function redirectWithMessage(string $message, string $type = 'success', string $returnQuery = ''): void
{
    $params = [];
    if ($returnQuery !== '') {
        parse_str($returnQuery, $params);
    }
    unset($params['msg'], $params['type']);
    $params['msg'] = $message;
    $params['type'] = $type;

    header('Location: bookings.php?' . http_build_query($params));
    exit;
}

$bookingsTableExists = tableExists($pdo, 'bookings');
if ($bookingsTableExists) {
    ensureBookingPaymentColumns($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $returnQuery = (string)($_POST['return_qs'] ?? '');

    if (!$bookingsTableExists) {
        redirectWithMessage('Bang bookings chua ton tai trong database.', 'error', $returnQuery);
    }

    if ($action === 'mark_paid') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            redirectWithMessage('Booking khong hop le.', 'error', $returnQuery);
        }

        try {
            $stmt = $pdo->prepare('SELECT id, payment_status FROM bookings WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                redirectWithMessage('Khong tim thay booking.', 'error', $returnQuery);
            }

            $currentStatus = strtoupper((string)($booking['payment_status'] ?? 'UNPAID'));
            if ($currentStatus !== 'PENDING') {
                redirectWithMessage('Chi doi soat duoc booking dang PENDING.', 'error', $returnQuery);
            }

            $setParts = ['payment_status = :payment_status', 'payment_message = :payment_message'];
            $params = [
                'payment_status' => 'PAID',
                'payment_message' => 'Cap nhat thu cong boi admin',
                'id' => $bookingId,
                'current_status' => 'PENDING',
            ];

            if (tableHasColumn($pdo, 'bookings', 'payment_trans_id')) {
                $setParts[] = 'payment_trans_id = :payment_trans_id';
                $params['payment_trans_id'] = 'MANUAL-' . date('YmdHis');
            }

            if (tableHasColumn($pdo, 'bookings', 'paid_at')) {
                $setParts[] = 'paid_at = :paid_at';
                $params['paid_at'] = date('Y-m-d H:i:s');
            }

            $sql = 'UPDATE bookings SET ' . implode(', ', $setParts) . ' WHERE id = :id AND payment_status = :current_status';
            $update = $pdo->prepare($sql);
            $update->execute($params);

            if ($update->rowCount() > 0) {
                redirectWithMessage('Da doi soat thanh cong: PENDING -> PAID.', 'success', $returnQuery);
            }

            redirectWithMessage('Booking da duoc cap nhat boi thao tac khac.', 'error', $returnQuery);
        } catch (Throwable $e) {
            redirectWithMessage('Khong the doi soat booking: ' . $e->getMessage(), 'error', $returnQuery);
        }
    }

    redirectWithMessage('Hanh dong khong hop le.', 'error', $returnQuery);
}

$filterMethod = strtoupper(trim((string)($_GET['payment_method'] ?? '')));
$filterStatus = strtoupper(trim((string)($_GET['payment_status'] ?? '')));
$filterKeyword = trim((string)($_GET['keyword'] ?? ''));

$allowedMethods = ['MOMO', 'CASH'];
$allowedStatuses = ['PENDING', 'PAID', 'FAILED', 'UNPAID'];

if (!in_array($filterMethod, $allowedMethods, true)) {
    $filterMethod = '';
}
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

$sortBy = (string)($_GET['sort_by'] ?? 'created_at');
$sortDir = strtolower((string)($_GET['sort_dir'] ?? 'desc'));
$sortMap = [
    'id' => 'b.id',
    'tour' => 't.title',
    'customer_name' => 'b.customer_name',
    'payment_method' => 'b.payment_method',
    'payment_status' => 'b.payment_status',
    'total_amount' => 'b.total_amount',
    'created_at' => 'b.created_at',
];
if (!array_key_exists($sortBy, $sortMap)) {
    $sortBy = 'created_at';
}
if (!in_array($sortDir, ['asc', 'desc'], true)) {
    $sortDir = 'desc';
}

$perPage = (int)($_GET['per_page'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100], true)) {
    $perPage = 20;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = [];
$bindings = [];

if ($filterMethod !== '') {
    $where[] = 'UPPER(COALESCE(b.payment_method, \"CASH\")) = :payment_method';
    $bindings['payment_method'] = $filterMethod;
}
if ($filterStatus !== '') {
    $where[] = 'UPPER(COALESCE(b.payment_status, \"UNPAID\")) = :payment_status';
    $bindings['payment_status'] = $filterStatus;
}
if ($filterKeyword !== '') {
    $where[] = '(b.customer_name LIKE :kw OR b.phone LIKE :kw OR t.title LIKE :kw OR b.payment_reference LIKE :kw)';
    $bindings['kw'] = '%' . $filterKeyword . '%';
}

$bookings = [];
$totalRows = 0;
$totalPages = 1;
$statPending = 0;
$statPaid = 0;

if ($bookingsTableExists) {
    try {
        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "
            SELECT
                COUNT(*) AS total_rows,
                SUM(CASE WHEN UPPER(COALESCE(b.payment_status, 'UNPAID')) = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN UPPER(COALESCE(b.payment_status, 'UNPAID')) = 'PAID' THEN 1 ELSE 0 END) AS paid_count
            FROM bookings b
            LEFT JOIN tours t ON b.tour_id = t.id
            {$whereSql}
        ";
        $countStmt = $pdo->prepare($countSql);
        foreach ($bindings as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $stats = $countStmt->fetch();
        $totalRows = (int)($stats['total_rows'] ?? 0);
        $statPending = (int)($stats['pending_count'] ?? 0);
        $statPaid = (int)($stats['paid_count'] ?? 0);

        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $orderExpr = $sortMap[$sortBy];
        $sql = "
            SELECT b.*, t.title AS tour_title
            FROM bookings b
            LEFT JOIN tours t ON b.tour_id = t.id
            {$whereSql}
            ORDER BY {$orderExpr} " . strtoupper($sortDir) . ", b.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll();
    } catch (Throwable $e) {
        $bookings = [];
    }
}

$flashMessage = (string)($_GET['msg'] ?? '');
$flashType = (string)($_GET['type'] ?? 'success');
$returnQuery = buildQuery([], ['msg', 'type']);

function sortLink(string $column, string $label, string $currentSort, string $currentDir): string
{
    $newDir = 'asc';
    $arrow = '↕';
    $active = false;
    if ($currentSort === $column) {
        $active = true;
        if ($currentDir === 'asc') {
            $newDir = 'desc';
            $arrow = '↑';
        } else {
            $newDir = 'asc';
            $arrow = '↓';
        }
    }

    $qs = buildQuery(['sort_by' => $column, 'sort_dir' => $newDir, 'page' => 1], ['msg', 'type']);
    $class = $active ? 'sort-link active' : 'sort-link';
    return '<a class="' . $class . '" href="bookings.php?' . e($qs) . '">' . e($label) . ' <span class="arrow">' . $arrow . '</span></a>';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quan ly dat tour | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-theme" id="adminBody">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <p class="text-xs uppercase tracking-[0.22em] text-cyan-700 font-semibold">SmartTourist</p>
        <h2 class="mt-1 font-extrabold text-slate-900">Admin Console</h2>
        <nav class="mt-6">
            <a class="sidebar-link" href="tours.php">Tours</a>
            <a class="sidebar-link active" href="bookings.php">Bookings</a>
            <a class="sidebar-link" href="payments.php">Payments</a>
            <a class="sidebar-link" href="settings.php">Settings</a>
        </nav>
        <div class="mt-6 pt-4 border-t border-slate-200">
            <a href="logout.php" class="admin-btn admin-btn-danger w-full">Dang xuat</a>
        </div>
    </aside>

    <div class="admin-content">
        <header class="admin-topbar">
            <div class="admin-shell py-3 flex items-center justify-between">
                <div>
                    <h1 class="admin-title text-2xl">Quan ly dat tour</h1>
                    <p class="admin-subtitle">Loc, sap xep, doi soat thanh toan nhanh.</p>
                </div>
                <button type="button" id="darkModeToggle" class="admin-btn admin-btn-outline">Dark mode</button>
            </div>
        </header>

        <main class="admin-shell space-y-6">
            <section class="admin-stat-grid">
                <article class="admin-stat">
                    <p class="label">Tong booking</p>
                    <p class="value"><?= (int)$totalRows ?></p>
                </article>
                <article class="admin-stat">
                    <p class="label">Booking PENDING</p>
                    <p class="value text-amber-700"><?= (int)$statPending ?></p>
                </article>
                <article class="admin-stat">
                    <p class="label">Booking PAID</p>
                    <p class="value text-emerald-700"><?= (int)$statPaid ?></p>
                </article>
            </section>

            <?php if ($flashMessage !== ''): ?>
                <div class="flash <?= $flashType === 'error' ? 'flash-error' : 'flash-success' ?>">
                    <?= e($flashMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!$bookingsTableExists): ?>
                <section class="bg-red-100 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
                    Khong tim thay bang bookings trong database. Vui long tao bang nay truoc khi quan ly dat tour.
                </section>
            <?php endif; ?>

            <section class="admin-panel p-4 <?= !$bookingsTableExists ? 'opacity-60 pointer-events-none' : '' ?>">
                <form method="get" class="grid md:grid-cols-5 gap-3 items-end">
                    <label class="block">
                        <span class="text-xs text-slate-600">Phuong thuc</span>
                        <select name="payment_method" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Tat ca</option>
                            <option value="MOMO" <?= $filterMethod === 'MOMO' ? 'selected' : '' ?>>MOMO</option>
                            <option value="CASH" <?= $filterMethod === 'CASH' ? 'selected' : '' ?>>CASH</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs text-slate-600">Trang thai TT</span>
                        <select name="payment_status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Tat ca</option>
                            <option value="PENDING" <?= $filterStatus === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                            <option value="PAID" <?= $filterStatus === 'PAID' ? 'selected' : '' ?>>PAID</option>
                            <option value="FAILED" <?= $filterStatus === 'FAILED' ? 'selected' : '' ?>>FAILED</option>
                            <option value="UNPAID" <?= $filterStatus === 'UNPAID' ? 'selected' : '' ?>>UNPAID</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs text-slate-600">So dong / trang</span>
                        <select name="per_page" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <?php foreach ([10, 20, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="block md:col-span-2">
                        <span class="text-xs text-slate-600">Tim kiem (ten khach, SDT, tour, ma thanh toan)</span>
                        <div class="mt-1 flex gap-2">
                            <input type="text" name="keyword" value="<?= e($filterKeyword) ?>" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Nhap tu khoa...">
                            <button type="submit" class="admin-btn admin-btn-primary">Loc</button>
                            <a href="bookings.php" class="admin-btn admin-btn-outline">Xoa loc</a>
                        </div>
                    </label>
                </form>
            </section>

            <section class="admin-panel overflow-hidden <?= !$bookingsTableExists ? 'opacity-60' : '' ?>">
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= sortLink('id', 'ID', $sortBy, $sortDir) ?></th>
                                <th><?= sortLink('tour', 'Tour', $sortBy, $sortDir) ?></th>
                                <th><?= sortLink('customer_name', 'Khach', $sortBy, $sortDir) ?></th>
                                <th>SDT</th>
                                <th><?= sortLink('payment_method', 'Thanh toan', $sortBy, $sortDir) ?></th>
                                <th><?= sortLink('payment_status', 'Trang thai TT', $sortBy, $sortDir) ?></th>
                                <th>Ma thanh toan</th>
                                <th>Nguoi lon</th>
                                <th>Tre em</th>
                                <th>Em be</th>
                                <th><?= sortLink('total_amount', 'Tong tien', $sortBy, $sortDir) ?></th>
                                <th><?= sortLink('created_at', 'Ngay dat', $sortBy, $sortDir) ?></th>
                                <th>Thao tac</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bookings)): ?>
                                <?php foreach ($bookings as $row): ?>
                                    <?php $status = strtoupper((string)($row['payment_status'] ?? 'UNPAID')); ?>
                                    <tr>
                                        <td><?= (int)($row['id'] ?? 0) ?></td>
                                        <td><?= e($row['tour_title'] ?? 'Tour da bi xoa') ?></td>
                                        <td><?= e($row['customer_name'] ?? '') ?></td>
                                        <td><?= e($row['phone'] ?? '') ?></td>
                                        <td><?= e($row['payment_method'] ?? 'CASH') ?></td>
                                        <td>
                                            <span class="badge <?= $status === 'PAID' ? 'badge-success' : ($status === 'FAILED' ? 'badge-error' : 'badge-warning') ?>">
                                                <?= e($status) ?>
                                            </span>
                                        </td>
                                        <td class="text-xs text-slate-500"><?= e($row['payment_reference'] ?? '') ?></td>
                                        <td><?= (int)($row['adult'] ?? $row['quantity'] ?? 0) ?></td>
                                        <td><?= (int)($row['child'] ?? 0) ?></td>
                                        <td><?= (int)($row['baby'] ?? 0) ?></td>
                                        <td><?= number_format((float)($row['total_amount'] ?? 0)) ?> đ</td>
                                        <td><?= e($row['created_at'] ?? '') ?></td>
                                        <td>
                                            <?php if ($status === 'PENDING'): ?>
                                                <form method="post" onsubmit="return confirm('Xac nhan doi soat booking nay sang PAID?');">
                                                    <input type="hidden" name="action" value="mark_paid">
                                                    <input type="hidden" name="booking_id" value="<?= (int)($row['id'] ?? 0) ?>">
                                                    <input type="hidden" name="return_qs" value="<?= e($returnQuery) ?>">
                                                    <button type="submit" class="admin-btn admin-btn-primary !px-3 !py-1.5 !text-xs">Doi soat -> PAID</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="13" class="text-center text-slate-500">Khong co booking phu hop bo loc.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($bookingsTableExists): ?>
                    <div class="admin-pagination">
                        <p>Hien thi <?= count($bookings) ?> / <?= (int)$totalRows ?> booking</p>
                        <div class="admin-page-links">
                            <?php if ($page > 1): ?>
                                <a class="admin-page-link" href="bookings.php?<?= e(buildQuery(['page' => $page - 1], ['msg', 'type'])) ?>">Truoc</a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            for ($p = $start; $p <= $end; $p++):
                            ?>
                                <a class="admin-page-link <?= $p === $page ? 'active' : '' ?>" href="bookings.php?<?= e(buildQuery(['page' => $p], ['msg', 'type'])) ?>"><?= $p ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a class="admin-page-link" href="bookings.php?<?= e(buildQuery(['page' => $page + 1], ['msg', 'type'])) ?>">Sau</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
(function() {
    const body = document.getElementById('adminBody');
    const toggle = document.getElementById('darkModeToggle');
    const key = 'smarttourist-admin-dark';

    const saved = localStorage.getItem(key);
    if (saved === '1') {
        body.classList.add('admin-dark');
    }

    function syncLabel() {
        toggle.textContent = body.classList.contains('admin-dark') ? 'Light mode' : 'Dark mode';
    }
    syncLabel();

    toggle.addEventListener('click', function() {
        body.classList.toggle('admin-dark');
        localStorage.setItem(key, body.classList.contains('admin-dark') ? '1' : '0');
        syncLabel();
    });
})();
</script>
</body>
</html>
