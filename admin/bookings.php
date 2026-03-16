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
            $params['current_status'] = 'PENDING';
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
if ($bookingsTableExists) {
    try {
        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
            SELECT b.*, t.title AS tour_title
            FROM bookings b
            LEFT JOIN tours t ON b.tour_id = t.id
            {$whereSql}
            ORDER BY b.created_at DESC, b.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        $bookings = $stmt->fetchAll();
    } catch (Throwable $e) {
        $bookings = [];
    }
}

$flashMessage = (string)($_GET['msg'] ?? '');
$flashType = (string)($_GET['type'] ?? 'success');

$returnParams = [];
if ($filterMethod !== '') {
    $returnParams['payment_method'] = $filterMethod;
}
if ($filterStatus !== '') {
    $returnParams['payment_status'] = $filterStatus;
}
if ($filterKeyword !== '') {
    $returnParams['keyword'] = $filterKeyword;
}
$returnQuery = http_build_query($returnParams);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quan ly dat tour | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Danh sach dat tour</h1>
                <p class="text-sm text-slate-600">Thong tin booking, doi soat thanh toan va loc du lieu.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="tours.php" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Quan ly tour</a>
                <a href="logout.php" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm">Dang xuat</a>
            </div>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="rounded-lg px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200' ?>">
                <?= e($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!$bookingsTableExists): ?>
            <section class="bg-red-100 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
                Khong tim thay bang bookings trong database. Vui long tao bang nay truoc khi quan ly dat tour.
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow p-4 <?= !$bookingsTableExists ? 'opacity-60 pointer-events-none' : '' ?>">
            <form method="get" class="grid md:grid-cols-4 gap-3 items-end">
                <label class="block">
                    <span class="text-xs text-slate-600">Phuong thuc</span>
                    <select name="payment_method" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Tat ca</option>
                        <option value="MOMO" <?= $filterMethod === 'MOMO' ? 'selected' : '' ?>>MOMO</option>
                        <option value="CASH" <?= $filterMethod === 'CASH' ? 'selected' : '' ?>>CASH</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs text-slate-600">Trang thai thanh toan</span>
                    <select name="payment_status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Tat ca</option>
                        <option value="PENDING" <?= $filterStatus === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                        <option value="PAID" <?= $filterStatus === 'PAID' ? 'selected' : '' ?>>PAID</option>
                        <option value="FAILED" <?= $filterStatus === 'FAILED' ? 'selected' : '' ?>>FAILED</option>
                        <option value="UNPAID" <?= $filterStatus === 'UNPAID' ? 'selected' : '' ?>>UNPAID</option>
                    </select>
                </label>

                <label class="block md:col-span-2">
                    <span class="text-xs text-slate-600">Tim kiem (ten khach, SDT, tour, ma thanh toan)</span>
                    <div class="mt-1 flex gap-2">
                        <input type="text" name="keyword" value="<?= e($filterKeyword) ?>" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Nhap tu khoa...">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">Loc</button>
                        <a href="bookings.php" class="px-4 py-2 rounded-lg border border-slate-300 text-sm">Xoa loc</a>
                    </div>
                </label>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow overflow-hidden <?= !$bookingsTableExists ? 'opacity-60' : '' ?>">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Tour</th>
                            <th class="px-4 py-3 text-left">Khach</th>
                            <th class="px-4 py-3 text-left">SDT</th>
                            <th class="px-4 py-3 text-left">Thanh toan</th>
                            <th class="px-4 py-3 text-left">Trang thai TT</th>
                            <th class="px-4 py-3 text-left">Ma thanh toan</th>
                            <th class="px-4 py-3 text-left">Nguoi lon</th>
                            <th class="px-4 py-3 text-left">Tre em</th>
                            <th class="px-4 py-3 text-left">Em be</th>
                            <th class="px-4 py-3 text-left">Tong tien</th>
                            <th class="px-4 py-3 text-left">Ngay dat</th>
                            <th class="px-4 py-3 text-left">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $row): ?>
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3"><?= (int)($row['id'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= e($row['tour_title'] ?? 'Tour da bi xoa') ?></td>
                                    <td class="px-4 py-3"><?= e($row['customer_name'] ?? '') ?></td>
                                    <td class="px-4 py-3"><?= e($row['phone'] ?? '') ?></td>
                                    <td class="px-4 py-3"><?= e($row['payment_method'] ?? 'CASH') ?></td>
                                    <td class="px-4 py-3">
                                        <?php $status = strtoupper((string)($row['payment_status'] ?? 'UNPAID')); ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $status === 'PAID' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                            <?= e($status) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600"><?= e($row['payment_reference'] ?? '') ?></td>
                                    <td class="px-4 py-3"><?= (int)($row['adult'] ?? $row['quantity'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= (int)($row['child'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= (int)($row['baby'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= number_format((float)($row['total_amount'] ?? 0)) ?> đ</td>
                                    <td class="px-4 py-3"><?= e($row['created_at'] ?? '') ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($status === 'PENDING'): ?>
                                            <form method="post" onsubmit="return confirm('Xac nhan doi soat booking nay sang PAID?');">
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="booking_id" value="<?= (int)($row['id'] ?? 0) ?>">
                                                <input type="hidden" name="return_qs" value="<?= e($returnQuery) ?>">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                                    Doi soat -> PAID
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14" class="px-4 py-6 text-center text-slate-500">Khong co booking phu hop bo loc.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
