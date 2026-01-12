<?php
include "../config/database.php";
$result = $conn->query("
    SELECT b.*, t.title 
    FROM bookings b 
    JOIN tours t ON b.tour_id = t.id
");
?>

<h2>Danh sách đặt tour</h2>
<table border="1">
<tr>
    <th>Tour</th>
    <th>Khách</th>
    <th>SĐT</th>
    <th>Số lượng</th>
</tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['title'] ?></td>
    <td><?= $row['customer_name'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= $row['quantity'] ?></td>
</tr>
<?php endwhile; ?>
</table>
