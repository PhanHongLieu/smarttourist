<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/payment_helpers.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$orderId = trim((string)($_GET['orderId'] ?? ''));
$resultCode = (int)($_GET['resultCode'] ?? -1);
$message = trim((string)($_GET['message'] ?? ''));
$signatureValid = false;

$cfg = momoConfig();
if ($cfg['secretKey'] !== '') {
    // Return URL payload from MoMo normally contains similar fields as IPN.
    $signatureValid = momoVerifyIpnSignature($_GET, $cfg['secretKey']);
}

$booking = null;
if ($orderId !== '') {
    try {
        ensureBookingPaymentColumns($pdo);
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE payment_reference = :ref LIMIT 1');
        $stmt->execute(['ref' => $orderId]);
        $booking = $stmt->fetch();

        if ($booking && $signatureValid) {
            $newStatus = $resultCode === 0 ? 'PAID' : 'FAILED';
            [$sql, $updateData] = buildUpdateFromAvailableColumns($pdo, 'id', [
                'payment_status' => $newStatus,
                'payment_message' => $message,
                'paid_at' => $newStatus === 'PAID' ? date('Y-m-d H:i:s') : null,
            ]);
            $up = $pdo->prepare($sql);
            $up->execute(array_merge($updateData, ['where_value' => (int)$booking['id']]));

            $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int)$booking['id']]);
            $booking = $stmt->fetch();
        }
    } catch (Throwable $e) {
        $booking = null;
    }
}

$displayStatus = strtoupper((string)($booking['payment_status'] ?? ($resultCode === 0 ? 'PAID' : 'PENDING')));
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ket qua thanh toan MoMo | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-100 min-h-screen">
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-bold text-slate-900">Ket qua thanh toan MoMo</h1>
        <p class="text-sm text-slate-600 mt-1">Ma thanh toan: <strong><?= e($orderId !== '' ? $orderId : 'Khong co') ?></strong></p>

        <div class="mt-6 rounded-xl border p-4 <?= $displayStatus === 'PAID' ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' ?>">
            <p class="text-sm">Trang thai: <strong><?= e($displayStatus) ?></strong></p>
            <p class="text-sm mt-1">Thong bao: <?= e($message !== '' ? $message : 'Dang cho IPN cap nhat trang thai') ?></p>
            <p class="text-xs mt-2 text-slate-500">Chu ky du lieu: <?= $signatureValid ? 'Hop le' : 'Khong xac minh duoc' ?></p>
        </div>

        <?php if ($booking): ?>
            <div class="mt-5 text-sm space-y-1">
                <p>Booking ID: <strong>#<?= (int)$booking['id'] ?></strong></p>
                <p>Tong tien: <strong><?= number_format((float)($booking['total_amount'] ?? 0)) ?> đ</strong></p>
                <p>Phuong thuc: <strong><?= e((string)($booking['payment_method'] ?? 'MOMO')) ?></strong></p>
            </div>
        <?php endif; ?>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="tours.php" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Ve danh sach tour</a>
            <a href="index.php" class="px-4 py-2 rounded-lg border border-slate-300 text-sm">Ve trang chu</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
