<?php
// Юридичні сторінки. ЧПУ через .htaccess: /privacy /returns /offer → legal.php?p=...
require_once __DIR__ . '/api/bootstrap.php';

$LEGAL = json_decode(file_get_contents(__DIR__ . '/data/legal.json'), true) ?: [];
$key = preg_replace('/[^a-z]/', '', (string)($_GET['p'] ?? ''));
$page = $LEGAL[$key] ?? null;
if (!$page) {
    header('Location: index.php');
    exit;
}

$SEO = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true) ?: [];
$baseUrl = rtrim($SEO['url'] ?? '', '/') . '/';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$IMAGES = [];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($page['title']) ?> — Hydrophob</title>
    <meta name="description" content="<?= $e($page['title']) ?> інтернет-магазину Hydrophob.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $e($baseUrl . $key) ?>">
    <link rel="icon" type="image/svg+xml" href="image/hydrophob/logo.svg">
    <link rel="icon" type="image/png" href="image/hydrophob/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="catalog/view/theme/default/stylesheet/hydrophob.css?v=<?= @filemtime(__DIR__ . '/catalog/view/theme/default/stylesheet/hydrophob.css') ?>">
</head>
<body class="body">
    <?php require __DIR__ . '/sections/header.php'; ?>

    <main class="main">
        <section class="legal">
            <div class="container">
                <a class="legal__back" href="./">← На головну</a>
                <h1 class="legal__title"><?= $e($page['title']) ?></h1>
                <div class="legal__content"><?= $page['html'] ?></div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/sections/footer.php'; ?>
    <script src="catalog/view/theme/default/javascript/hydrophob.js?v=<?= @filemtime(__DIR__ . '/catalog/view/theme/default/javascript/hydrophob.js') ?>"></script>
</body>
</html>
