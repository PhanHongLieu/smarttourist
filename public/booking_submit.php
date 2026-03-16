<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/payment_helpers.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tours.php');
    exit;
}

$tourId = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
$fullname = trim((string)($_POST['fullname'] ?? ''));
$gender = trim((string)($_POST['gender'] ?? ''));
$dob = trim((string)($_POST['dob'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$departureDate = trim((string)($_POST['departure_date'] ?? ''));
$adult = max(1, (int)($_POST['adult'] ?? 1));
$child = max(0, (int)($_POST['child'] ?? 0));
$baby = max(0, (int)($_POST['baby'] ?? 0));
$paymentMethod = strtoupper(trim((string)($_POST['payment_method'] ?? 'CASH')));

if (!in_array($paymentMethod, ['MOMO', 'CASH'], true)) {
    $paymentMethod = 'CASH';
}

if ($tourId <= 0 || $fullname === '' || $email === '' || $phone === '') {
    http_response_code(422);
    exit('Thong tin dat tour khong hop le.');
}

$stmt = $pdo->prepare('SELECT id, title, price_adult, price_child, price_baby FROM tours WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $tourId]);
$tour = $stmt->fetch();

if (!$tour) {
    http_response_code(404);
    exit('Tour khong ton tai.');
}

$priceAdult = (int)($tour['price_adult'] ?? 0);
$priceChild = (int)($tour['price_child'] ?? 0);
$priceBaby = (int)($tour['price_baby'] ?? 0);
$totalAmount = $adult * $priceAdult + $child * $priceChild + $baby * $priceBaby;

$paymentStatus = $paymentMethod === 'MOMO' ? 'PENDING' : 'UNPAID';
$paymentReference = 'BK-' . date('YmdHis') . '-' . random_int(100, 999);

ensureBookingPaymentColumns($pdo);

$payload = [
    'tour_id' => $tourId,
    'customer_name' => $fullname,
    'fullname' => $fullname,
    'gender' => $gender,
    'dob' => $dob !== '' ? $dob : null,
    'email' => $email,
    'phone' => $phone,
    'adult' => $adult,
    'child' => $child,
    'baby' => $baby,
    'quantity' => $adult + $child + $baby,
    'departure_date' => $departureDate !== '' ? $departureDate : null,
    'total_amount' => $totalAmount,
    'payment_method' => $paymentMethod,
    'payment_status' => $paymentStatus,
    'payment_reference' => $paymentReference,
    'payment_message' => null,
];

$bookingId = 0;
try {
    [$sql, $insertData] = buildInsertFromAvailableColumns($pdo, $payload);
    $insert = $pdo->prepare($sql);
    $insert->execute($insertData);
    $bookingId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Khong the tao don dat tour: ' . e($e->getMessage()));
}

$momoCreateError = '';
$momoPayUrl = '';

if ($paymentMethod === 'MOMO') {
    $baseUrl = getPublicBaseUrl();
    $orderId = $paymentReference;
    $requestId = $paymentReference . '-REQ';
    $orderInfo = 'Thanh toan booking #' . $bookingId . ' - SmartTourist';
    $extraDataPayload = [
        'booking_id' => $bookingId,
        'tour_id' => $tourId,
    ];
    $extraData = base64_encode(json_encode($extraDataPayload));

    $result = momoCreatePayment([
        'requestId' => $requestId,
        'amount' => (string)$totalAmount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $baseUrl . '/momo_return.php',
        'ipnUrl' => $baseUrl . '/momo_ipn.php',
        'extraData' => $extraData,
    ]);

    if (!empty($result['ok'])) {
        $momoPayUrl = (string)$result['payUrl'];
        header('Location: ' . $momoPayUrl);
        exit;
    }

    $momoCreateError = (string)($result['message'] ?? 'Khong tao duoc giao dich MoMo');

    try {
        [$updateSql, $updateData] = buildUpdateFromAvailableColumns($pdo, 'id', [
            'payment_status' => 'FAILED',
            'payment_message' => $momoCreateError,
        ]);
        $update = $pdo->prepare($updateSql);
        $update->execute(array_merge($updateData, ['where_value' => $bookingId]));
    } catch (Throwable $e) {
        // Ignore update errors, booking was still created.
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xac nhan thanh toan | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-100 min-h-screen">
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900">Dat tour thanh cong</h1>
        <p class="text-sm text-slate-600 mt-1">Ma booking: <strong>#<?= (int)$bookingId ?></strong> - Ma thanh toan: <strong><?= e($paymentReference) ?></strong></p>

        <div class="mt-6 grid md:grid-cols-2 gap-6">
            <section class="rounded-xl border border-slate-200 p-4">
                <h2 class="font-semibold text-slate-800 mb-3">Thong tin don</h2>
                <div class="space-y-2 text-sm">
                    <p><span class="text-slate-500">Tour:</span> <?= e($tour['title']) ?></p>
                    <p><span class="text-slate-500">Khach hang:</span> <?= e($fullname) ?></p>
                    <p><span class="text-slate-500">Dien thoai:</span> <?= e($phone) ?></p>
                    <p><span class="text-slate-500">Ngay khoi hanh:</span> <?= e($departureDate !== '' ? $departureDate : 'Chua chon') ?></p>
                    <p><span class="text-slate-500">So luong:</span> <?= (int)$adult ?> nguoi lon, <?= (int)$child ?> tre em, <?= (int)$baby ?> em be</p>
                    <p class="text-lg font-bold text-slate-900 pt-2">Tong tien: <?= number_format((float)$totalAmount) ?> đ</p>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 p-4">
                <h2 class="font-semibold text-slate-800 mb-3">Thanh toan: <?= e($paymentMethod) ?></h2>

                <?php if ($paymentMethod === 'MOMO'): ?>
                    <?php if ($momoCreateError !== ''): ?>
                        <p class="text-sm text-rose-600">Khong tao duoc giao dich MoMo: <?= e($momoCreateError) ?></p>
                        <p class="text-sm text-slate-600 mt-2">Vui long dat lai don hoac lien he ho tro de thanh toan thu cong.</p>
                    <?php else: ?>
                        <p class="text-sm text-slate-600">Dang chuyen den cong thanh toan MoMo...</p>
                        <div class="mt-4 flex justify-center">
                            <a href="<?= e($momoPayUrl) ?>" class="px-4 py-2 rounded-lg bg-fuchsia-600 text-white text-sm font-semibold">Neu khong tu dong chuyen, bam vao day</a>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4 text-sm space-y-1">
                        <p><span class="text-slate-500">Trang thai:</span> <span class="font-semibold <?= $momoCreateError !== '' ? 'text-rose-600' : 'text-amber-600' ?>"><?= $momoCreateError !== '' ? 'FAILED' : 'PENDING' ?></span></p>
                        <p><span class="text-slate-500">Noi dung:</span> <strong><?= e($paymentReference) ?></strong></p>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-600">Ban da chon thanh toan tien mat. Vui long den van phong SmartTourist de thanh toan truoc ngay khoi hanh.</p>
                    <div class="mt-4 text-sm space-y-1">
                        <p><span class="text-slate-500">Trang thai:</span> <span class="font-semibold text-amber-600">UNPAID</span></p>
                        <p><span class="text-slate-500">Huong dan:</span> Nhan vien se lien he xac nhan lich hen thanh toan.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="tours.php" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Ve danh sach tour</a>
            <a href="index.php" class="px-4 py-2 rounded-lg border border-slate-300 text-sm">Ve trang chu</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
