<?php
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Vui lòng nhập tên.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Vui lòng nhập email hợp lệ.';
    if ($message === '') $errors[] = 'Vui lòng nhập nội dung liên hệ.';

    if (empty($errors)) {
        // Insert into DB (create table if not exists)
        $createSql = "CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            subject VARCHAR(255),
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($createSql);

        $stmt = $conn->prepare('INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
            $stmt->execute();
            $stmt->close();
        }

        // Send email (best-effort)
        $to = 'SmartTouristt@gmail.com';
        $mailSubject = 'Liên hệ từ website: ' . ($subject ?: 'Không có tiêu đề');
        $body = "Tên: {$name}\nEmail: {$email}\nSĐT: {$phone}\n\nNội dung:\n{$message}\n";
        $headers = 'From: ' . $email . "\r\n" . 'Reply-To: ' . $email . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
        @mail($to, $mailSubject, $body, $headers);

        $success = 'Cảm ơn bạn — thông tin đã được gửi. Chúng tôi sẽ liên hệ sớm.';
        // clear fields
        $name = $email = $phone = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Liên hệ — SmartTourist</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>:root{--gold:#f5b942;--navy:#0a1f44}</style>
  <link rel="stylesheet" href="/smarttourist/assets/css/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
</head>
<body class="overflow-x-hidden">
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="container mx-auto px-6 py-20">
  <div class="max-w-4xl mx-auto text-center mb-12">
    <h1 class="text-4xl font-extrabold text-[var(--navy)] mb-3">Liên hệ SmartTourist</h1>
    <p class="text-gray-600">Gửi thông tin, phản hồi hoặc yêu cầu tư vấn — chúng tôi sẽ liên hệ lại trong thời gian sớm nhất.</p>
  </div>

  <div class="grid md:grid-cols-2 gap-10 max-w-6xl mx-auto">
    <div>
      <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
          <ul class="list-disc pl-5">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4 bg-white p-6 rounded-lg shadow">
        <div>
          <label class="block text-sm text-gray-600">Họ & tên</label>
          <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" class="w-full border rounded px-3 py-2" required />
        </div>

        <div>
          <label class="block text-sm text-gray-600">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" class="w-full border rounded px-3 py-2" required />
        </div>

        <div>
          <label class="block text-sm text-gray-600">Số điện thoại</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm text-gray-600">Tiêu đề</label>
          <input type="text" name="subject" value="<?= htmlspecialchars($subject ?? '') ?>" class="w-full border rounded px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm text-gray-600">Nội dung</label>
          <textarea name="message" rows="6" class="w-full border rounded px-3 py-2" required><?= htmlspecialchars($message ?? '') ?></textarea>
        </div>

        <div>
          <button type="submit" class="bg-[var(--gold)] text-black px-6 py-2 rounded font-semibold">Gửi liên hệ</button>
        </div>
      </form>
    </div>

    <div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-4">Thông tin liên hệ</h3>
        <p class="text-sm text-gray-700">Email: <a href="mailto:SmartTouristt@gmail.com" class="text-[var(--gold)]">SmartTouristt@gmail.com</a></p>
        <p class="text-sm text-gray-700 mt-2">Hotline: <span class="font-semibold text-[var(--gold)]">0368406350</span></p>
        <p class="text-sm text-gray-700 mt-4">Địa chỉ: 10 Nguyễn Văn Dung, phường Hạnh Thông, TP. Hồ Chí Minh</p>
      </div>

      <div class="mt-6">
        <!-- Simple map embed (Google Maps) -->
        <iframe
          src="https://www.google.com/maps?q=10+Nguyen+Van+Dung+Ho+Chi+Minh&output=embed"
          class="w-full h-80 rounded-lg border-0"
          allowfullscreen=""
          loading="lazy"
        ></iframe>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
