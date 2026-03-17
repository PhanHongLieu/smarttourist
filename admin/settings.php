<?php
require_once __DIR__ . '/auth.php';
adminRequireLogin();

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $table]);
    return (bool)$stmt->fetchColumn();
}

function ensureSettingsTable(PDO $pdo): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
}

function getAllSettings(PDO $pdo): array
{
    if (!tableExists($pdo, 'admin_settings')) {
        return [];
    }

    $stmt = $pdo->query('SELECT setting_key, setting_value FROM admin_settings');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $row) {
        $key = (string)($row['setting_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $out[$key] = (string)($row['setting_value'] ?? '');
    }
    return $out;
}

function saveSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO admin_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
    ]);
}

$settingsDef = [
    'app_base_url' => ['label' => 'Địa chỉ APP Base URL', 'group' => 'system', 'placeholder' => 'https://smarttourist-web.onrender.com'],
    'company_name' => ['label' => 'Tên công ty', 'group' => 'system', 'placeholder' => 'SmartTourist'],
    'support_email' => ['label' => 'Email hỗ trợ', 'group' => 'system', 'placeholder' => 'support@smarttourist.vn'],
    'support_phone' => ['label' => 'Số hotline', 'group' => 'system', 'placeholder' => '0909000000'],
    'office_address' => ['label' => 'Địa chỉ văn phòng', 'group' => 'system', 'placeholder' => '123 Nguyen Hue, Quan 1, TP.HCM'],

    'momo_endpoint' => ['label' => 'MoMo endpoint', 'group' => 'momo', 'placeholder' => 'https://test-payment.momo.vn/v2/gateway/api/create'],
    'momo_partner_code' => ['label' => 'Mã đối tác MoMo', 'group' => 'momo', 'placeholder' => 'MOMOxxxxx'],
    'momo_access_key' => ['label' => 'Khóa truy cập MoMo', 'group' => 'momo', 'placeholder' => 'access_key'],
    'momo_secret_key' => ['label' => 'Khóa bí mật MoMo', 'group' => 'momo', 'placeholder' => 'secret_key'],
    'momo_request_type' => ['label' => 'Loại yêu cầu MoMo', 'group' => 'momo', 'placeholder' => 'captureWallet'],
    'momo_lang' => ['label' => 'MoMo lang', 'group' => 'momo', 'placeholder' => 'vi'],
];

$flashMessage = '';
$flashType = 'success';

try {
    ensureSettingsTable($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($settingsDef as $key => $meta) {
            if ($key === 'momo_secret_key') {
                $incomingSecret = trim((string)($_POST[$key] ?? ''));
                if ($incomingSecret !== '') {
                    saveSetting($pdo, $key, $incomingSecret);
                }
                continue;
            }

            $incoming = trim((string)($_POST[$key] ?? ''));
            saveSetting($pdo, $key, $incoming);
        }

        $flashMessage = 'Đã lưu cấu hình thành công.';
        $flashType = 'success';
    }
} catch (Throwable $e) {
    $flashMessage = 'Không thể lưu cấu hình: ' . $e->getMessage();
    $flashType = 'error';
}

$current = [];
try {
    $current = getAllSettings($pdo);
} catch (Throwable $e) {
    $current = [];
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cài đặt | SmartTourist Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-theme" id="adminBody">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <p class="sidebar-brand">SmartTourist</p>
        <h2 class="sidebar-title">Bảng điều khiển</h2>
        <nav class="mt-6">
            <a class="sidebar-link" href="tours.php">Tour</a>
            <a class="sidebar-link" href="bookings.php">Đặt tour</a>
            <a class="sidebar-link" href="payments.php">Thanh toán</a>
            <a class="sidebar-link active" href="settings.php">Cài đặt</a>
        </nav>
        <div class="mt-6 pt-4 border-t border-white/15">
            <a href="logout.php" class="admin-btn admin-btn-danger w-full">Đăng xuất</a>
        </div>
    </aside>

    <div class="admin-content">
        <header class="admin-topbar">
            <div class="admin-shell py-3 flex items-center justify-between gap-2">
                <div>
                    <h1 class="admin-title text-2xl">Cài đặt</h1>
                    <p class="admin-subtitle">Quản lý cấu hình hệ thống và thanh toán.</p>
                </div>
                <button type="button" id="darkModeToggle" class="admin-btn admin-btn-outline">Chế độ tối</button>
            </div>
        </header>

        <main class="admin-shell space-y-6">
            <?php if ($flashMessage !== ''): ?>
                <div class="flash <?= $flashType === 'error' ? 'flash-error' : 'flash-success' ?>">
                    <?= e($flashMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <section class="admin-panel p-6">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-lg font-bold" style="color:var(--admin-navy)">Cài đặt hệ thống</h2>
                            <p class="text-sm text-slate-500">Thông tin hệ thống và liên hệ mặc định.</p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($settingsDef as $key => $meta): ?>
                            <?php if ($meta['group'] !== 'system') continue; ?>
                            <label class="block">
                                <span class="text-xs uppercase tracking-wide text-slate-500"><?= e($meta['label']) ?></span>
                                <input
                                    name="<?= e($key) ?>"
                                    value="<?= e($current[$key] ?? '') ?>"
                                    placeholder="<?= e($meta['placeholder']) ?>"
                                    class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
                                >
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-panel p-6">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-lg font-bold" style="color:var(--admin-navy)">Cài đặt MoMo</h2>
                            <p class="text-sm text-slate-500">Cấu hình thanh toán MoMo (được ưu tiên hơn biến môi trường nếu có giá trị).</p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($settingsDef as $key => $meta): ?>
                            <?php if ($meta['group'] !== 'momo') continue; ?>
                            <label class="block <?= $key === 'momo_secret_key' ? 'md:col-span-2' : '' ?>">
                                <span class="text-xs uppercase tracking-wide text-slate-500"><?= e($meta['label']) ?></span>
                                <?php if ($key === 'momo_secret_key'): ?>
                                    <input
                                        type="password"
                                        name="<?= e($key) ?>"
                                        value=""
                                        placeholder="Để trống để giữ nguyên secret key hiện tại"
                                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
                                    >
                                <?php else: ?>
                                    <input
                                        name="<?= e($key) ?>"
                                        value="<?= e($current[$key] ?? '') ?>"
                                        placeholder="<?= e($meta['placeholder']) ?>"
                                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
                                    >
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="flex items-center justify-end">
                    <button type="submit" class="admin-btn admin-btn-primary">Lưu cài đặt</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
(function() {
  const body = document.getElementById('adminBody');
  const btn = document.getElementById('darkModeToggle');
  const key = 'smarttourist-admin-dark';
  if (localStorage.getItem(key) === '1') {
    body.classList.add('admin-dark');
  }
  const sync = () => {
    btn.textContent = body.classList.contains('admin-dark') ? 'Chế độ sáng' : 'Chế độ tối';
  };
  sync();
  btn.addEventListener('click', () => {
    body.classList.toggle('admin-dark');
    localStorage.setItem(key, body.classList.contains('admin-dark') ? '1' : '0');
    sync();
  });
})();
</script>
</body>
</html>

