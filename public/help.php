<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>SmartTourist - Hoạt động thế nào</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">
  <style>
    :root { --gold: #f5b942; --navy: #0a1f44; }
  </style>
</head>
<body class="bg-gray-50">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mx-auto px-6 py-12">
  <header class="max-w-5xl mx-auto text-center mb-12">
    <div class="rounded-2xl bg-gradient-to-r from-[#07203a] to-[#0b3b61] text-white p-10 shadow-lg">
      <h1 class="text-3xl md:text-4xl font-bold">SmartTourist hoạt động thế nào</h1>
      <p class="mt-3 text-gray-200 max-w-2xl mx-auto">Tìm, so sánh và đặt tour dễ dàng — chúng tôi tổng hợp hành trình, giá và điều khoản để bạn chọn chuyến đi phù hợp.</p>
      <div class="mt-6 flex justify-center gap-4">
        <a href="tours.php" class="inline-block bg-[var(--gold)] text-black px-5 py-3 rounded-lg font-semibold">Xem tour</a>
        <a href="contact.php" class="inline-block border border-white/30 text-white px-5 py-3 rounded-lg">Liên hệ hỗ trợ</a>
      </div>
    </div>
  </header>

  <section class="grid lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
      <div class="text-3xl">🔎</div>
      <h3 class="font-semibold mt-3">Tìm & Lọc</h3>
      <p class="text-sm text-gray-600 mt-2">Dùng thanh tìm kiếm, mega-menu hoặc bộ lọc để thu hẹp kết quả theo tỉnh, giá, thời gian và nhiều tiêu chí khác.</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <div class="text-3xl">📋</div>
      <h3 class="font-semibold mt-3">Xem chi tiết</h3>
      <p class="text-sm text-gray-600 mt-2">Trang chi tiết trình bày lịch trình theo ngày, hình ảnh, bao gồm/không bao gồm và chính sách huỷ.</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <div class="text-3xl">💳</div>
      <h3 class="font-semibold mt-3">Đặt & Thanh toán</h3>
      <p class="text-sm text-gray-600 mt-2">Chọn ngày khởi hành, số lượng khách và thanh toán an toàn qua cổng hoặc chuyển khoản.</p>
    </div>
  </section>

  <section class="bg-white rounded-xl shadow p-6 mb-8">
    <h3 class="font-semibold text-lg mb-3">Giá, Hủy & Hỗ trợ</h3>
    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <h4 class="font-medium">Giá minh bạch</h4>
        <p class="text-sm text-gray-600">Giá hiển thị là giá cơ bản theo tour; tổng cuối cùng sẽ phụ thuộc vào số lượng người và các lựa chọn thêm.</p>
      </div>
      <div>
        <h4 class="font-medium">Chính sách huỷ</h4>
        <p class="text-sm text-gray-600">Mỗi tour có chính sách riêng; kiểm tra mục "Chính sách" trước khi đặt để biết điều kiện hoàn tiền.</p>
      </div>
      <div>
        <h4 class="font-medium">Hỗ trợ 24/7</h4>
        <p class="text-sm text-gray-600">Hotline và email hỗ trợ luôn sẵn sàng để giúp bạn trong suốt quá trình.</p>
      </div>
    </div>
  </section>

  <section class="bg-white rounded-xl shadow p-6 mb-8">
    <h3 class="font-semibold text-lg mb-3">Câu hỏi thường gặp (FAQ)</h3>
    <div class="space-y-4 text-sm text-gray-700">
      <details class="p-3 border rounded"><summary class="font-medium">Làm sao để thay đổi ngày khởi hành?</summary>
        <div class="mt-2 text-gray-600">Liên hệ bộ phận hỗ trợ; việc thay đổi tuỳ thuộc vào chính sách tour và tình trạng chỗ trống.</div>
      </details>

      <details class="p-3 border rounded"><summary class="font-medium">Tôi nhận được xác nhận như thế nào?</summary>
        <div class="mt-2 text-gray-600">Sau khi thanh toán, bạn sẽ nhận email xác nhận và mã booking.</div>
      </details>

      <details class="p-3 border rounded"><summary class="font-medium">Có hỗ trợ trả góp không?</summary>
        <div class="mt-2 text-gray-600">Tùy chương trình và cổng thanh toán; nếu có sẽ hiển thị ở bước thanh toán.</div>
      </details>
    </div>
  </section>

  <div class="mt-8 text-center">
    <a href="tours.php" class="inline-block px-6 py-3 bg-[var(--gold)] text-black rounded-lg font-semibold">Xem tour ngay</a>
  </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
