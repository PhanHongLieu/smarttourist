<?php
require_once __DIR__ . '/auth.php';
adminRequireLogin();

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$bookings = [];
try {
    $sql = "
        SELECT b.*, t.title AS tour_title
        FROM bookings b
        LEFT JOIN tours t ON b.tour_id = t.id
        ORDER BY b.created_at DESC, b.id DESC
    ";
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll();
} catch (Throwable $e) {
    $bookings = [];
}
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
                <p class="text-sm text-slate-600">Thong tin booking tu khach hang.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="tours.php" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Quan ly tour</a>
                <a href="logout.php" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm">Dang xuat</a>
            </div>
        </div>

        <section class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Tour</th>
                            <th class="px-4 py-3 text-left">Khach</th>
                            <th class="px-4 py-3 text-left">SDT</th>
                            <th class="px-4 py-3 text-left">Nguoi lon</th>
                            <th class="px-4 py-3 text-left">Tre em</th>
                            <th class="px-4 py-3 text-left">Em be</th>
                            <th class="px-4 py-3 text-left">Tong tien</th>
                            <th class="px-4 py-3 text-left">Ngay dat</th>
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
                                    <td class="px-4 py-3"><?= (int)($row['adult'] ?? $row['quantity'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= (int)($row['child'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= (int)($row['baby'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= number_format((float)($row['total_amount'] ?? 0)) ?> đ</td>
                                    <td class="px-4 py-3"><?= e($row['created_at'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-slate-500">Chua co du lieu dat tour.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
