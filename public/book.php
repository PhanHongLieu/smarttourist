<?php
require_once __DIR__ . '/../config/database.php';

/* ========= LẤY TOUR ========= */
$tourId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tourId <= 0) die('Tour không hợp lệ');

$stmt = $pdo->prepare("SELECT * FROM tours WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $tourId]);
$tour = $stmt->fetch();

if (!$tour) die('Không tìm thấy tour');

function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// read incoming booking params (from tour-detail form)
$departure_date = $_GET['departure_date'] ?? '';
$qty_adult = isset($_GET['adult']) ? max(1, (int)$_GET['adult']) : 1;
$qty_child = isset($_GET['child']) ? max(0, (int)$_GET['child']) : 0;
$qty_baby  = isset($_GET['baby'])  ? max(0, (int)$_GET['baby'])  : 0;

$priceAdult = (int)$tour['price_adult'];
$priceChild = (int)$tour['price_child'];
$priceBaby  = isset($tour['price_baby']) ? (int)$tour['price_baby'] : 0;

// server-side total (defensive)
$serverTotal = $qty_adult * $priceAdult + $qty_child * $priceChild + $qty_baby * $priceBaby;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đặt tour – <?= e($tour['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="/smarttourist/assets/css/style.css">
<style>
:root {
    --gold: #f5b942;
    --navy: #0a1f44;
}
</style>                                                                                                                                                                    
</head>

<body class="bg-gray-100">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-10">

<!-- ===== STEP ===== -->
<div class="flex justify-between mb-8 text-sm font-semibold">
    <span class="text-blue-600">1. Chọn tour</span>
    <span class="text-blue-600">2. Điền thông tin</span>
    <span class="text-gray-400">3. Thanh toán</span>
</div>

<form action="booking_submit.php" method="post">
<input type="hidden" name="tour_id" value="<?= $tourId ?>">
<input type="hidden" name="departure_date" id="hidden_departure_date" value="<?= e($departure_date) ?>">
<input type="hidden" name="adult" id="hidden_adult" value="<?= $qty_adult ?>">
<input type="hidden" name="child" id="hidden_child" value="<?= $qty_child ?>">
<input type="hidden" name="baby" id="hidden_baby" value="<?= $qty_baby ?>">
<input type="hidden" name="server_total" value="<?= $serverTotal ?>">

<!-- ✅ FLEX LAYOUT -->
<div class="flex flex-col lg:flex-row gap-8">

<!-- ================= LEFT ================= -->
<div class="flex-1 space-y-8">

<!-- TOUR INFO -->
<div class="bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-2"><?= e($tour['title']) ?></h1>
    <p class="text-sm text-gray-600">
        <?= e($tour['destination']) ?> •
        <?= (int)$tour['duration_days'] ?>N<?= (int)$tour['duration_nights'] ?>Đ
    </p>
</div>

<!-- PASSENGER -->
<div class="bg-white p-6 rounded shadow">
<h2 class="font-semibold text-lg mb-4">Thông tin hành khách</h2>

<div class="grid md:grid-cols-2 gap-4">
<input name="fullname" required placeholder="Họ tên *" class="border rounded px-3 py-2">
<select name="gender" required class="border rounded px-3 py-2">
<option value="">Giới tính *</option>
<option>Nam</option>
<option>Nữ</option>
</select>
<input type="date" name="dob" required class="border rounded px-3 py-2">
<input type="email" name="email" required placeholder="Email *" class="border rounded px-3 py-2">
<input name="phone" required placeholder="Điện thoại *" class="border rounded px-3 py-2">
</div>
</div>

<!-- PAYMENT -->
<div class="bg-white p-6 rounded shadow">
<h2 class="font-semibold text-lg mb-4">Phương thức thanh toán</h2>
<div class="space-y-2 text-sm">
<?php
$payments = [
    'ATM'=>'Thẻ ATM nội địa',
    'CREDIT'=>'Thẻ tín dụng',
    'BANK'=>'Chuyển khoản',
    'CASH'=>'Tiền mặt',
    'MOMO'=>'Ví MoMo'
];
foreach ($payments as $k=>$v): ?>
<label class="flex gap-2">
<input type="radio" name="payment_method" value="<?= $k ?>" required> <?= $v ?>
</label>
<?php endforeach; ?>
</div>
</div>

<!-- TERMS -->
<div class="bg-white p-6 rounded shadow text-sm h-96 overflow-y-auto">
    <h2 class="font-bold text-lg mb-3">
    I. Thông tin điều khoản và điều kiện áp dụng cho Tour trọn gói
    </h2>

    <p class="mb-3">
    Điều khoản này là sự thoả thuận đồng ý của quý khách khi sử dụng dịch vụ thanh toán
    trên trang web của Công ty Dịch vụ Lữ hành MTV
    Smarttourist (CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST) và những trang web của bên thứ ba.
    Việc quý khách đánh dấu vào ô <strong>“Đồng ý”</strong> và nhấp chuột vào thanh
    <strong>“Chấp nhận”</strong> nghĩa là quý khách đồng ý tất cả các điều khoản
    thỏa thuận trong các trang web này.
    </p>

    <h3 class="font-semibold mt-4 mb-2">1. Giải thích từ ngữ</h3>
    <ul class="list-disc pl-5 space-y-1">
    <li><strong>Điều khoản:</strong> là những điều quy định giữa CÔNG TY TNHH MTV DỊCH VỤ CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST và quý khách.</li>
    <li><strong>Bên thứ ba:</strong> là những đơn vị liên kết với CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST
    (OnePay, Vietcombank) nhằm hỗ trợ việc thanh toán qua mạng cho quý khách.</li>
    <li><strong>Vé điện tử:</strong> là những thông tin và hành trình của quý khách cho chuyến đi
    được thể hiện trên một trang giấy mà quý khách có thể in ra được.</li>
    </ul>

    <h3 class="font-semibold mt-4 mb-2">2. Về sở hữu bản quyền</h3>
    <p>
    Trang web thuộc quyền sở hữu của CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST
    và được bảo vệ theo luật bản quyền. Quý khách chỉ được sử dụng trang web này với
    mục đích xem thông tin và đăng ký thanh toán online cho cá nhân, không được sử dụng
    cho bất cứ mục đích thương mại nào khác.
    </p>
    <p class="mt-2">
    Việc lấy nội dung để tham khảo, làm tài liệu nghiên cứu phải ghi rõ nguồn từ
    CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST. Không được sử dụng logo, nhãn hiệu của CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST
    dưới mọi hình thức nếu chưa có sự đồng ý bằng văn bản.
    </p>

    <h3 class="font-semibold mt-4 mb-2">3. Về thông tin khách hàng</h3>
    <p>
    Khi đăng ký thanh toán qua mạng, quý khách sẽ được yêu cầu cung cấp một số thông tin cá nhân
    và thông tin tài khoản.
    </p>
    <p class="mt-2">
    <strong>Đối với thông tin cá nhân:</strong> chỉ dùng để xác nhận mua dịch vụ và hiển thị trên vé điện tử.
    CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST có thể sử dụng thông tin liên lạc để gửi tin khuyến mãi nếu quý khách đồng ý.
    Thông tin được bảo mật và không tiết lộ cho bên thứ ba, trừ khi có yêu cầu pháp lý.
    </p>
    <p class="mt-2">
    <strong>Đối với thông tin tài khoản:</strong> được bảo mật bằng các biện pháp cao nhất
    theo tiêu chuẩn của các hệ thống thanh toán quốc tế như Visa, MasterCard.
    </p>

    <h3 class="font-semibold mt-4 mb-2">4. Về trang web liên kết</h3>
    <p>
    Các trang web của CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST có thể chứa liên kết đến bên thứ ba.
    Việc liên kết này chỉ nhằm cung cấp tiện ích cho quý khách và không đồng nghĩa
    với việc chấp nhận nội dung của các trang đó. CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST không chịu
    trách nhiệm pháp lý liên quan đến nội dung của các trang bên thứ ba.
    </p>

    <h3 class="font-semibold mt-4 mb-2">5. Về hủy tour</h3>
    <p>
    Trong trường hợp hủy tour, quý khách vui lòng gửi email thông báo hủy tour đến
    CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST. Sau khi xác nhận thông tin, CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST sẽ hoàn tiền
    vào tài khoản đã thanh toán sau khi trừ các khoản lệ phí hủy tour tùy theo từng tour.
    </p>

    <h3 class="font-semibold mt-4 mb-2">6. Trách nhiệm của CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST</h3>
    <ul class="list-disc pl-5 space-y-1">
    <li>Bảo mật và lưu trữ an toàn thông tin khách hàng.</li>
    <li>Giải quyết các sai sót trong quá trình thanh toán do lỗi của hệ thống.</li>
    <li>Đảm bảo thực hiện đầy đủ chương trình tour đã cam kết.</li>
    <li>Có quyền thay đổi lộ trình hoặc hủy tour vì lý do an toàn.</li>
    </ul>

    <h3 class="font-semibold mt-4 mb-2">7. Trường hợp miễn trách nhiệm</h3>
    <ul class="list-disc pl-5 space-y-1">
    <li>Thông tin khách hàng cung cấp không chính xác.</li>
    <li>Thông tin bị đánh cắp do virus hoặc lỗi từ thiết bị người dùng.</li>
    <li>Sự cố kỹ thuật ngoài tầm kiểm soát.</li>
    <li>Mất dữ liệu do thiên tai, hỏa hoạn, chiến tranh.</li>
    </ul>

    <h3 class="font-semibold mt-4 mb-2">8. Trách nhiệm của khách hàng</h3>
    <ul class="list-disc pl-5 space-y-1">
    <li>Cung cấp thông tin cá nhân trung thực, chính xác.</li>
    <li>Kiểm tra tài khoản và thông báo sự cố trong vòng 30 ngày.</li>
    <li>Không sử dụng nội dung website cho mục đích thương mại.</li>
    <li>Tự áp dụng biện pháp bảo vệ thiết bị khi sử dụng website.</li>
    </ul>

    <hr class="my-4">

    <h2 class="font-bold text-lg mb-3">
    II. Thông tin điều khoản và điều kiện áp dụng cho Dịch vụ
    </h2>

    <h3 class="font-semibold mb-2">1. Xác nhận thông tin đặt dịch vụ</h3>
    <ul class="list-disc pl-5 space-y-1">
    <li>Email xác nhận là căn cứ xác định dịch vụ được cung cấp.</li>
    <li>Thanh toán trễ có thể làm mất hiệu lực đặt dịch vụ.</li>
    <li>Dịch vụ chỉ được đảm bảo sau email xác nhận cuối cùng.</li>
    <li>Khách hàng đồng ý các điều khoản của nhà cung cấp.</li>
    <li>Yêu cầu hủy, đổi, hoàn tiền cần liên hệ trước khi sử dụng dịch vụ.</li>
    </ul>

    <h3 class="font-semibold mt-4 mb-2">2. Yêu cầu đăng ký thông tin</h3>
    <p>
    Thông tin thẻ tín dụng không được cung cấp cho nhà cung cấp dịch vụ.
    Nhà cung cấp có thể yêu cầu xuất trình CMND/hộ chiếu khi sử dụng dịch vụ.
    </p>

    <h3 class="font-semibold mt-4 mb-2">3. Thông tin trang web</h3>
    <p>
    CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST cam kết hiển thị thông tin chính xác nhất có thể,
    nhưng không đảm bảo tuyệt đối. Mọi thay đổi có hiệu lực ngay mà không cần báo trước.
    </p>

    <h3 class="font-semibold mt-4 mb-2">4. Trách nhiệm</h3>
    <p>
    CÔNG TY TNHH MTV DỊCH VỤ LỮ HÀNH SMARTTOURIST không chịu trách nhiệm với các thiệt hại phát sinh gián tiếp,
    không bảo đảm tình trạng phòng trống nếu nhà cung cấp đã cho thuê vượt mức.
    </p>

</div>
    <div class="flex items-center gap-2 text-sm">
    <input type="checkbox" required>
    <span>Tôi đã đọc và đồng ý điều khoản</span>
    </div>

</div>

<!-- ================= RIGHT ================= -->
<aside class="w-full lg:w-[380px] shrink-0">
<div class="bg-white p-6 rounded shadow sticky top-6">

<h3 class="font-semibold mb-4">Thông tin đơn hàng</h3>

<div class="flex justify-between text-sm mb-2">
<span>Giá người lớn</span>
<span><?= number_format($priceAdult) ?> ₫</span>
</div>
<div class="flex justify-between text-sm mb-2">
<span>Giá trẻ em</span>
<span><?= number_format($priceChild) ?> ₫</span>
</div>
<div class="flex justify-between text-sm mb-2">
<span>Giá em bé</span>
<span><?= number_format($priceBaby) ?> ₫</span>
</div>

<div class="space-y-2 text-sm">
    <div class="flex justify-between items-center">
        <label>Người lớn</label>
        <input type="number" id="aside_adult" name="aside_adult" value="<?= $qty_adult ?>" min="1" class="w-20 border rounded text-center" oninput="calcTotal()">
    </div>
    <div class="flex justify-between items-center">
        <label>Trẻ em</label>
        <input type="number" id="aside_child" name="aside_child" value="<?= $qty_child ?>" min="0" class="w-20 border rounded text-center" oninput="calcTotal()">
    </div>
    <div class="flex justify-between items-center">
        <label>Em bé</label>
        <input type="number" id="aside_baby" name="aside_baby" value="<?= $qty_baby ?>" min="0" class="w-20 border rounded text-center" oninput="calcTotal()">
    </div>
    <div class="mt-2">
        <label>Ngày khởi hành</label>
        <input type="date" id="aside_date" name="aside_date" value="<?= e($departure_date) ?>" min="<?= date('Y-m-d') ?>" class="w-full border rounded p-2" onchange="syncDate()">
    </div>
</div>

<hr class="my-4">

<div class="flex justify-between font-bold text-lg">
<span>Tổng tiền</span>
<span id="totalPrice"><?= number_format($serverTotal) ?> ₫</span>
</div>

<button class="mt-6 w-full bg-yellow-400 hover:bg-yellow-500 py-3 font-semibold rounded">
Tiếp tục thanh toán
</button>

</div>
</aside>

</div>
</form>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const priceAdult = <?= $priceAdult ?>;
const priceChild = <?= $priceChild ?>;
const priceBaby  = <?= $priceBaby ?>;

function calcTotal(){
    const a = parseInt(document.getElementById('aside_adult').value) || 0;
    const c = parseInt(document.getElementById('aside_child').value) || 0;
    const b = parseInt(document.getElementById('aside_baby').value) || 0;
    const total = a*priceAdult + c*priceChild + b*priceBaby;
    document.getElementById('totalPrice').innerText = total.toLocaleString('vi-VN') + ' ₫';
    // sync hidden inputs for POST
    document.getElementById('hidden_adult').value = a;
    document.getElementById('hidden_child').value = c;
    document.getElementById('hidden_baby').value = b;
    document.getElementById('hidden_departure_date').value = document.getElementById('aside_date').value;
}

function syncDate(){
    document.getElementById('departureDateInput').value = document.getElementById('aside_date').value;
    calcTotal();
}

document.addEventListener('DOMContentLoaded', function(){
    calcTotal();
});
</script>

</body>
</html>
