<?php
require_once __DIR__ . '/../config/database.php';

/* =========================
   FILTER LOGIC (PDO-friendly)
   - When no filters are applied, show ALL tours.
   - When any filter is applied, restrict to published tours and apply filters.
========================= */
$params = [];
$clauses = [];
$bindings = []; // named parameters for prepared statements
$filtersApplied = false;

// Helper: add LIKE pattern
function likeParamName($base, &$i) { return ':' . $base . '_' . ($i++); }

// region[] (checkboxes from mega-menu)
if (!empty($_GET['region'])) {
  $regions = is_array($_GET['region']) ? $_GET['region'] : [$_GET['region']];
  $or = [];
  $idx = 0;
  foreach ($regions as $r) {
    $r = trim($r);
    if ($r === '') continue;
    $p = ':region_' . $idx;
    $or[] = "(destination LIKE $p OR departure_location LIKE $p)";
    $bindings[$p] = "%{$r}%";
    $idx++;
  }
  if (count($or) > 0) {
    $clauses[] = '(' . implode(' OR ', $or) . ')';
    $params['region'] = $regions;
    $filtersApplied = true;
  }
}

// location[] (checkboxes) or single location param
if (!empty($_GET['location'])) {
  $locs = is_array($_GET['location']) ? $_GET['location'] : [$_GET['location']];
  $or = [];
  $idx = 0;
  foreach ($locs as $loc) {
    $loc = trim($loc);
    if ($loc === '') continue;
    $p = ':location_' . $idx;
    $or[] = "(destination LIKE $p OR title LIKE $p OR departure_location LIKE $p)";
    $bindings[$p] = "%{$loc}%";
    $idx++;
  }
  if (count($or) > 0) {
    $clauses[] = '(' . implode(' OR ', $or) . ')';
    $params['location'] = $locs[0];
    $filtersApplied = true;
  }
}

// category[] (map to highlight or title)
if (!empty($_GET['category'])) {
  $cats = is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']];
  $or = [];
  $idx = 0;
  foreach ($cats as $c) {
    $c = trim($c);
    if ($c === '') continue;
    $p = ':cat_' . $idx;
    $or[] = "(highlight LIKE $p OR title LIKE $p)";
    $bindings[$p] = "%{$c}%";
    $idx++;
  }
  if (count($or) > 0) {
    $clauses[] = '(' . implode(' OR ', $or) . ')';
    $params['category'] = $cats;
    $filtersApplied = true;
  }
}

// length
if (!empty($_GET['length'])) {
  if ($_GET['length'] === 'short') {
    $clauses[] = "duration_days <= 3";
  } elseif ($_GET['length'] === 'long') {
    $clauses[] = "duration_days >= 4";
  }
  $params['length'] = $_GET['length'];
  $filtersApplied = true;
}

// Advanced text fields: code, title, slug, overview, description,
$textFields = ['code','title','slug','overview','description','departure_location','destination','vehicle','main_image','highlight','policy'];
foreach ($textFields as $f) {
  if (!empty($_GET[$f])) {
    $p = ':' . $f;
    $clauses[] = "$f LIKE $p";
    $bindings[$p] = '%' . trim($_GET[$f]) . '%';
    $params[$f] = trim($_GET[$f]);
    $filtersApplied = true;
  }
}

// Numeric ranges
$ranges = [
  ['min'=>'duration_days_min','max'=>'duration_days_max','col'=>'duration_days'],
  ['min'=>'duration_nights_min','max'=>'duration_nights_max','col'=>'duration_nights'],
  ['min'=>'price_adult_min','max'=>'price_adult_max','col'=>'price_adult'],
  ['min'=>'price_child_min','max'=>'price_child_max','col'=>'price_child'],
  ['min'=>'max_passengers_min','max'=>'max_passengers_max','col'=>'max_passengers'],
];
foreach ($ranges as $r) {
  if (isset($_GET[$r['min']]) && $_GET[$r['min']] !== '') {
    $param = ':' . $r['min'];
    $clauses[] = "{$r['col']} >= $param";
    $bindings[$param] = $_GET[$r['min']];
    $params[$r['min']] = $_GET[$r['min']];
    $filtersApplied = true;
  }
  if (isset($_GET[$r['max']]) && $_GET[$r['max']] !== '') {
    $param = ':' . $r['max'];
    $clauses[] = "{$r['col']} <= $param";
    $bindings[$param] = $_GET[$r['max']];
    $params[$r['max']] = $_GET[$r['max']];
    $filtersApplied = true;
  }
}

