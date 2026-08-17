<?php
// Приймання замовлення: валідація, лист на пошту, одноразовий токен для сторінки успіху.

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/SmtpMailer.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    hydro_json(['ok' => false, 'error' => 'POST only'], 405);
}

$input = hydro_input();
$items = $input['items'] ?? [];
$contact = $input['contact'] ?? [];
$delivery = $input['delivery'] ?? [];
$payment = trim((string)($input['payment'] ?? ''));

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

// Ціни й назви беремо ТІЛЬКИ з products.json — з фронта лише id та кількість.
$catalog = json_decode((string)file_get_contents(HYDRO_ROOT . '/data/products.json'), true) ?: [];
$byId = [];
foreach ($catalog as $p) {
    $byId[$p['id']] = $p;
}

$lines = [];
$total = 0;
foreach ($items as $item) {
    $pid = (string)($item['id'] ?? '');
    $qty = (int)($item['qty'] ?? 0);
    if (!isset($byId[$pid]) || $qty < 1 || $qty > 100) {
        continue;
    }
    $p = $byId[$pid];
    if (($p['qty'] ?? 0) <= 0) { // немає в наявності
        continue;
    }
    $title = is_array($p['title']) ? $p['title']['UA'] : $p['title'];
    $sum = $p['price'] * $qty;
    $total += $sum;
    $lines[] = sprintf('- %s — %d шт x %s грн = %s грн', $title, $qty, $p['price'], $sum);
}
if (!$lines) {
    hydro_json(['ok' => false, 'error' => 'no valid items'], 422);
}

$orderId = date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$methodLabels = ['np' => 'Нова пошта', 'ukrposhta' => 'Укрпошта', 'meest' => 'Meest'];
$paymentLabels = ['card' => 'Оплата карткою', 'cod' => 'Накладений платіж'];

$body = "Нове замовлення №{$orderId} з лендінгу Hydrophob\n\n"
    . "Товари:\n" . implode("\n", $lines) . "\n\n"
    . "Разом: {$total} грн\n\n"
    . "Покупець: {$name}\n"
    . "Телефон: {$phone}\n"
    . ($email !== '' ? "Email: {$email}\n" : '')
    . "\nДоставка: " . ($methodLabels[$dMethod] ?? $dMethod) . "\n"
    . "Місто: {$dCity}\n"
    . ($dType === 'courier' ? "Адреса (кур'єр): {$dBranch}\n" : "Відділення: {$dBranch}\n")
    . ($payment !== '' ? "\nОплата: " . ($paymentLabels[$payment] ?? $payment) . "\n" : '')
    . "\nЧас: " . date('d.m.Y H:i:s');

// Одноразовий токен сторінки успіху
$token = bin2hex(random_bytes(24)); // 48 hex
$orderFile = hydro_storage_dir('orders') . '/' . $token . '.json';
file_put_contents($orderFile, json_encode([
    'order_id' => $orderId,
    'used' => false,
    'total' => $total,
    'created_at' => date('c'),
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'delivery' => ['method' => $dMethod, 'type' => $dType, 'city' => $dCity, 'branch' => $dBranch],
    'payment' => $payment,
    'lines' => $lines,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Листи (кожному отримувачу окремо; помилка пошти не блокує замовлення)
$mailErrors = [];
$recipients = array_filter(array_map('trim', explode(',', hydro_env('ORDER_EMAIL_TO'))));
if ($recipients && hydro_env('SMTP_USER') !== '' && hydro_env('SMTP_PASS') !== '') {
    $mailer = new SmtpMailer(
        hydro_env('SMTP_HOST', 'smtp.gmail.com'),
        (int)hydro_env('SMTP_PORT', '587'),
        hydro_env('SMTP_USER'),
        hydro_env('SMTP_PASS'),
        hydro_env('SMTP_FROM')
    );
    foreach ($recipients as $to) {
        try {
            $mailer->send($to, "Hydrophob: замовлення №{$orderId} на {$total} грн", $body);
        } catch (Throwable $e) {
            $mailErrors[] = $to . ': ' . $e->getMessage();
        }
    }
}
if ($mailErrors) {
    error_log('order ' . $orderId . ' mail errors: ' . implode(' | ', $mailErrors));
}

hydro_json(['ok' => true, 'token' => $token, 'order_id' => $orderId]);
