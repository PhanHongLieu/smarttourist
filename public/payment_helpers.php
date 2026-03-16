<?php

function paymentTableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
    $stmt->execute(['column_name' => $column]);
    return (bool)$stmt->fetch();
}

function ensureBookingPaymentColumns(PDO $pdo): void
{
    $ddlMap = [
        'payment_method' => "ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(20) NULL AFTER total_amount",
        'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) NULL AFTER payment_method",
        'payment_reference' => "ALTER TABLE bookings ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status",
        'payment_trans_id' => "ALTER TABLE bookings ADD COLUMN payment_trans_id VARCHAR(100) NULL AFTER payment_reference",
        'payment_message' => "ALTER TABLE bookings ADD COLUMN payment_message VARCHAR(255) NULL AFTER payment_trans_id",
        'paid_at' => "ALTER TABLE bookings ADD COLUMN paid_at DATETIME NULL AFTER payment_message",
    ];

    foreach ($ddlMap as $column => $ddl) {
        if (!paymentTableHasColumn($pdo, 'bookings', $column)) {
            try {
                $pdo->exec($ddl);
            } catch (Throwable $e) {
                // Ignore when DB user cannot ALTER.
            }
        }
    }
}

function bookingColumns(PDO $pdo): array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM bookings');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function buildInsertFromAvailableColumns(PDO $pdo, array $payload): array
{
    $columns = bookingColumns($pdo);
    $data = [];

    foreach ($payload as $column => $value) {
        if (in_array($column, $columns, true)) {
            $data[$column] = $value;
        }
    }

    if (empty($data)) {
        throw new RuntimeException('Khong tim thay cot phu hop de luu booking.');
    }

    $fields = array_keys($data);
    $placeholders = array_map(static fn($f) => ':' . $f, $fields);
    $sql = 'INSERT INTO bookings (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

    return [$sql, $data];
}

function buildUpdateFromAvailableColumns(PDO $pdo, string $whereColumn, array $payload): array
{
    $columns = bookingColumns($pdo);
    $sets = [];
    $data = [];

    foreach ($payload as $column => $value) {
        if (in_array($column, $columns, true)) {
            $sets[] = "{$column} = :{$column}";
            $data[$column] = $value;
        }
    }

    if (empty($sets)) {
        throw new RuntimeException('Khong tim thay cot phu hop de cap nhat booking.');
    }

    $sql = 'UPDATE bookings SET ' . implode(', ', $sets) . " WHERE {$whereColumn} = :where_value";
    return [$sql, $data];
}

function getPublicBaseUrl(): string
{
    if (!empty(getenv('APP_BASE_URL'))) {
        return rtrim((string)getenv('APP_BASE_URL'), '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function momoConfig(): array
{
    return [
        'endpoint' => getenv('MOMO_ENDPOINT') ?: 'https://test-payment.momo.vn/v2/gateway/api/create',
        'partnerCode' => (string)(getenv('MOMO_PARTNER_CODE') ?: ''),
        'accessKey' => (string)(getenv('MOMO_ACCESS_KEY') ?: ''),
        'secretKey' => (string)(getenv('MOMO_SECRET_KEY') ?: ''),
        'requestType' => getenv('MOMO_REQUEST_TYPE') ?: 'captureWallet',
        'lang' => getenv('MOMO_LANG') ?: 'vi',
    ];
}

function momoCreateSignatureCreate(array $params, string $secretKey): string
{
    $raw = 'accessKey=' . $params['accessKey']
        . '&amount=' . $params['amount']
        . '&extraData=' . $params['extraData']
        . '&ipnUrl=' . $params['ipnUrl']
        . '&orderId=' . $params['orderId']
        . '&orderInfo=' . $params['orderInfo']
        . '&partnerCode=' . $params['partnerCode']
        . '&redirectUrl=' . $params['redirectUrl']
        . '&requestId=' . $params['requestId']
        . '&requestType=' . $params['requestType'];

    return hash_hmac('sha256', $raw, $secretKey);
}

function momoVerifyIpnSignature(array $data, string $secretKey): bool
{
    if (!isset($data['signature'])) {
        return false;
    }

    $baseFields = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
    foreach ($baseFields as $key) {
        if (!array_key_exists($key, $data)) {
            return false;
        }
    }

    $parts = [];
    if (array_key_exists('accessKey', $data)) {
        $parts[] = 'accessKey=' . $data['accessKey'];
    }

    foreach ($baseFields as $field) {
        $parts[] = $field . '=' . $data[$field];
    }

    $raw = implode('&', $parts);
    $expected = hash_hmac('sha256', $raw, $secretKey);
    return hash_equals($expected, (string)$data['signature']);
}

function momoCreatePayment(array $input): array
{
    $config = momoConfig();
    if ($config['partnerCode'] === '' || $config['accessKey'] === '' || $config['secretKey'] === '') {
        return [
            'ok' => false,
            'message' => 'Thieu cau hinh MOMO_PARTNER_CODE/MOMO_ACCESS_KEY/MOMO_SECRET_KEY',
        ];
    }

    $payload = [
        'partnerCode' => $config['partnerCode'],
        'accessKey' => $config['accessKey'],
        'requestId' => $input['requestId'],
        'amount' => (string)$input['amount'],
        'orderId' => $input['orderId'],
        'orderInfo' => $input['orderInfo'],
        'redirectUrl' => $input['redirectUrl'],
        'ipnUrl' => $input['ipnUrl'],
        'extraData' => $input['extraData'] ?? '',
        'requestType' => $config['requestType'],
        'lang' => $config['lang'],
        'autoCapture' => true,
    ];

    $payload['signature'] = momoCreateSignatureCreate($payload, $config['secretKey']);

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'message' => 'PHP cURL chua duoc bat tren server',
        ];
    }

    $ch = curl_init($config['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false || $responseBody === null) {
        return ['ok' => false, 'message' => 'Khong goi duoc API MoMo: ' . $curlError];
    }

    $json = json_decode($responseBody, true);
    if (!is_array($json)) {
        return ['ok' => false, 'message' => 'Phan hoi MoMo khong hop le'];
    }

    $resultCode = (int)($json['resultCode'] ?? -1);
    if ($httpCode >= 200 && $httpCode < 300 && $resultCode === 0 && !empty($json['payUrl'])) {
        return [
            'ok' => true,
            'payUrl' => (string)$json['payUrl'],
            'message' => (string)($json['message'] ?? 'OK'),
            'raw' => $json,
        ];
    }

    return [
        'ok' => false,
        'message' => (string)($json['message'] ?? 'Tao giao dich MoMo that bai'),
        'raw' => $json,
    ];
}
