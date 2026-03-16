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
        $error = 'Vui long nhap day du thong tin.';
    } elseif (strlen($password) < 8) {
        $error = 'Mat khau toi thieu 8 ky tu.';
    } elseif ($password !== $confirm) {
        $error = 'Xac nhan mat khau khong trung khop.';
    } else {
        try {
            if (adminCreateUser($username, $password)) {
                $success = 'Tao tai khoan admin thanh cong. Ban co the dang nhap ngay bay gio.';
            } else {
                $error = 'Khong tao duoc tai khoan. Username co the da ton tai.';
            }
        } catch (Throwable $e) {
            $error = 'Khong the tao tai khoan: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tao admin | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-1">Tao tai khoan admin</h1>
        <p class="text-sm text-slate-600 mb-6">Thong tin dang nhap se duoc luu bang password hash trong database.</p>

        <?php if ($error !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-red-100 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <a href="login.php" class="inline-block w-full text-center bg-slate-900 text-white py-2.5 rounded-lg font-semibold">Di den trang dang nhap</a>
        <?php else: ?>
            <form method="post" class="space-y-4">
                <label class="block">
                    <span class="text-sm text-slate-700">Tai khoan</span>
                    <input name="username" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Mat khau</span>
                    <input type="password" name="password" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <label class="block">
                    <span class="text-sm text-slate-700">Xac nhan mat khau</span>
                    <input type="password" name="confirm_password" class="mt-1 w-full border rounded-lg px-3 py-2" required>
                </label>

                <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg font-semibold">
                    Tao tai khoan
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
