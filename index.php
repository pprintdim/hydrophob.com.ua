<?php
require_once __DIR__ . '/api/bootstrap.php';
$anMode = hydro_env('ANALYTICS_MODE', 'test') === 'production' ? 'production' : 'test';
$ga4Id = trim(hydro_env('GA4_ID'));
$gtmId = trim(hydro_env('GTM_ID'));
$adsId = trim(hydro_env('GOOGLE_ADS_ID'));
$adsLabel = trim(hydro_env('GOOGLE_ADS_PURCHASE_LABEL'));

// ── SEO: конфіг + вибір мови (?lang=uk|ru|en, дефолт UA) ──
$SEO = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true) ?: [];
$langMap = ['uk' => 'UA', 'ua' => 'UA', 'ru' => 'RU', 'en' => 'EN'];
$reqLang = strtolower($_GET['lang'] ?? '');
$LANG = $langMap[$reqLang] ?? ($SEO['defaultLang'] ?? 'UA');
$meta = $SEO['meta'][$LANG] ?? ($SEO['meta']['UA'] ?? ['title' => 'Hydrophob', 'description' => '', 'keywords' => '']);
$htmlLang = $SEO['htmlLang'][$LANG] ?? 'uk';
$ogLocale = $SEO['locales'][$LANG] ?? 'uk_UA';
$baseUrl = rtrim($SEO['url'] ?? '', '/') . '/';
$canonical = $baseUrl . ($LANG !== ($SEO['defaultLang'] ?? 'UA') ? '?lang=' . $htmlLang : '');
$ogImageAbs = $baseUrl . ($SEO['ogImage'] ?? 'img/og-image.jpg');
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// JSON-LD: Organization/Store + WebSite + ItemList + FAQPage
$SEO_PRODUCTS = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true) ?: [];
$SEO_STRINGS = json_decode(file_get_contents(__DIR__ . '/data/strings.json'), true) ?: [];
$org = $SEO['org'] ?? [];
$rating = $SEO['rating'] ?? ['value' => '4.9', 'count' => '251', 'best' => '5'];
$ld = [];
$ld[] = [
    '@context' => 'https://schema.org', '@type' => ['Organization', 'Store'],
    'name' => $SEO['siteName'] ?? 'Hydrophob', 'url' => $baseUrl,
    'logo' => $baseUrl . 'img/logo.svg', 'image' => $ogImageAbs,
    'email' => $org['email'] ?? '', 'telephone' => $org['telephoneDisplay'] ?? '',
    'priceRange' => '₴₴',
    'openingHours' => $org['openHours'] ?? '',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $org['streetAddress'] ?? '', 'addressLocality' => $org['addressLocality'] ?? '',
        'addressRegion' => $org['addressRegion'] ?? '', 'postalCode' => $org['postalCode'] ?? '',
        'addressCountry' => $org['addressCountry'] ?? 'UA',
    ],
    'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $org['lat'] ?? null, 'longitude' => $org['lng'] ?? null],
    'aggregateRating' => [
        '@type' => 'AggregateRating', 'ratingValue' => $rating['value'], 'reviewCount' => $rating['count'], 'bestRating' => $rating['best'],
    ],
    'review' => array_map(function ($r) use ($rating) {
        return [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $r['author']],
            'datePublished' => $r['date'] ?? '',
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => '5', 'bestRating' => $rating['best']],
            'reviewBody' => $r['text'],
        ];
    }, $SEO['reviews'] ?? []),
    'sameAs' => $org['socials'] ?? [],
];
$ld[] = [
    '@context' => 'https://schema.org', '@type' => 'WebSite',
    'name' => $SEO['siteName'] ?? 'Hydrophob', 'url' => $baseUrl,
    'inLanguage' => $ogLocale,
];
$listItems = [];
$pos = 0;
foreach ($SEO_PRODUCTS as $p) {
    if (($p['qty'] ?? 0) <= 0) { continue; }
    $pos++;
    $title = is_array($p['title'] ?? null) ? ($p['title'][$LANG] ?? $p['title']['UA']) : ($p['title'] ?? '');
    $descr = is_array($p['descr'] ?? null) ? ($p['descr'][$LANG] ?? $p['descr']['UA']) : ($p['descr'] ?? '');
    $listItems[] = [
        '@type' => 'ListItem', 'position' => $pos,
        'item' => [
            '@type' => 'Product', 'name' => $title,
            'description' => mb_substr(strip_tags($descr), 0, 300),
            'image' => $baseUrl . ($p['image'] ?? ''),
            'sku' => $p['id'] ?? '', 'brand' => ['@type' => 'Brand', 'name' => 'Hydrophob'],
            'category' => $p['category'] ?? '',
            'offers' => [
                '@type' => 'Offer', 'price' => (string)($p['price'] ?? ''), 'priceCurrency' => 'UAH',
                'availability' => 'https://schema.org/InStock', 'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $canonical, 'seller' => ['@type' => 'Organization', 'name' => $SEO['siteName'] ?? 'Hydrophob'],
            ],
        ],
    ];
}
if ($listItems) {
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $listItems];
}

