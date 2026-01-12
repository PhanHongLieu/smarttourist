<?php
// Simple About page converted from Next.js component to PHP
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Về SmartTourist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{--gold:#f5b942;--navy:#0a1f44}

    /* floating animations */
    @keyframes floatY { 0%{transform:translateY(0)}50%{transform:translateY(-12px)}100%{transform:translateY(0)} }
    .animate-float-slow{ animation: floatY 4s ease-in-out infinite; }
    .animate-float-medium{ animation: floatY 3s ease-in-out infinite; }
    .animate-float-fast{ animation: floatY 2.6s ease-in-out infinite; }
  </style>
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">
</head>
<body class="overflow-x-hidden">
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- HERO -->
<section class="relative py-32 bg-gradient-to-br from-[var(--navy)] via-blue-900 to-black text-white overflow-hidden">
  <div class="absolute inset-0 opacity-10 bg-gradient-to-t from-yellow-400 via-transparent to-transparent"></div>

  <!-- Floating elements -->
  <div class="absolute top-20 left-10 w-20 h-20 bg-[var(--gold)]/20 rounded-full blur-xl animate-float-medium"></div>
  <div class="absolute top-40 right-20 w-16 h-16 bg-blue-400/20 rounded-full blur-lg animate-float-slow"></div>
  <div class="absolute bottom-32 left-1/4 w-12 h-12 bg-yellow-300/30 rounded-full blur-md animate-float-fast"></div>

  <div class="relative container mx-auto px-6 max-w-6xl">
    <p class="uppercase tracking-widest text-sm text-[var(--gold)]">Về SmartTourist</p>
    <h1 class="mt-6 text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
      Kiến tạo trải nghiệm du lịch<br>
      <span class="text-[var(--gold)] bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-600 bg-clip-text text-transparent">bằng tư duy công nghệ</span>
    </h1>

    <p class="mt-8 max-w-3xl text-gray-300 text-lg leading-relaxed">
      SmartTourist là nền tảng du lịch thế hệ mới, kết hợp giữa
      kinh nghiệm tổ chức tour chuyên sâu và công nghệ hiện đại,
      nhằm mang đến những hành trình tối ưu, minh bạch và cá nhân hóa
      cho từng khách hàng.
    </p>

    <div class="mt-10 flex flex-col sm:flex-row gap-4">
      <a href="/smarttourist/public/tours.php" class="bg-gradient-to-r from-[var(--gold)] to-yellow-500 text-black px-8 py-4 rounded-xl font-semibold shadow-lg">Khám phá tour</a>
      <a href="/smarttourist/public/contact.php" class="border-2 border-[var(--gold)] text-[var(--gold)] px-8 py-4 rounded-xl font-semibold bg-white/5">Liên hệ chúng tôi</a>
    </div>
  </div>
</section>

<!-- COMPANY OVERVIEW -->
<section class="py-28 bg-gradient-to-b from-gray-50 via-white to-gray-50">
  <div class="container mx-auto px-6 max-w-6xl grid md:grid-cols-2 gap-16 items-center">
    <div>
      <h2 class="text-3xl font-bold text-[var(--navy)] mb-6">Tổng quan doanh nghiệp</h2>
      <p class="text-gray-600 leading-relaxed mb-4">Được thành lập với sứ mệnh nâng tầm trải nghiệm du lịch Việt Nam, SmartTourist tập trung phát triển hệ sinh thái dịch vụ du lịch toàn diện, từ tour trong nước đến các tuyến Châu Á và Châu Âu.</p>
      <p class="text-gray-600 leading-relaxed mb-4">Chúng tôi không chỉ cung cấp tour, mà còn đồng hành cùng khách hàng trong việc thiết kế hành trình phù hợp nhất với thời gian, ngân sách và phong cách trải nghiệm cá nhân.</p>
      <p class="text-gray-600 leading-relaxed">Với định hướng lấy công nghệ làm nền tảng, SmartTourist đầu tư mạnh vào hệ thống dữ liệu, tự động hóa quy trình và trải nghiệm người dùng.</p>
    </div>

    <div class="relative h-[420px] rounded-3xl overflow-hidden shadow-xl group">
      <img src="../assets/image/hero-2.jpg" alt="Office" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
    </div>
  </div>
</section>

<!-- DARK STRATEGY -->
<section class="relative py-32 bg-[#020617] text-white">
  <div class="container mx-auto px-6 max-w-6xl">
    <h2 class="text-3xl font-bold mb-6">Chiến lược phát triển bền vững</h2>
    <p class="text-gray-300 max-w-4xl text-lg leading-relaxed">SmartTourist hướng đến mô hình tăng trưởng dài hạn dựa trên ba trụ cột cốt lõi: công nghệ – con người – trải nghiệm.</p>

    <div class="mt-12 grid md:grid-cols-3 gap-8">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--gold)]/10 flex items-center justify-center text-3xl">🤖</div>
        <h3 class="text-xl font-semibold mb-2">Công nghệ</h3>
        <p class="text-gray-400 text-sm">Ứng dụng AI và dữ liệu để tối ưu hóa trải nghiệm</p>
      </div>

      <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--gold)]/10 flex items-center justify-center text-3xl">👥</div>
        <h3 class="text-xl font-semibold mb-2">Con người</h3>
        <p class="text-gray-400 text-sm">Đội ngũ chuyên nghiệp, tận tâm với khách hàng</p>
      </div>

      <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--gold)]/10 flex items-center justify-center text-3xl">✨</div>
        <h3 class="text-xl font-semibold mb-2">Trải nghiệm</h3>
        <p class="text-gray-400 text-sm">Mỗi hành trình là một câu chuyện độc đáo</p>
      </div>
    </div>
  </div>
