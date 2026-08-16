<?php
// Сторінка успішного замовлення. Відкривається ОДИН раз за унікальним токеном
// (патерн як на pprintdim: /success/{token}); повторний візит — редирект на головну.

$token = (string)($_GET['token'] ?? '');
if (!preg_match('/^[a-f0-9]{32,80}$/', $token)) {
    header('Location: index.php');
    exit;
}

$file = __DIR__ . '/api/storage/orders/' . $token . '.json';
$order = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;

if (!$order || !empty($order['used'])) {
    header('Location: index.php');
    exit;
}

// позначаємо використаним — вдруге сторінка не відкриється
$order['used'] = true;
$order['used_at'] = date('c');
file_put_contents($file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$orderId = htmlspecialchars($order['order_id'] ?? '');
$total = htmlspecialchars((string)($order['total'] ?? ''));

require_once __DIR__ . '/api/bootstrap.php';
$anMode = hydro_env('ANALYTICS_MODE', 'test') === 'production' ? 'production' : 'test';
$ga4Id = trim(hydro_env('GA4_ID'));
$adsId = trim(hydro_env('GOOGLE_ADS_ID'));
$adsLabel = trim(hydro_env('GOOGLE_ADS_PURCHASE_LABEL'));
$orderIdJs = json_encode($order['order_id'] ?? '', JSON_UNESCAPED_UNICODE);
$totalJs = (float)($order['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Замовлення прийнято — Hydrophob</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .successPage{
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 28px;
            background: #121212;
            color: #fff;
            text-align: center;
            padding: 40px 20px;
        }
        .successPage__logo img{
            max-width: 220px;
        }
        .successPage__name{
            color: #1D9CB2;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .successPage__title{
            font-size: clamp(28px, 5vw, 48px);
            font-weight: 700;
        }
        .successPage__descr{
            font-size: 18px;
            line-height: 28px;
            opacity: .8;
            max-width: 520px;
        }
        .successPage__order{
            font-size: 22px;
            font-weight: 700;
        }
        .successPage__order span{
            color: #1D9CB2;
        }
        .successPage .btn{
            margin-top: 12px;
        }
    </style>
</head>
<body class="body">
    <main class="successPage">
        <a class="successPage__logo" href="index.php"><img src="img/logo.svg" alt="Hydrophob"></a>
        <p class="successPage__name">Дякуємо</p>
        <h1 class="successPage__title">Ваше замовлення прийнято!</h1>
        <p class="successPage__order">Номер замовлення: <span>№<?= $orderId ?></span> · Сума: <span><?= $total ?> грн</span></p>
        <p class="successPage__descr">Менеджер зв'яжеться з вами найближчим часом для підтвердження. Збережіть номер замовлення — сторінка доступна лише один раз.</p>
        <a class="btn" href="index.php">На головну</a>
    </main>
<?php if ($anMode === 'production' && ($ga4Id !== '' || $adsId !== '')): $firstId = $ga4Id !== '' ? $ga4Id : $adsId; ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
        if (localStorage.getItem('hydro_cookie_consent') === 'granted') {
            gtag('consent', 'update', { analytics_storage: 'granted', ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted' });
        }
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($firstId) ?>"></script>
    <script>
        gtag('js', new Date());
<?php if ($ga4Id !== ''): ?>        gtag('config', '<?= htmlspecialchars($ga4Id) ?>', { anonymize_ip: true });
<?php endif; ?>
<?php if ($adsId !== ''): ?>        gtag('config', '<?= htmlspecialchars($adsId) ?>');
<?php endif; ?>
        if (localStorage.getItem('hydro_cookie_consent') === 'granted') {
<?php if ($ga4Id !== ''): ?>            gtag('event', 'purchase', { transaction_id: <?= $orderIdJs ?>, value: <?= $totalJs ?>, currency: 'UAH' });
<?php endif; ?>
<?php if ($adsId !== '' && $adsLabel !== ''): ?>            gtag('event', 'conversion', { send_to: '<?= htmlspecialchars($adsId) ?>/<?= htmlspecialchars($adsLabel) ?>', value: <?= $totalJs ?>, currency: 'UAH', transaction_id: <?= $orderIdJs ?> });
<?php endif; ?>
        }
    </script>
<?php else: ?>
    <script>
        console.log('%c[GA4 TEST]', 'color:#1D9CB2;font-weight:bold', 'purchase', { transaction_id: <?= $orderIdJs ?>, value: <?= $totalJs ?>, currency: 'UAH' });
    </script>
<?php endif; ?>
</body>
</html>
