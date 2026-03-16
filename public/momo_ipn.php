<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/payment_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    ensureBookingPaymentColumns($pdo);

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    if (!is_array($data) || empty($data)) {
        http_response_code(400);
        echo json_encode(['resultCode' => 1, 'message' => 'Invalid payload']);
        exit;
    }

    $cfg = momoConfig();
    if ($cfg['secretKey'] === '') {
        http_response_code(500);
        echo json_encode(['resultCode' => 2, 'message' => 'Missing MoMo secret']);
        exit;
    }

    if (!momoVerifyIpnSignature($data, $cfg['secretKey'])) {
        http_response_code(400);
        echo json_encode(['resultCode' => 3, 'message' => 'Invalid signature']);
        exit;
    }

    $orderId = (string)($data['orderId'] ?? '');
    if ($orderId === '') {
        http_response_code(422);
        echo json_encode(['resultCode' => 4, 'message' => 'Missing orderId']);
        exit;
    }

    $resultCode = (int)($data['resultCode'] ?? -1);
    $message = (string)($data['message'] ?? '');
    $transId = (string)($data['transId'] ?? '');

    $paymentStatus = $resultCode === 0 ? 'PAID' : 'FAILED';
    $updatePayload = [
        'payment_status' => $paymentStatus,
        'payment_trans_id' => $transId,
        'payment_message' => $message,
    ];
    if ($paymentStatus === 'PAID') {
        $updatePayload['paid_at'] = date('Y-m-d H:i:s');
    }

    [$sql, $updateData] = buildUpdateFromAvailableColumns($pdo, 'payment_reference', $updatePayload);
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($updateData, ['where_value' => $orderId]));

    echo json_encode(['resultCode' => 0, 'message' => 'OK']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['resultCode' => 99, 'message' => 'Server error']);
}
