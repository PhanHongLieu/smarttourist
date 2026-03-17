<?php
require_once __DIR__ . '/auth.php';
adminRequireLogin();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thanh toán | SmartTourist Admin</title>
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
            <a class="sidebar-link active" href="payments.php">Thanh toán</a>
            <a class="sidebar-link" href="settings.php">Cài đặt</a>
        </nav>
        <div class="mt-6 pt-4 border-t border-white/15">
            <a href="logout.php" class="admin-btn admin-btn-danger w-full">Đăng xuất</a>
        </div>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar">
            <div class="admin-shell py-3 flex items-center justify-between">
                <div>
                    <h1 class="admin-title text-2xl">Thanh toán</h1>
                    <p class="admin-subtitle">Quản lý đối soát và cấu hình cổng thanh toán.</p>
                </div>
                <button type="button" id="darkModeToggle" class="admin-btn admin-btn-outline">Chế độ tối</button>
            </div>
        </header>
        <main class="admin-shell">
            <section class="admin-panel p-6">
                <h2 class="text-lg font-bold" style="color:var(--admin-navy)">Sắp ra mắt</h2>
                <p class="text-sm text-slate-600 mt-2">Trang Thanh toán se duoc mo rong cho doi soat MoMo, lich su IPN va export bao cao.</p>
            </section>
        </main>
    </div>
</div>
<script>
(function(){
  const body=document.getElementById('adminBody');
  const btn=document.getElementById('darkModeToggle');
  const key='smarttourist-admin-dark';
  if(localStorage.getItem(key)==='1'){body.classList.add('admin-dark');}
  const sync=()=>btn.textContent=body.classList.contains('admin-dark')?'Chế độ sáng':'Chế độ tối';
  sync();
  btn.addEventListener('click',()=>{body.classList.toggle('admin-dark');localStorage.setItem(key,body.classList.contains('admin-dark')?'1':'0');sync();});
})();
</script>
</body>
</html>