// status (exact match) — if provided, respect it; otherwise if any filter used, default to PUBLISHED
if (!empty($_GET['status'])) {
  $clauses[] = "status = :status";
  $bindings[':status'] = $_GET['status'];
  $params['status'] = $_GET['status'];
  $filtersApplied = true;
}

if ($filtersApplied && empty($_GET['status'])) {
  $clauses[] = "status = 'PUBLISHED'";
}

$where = '';
if (count($clauses) > 0) {
  $where = 'WHERE ' . implode(' AND ', $clauses);
}

/* =========================
   PAGINATION
========================= */
$limit  = 12;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Count total
$countSql = "SELECT COUNT(*) AS total FROM tours $where";
try {
  $countStmt = $pdo->prepare($countSql);
  // bind dynamic params
  foreach ($bindings as $k => $v) {
    $countStmt->bindValue($k, $v);
  }
  $countStmt->execute();
  $row = $countStmt->fetch();
  $total = $row ? (int)$row['total'] : 0;
} catch (Exception $e) {
  $total = 0;
}
$pages = max(1, ceil($total / $limit));

// Fetch tours
$sql = "
  SELECT
    id,
    title,
    slug,
    main_image,
    price_adult,
    departure_location,
    destination,
    duration_days,
    duration_nights
  FROM tours
  $where
  ORDER BY created_at DESC
  LIMIT :limit OFFSET :offset
";
try {
    $stmt = $pdo->prepare($sql);
  // bind dynamic params built from filters
  foreach ($bindings as $k => $v) {
    $stmt->bindValue($k, $v);
  }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
} catch (Exception $e) {
    $stmt = false;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Danh sách tour — SmartTourist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">
  <style>
    :root {
      --gold: #f5b942;
      --navy: #0a1f44;
    }
  </style>
</head>

<body class="overflow-x-hidden bg-gray-50">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mx-auto px-6 py-12">

    <!-- ================= LIST TOUR ================= -->
    <section class="md:col-span-3">
      <h1 class="text-2xl font-bold mb-6 text-[var(--navy)]">
        Danh sách tour (<?= $total ?>)
      </h1>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($stmt && $stmt->rowCount() > 0): ?>
          <?php while ($t = $stmt->fetch()): ?>
            <?php
              // xử lý ảnh
              $img = $t['main_image'];
              if (!$img) {
                $img = '/smarttourist/assets/image/hero-1.webp';
              }
            ?>
            <article class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition">
              <a href="tour-detail.php?slug=<?= htmlspecialchars($t['slug']) ?>">
                <div class="h-44 overflow-hidden relative">
                  <img
                    src="<?= htmlspecialchars($img) ?>"
                    alt="<?= htmlspecialchars($t['title']) ?>"
                    class="w-full h-full object-cover hover:scale-105 transition duration-500">

                  <span class="absolute top-3 left-3 bg-[var(--gold)] px-3 py-1 rounded font-semibold">
                    Từ <?= number_format($t['price_adult']) ?>₫
                  </span>
                </div>
              </a>

              <div class="p-4">
                <h3 class="font-semibold text-[var(--navy)] mb-2">
                  <a href="tour-detail.php?slug=<?= htmlspecialchars($t['slug']) ?>">
                    <?= htmlspecialchars($t['title']) ?>
                  </a>
                </h3>

                <p class="text-sm text-gray-600 mb-1">
                  📍 <?= htmlspecialchars($t['destination']) ?>
                </p>

                <p class="text-sm text-gray-500 mb-3">
                  ⏱ <?= (int)$t['duration_days'] ?>N<?= (int)$t['duration_nights'] ?>Đ
                </p>

                <div class="flex justify-between items-center">
                  <a href="tour-detail.php?slug=<?= htmlspecialchars($t['slug']) ?>"
                     class="text-sm font-semibold text-[var(--navy)]">
                    Xem chi tiết →
                  </a>

                  <a href="book.php?id=<?= (int)$t['id'] ?>"
                     class="bg-[var(--gold)] px-3 py-1 rounded text-sm font-semibold">
                    Đặt tour
                  </a>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="col-span-full bg-white p-6 rounded">
            Không tìm thấy tour phù hợp.
          </div>
        <?php endif; ?>

      <!-- ================= PAGINATION ================= -->
      <?php if ($pages > 1): ?>
      <div class="mt-10 justify-center gap-2">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <?php
            $qs = $_GET;
            $qs['page'] = $p;
            $link = 'tours.php?' . http_build_query($qs);
          ?>
          <a href="<?= htmlspecialchars($link) ?>"
             class="px-3 py-1 border rounded <?= $p === $page ? 'bg-[var(--gold)] font-semibold' : '' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </section>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
