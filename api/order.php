<?php
// Приймання замовлення: валідація, запис в oc_order (адмінка Замовлення),
// лист через Brevo, одноразовий токен сторінки успіху, WayForPay-редірект за потреби.

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/db.php';
require __DIR__ . '/BrevoMailer.php';
require __DIR__ . '/W4P.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    hydro_json(['ok' => false, 'error' => 'POST only'], 405);
}

$input = hydro_input();
$items = $input['items'] ?? [];
$contact = $input['contact'] ?? [];
$delivery = $input['delivery'] ?? [];
$payment = trim((string)($input['payment'] ?? 'cod'));

$name = trim((string)($contact['name'] ?? ''));
$phone = trim((string)($contact['phone'] ?? ''));
$email = trim((string)($contact['email'] ?? ''));
$dMethod = trim((string)($delivery['method'] ?? ''));
$dCity = trim((string)($delivery['city'] ?? ''));
$dBranch = trim((string)($delivery['branch'] ?? ''));
$dType = ($delivery['type'] ?? 'branch') === 'courier' ? 'courier' : 'branch';

if (!$items || !is_array($items)) {
    hydro_json(['ok' => false, 'error' => 'empty cart'], 422);
}
if ($name === '' || $phone === '' || $dCity === '' || $dBranch === '') {
    hydro_json(['ok' => false, 'error' => 'missing fields'], 422);
}
if (mb_strlen($name) > 200 || mb_strlen($phone) > 50 || mb_strlen($email) > 200
    || mb_strlen($dCity) > 300 || mb_strlen($dBranch) > 300) {
    hydro_json(['ok' => false, 'error' => 'field too long'], 422);
}

// Спосіб оплати — тільки з увімкнених у налаштуваннях
$paymentMethods = hydro_payment_methods(2); // uk-ua
if (!isset($paymentMethods[$payment])) {
    hydro_json(['ok' => false, 'error' => 'bad payment method'], 422);
}
$paymentTitle = $paymentMethods[$payment];

