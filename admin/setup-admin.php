<?php
require_once __DIR__ . '/auth.php';

if (adminHasAnyUser() && !adminIsLoggedIn()) {
    http_response_code(403);
    exit('Forbidden');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (strlen($password) < 8) {
        $error = 'Mật khẩu tối thiểu 8 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Xác nhận mật khẩu không trùng khớp.';
    } else {
        try {
            if (adminCreateUser($username, $password)) {
                $success = 'Tạo tài khoản admin thành công. Bạn có thể đăng nhập ngay bây giờ.';
            } else {
                $error = 'Không tạo được tài khoản. Username có thể đã tồn tại.';
            }
        } catch (Throwable $e) {
            $error = 'Không thể tạo tài khoản: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tạo admin | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-theme min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md admin-panel p-6">
        <p class="text-xs uppercase tracking-[0.22em] text-cyan-700 font-semibold">SmartTourist Admin</p>
        <h1 class="admin-title mt-1">Tạo tài khoản admin</h1>
        <p class="admin-subtitle mb-6">Thông tin đăng nhập được bảo mật bằng password hash.</p>

        <?php if ($error !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-red-100 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <a href="login.php" class="inline-block w-full text-center bg-slate-900 text-white py-2.5 rounded-lg font-semibold">Đi đến trang đăng nhập</a>
        <?php else: ?>
            <form method="post" class="space-y-4">
                <label class="block">
                    <span class="text-sm text-slate-700">Tài khoản</span>
                    <input name="username" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Mật khẩu</span>
                    <input type="password" name="password" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Xác nhận mật khẩu</span>
                    <input type="password" name="confirm_password" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg font-semibold">
                    Tạo tài khoản
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