// FAQPage — з секції FAQ (strings.json), поточна мова
$faq = $SEO_STRINGS['faq'] ?? [];
$faqEntities = [];
for ($i = 1; $i <= 6; $i++) {
    $q = $faq["q{$i}"][$LANG] ?? ($faq["q{$i}"]['UA'] ?? '');
    $a = $faq["a{$i}"][$LANG] ?? ($faq["a{$i}"]['UA'] ?? '');
    if ($q === '' || $a === '') { continue; }
    $faqEntities[] = [
        '@type' => 'Question', 'name' => $q,
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)],
    ];
}
if ($faqEntities) {
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqEntities];
}
?>
<!DOCTYPE html>
<html lang="<?= $e($htmlLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($meta['title']) ?></title>
    <meta name="description" content="<?= $e($meta['description']) ?>">
    <meta name="keywords" content="<?= $e($meta['keywords']) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $e($canonical) ?>">
<?php foreach (($SEO['htmlLang'] ?? []) as $lk => $hl): $u = $baseUrl . ($lk === ($SEO['defaultLang'] ?? 'UA') ? '' : '?lang=' . $hl); ?>
    <link rel="alternate" hreflang="<?= $e($hl) ?>" href="<?= $e($u) ?>">
<?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= $e($baseUrl) ?>">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?= $e($ogLocale) ?>">
    <meta property="og:site_name" content="<?= $e($SEO['siteName'] ?? 'Hydrophob') ?>">
    <meta property="og:title" content="<?= $e($meta['title']) ?>">
    <meta property="og:description" content="<?= $e($meta['description']) ?>">
    <meta property="og:url" content="<?= $e($canonical) ?>">
    <meta property="og:image" content="<?= $e($ogImageAbs) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $e($meta['title']) ?>">
    <meta name="twitter:description" content="<?= $e($meta['description']) ?>">
    <meta name="twitter:image" content="<?= $e($ogImageAbs) ?>">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="img/logo.svg">
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/favicon.png">
    <meta name="theme-color" content="#121212">

    <script>window.HYDRO_ANALYTICS = { mode: "<?= $e($anMode) ?>", ga4: "<?= $e($ga4Id) ?>", ads: "<?= $e($adsId) ?>", adsLabel: "<?= $e($adsLabel) ?>" };</script>
<?php if ($anMode === 'production' && ($ga4Id !== '' || $gtmId !== '' || $adsId !== '')): ?>
    <!-- Consent Mode v2: за замовчуванням усе заборонено, дозвіл — після згоди на cookie -->
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('consent', 'default', {
            ad_storage: 'denied', ad_user_data: 'denied',
            ad_personalization: 'denied', analytics_storage: 'denied',
            wait_for_update: 500
        });
    </script>
<?php if ($gtmId !== ''): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= $e($gtmId) ?>');</script>
    <!-- End Google Tag Manager -->
<?php endif; ?>
<?php if ($ga4Id !== '' || $adsId !== ''): $firstId = $ga4Id !== '' ? $ga4Id : $adsId; ?>
    <!-- gtag.js (GA4<?= $adsId !== '' ? ' + Google Ads' : '' ?>) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $e($firstId) ?>"></script>
    <script>
        gtag('js', new Date());
<?php if ($ga4Id !== ''): ?>        gtag('config', '<?= $e($ga4Id) ?>', { anonymize_ip: true });
<?php endif; ?>
<?php if ($adsId !== ''): ?>        gtag('config', '<?= $e($adsId) ?>');
<?php endif; ?>    </script>
<?php endif; ?>
<?php endif; ?>

    <script type="application/ld+json"><?= json_encode(count($ld) === 1 ? $ld[0] : $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body class="body">
<?php if ($anMode === 'production' && $gtmId !== ''): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= $e($gtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
    <?php
    $IMAGES = json_decode(file_get_contents(__DIR__ . '/data/images.json'), true);
    if (!is_array($IMAGES)) { $IMAGES = []; }
    require __DIR__ . '/sections/header.php';
    ?>

    <main class="main">
<?php
        require __DIR__ . '/sections/hero.php';
        require __DIR__ . '/sections/popup-video.php';
        require __DIR__ . '/sections/popup-photo.php';
        require __DIR__ . '/sections/popup-product.php';
        require __DIR__ . '/sections/popup-about.php';
        require __DIR__ . '/sections/popup-delivery.php';
        require __DIR__ . '/sections/about.php';
        require __DIR__ . '/sections/action.php';
        require __DIR__ . '/sections/images-block.php';
        require __DIR__ . '/sections/product.php';
        require __DIR__ . '/sections/info-block.php';
        require __DIR__ . '/sections/reviews.php';
        require __DIR__ . '/sections/guarantee.php';
        require __DIR__ . '/sections/faq.php';
        require __DIR__ . '/sections/delivery.php';
        require __DIR__ . '/sections/contacts.php';
        require __DIR__ . '/sections/cart.php';
    ?>
    </main>

    <?php require __DIR__ . '/sections/footer.php'; ?>
    <?php require __DIR__ . '/sections/cookie.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="js/script.js?v=<?= @filemtime(__DIR__ . '/js/script.js') ?>"></script>
</body>
</html>