// Ціни/назви/наявність — ТІЛЬКИ з БД (oc_product за model); з фронта лише id та кількість.
$db = hydro_db();
$lines = [];
$orderProducts = [];
$total = 0;
foreach ($items as $item) {
    $pid = (string)($item['id'] ?? '');
    $qty = (int)($item['qty'] ?? 0);
    if ($pid === '' || $qty < 1 || $qty > 100) {
        continue;
    }
    $stmt = $db->prepare("SELECT p.product_id, p.price, p.quantity, p.status, pd.name
        FROM " . DB_PREFIX . "product p
        JOIN " . DB_PREFIX . "product_description pd ON pd.product_id = p.product_id AND pd.language_id = 2
        WHERE p.model = ?");
    $stmt->bind_param('s', $pid);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if (!$p || !$p['status'] || $p['quantity'] <= 0) {
        continue;
    }
    $price = round((float)$p['price']);
    $sum = $price * $qty;
    $total += $sum;
    $lines[] = sprintf('- %s — %d шт x %s грн = %s грн', $p['name'], $qty, $price, $sum);
    $orderProducts[] = [
        'product_id' => (int)$p['product_id'],
        'name'       => $p['name'],
        'model'      => $pid,
        'quantity'   => $qty,
        'price'      => $price,
        'total'      => $sum,
    ];
}
if (!$orderProducts) {
    hydro_json(['ok' => false, 'error' => 'no valid items'], 422);
}

$orderRef = date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$methodLabels = ['np' => 'Нова пошта', 'ukrposhta' => 'Укрпошта', 'meest' => 'Meest'];
$shippingTitle = ($methodLabels[$dMethod] ?? $dMethod)
    . ($dType === 'courier' ? " (кур'єр)" : ' (відділення)');

// ===== oc_order: запис для вкладки "Замовлення" в адмінці =====
// wayforpay стартує зі статусу "Очікування" (1) до колбека; решта — з робочого статусу з налаштувань
$statusId = ($payment === 'wayforpay')
    ? 1
    : (int)(hydro_setting('payment_' . $payment . '_order_status_id') ?: 1);

$firstname = $name;
$lastname = '';
if (strpos($name, ' ') !== false) {
    [$firstname, $lastname] = explode(' ', $name, 2);
}
$comment = "Доставка: {$shippingTitle}\nМісто: {$dCity}\n"
    . ($dType === 'courier' ? "Адреса: {$dBranch}" : "Відділення: {$dBranch}");

$stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "order SET
    invoice_prefix = 'INV', store_id = 0, store_name = 'Hydrophob',
    store_url = 'https://hydrophob.net.ua/',
    customer_id = 0, customer_group_id = 1,
    firstname = ?, lastname = ?, email = ?, telephone = ?, fax = '', custom_field = '',
    payment_firstname = ?, payment_lastname = ?, payment_company = '',
    payment_address_1 = ?, payment_address_2 = '', payment_city = ?, payment_postcode = '',
    payment_country = 'Україна', payment_country_id = 220, payment_zone = '', payment_zone_id = 0,
    payment_address_format = '', payment_custom_field = '', payment_method = ?, payment_code = ?,
    shipping_firstname = ?, shipping_lastname = ?, shipping_company = '',
    shipping_address_1 = ?, shipping_address_2 = '', shipping_city = ?, shipping_postcode = '',
    shipping_country = 'Україна', shipping_country_id = 220, shipping_zone = '', shipping_zone_id = 0,
    shipping_address_format = '', shipping_custom_field = '', shipping_method = ?, shipping_code = ?,
    comment = ?, total = ?, order_status_id = ?,
    affiliate_id = 0, commission = 0, marketing_id = 0, tracking = '',
    language_id = 2, currency_id = (SELECT currency_id FROM " . DB_PREFIX . "currency WHERE code = 'UAH' LIMIT 1),
    currency_code = 'UAH', currency_value = 1.0,
    ip = ?, forwarded_ip = '', user_agent = ?, accept_language = '',
    date_added = NOW(), date_modified = NOW()");
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$dShipCode = 'hydro.' . $dMethod;
$stmt->bind_param('ssssssssssssssssssdiss',
    $firstname, $lastname, $email, $phone,
    $firstname, $lastname, $dBranch, $dCity, $paymentTitle, $payment,
    $firstname, $lastname, $dBranch, $dCity, $shippingTitle, $dShipCode,
    $comment, $total, $statusId, $ip, $ua);
$stmt->execute();
$ocOrderId = (int)$db->insert_id;

foreach ($orderProducts as $op) {
    $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "order_product
        SET order_id = ?, product_id = ?, name = ?, model = ?, quantity = ?, price = ?, total = ?, tax = 0, reward = 0");
    $stmt->bind_param('iissidd', $ocOrderId, $op['product_id'], $op['name'], $op['model'], $op['quantity'], $op['price'], $op['total']);
    $stmt->execute();
}
$db->query("INSERT INTO " . DB_PREFIX . "order_total (order_id, code, title, value, sort_order) VALUES
    ($ocOrderId, 'sub_total', 'Разом', $total, 1),
    ($ocOrderId, 'total', 'До сплати', $total, 9)");
$db->query("INSERT INTO " . DB_PREFIX . "order_history (order_id, order_status_id, notify, comment, date_added)
    VALUES ($ocOrderId, $statusId, 0, 'Замовлення з лендінгу (№ {$orderRef})', NOW())");

// ===== одноразовий токен сторінки успіху =====
$token = bin2hex(random_bytes(24));
$orderFile = hydro_storage_dir('orders') . '/' . $token . '.json';
file_put_contents($orderFile, json_encode([
    'order_id' => $orderRef,
    'oc_order_id' => $ocOrderId,
    'used' => false,
    'total' => $total,
    'created_at' => date('c'),
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'delivery' => ['method' => $dMethod, 'type' => $dType, 'city' => $dCity, 'branch' => $dBranch],
    'payment' => $payment,
    'payment_status' => $payment === 'wayforpay' ? 'pending' : 'n/a',
    'lines' => $lines,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// ===== лист (Brevo; помилка пошти не блокує замовлення) =====
$body = "Нове замовлення №{$orderRef} (адмінка: #{$ocOrderId}) з hydrophob.net.ua\n\n"
    . "Товари:\n" . implode("\n", $lines) . "\n\n"
    . "Разом: {$total} грн\n\n"
    . "Покупець: {$name}\nТелефон: {$phone}\n"
    . ($email !== '' ? "Email: {$email}\n" : '')
    . "\nДоставка: {$shippingTitle}\nМісто: {$dCity}\n"
    . ($dType === 'courier' ? "Адреса (кур'єр): {$dBranch}\n" : "Відділення: {$dBranch}\n")
    . "\nОплата: {$paymentTitle}\n"
    . "\nЧас: " . date('d.m.Y H:i:s');

$apiKey = (string)hydro_setting('config_mail_parameter', '');
$fromEmail = (string)hydro_setting('config_email', 'owner@hydrophob.net.ua');
$fromName = (string)hydro_setting('config_name', 'Hydrophob');
$recipients = array_filter(array_map('trim', explode(',', hydro_env('ORDER_EMAIL_TO'))));
if (!$recipients) {
    $recipients = [$fromEmail];
}
if ($apiKey !== '') {
    $mailer = new BrevoMailer($apiKey, $fromEmail, $fromName);
    foreach ($recipients as $to) {
        try {
            $mailer->send($to, "Hydrophob: замовлення №{$orderRef} на {$total} грн", $body);
        } catch (Throwable $e) {
            error_log('order ' . $orderRef . ' brevo error: ' . $e->getMessage());
        }
    }
}

// ===== WayForPay: підписана форма покупки =====
if ($payment === 'wayforpay') {
    $secret = (string)hydro_setting('payment_wayforpay_secretkey', '');
    $merchant = (string)hydro_setting('payment_wayforpay_merchant', '');
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'hydrophob.net.ua');

    $fields = [
        'merchantAccount'               => $merchant,
        'merchantAuthType'              => 'simpleSignature',
        'merchantDomainName'            => $host,
        'merchantTransactionSecureType' => 'AUTO',
        'orderReference'                => $orderRef . '#' . $ocOrderId,
        'orderDate'                     => time(),
        'amount'                        => $total,
        'currency'                      => 'UAH',
        'productName'                   => array_column($orderProducts, 'name'),
        'productPrice'                  => array_column($orderProducts, 'price'),
        'productCount'                  => array_column($orderProducts, 'quantity'),
        'clientFirstName'               => $firstname,
        'clientLastName'                => $lastname,
        'clientEmail'                   => $email,
        'clientPhone'                   => $phone,
        'language'                      => 'UA',
        'serviceUrl'                    => 'https://' . $host . '/api/wayforpay_callback.php',
        'returnUrl'                     => 'https://' . $host . '/index.php?route=checkout/hydro_success&token=' . $token,
    ];
    $w4p = new W4P($secret);
    $fields['merchantSignature'] = $w4p->getRequestSignature($fields);

    hydro_json(['ok' => true, 'token' => $token, 'order_id' => $orderRef,
        'pay' => ['url' => W4P::URL, 'fields' => $fields]]);
}

hydro_json(['ok' => true, 'token' => $token, 'order_id' => $orderRef]);
