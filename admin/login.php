<?php
require_once __DIR__ . '/auth.php';

if (!adminHasAnyUser()) {
    header('Location: setup-admin.php');
    exit;
}

if (adminIsLoggedIn()) {
    header('Location: tours.php');
    exit;
}

$error = '';
$next = $_GET['next'] ?? $_POST['next'] ?? 'tours.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.';
    } else {
        if (adminLogin($username, $password)) {
            if (strpos($next, '/admin/') === false && strpos($next, 'admin/') === false) {
                $next = 'tours.php';
            }
            header('Location: ' . $next);
            exit;
        }
        $error = 'Tài khoản hoặc mật khẩu không đúng.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập admin | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-theme min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md admin-panel p-6">
        <p class="text-xs uppercase tracking-[0.22em] font-semibold" style="color:var(--admin-gold)">SmartTourist Admin</p>
        <h1 class="admin-title mt-1">Đăng nhập hệ thống</h1>
        <p class="admin-subtitle mb-6">Truy cập khu vực quản trị an toàn.</p>

        <?php if ($error !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-red-100 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="next" value="<?= htmlspecialchars((string)$next, ENT_QUOTES, 'UTF-8') ?>">

            <label class="block">
                <span class="text-sm text-slate-700">Tài khoản</span>
                <input name="username" class="mt-1 w-full border rounded-lg px-3 py-2" autocomplete="username" required>
            </label>

            <label class="block">
                <span class="text-sm text-slate-700">Mật khẩu</span>
                <input type="password" name="password" class="mt-1 w-full border rounded-lg px-3 py-2" autocomplete="current-password" required>
            </label>

            <button type="submit" class="w-full admin-btn admin-btn-primary py-2.5 rounded-lg">
                Đăng nhập
            </button>
        </form>

        <p class="text-xs text-slate-500 mt-4">Mật khẩu được xác thực bằng password hash lưu trong database.</p>
    </div>
</body>
</html>

