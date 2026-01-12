<?php
// ================= DATA =================
$DOMESTIC = [
  "Miền Bắc" => ["Hà Nội", "Quảng Ninh", "Cao Bằng", "Ninh Bình", "Hà Giang", "Lào Cai"],
  "Miền Trung" => ["Đà Nẵng", "Hội An", "Huế", "Nha Trang", "Bình Định - Phú Yên", "Phan Thiết"],
  "Tây Nguyên" => ["Lâm Đồng", "Pleiku", "Buôn Ma Thuột", "Kon Tum", "ĐắK LắK - Gia Lai"],
  "Đông Nam Bộ" => ["TP HCM", "Bà Rịa - Vũng Tàu", "Côn Đảo", "Hồ Tràm", "Tây Ninh", "Bình Thuận"],
  "Tây Nam Bộ" => ["Cần Thơ", "Kiên Giang", "An Giang", "Cà Mau", "Tiền Giang", "Bến Tre"],
];

$INTERNATIONAL = [
  "Đông Nam Á" => ["Thái Lan", "Singapore", "Malaysia", "Indonesia", "Philippines"],
  "Đông Bắc Á" => ["Nhật Bản", "Hàn Quốc", "Đài Loan", "Trung Quốc"],
  "Châu Âu" => ["Pháp", "Ý", "Thụy Sĩ", "Đức", "Hà Lan"],
  "Châu Úc" => ["Úc", "New Zealand"],
  "Châu Mỹ" => ["Mỹ", "Canada"],
];

// flatten giống logic React
$regionColumns = [];
foreach ($DOMESTIC as $region => $places) {
  $regionColumns[] = ['region' => $region, 'places' => $places];
}
foreach ($INTERNATIONAL as $region => $places) {
  $regionColumns[] = ['region' => $region, 'places' => $places];
}
?>

<!-- ===== BACKDROP ===== -->
<div
  id="megamenu-backdrop"
  class="hidden fixed inset-0 bg-black/30 z-30"
></div>

<!-- ===== MEGA MENU ===== -->
<div class="absolute left-0 top-full w-full bg-transparent z-40 pointer-events-none">
  <div class="container mx-auto px-6 py-6 pointer-events-auto">
    <div
      id="mega-menu"
      class="hidden bg-white shadow-2xl border-t p-6"
    >
      <form id="mega-filter-form" action="tours.php" method="get">
      <div class="grid grid-cols-[220px_1fr] gap-8">

        <!-- ================= SIDEBAR ================= -->
        <div class="border-r pr-6">
          <h3 class="text-lg font-semibold mb-4">Tìm theo</h3>
          <ul class="space-y-3 text-sm text-gray-700">
            <li class="font-medium">Thể loại</li>
            <li>
              <label class="inline-flex items-center">
                <input type="radio" name="length" value="" class="mr-2" checked>
                <span class="text-gray-600">Tất cả</span>
              </label>
            </li>
            <li>
              <label class="inline-flex items-center">
                <input type="radio" name="length" value="short" class="mr-2">
                <span class="text-gray-600">Ngắn ngày (≤ 3 ngày)</span>
              </label>
            </li>
            <li>
              <label class="inline-flex items-center">
                <input type="radio" name="length" value="long" class="mr-2">
                <span class="text-gray-600">Dài ngày (≥ 4 ngày)</span>
              </label>
            </li>
          </ul>
        </div>
        <!-- ================= CONTENT ================= -->
        <div class="grid grid-cols-6 gap-6">

          <?php foreach ($regionColumns as $col): ?>
            <div>
              <label class="flex items-center mb-2">
                <input type="checkbox" name="region[]" value="<?= htmlspecialchars($col['region']) ?>" class="mr-2">
                <h4 class="font-semibold text-[var(--navy)] uppercase text-sm mb-0">
                  <?= htmlspecialchars($col['region']) ?>
                </h4>
              </label>

              <ul class="space-y-2">
                <?php foreach ($col['places'] as $place): ?>
                  <li>
                    <div class="flex items-center">
                      <input type="checkbox" name="destination[]" value="<?= htmlspecialchars($place) ?>" class="mr-2">
                      <a
                        href="tours.php?destination=<?= urlencode($place) ?>"
                        class="text-gray-600 hover:text-[var(--gold)] transition"
                        onclick="closeMegaMenu()"
                      >
                        <?= htmlspecialchars($place) ?>
                      </a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>

              <a
                href="tours.php?region=<?= urlencode($col['region']) ?>"
                class="inline-block mt-3 text-sm text-[var(--gold)] font-medium"
                onclick="closeMegaMenu()"
              >
                Xem tất cả →
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>
  const megaMenu = document.getElementById('mega-menu');
  const megaBackdrop = document.getElementById('megamenu-backdrop');

  function openMegaMenu() {
    megaMenu.classList.remove('hidden');
    megaBackdrop.classList.remove('hidden');
  }

  function closeMegaMenu() {
    megaMenu.classList.add('hidden');
    megaBackdrop.classList.add('hidden');
  }

  megaBackdrop.addEventListener('click', closeMegaMenu);
</script>

<script>
  // Optional: auto-submit when checkboxes change. Uncomment to enable.
  // document.querySelectorAll('#mega-filter-form input[type=checkbox], #mega-filter-form input[type=radio]').forEach(el => {
  //   el.addEventListener('change', () => document.getElementById('mega-filter-form').submit());
  // });
</script>