</section>

<!-- VISION / MISSION -->
<section class="py-32 bg-gradient-to-b from-gray-50 to-white">
  <div class="container mx-auto px-6 max-w-6xl">
    <div class="text-center max-w-3xl mx-auto mb-20">
      <p class="text-[var(--gold)] uppercase tracking-widest text-sm mb-3">Định hướng phát triển</p>
      <h2 class="text-3xl md:text-4xl font-bold text-[var(--navy)]">Tầm nhìn – Sứ mệnh – Giá trị cốt lõi</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-10">
      <div class="group relative bg-white/80 backdrop-blur-lg rounded-3xl p-10 shadow-xl border border-white/20">
        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-[var(--gold)] to-transparent rounded-t-3xl"></div>
        <div class="w-14 h-14 mb-6 rounded-2xl bg-[var(--navy)]/5 flex items-center justify-center text-2xl">🎯</div>
        <h3 class="text-xl font-semibold text-[var(--navy)] mb-4">Tầm nhìn (Vision)</h3>
        <p class="text-gray-600 text-sm">Trở thành hệ sinh thái du lịch thông minh hàng đầu khu vực...</p>
      </div>

      <div class="group relative bg-white/80 backdrop-blur-lg rounded-3xl p-10 shadow-xl border border-white/20">
        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-[var(--gold)] to-transparent rounded-t-3xl"></div>
        <div class="w-14 h-14 mb-6 rounded-2xl bg-[var(--navy)]/5 flex items-center justify-center text-2xl">🚀</div>
        <h3 class="text-xl font-semibold text-[var(--navy)] mb-4">Sứ mệnh (Mission)</h3>
        <p class="text-gray-600 text-sm">Thiết kế những hành trình tối ưu dựa trên nền tảng dữ liệu...</p>
      </div>

      <div class="group relative bg-white/80 backdrop-blur-lg rounded-3xl p-10 shadow-xl border border-white/20">
        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-[var(--gold)] to-transparent rounded-t-3xl"></div>
        <div class="w-14 h-14 mb-6 rounded-2xl bg-[var(--navy)]/5 flex items-center justify-center text-2xl">💎</div>
        <h3 class="text-xl font-semibold text-[var(--navy)] mb-4">Giá trị cốt lõi (Core Values)</h3>
        <p class="text-gray-600 text-sm">Smart: Luôn cải tiến; Trust: Tin cậy; Tailored: Cá nhân hóa.</p>
      </div>
    </div>
  </div>
</section>

<!-- BOARD OF DIRECTORS -->
<section class="container mx-auto px-6 py-24 max-w-6xl">
  <h2 class="text-3xl font-bold text-center text-[var(--navy)] mb-16">Ban giám đốc SmartTourist</h2>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-10">
    <?php
    $members = [
      ['name'=>'Hà Phạm Minh Sáng','role'=>'Tổng Giám Đốc (CEO)','img'=>'../assets/image/ceo.jpg'],
      ['name'=>'Nông Công Trình','role'=>'Trưởng Phòng Hướng Dẫn Viên','img'=>'../assets/image/leadhd.jpg'],
      ['name'=>'Lê Kim Chi','role'=>'Trưởng Phòng Kinh Doanh Khách Đoàn','img'=>'../assets/image/leadkd.jpg'],
      ['name'=>'Hồ Văn Trường','role'=>'Trưởng Phòng Điều Hành','img'=>'../assets/image/leaddh.jpg'],
      ['name'=>'Trần Minh Ngọc','role'=>'Trưởng Phòng Truyền Thông & Marketing','img'=>'../assets/image/leadtt.jpg'],
      ['name'=>'Nguyễn Vân Anh','role'=>'Chuyên Viên Kinh Doanh','img'=>'../assets/image/cv.jpg'],
    ];

    foreach ($members as $m): ?>
      <div class="bg-white rounded-3xl overflow-hidden shadow-lg group hover:shadow-2xl transition-all duration-300">
        <div class="relative h-80 w-full overflow-hidden">
          <img src="<?= htmlspecialchars($m['img']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-500" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
        </div>
        <div class="p-6 text-center">
          <h3 class="text-lg font-semibold text-[var(--navy)]"><?= htmlspecialchars($m['name']) ?></h3>
          <p class="text-[var(--navy)] font-medium mt-1 opacity-80"><?= htmlspecialchars($m['role']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA -->
<section class="relative py-32 bg-gradient-to-br from-[var(--navy)] via-blue-900 to-black text-white text-center">
  <div class="container mx-auto px-6 max-w-4xl">
    <h2 class="text-3xl md:text-4xl font-bold">Đồng hành cùng SmartTourist</h2>
    <p class="mt-6 text-gray-300 text-lg">Từ Việt Nam đến Châu Á & Châu Âu – chúng tôi sẵn sàng cùng bạn kiến tạo hành trình đáng nhớ.</p>
    <div class="mt-10">
      <a href="/smarttourist/public/tours.php" class="inline-block bg-gradient-to-r from-[var(--gold)] to-yellow-500 text-black px-12 py-4 rounded-xl font-semibold">Khám phá tour ngay</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
