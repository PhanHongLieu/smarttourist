<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Lấy 3 tour gần nhất (ACTIVE)
 */
$featuredTours = [];
try {
  $stmt = $pdo->prepare(
    "SELECT id, title, slug, main_image AS thumbnail, price_adult, departure_location
     FROM tours
     WHERE status = 'PUBLISHED'
     ORDER BY created_at DESC
     LIMIT 3"
  );
  $stmt->execute();
  $featuredTours = $stmt->fetchAll();
} catch (Exception $e) {
  $featuredTours = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartTourist – Nền Tảng Du Lịch Thông Minh Hàng Đầu</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">

  <style>
    :root {
      --gold: #f5b942;
      --navy: #0a1f44;
      --soft-gold: #fdf2d8;
    }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; color: #1a1a1a; }
    
    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .tour-card {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .tour-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .slide {
      transition: opacity 1.5s ease-in-out, transform 6s ease-out;
      transform: scale(1.05);
    }
    .slide.opacity-100 { transform: scale(1); }
  </style>
</head>
<body class="overflow-x-hidden">
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="relative overflow-hidden text-white min-h-screen flex items-center">
  <?php
    $bgImages = [
      '../assets/image/hero-1.webp', '../assets/image/hero-2.jpg', '../assets/image/hero-3.webp',
      '../assets/image/hero-4.jpg', '../assets/image/hero-5.jpg', '../assets/image/hero-6.webp',
      '../assets/image/hero-7.jpg', '../assets/image/hero-8.jpg', '../assets/image/hero-9.jpg',
    ];
  ?>

  <div id="hero-slideshow" class="absolute inset-0 z-0">
    <?php foreach ($bgImages as $i => $src): ?>
      <div
        class="slide absolute inset-0 bg-cover bg-center <?php echo $i === 0 ? 'opacity-100' : 'opacity-0'; ?>"
        style="background-image:url('<?php echo $src; ?>');">
      </div>
    <?php endforeach; ?>
  </div>

  <div class="absolute inset-0 bg-black/40 z-10"></div>
  <div class="absolute inset-0 bg-gradient-to-r from-[var(--navy)]/90 via-[var(--navy)]/40 to-transparent z-10"></div>

  <div class="relative z-20 container mx-auto px-4 py-20 grid lg:grid-cols-2 gap-12 items-center">
    <div>
      <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-2 rounded-full mb-6">
        <span class="w-2 h-2 bg-[var(--gold)] rounded-full animate-pulse"></span>
        <span class="text-xs font-bold tracking-[0.2em] uppercase">Smart Travel Platform 2026</span>
      </div>

      <h1 class="text-5xl md:text-7xl font-extrabold leading-[1.1] mb-6">
        Hành trình <span class="text-[var(--gold)] italic">thông minh</span> <br> Cảm xúc trọn vẹn
      </h1>

      <p class="text-lg text-gray-200 mb-8 max-w-lg leading-relaxed font-light">
        SmartTourist không chỉ bán tour, chúng tôi kiến tạo những trải nghiệm cá nhân hóa. Kết nối bạn với những vùng đất mới bằng công nghệ quản lý hành trình tối ưu và hỗ trợ tận tâm 24/7.
      </p>

      <div class="flex flex-wrap gap-4 mb-10">
        <a href="/smarttourist/public/tours.php"
           class="bg-[var(--gold)] text-[var(--navy)] px-10 py-5 rounded-2xl font-bold hover:bg-yellow-400 transition-all shadow-lg shadow-yellow-500/20 active:scale-95 text-center">
          Khám phá tour ngay
        </a>
        <a href="/smarttourist/public/help.php"
           class="glass-card px-10 py-5 rounded-2xl font-bold hover:bg-white/20 transition-all active:scale-95 text-center">
          Cách chúng tôi vận hành
        </a>
      </div>
    </div>

    <div class="hidden lg:flex justify-end">
      <div class="glass-card p-8 rounded-[3rem] w-80 space-y-6 shadow-2xl transform hover:-translate-y-2 transition-all duration-500">
        <div class="bg-white/20 p-5 rounded-2xl border border-white/10">
          <p class="text-xs text-[var(--gold)] font-bold uppercase mb-2 tracking-tighter">Xu hướng tìm kiếm</p>
          <p class="font-bold text-lg">Côn Đảo Tâm Linh 🕊️</p>
          <p class="text-xs text-gray-300 mt-1">Viếng mộ Cô Sáu đêm huyền bí</p>
        </div>
        <ul class="space-y-4 text-sm">
          <li class="flex items-center gap-3">✨ <span class="opacity-90">Bảo hiểm du lịch toàn cầu</span></li>
          <li class="flex items-center gap-3">✨ <span class="opacity-90">Hỗ trợ Visa bao đậu 99%</span></li>
          <li class="flex items-center gap-3">✨ <span class="opacity-90">Thanh toán trả góp 0%</span></li>
          <li class="flex items-center gap-3">✨ <span class="opacity-90">HDV am hiểu kiến thức bản địa</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="py-24 bg-gray-50">
  <div class="container mx-auto px-4">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
      <div class="max-w-xl">
        <h2 class="text-4xl font-extrabold text-[var(--navy)] tracking-tight">Điểm đến hàng đầu cho bạn</h2>
        <p class="text-gray-500 mt-4 leading-relaxed">Những hành trình được thiết kế tỉ mỉ, dựa trên sở thích và xu hướng du lịch của khách hàng SmartTourist trong năm 2026.</p>
        <div class="h-1.5 w-24 bg-[var(--gold)] mt-6 rounded-full"></div>
      </div>
      <a href="/smarttourist/public/tours.php" class="bg-[var(--navy)] text-white px-6 py-3 rounded-xl font-bold hover:bg-black transition-all flex items-center gap-2 group">
        Xem toàn bộ danh sách <span class="group-hover:translate-x-1 transition-transform">→</span>
      </a>
    </div>

    <div class="grid md:grid-cols-3 gap-10">
      <?php if (!empty($featuredTours)): ?>
        <?php foreach ($featuredTours as $t): ?>
          <a href="/tour.php?slug=<?= htmlspecialchars($t['slug']) ?>" class="tour-card bg-white rounded-[2.5rem] overflow-hidden block border border-gray-100 shadow-sm">
            <div class="h-72 relative overflow-hidden">
                <div class="absolute top-5 left-5 z-10 bg-[var(--gold)] px-4 py-1.5 rounded-full text-[10px] font-black text-[var(--navy)] shadow-lg uppercase">
                    Bán chạy nhất
                </div>
              <?php if (!empty($t['thumbnail'])): ?>
                <img src="<?= htmlspecialchars($t['thumbnail']) ?>" alt="<?= htmlspecialchars($t['title']) ?>" class="w-full h-full object-cover transition-transform duration-700 hover:scale-110" />
              <?php else: ?>
                <div class="w-full h-full bg-gray-200"></div>
              <?php endif; ?>
            </div>

            <div class="p-8">
              <div class="flex items-center gap-2 text-[var(--gold)] font-bold text-xs mb-4 uppercase tracking-widest">
                📍 <span>Khởi hành từ: <?= htmlspecialchars($t['departure_location'] ?: 'TP. Hồ Chí Minh') ?></span>
              </div>
              <h3 class="font-extrabold text-2xl text-[var(--navy)] mb-6 leading-snug line-clamp-2 hover:text-[var(--gold)] transition-colors"><?= htmlspecialchars($t['title']) ?></h3>
              <div class="flex justify-between items-center pt-6 border-t border-gray-100">
                <div class="flex flex-col">
                    <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Giá trọn gói</span>
                    <span class="text-2xl font-black text-[var(--navy)]"><?= isset($t['price_adult']) ? number_format($t['price_adult']) . 'đ' : '' ?></span>
                </div>
                <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-[var(--navy)] font-bold border border-gray-100">
                    →
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="py-24 bg-[var(--navy)] text-white relative overflow-hidden">
  <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px] -mr-40 -mt-40"></div>
  <div class="container mx-auto px-4 relative z-10 text-center">
    <div class="max-w-3xl mx-auto mb-20">
        <h2 class="text-4xl md:text-5xl font-black mb-6 tracking-tight">Sẵn sàng xách vali sau 4 bước</h2>
        <p class="text-gray-400 text-lg font-light leading-relaxed">Chúng tôi đã tối ưu hóa mọi quy trình phức tạp nhất về Visa, vé máy bay và lưu trú để bạn chỉ việc tận hưởng kỳ nghỉ.</p>
    </div>

    <div class="grid md:grid-cols-4 gap-8">
      <?php 
      $icons = ['🔍', '⚖️', '💳', '🎒'];
      $steps = ["Tìm kiếm thông minh","So sánh tối ưu","Thanh toán một chạm","Tận hưởng hành trình"]; 
      $descs = [
        "Sử dụng bộ lọc thông minh theo ngân sách và sở thích.",
        "Mọi lịch trình được hiển thị minh bạch để bạn lựa chọn.",
        "Hỗ trợ đa phương thức: QR, Ví điện tử, Trả góp linh hoạt.",
        "Đội ngũ HDV và chăm sóc khách hàng hỗ trợ suốt chuyến đi."
      ];
      foreach ($steps as $i => $step): ?>
        <div class="p-8 rounded-[2rem] bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-300">
          <div class="w-16 h-16 mx-auto rounded-2xl bg-[var(--gold)] text-[var(--navy)] flex items-center justify-center text-2xl mb-6 font-bold shadow-lg shadow-yellow-500/20">
            <?= $icons[$i] ?>
          </div>
          <p class="text-xl font-black mb-3 italic tracking-tight italic"><?php echo htmlspecialchars($step); ?></p>
          <p class="text-sm text-gray-400 leading-relaxed"><?php echo $descs[$i]; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="py-12 bg-white">
  <div class="container mx-auto px-6">
    <div class="relative overflow-hidden bg-gradient-to-r from-[var(--gold)] to-yellow-500 rounded-[2rem] p-8 md:p-12 shadow-xl">
      <div class="absolute top-0 right-0 w-40 h-40 bg-white/20 rounded-full blur-3xl -mr-10 -mt-10"></div>
      
      <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
        <div class="text-center lg:text-left max-w-2xl">
          <span class="inline-block px-3 py-1 bg-[var(--navy)] text-white rounded-lg text-[10px] font-bold mb-4 uppercase tracking-widest">
            Ưu đãi độc quyền
          </span>
          <h2 class="text-3xl md:text-4xl font-black text-[var(--navy)] mb-3 leading-tight tracking-tighter">
            Sẵn sàng khám phá thế giới?
          </h2>
          <p class="text-[var(--navy)] text-sm md:text-base font-medium opacity-80 leading-relaxed">
            Nhận ngay mã giảm giá <span class="font-bold underline">500.000đ</span> cho chuyến đi đầu tiên khi đăng ký hôm nay.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
          <a href="/smarttourist/public/tours.php" class="bg-[var(--navy)] text-white px-8 py-3 rounded-xl font-bold hover:shadow-lg transition-all text-sm text-center">
            Xem ưu đãi
          </a>
          <a href="/contact.php" class="bg-white/30 backdrop-blur border border-[var(--navy)]/20 text-[var(--navy)] px-8 py-3 rounded-xl font-bold hover:bg-white/50 transition-all text-sm text-center">
            Tư vấn ngay
          </a>
        </div>
      </div>

      <div class="mt-8 pt-6 border-t border-[var(--navy)]/10 flex flex-wrap items-center justify-center lg:justify-start gap-6 grayscale opacity-50">
        <span class="text-[var(--navy)] font-bold text-[10px] uppercase tracking-widest">Đối tác:</span>
        <span class="font-bold text-sm italic tracking-tighter text-[var(--navy)]">Vietnam Airlines</span>
        <span class="font-bold text-sm italic tracking-tighter text-[var(--navy)]">Bamboo Air</span>
        <span class="font-bold text-sm italic tracking-tighter text-[var(--navy)]">Traveloka</span>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  (function(){
    const start = function(){
      const slides = document.querySelectorAll('#hero-slideshow .slide');
      if (!slides || slides.length < 2) return;
      let idx = 0;
      const total = slides.length;
      const interval = 5000;

      setInterval(()=>{
        slides[idx].classList.remove('opacity-100');
        slides[idx].classList.add('opacity-0');
        idx = (idx + 1) % total;
        slides[idx].classList.remove('opacity-0');
        slides[idx].classList.add('opacity-100');
      }, interval);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
  })();
</script>
</body>
</html>