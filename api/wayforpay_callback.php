<?php
// Сервісний колбек WayForPay: перевірка підпису, оновлення статусу oc_order і json-замовлення.

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/db.php';
require __DIR__ . '/W4P.php';

$raw = (string)file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // W4P інколи шле form-encoded
    parse_str($raw, $data);
}
if (!is_array($data) || empty($data['orderReference'])) {
    http_response_code(400);
    exit('bad request');
}

$secret = (string)hydro_setting('payment_wayforpay_secretkey', '');
$w4p = new W4P($secret);
$valid = $w4p->isPaymentValid($data);

// orderReference формату "<ref>#<oc_order_id>"
$ocOrderId = 0;
if (strpos($data['orderReference'], '#') !== false) {
    $ocOrderId = (int)substr($data['orderReference'], strrpos($data['orderReference'], '#') + 1);
}

if ($ocOrderId) {
    $db = hydro_db();
    $status = (string)($data['transactionStatus'] ?? '');
    if ($valid === true) {
        $newStatus = (int)(hydro_setting('payment_wayforpay_order_status_id') ?: 2);
        $note = 'WayForPay: оплату підтверджено (' . $status . ')';
    } elseif (in_array($status, [W4P::ORDER_DECLINED, W4P::ORDER_EXPIRED], true)) {
        $newStatus = (int)(hydro_setting('payment_wayforpay_decline_status_id') ?: 8);
        $note = 'WayForPay: відхилено (' . $status . ')';
    } elseif ($status === W4P::ORDER_REFUNDED) {
        $newStatus = (int)(hydro_setting('payment_wayforpay_cancel_status_id') ?: 7);
        $note = 'WayForPay: повернення (' . $status . ')';
    } else {
        $newStatus = 0;
        $note = 'WayForPay: ' . (is_string($valid) ? $valid : $status);
    }

    if ($newStatus) {
        $stmt = $db->prepare("UPDATE " . DB_PREFIX . "order SET order_status_id = ?, date_modified = NOW() WHERE order_id = ?");
        $stmt->bind_param('ii', $newStatus, $ocOrderId);
        $stmt->execute();
        $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "order_history (order_id, order_status_id, notify, comment, date_added) VALUES (?, ?, 0, ?, NOW())");
        $stmt->bind_param('iis', $ocOrderId, $newStatus, $note);
        $stmt->execute();
    }

    // синхронізуємо json-файл замовлення (сторінка успіху/облік)
    foreach (glob(hydro_storage_dir('orders') . '/*.json') as $file) {
        $order = json_decode((string)file_get_contents($file), true);
        if (($order['oc_order_id'] ?? 0) == $ocOrderId) {
            $order['payment_status'] = ($valid === true) ? 'paid' : ('failed:' . $status);
            file_put_contents($file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            break;
        }
    }
}

header('Content-Type: application/json');
echo $w4p->getAnswerToGateWay($data);
