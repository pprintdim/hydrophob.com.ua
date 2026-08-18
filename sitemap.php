<?php
// Динамічний sitemap.xml — все актуальне з БД/налаштувань:
// зображення активних товарів (oc_product + oc_product_image), галерея imagesBlock,
// відео з модулів hero та imagesBlock (постери за тією ж детермінованою схемою, що й контролери).
define('HYDRO_ROOT', __DIR__);
require __DIR__ . '/api/db.php';

header('Content-Type: application/xml; charset=utf-8');

$base = 'https://hydrophob.net.ua';

function xmle(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** Відносний шлях відео (щодо image/) з значення налаштувань. */
function video_relative(string $video): string {
    if (strpos($video, 'image/') === 0) {
        return substr($video, 6);
    }
    if (strpos($video, 'video/') === 0) {
        return 'catalog/' . $video;
    }
    return $video;
}

/** Постер відео — та сама схема, що в common/video_poster і контролерах фронту. */
function video_poster(string $relative): string {
    $name = pathinfo($relative, PATHINFO_FILENAME) . '-' . substr(md5($relative), 0, 6) . '.webp';
    return is_file(HYDRO_ROOT . '/image/catalog/video-posters/' . $name) ? 'catalog/video-posters/' . $name : '';
}

// ---- зображення товарів (головне + додаткові), без дублів ----
$images = [];
$db = hydro_db();
$res = $db->query("SELECT image FROM " . DB_PREFIX . "product WHERE status = 1 AND image <> ''
    UNION SELECT pi.image FROM " . DB_PREFIX . "product_image pi
    JOIN " . DB_PREFIX . "product p ON p.product_id = pi.product_id AND p.status = 1 AND pi.image <> ''");
while ($row = $res->fetch_assoc()) {
    $images[$row['image']] = true;
}

// галерея imagesBlock (повнорозмірні кадри з data/images.json)
$imagesJson = json_decode((string)@file_get_contents(__DIR__ . '/data/images.json'), true) ?: [];
foreach (($imagesJson['imagesBlock']['items'] ?? []) as $item) {
    if (!empty($item['full'])) {
        $images['hydrophob/' . str_replace('img/', '', $item['full'])] = true;
    }
}

$imageTags = '';
foreach (array_keys($images) as $img) {
    if (!is_file(HYDRO_ROOT . '/image/' . $img)) {
        continue;
    }
    $imageTags .= "        <image:image><image:loc>" . xmle($base . '/image/' . $img) . "</image:loc></image:image>\n";
}

// ---- відео: hero-слайди + відео-плитки imagesBlock (усе з налаштувань модулів) ----
$videos = [];

$siteName = (string)hydro_setting('config_name', 'Hydrophob');

$heroSlides = hydro_setting('module_hydrophob_hero_slides', []);
if (is_array($heroSlides)) {
    foreach ($heroSlides as $i => $slide) {
        if (empty($slide['video'])) {
            continue;
        }
        $rel = video_relative($slide['video']);
        $poster = !empty($slide['poster']) ? $slide['poster'] : video_poster($rel);
        $videos[] = [
            'src'    => $rel,
            'poster' => $poster,
            'title'  => $siteName . ' — відео з головного слайдера ' . ($i + 1),
            'descr'  => 'Демонстрація дії гідрофобного нанопокриття ' . $siteName . '.',
        ];
    }
}

$tiles = hydro_setting('module_hydrophob_images_block_items', []);
if (is_array($tiles)) {
    foreach ($tiles as $i => $row) {
        if (empty($row['video'])) {
            continue;
        }
        $rel = video_relative($row['video']);
        $alt = is_array($row['alt'] ?? null) ? (string)($row['alt'][2] ?? '') : (string)($row['alt'] ?? '');
        $videos[] = [
            'src'    => $rel,
            'poster' => video_poster($rel) ?: (string)($row['tile'] ?? ''),
            'title'  => $alt !== '' ? $alt : $siteName . ' — відео галереї ' . ($i + 1),
            'descr'  => ($alt !== '' ? $alt . '. ' : '') . 'Відео з галереї ' . $siteName . '.',
        ];
    }
}

$videoTags = '';
$seen = [];
foreach ($videos as $v) {
    if (isset($seen[$v['src']]) || !is_file(HYDRO_ROOT . '/image/' . $v['src']) || $v['poster'] === '') {
        continue;
    }
    $seen[$v['src']] = true;
    $videoTags .= "        <video:video>\n"
        . "            <video:thumbnail_loc>" . xmle($base . '/image/' . $v['poster']) . "</video:thumbnail_loc>\n"
        . "            <video:title>" . xmle($v['title']) . "</video:title>\n"
        . "            <video:description>" . xmle($v['descr']) . "</video:description>\n"
        . "            <video:content_loc>" . xmle($base . '/image/' . $v['src']) . "</video:content_loc>\n"
        . "            <video:family_friendly>yes</video:family_friendly>\n"
        . "        </video:video>\n";
}

$hreflang = "        <xhtml:link rel=\"alternate\" hreflang=\"uk\" href=\"{$base}/\"/>\n"
    . "        <xhtml:link rel=\"alternate\" hreflang=\"ru\" href=\"{$base}/ru\"/>\n"
    . "        <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$base}/en\"/>\n"
    . "        <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$base}/\"/>\n";

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc><?= $base ?>/</loc>
<?= $hreflang ?>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
<?= $videoTags ?>
<?= $imageTags ?>
    </url>
    <url>
        <loc><?= $base ?>/ru</loc>
<?= $hreflang ?>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $base ?>/en</loc>
<?= $hreflang ?>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $base ?>/privacy</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= $base ?>/returns</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= $base ?>/offer</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
