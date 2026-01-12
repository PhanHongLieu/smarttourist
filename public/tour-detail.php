<?php
require_once __DIR__ . '/../config/database.php';

function e($v) {
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$slug = $_GET['slug'] ?? '';
if (!$slug) {
  http_response_code(404);
  exit('Tour không tồn tại');
}

/* ===== TOUR ===== */
$stmt = $pdo->prepare("
  SELECT * FROM tours 
  WHERE slug = :slug AND status='PUBLISHED' 
  LIMIT 1
");
$stmt->execute(['slug' => $slug]);
$tour = $stmt->fetch();
if (!$tour) {
  http_response_code(404);
  exit('Tour không tồn tại');
}

$tourId = (int)$tour['id'];

/* ===== RELATED DATA ===== */
$images     = $pdo->query("SELECT * FROM tour_images WHERE tour_id=$tourId ORDER BY sort_order")->fetchAll();
$schedule   = $pdo->query("SELECT * FROM tour_schedule WHERE tour_id=$tourId ORDER BY day_number")->fetchAll();
$departures = $pdo->query("
  SELECT * FROM tour_departure_dates 
  WHERE tour_id=$tourId 
    AND departure_date>=CURDATE() 
  ORDER BY departure_date
")->fetchAll();
$includes   = $pdo->query("SELECT title FROM tour_includes WHERE tour_id=$tourId")->fetchAll();
$excludes   = $pdo->query("SELECT title FROM tour_excludes WHERE tour_id=$tourId")->fetchAll();
$policies   = $pdo->query("SELECT * FROM tour_policy WHERE tour_id=$tourId")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?= e($tour['title']) ?> | SmartTourist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- CSS chung -->
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">

  <style>
    :root {
      --gold:#f5b942;
      --navy:#0a1f44;
    }
  </style>
</head>

<body class="bg-gray-50">
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ================= BREADCRUMB ================= -->
<div class="container mx-auto px-6 mt-6 text-sm text-gray-600">
  <a href="/" class="hover:underline">Trang chủ</a> /
  <a href="/smarttourist/pages/tours.php" class="hover:underline">Tour</a> /
  <span class="text-gray-800"><?= e($tour['title']) ?></span>
</div>

<main class="container mx-auto px-6 py-8 grid lg:grid-cols-3 gap-8">

<!-- ================= LEFT ================= -->
<section class="lg:col-span-2 space-y-8">

<!-- HERO -->
<div class="bg-white rounded-xl overflow-hidden shadow">
  <img
    src="<?= e($tour['main_image'] ?: '/smarttourist/assets/image/hero-1.webp') ?>"
    class="w-full h-[420px] object-cover"
  >
  <div class="p-6">
    <h1 class="text-2xl font-bold text-[var(--navy)] mb-2">
      <?= e($tour['title']) ?>
    </h1>
    <div class="text-sm text-gray-600 flex flex-wrap gap-4">
      <span>📍 <?= e($tour['destination']) ?></span>
      <span>⏱ <?= (int)$tour['duration_days'] ?>N<?= (int)$tour['duration_nights'] ?>Đ</span>
      <span>🚌 <?= e($tour['vehicle']) ?></span>
      <span class="font-semibold">Mã tour: <?= e($tour['code']) ?></span>
    </div>
  </div>
</div>

<!-- OVERVIEW -->
<div class="bg-white rounded-xl p-6 shadow">
  <h2 class="text-lg font-bold text-[var(--navy)] mb-3">Giới thiệu tour</h2>
  <div class="prose max-w-none">
    <?= nl2br(e($tour['overview'])) ?>
  </div>
</div>

<!-- HIGHLIGHT -->
<?php if ($tour['highlight']): ?>
<div class="bg-white rounded-xl p-6 shadow">
  <h2 class="text-lg font-bold text-[var(--navy)] mb-3">Điểm nổi bật</h2>
  <div class="prose max-w-none"><?= nl2br(e($tour['highlight'])) ?></div>
</div>
<?php endif; ?>

<!-- SCHEDULE -->
<?php if ($schedule): ?>
<div class="bg-white rounded-xl p-6 shadow">
  <h2 class="text-lg font-bold text-[var(--navy)] mb-4">Chương trình tour</h2>
  <div class="space-y-4">
    <?php foreach ($schedule as $s): ?>
    <details class="border rounded-lg p-4">
      <summary class="font-semibold cursor-pointer">
        Ngày <?= $s['day_number'] ?> – <?= e($s['title']) ?>
      </summary>
      <div class="mt-2 text-gray-700"><?= nl2br(e($s['description'])) ?></div>
    </details>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- IMAGE GALLERY -->
<?php if ($images): ?>
<div class="bg-white rounded-xl p-6 shadow">
  <h2 class="text-lg font-bold text-[var(--navy)] mb-3">Hình ảnh</h2>
  <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <?php foreach ($images as $img): ?>
      <img src="<?= e($img['image_url']) ?>" class="rounded object-cover h-40 w-full">
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</section>

<!-- ================= RIGHT ================= -->
<aside class="lg:col-span-1">
<div class="bg-white rounded-xl p-6 shadow sticky top-6 space-y-6">

<!-- PRICE TABLE -->
<div>
  <h3 class="font-semibold mb-3">Bảng giá tour</h3>
  <table class="w-full text-sm border rounded overflow-hidden">
    <tr class="bg-gray-100">
      <th class="text-left p-2">Đối tượng</th>
      <th class="text-right p-2">Giá</th>
    </tr>
    <tr class="border-t">
      <td class="p-2">Người lớn</td>
      <td class="p-2 text-right font-semibold text-[var(--navy)]">
        <?= number_format($tour['price_adult']) ?>₫
      </td>
    </tr>
    <tr class="border-t">
      <td class="p-2">Trẻ em</td>
      <td class="p-2 text-right"><?= number_format($tour['price_child']) ?>₫</td>
    </tr>
    <tr class="border-t">
      <td class="p-2">Em bé</td>
      <td class="p-2 text-right"><?= isset($tour['price_baby']) ? number_format($tour['price_baby']) . '₫' : '0₫' ?></td>
    </tr>
  </table>
</div>

<!-- INLINE BOOKING FORM (moved here) -->
<form id="bookingForm" action="book.php" method="get" class="space-y-4">

  <input type="hidden" name="id" value="<?= $tourId ?>">

  <div>
    <label class="text-sm font-medium">Ngày khởi hành</label>
    <input type="date" name="departure_date" id="departureDateInput" required
           class="w-full border rounded px-3 py-2 mt-1"
           min="<?= date('Y-m-d') ?>" />
    <div id="departureHelp" class="text-xs text-red-600 mt-1 hidden">Vui lòng chọn ngày trong tương lai.</div>
    <div class="text-sm text-gray-700 mt-2">
      Khởi hành: <span id="selectedDeparture">—</span>
      &nbsp;•&nbsp; Kết thúc: <span id="selectedEnd">—</span>
    </div>
  </div>

  <div class="grid grid-cols-3 gap-3">
    <div>
      <label class="text-sm">Người lớn</label>
      <input type="number" id="qty_adult" name="adult" value="1" min="1"
             class="w-full border rounded px-2 py-1">
    </div>
    <div>
      <label class="text-sm">Trẻ em</label>
      <input type="number" id="qty_child" name="child" value="0" min="0"
             class="w-full border rounded px-2 py-1">
    </div>
    <div>
      <label class="text-sm">Em bé</label>
      <input type="number" id="qty_baby" name="baby" value="0" min="0"
             class="w-full border rounded px-2 py-1">
    </div>
  </div>

  <div class="mt-3 text-right">
    <div class="text-sm">Tổng tiền: <span id="modalTotal" class="font-semibold text-red-600">0 đ</span></div>
  </div>

  <button type="submit" class="w-full bg-[var(--gold)] text-black py-3 rounded font-semibold">
    Tiếp tục
  </button>

</form>

<!-- INCLUDES -->
<?php if ($includes): ?>
<div>
  <h3 class="font-semibold mb-2">Giá bao gồm</h3>
  <ul class="list-disc list-inside text-sm">
    <?php foreach ($includes as $i): ?><li><?= e($i['title']) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- EXCLUDES -->
<?php if ($excludes): ?>
<div>
  <h3 class="font-semibold mb-2">Không bao gồm</h3>
  <ul class="list-disc list-inside text-sm">
    <?php foreach ($excludes as $i): ?><li><?= e($i['title']) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- POLICY -->
<?php if ($policies): ?>
<div>
  <h3 class="font-semibold mb-2">Chính sách</h3>
  <?php foreach ($policies as $p): ?>
    <div class="mb-2">
      <strong><?= e($p['title']) ?></strong>
      <div class="text-sm"><?= nl2br(e($p['description'])) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
</aside>
</main>

<!-- modal removed; booking form is inline above -->

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const price = {
  adult: <?= (float)$tour['price_adult'] ?>,
  child: <?= (float)$tour['price_child'] ?>,
  baby:  <?= (float)(isset($tour['price_baby']) ? $tour['price_baby'] : 0) ?>
};

// duration in days (used to compute end date)
const durationDays = <?= (int)$tour['duration_days'] ?>;

function openBooking(date){
  const input = document.getElementById('departureDateInput');
  const form = document.getElementById('bookingForm');
  if (date) input.value = date;
  updateModalTotal();
  if (form) {
    form.scrollIntoView({behavior:'smooth', block:'center'});
    input.focus();
  }
}

function formatDateYMDtoDMY(ymd){
  if (!ymd) return '—';
  const d = new Date(ymd + 'T00:00:00');
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yy = d.getFullYear();
  return dd + '/' + mm + '/' + yy;
}

function computeEndDate(ymd){
  if (!ymd) return null;
  const d = new Date(ymd + 'T00:00:00');
  // end date = start + (durationDays - 1)
  d.setDate(d.getDate() + Math.max(0, durationDays - 1));
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yy = d.getFullYear();
  return dd + '/' + mm + '/' + yy;
}

function updateModalTotal(){
  const a = parseInt(document.getElementById('qty_adult').value) || 0;
  const c = parseInt(document.getElementById('qty_child').value) || 0;
  const b = parseInt(document.getElementById('qty_baby').value) || 0;
  const total = a*price.adult + c*price.child + b*price.baby;
  document.getElementById('modalTotal').innerText = total.toLocaleString('vi-VN') + ' đ';

  // update selected departure and end date display
  const dateInput = document.getElementById('departureDateInput');
  const sel = document.getElementById('selectedDeparture');
  const end = document.getElementById('selectedEnd');
  if (dateInput && sel && end) {
    sel.textContent = dateInput.value ? formatDateYMDtoDMY(dateInput.value) : '—';
    end.textContent = dateInput.value ? computeEndDate(dateInput.value) : '—';
  }
}

document.addEventListener('DOMContentLoaded', function(){
  ['qty_adult','qty_child','qty_baby','departureDateInput'].forEach(id=>{
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', updateModalTotal);
  });

  // validate form on submit: ensure date is today or future
  const form = document.querySelector('#bookingForm');
  if (form) {
    form.addEventListener('submit', function(e){
      const dateInput = document.getElementById('departureDateInput');
      if (!dateInput.value) { e.preventDefault(); alert('Vui lòng chọn ngày khởi hành'); return; }
      const selected = new Date(dateInput.value);
      const today = new Date();
      today.setHours(0,0,0,0);
      if (selected < today) { e.preventDefault(); alert('Ngày khởi hành phải là ngày hôm nay hoặc tương lai'); return; }
    });
  }

  updateModalTotal();
});
</script>

</body>
</html>
