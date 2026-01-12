<?php
$year = date('Y');
?>

<footer class="bg-gradient-to-br from-[#0a1f44] to-[#1e3a5f] text-white mt-24 relative overflow-hidden">
  <!-- Background Pattern -->
  <div class="absolute inset-0 opacity-10">
    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/5 to-transparent"></div>
  </div>

  <!-- ================= TOP FOOTER ================= -->
  <div class="relative container mx-auto px-6 py-16 grid lg:grid-cols-4 md:grid-cols-2 gap-12">

    <!-- ===== BRAND / CERT ===== -->
    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
      <div class="flex items-center gap-3">
        <div class="text-2xl font-bold">
          Smart<span class="text-[var(--gold)]">Tourist</span>
        </div>
      </div>

      <p class="mt-4 text-sm text-gray-300 leading-relaxed">
        Nền tảng du lịch thông minh – đặt tour trong nước, châu Á & châu Âu
        với trải nghiệm minh bạch, an toàn và tối ưu.
      </p>

      <ul class="mt-4 space-y-2 text-sm text-gray-300">
        <li>🏆 Thương hiệu uy tín</li>
        <li>🌱 Du lịch bền vững</li>
        <li>🛡 Thanh toán an toàn</li>
      </ul>

      <p class="mt-4 text-sm">
        Email:
        <a href="mailto:SmartTouristt@gmail.com"
           class="text-[var(--gold)] hover:text-yellow-300 transition-colors">
          SmartTouristt@gmail.com
        </a>
      </p>
    </div>

    <!-- ===== SERVICES ===== -->
    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
      <h4 class="font-semibold mb-4 uppercase text-[var(--gold)]">Dịch vụ</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <li><a href="/tours.php" class="hover:text-white transition-colors">Tour trong nước</a></li>
        <li><a href="/tours.php?region=asia" class="hover:text-white transition-colors">Tour nước ngoài</a></li>
        <li><a href="/services.php" class="hover:text-white transition-colors">Dịch vụ du lịch</a></li>
        <li><a href="/insurance.php" class="hover:text-white transition-colors">Bảo hiểm du lịch</a></li>
        <li><a href="/study.php" class="hover:text-white transition-colors">Du học</a></li>
        <li><a href="/jobs.php" class="hover:text-white transition-colors">Việc làm nước ngoài</a></li>
      </ul>
    </div>

    <!-- ===== CUSTOMER CARE ===== -->
    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
      <h4 class="font-semibold mb-4 uppercase text-[var(--gold)]">Chăm sóc khách hàng</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <li class="hover:text-white transition-colors cursor-pointer">Thẻ khách hàng</li>
        <li class="hover:text-white transition-colors cursor-pointer">Tra cứu thông tin tour</li>
        <li class="hover:text-white transition-colors cursor-pointer">Giải quyết khiếu nại</li>
        <li class="hover:text-white transition-colors cursor-pointer">Hướng dẫn mua tour online</li>
        <li class="hover:text-white transition-colors cursor-pointer">Chính sách hoàn huỷ</li>
      </ul>
    </div>

    <!-- ===== POLICY ===== -->
    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
      <h4 class="font-semibold mb-4 uppercase text-[var(--gold)]">Quy định</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <li class="hover:text-white transition-colors cursor-pointer">Điều khoản & điều kiện</li>
        <li class="hover:text-white transition-colors cursor-pointer">Chính sách thanh toán</li>
        <li class="hover:text-white transition-colors cursor-pointer">Chính sách bảo mật</li>
        <li class="hover:text-white transition-colors cursor-pointer">Chính sách chất lượng</li>
      </ul>
    </div>
  </div>

  <!-- ================= LEGAL ================= -->
  <div class="relative bg-gradient-to-r from-white/10 to-white/5 backdrop-blur-sm border-t border-white/20 text-gray-200 text-sm">
    <div class="container mx-auto px-6 py-8 text-center space-y-2">
      <p class="font-semibold text-white">
        CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST
      </p>

      <p>
        Địa chỉ: 10 Nguyễn Văn Dung, phường Hạnh Thông, TP. Hồ Chí Minh
      </p>

      <p>
        Hotline tư vấn & đặt tour:
        <span class="font-semibold text-[var(--gold)]">
          0368406350
        </span>
      </p>

      <p>
        Giấy phép kinh doanh dịch vụ lữ hành quốc tế.
        Số giấy phép: 00-xxxx/2026/CDLQGVN-GPLHQZ.
      </p>

      <p>
        ĐÂY LÀ WEBSITE MÔN HỌC DỰ ÁN KINH DOANH DU LỊCH
        CỦA SINH VIÊN ĐẠI HỌC CÔNG NGHIỆP TP. HỒ CHÍ MINH
      </p>

      <div class="flex justify-center mt-4">
        <div class="bg-gradient-to-r from-[var(--gold)] to-yellow-400 text-black px-4 py-2 rounded-full text-xs font-semibold shadow-lg">
          ĐÃ THÔNG BÁO BỘ CÔNG THƯƠNG
        </div>
      </div>

      <p class="text-xs text-gray-400 mt-4">
        © <?= $year ?> SmartTourist. Phát triển bởi @phanhonglieu
      </p>
    </div>
  </div>
</footer>
<?php
// Include server-generated meteor shower so it's present on every page
if (file_exists(__DIR__ . '/meteor-shower.php')) {
  include __DIR__ . '/meteor-shower.php';
}


