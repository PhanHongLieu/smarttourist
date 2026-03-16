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
        $error = 'Vui long nhap day du tai khoan va mat khau.';
    } else {
        if (adminLogin($username, $password)) {
            if (strpos($next, '/admin/') === false && strpos($next, 'admin/') === false) {
                $next = 'tours.php';
            }
            header('Location: ' . $next);
            exit;
        }
        $error = 'Tai khoan hoac mat khau khong dung.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dang nhap admin | SmartTourist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-1">Admin Login</h1>
        <p class="text-sm text-slate-600 mb-6">Dang nhap de quan tri he thong.</p>

        <?php if ($error !== ''): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-red-100 border border-red-200 text-red-700 text-sm">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="next" value="<?= htmlspecialchars((string)$next, ENT_QUOTES, 'UTF-8') ?>">

            <label class="block">
                <span class="text-sm text-slate-700">Tai khoan</span>
                <input name="username" class="mt-1 w-full border rounded-lg px-3 py-2" autocomplete="username" required>
            </label>

            <label class="block">
                <span class="text-sm text-slate-700">Mat khau</span>
                <input type="password" name="password" class="mt-1 w-full border rounded-lg px-3 py-2" autocomplete="current-password" required>
            </label>

            <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg font-semibold">
                Dang nhap
            </button>
        </form>

        <p class="text-xs text-slate-500 mt-4">Mat khau duoc xac thuc bang password hash luu trong database.</p>
    </div>
</body>
</html>
