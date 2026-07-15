
<?php
$INFO_PRODUCTS = json_decode(file_get_contents(__DIR__ . '/../products.json'), true);
if (!is_array($INFO_PRODUCTS)) {
    $INFO_PRODUCTS = [];
}
$infoTabs = [
    'Automobile' => 'p2486252839',
    'Textile'    => 'p2523866690',
    'Industrial' => 'p2528418243',
];
$infoById = [];
foreach ($INFO_PRODUCTS as $infoProduct) {
    $infoById[$infoProduct['id']] = $infoProduct;
}
$tabMedia = [
    'Automobile' => ['poster' => $IMAGES['infoBlock']['Automobile']['poster'] ?? 'img/infoBlock/automobile.webp', 'video' => $IMAGES['infoBlock']['Automobile']['video'] ?? 'video/reels/Hydro01757373.mp4'],
    'Textile'    => ['poster' => $IMAGES['infoBlock']['Textile']['poster'] ?? 'img/infoBlock/textile.webp', 'video' => $IMAGES['infoBlock']['Textile']['video'] ?? 'video/reels/Hydro01762351.mp4'],
    'Industrial' => ['poster' => $IMAGES['infoBlock']['Industrial']['poster'] ?? 'img/infoBlock/industrial.webp', 'video' => $IMAGES['infoBlock']['Industrial']['video'] ?? 'video/reels/HYDROFOB-vol2-canister.mp4'],
];
?>
<section class="infoBlock">
    <div class="container">
        <div class="infoBlock__inner">
            <div class="infoBlock__select">
<?php $first = true; foreach ($infoTabs as $tabKey => $tabPid): $tabProduct = $infoById[$tabPid] ?? null; ?>
                <button class="infoBlock__select-btn<?= $first ? ' active' : '' ?>" data-infoBlock-btn="<?= $tabKey ?>"><?= htmlspecialchars($tabProduct['details']['tabTitle']['UA'] ?? ('Hydrophob ' . $tabKey)) ?></button>
<?php $first = false; endforeach; ?>
            </div>
            <div class="infoBlock__result">
<?php
$first = true;
foreach ($infoTabs as $tabKey => $tabPid):
    $tabProduct = $infoById[$tabPid] ?? null;
    if (!$tabProduct) { continue; }
    $details = $tabProduct['details'] ?? ['tabTitle' => ['UA' => $tabProduct['title']['UA']], 'subtitle' => ['UA' => ''], 'blocks' => []];
?>
                <div class="infoBlock__result-content<?= $first ? ' active' : '' ?>" data-infoBlock-result="<?= $tabKey ?>">
                    <div class="infoBlock__left">
                        <div class="infoBlock__video">
                            <img class="infoBlock__video-image" src="<?= htmlspecialchars($tabMedia[$tabKey]['poster']) ?>" alt="">
                            <button class="infoBlock__video-play play-btn play-btn-open" data-video="<?= htmlspecialchars($tabMedia[$tabKey]['video']) ?>">Play reels</button>
                        </div>
                    </div>
                    <div class="infoBlock__content">
                        <h3 class="infoBlock__title"><?= htmlspecialchars($details['tabTitle']['UA']) ?> <span data-p-id="<?= $tabPid ?>" data-p-part="subtitle"><?= htmlspecialchars($details['subtitle']['UA'] ?? '') ?></span></h3>
                        <div class="infoBlock__block">
                            <p class="infoBlock__volume"><span data-i18n="product.volumeLabel">Об'єм</span> <?= htmlspecialchars($tabProduct['volume'] !== '' ? $tabProduct['volume'] : '—') ?></p>
                            <p class="infoBlock__price"><?= htmlspecialchars($tabProduct['price']) ?> <span data-i18n="product.uah">грн.</span></p>
                            <button class="infoBlock__btn btn cart-open" data-product-id="<?= $tabPid ?>" data-i18n="product.buy">Купити</button>
                        </div>
                        <div class="infoBlock__more">
<?php foreach (($details['blocks'] ?? []) as $blockIndex => $detailBlock): ?>
                            <div class="infoBlock__more-item">
                                <h3 class="infoBlock__more-title" data-p-id="<?= $tabPid ?>" data-p-block="<?= $blockIndex ?>" data-p-part="title"> <?= htmlspecialchars($detailBlock['title']['UA']) ?></h3>
                                <div class="infoBlock__more-content" data-p-id="<?= $tabPid ?>" data-p-block="<?= $blockIndex ?>" data-p-part="html"><?= $detailBlock['html']['UA'] ?></div>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
<?php $first = false; endforeach; ?>
            </div>
        </div>
    </div>
</section>
