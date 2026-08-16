
<?php
$products = json_decode(file_get_contents(__DIR__ . '/../data/products.json'), true);
if (!is_array($products)) {
    $products = [];
}
?>
<section class="product" id="catalog">
    <p class="product__name">Hydrophob</p>
    <div class="container">
        <div class="product__inner">
            <button class="product__slider-prev">
                <svg xmlns="http://www.w3.org/2000/svg" width="104" height="104" viewBox="0 0 104 104" fill="none">
                    <circle opacity="0.6" cx="52" cy="52" r="51.5" transform="matrix(-1 0 0 1 104 0)" stroke="white"/>
                    <circle opacity="0.04" cx="52" cy="52" r="52" transform="matrix(-1 0 0 1 104 0)" fill="white"/>
                    <path opacity="0.4" d="M61 21L44 51.5L61 82" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="product__slider">
                <div class="swiper-wrapper">
<?php foreach ($products as $product): ?>
<?php if (($product['qty'] ?? 0) <= 0) { continue; } ?>
                    <div class="product__slide swiper-slide" data-product-id="<?= htmlspecialchars($product['id']) ?>">
                        <div class="product__slide-image">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']['UA']) ?>" loading="lazy">
                        </div>
                        <div class="product__slide-content">
                            <h3 class="product__slide-title" data-p-id="<?= htmlspecialchars($product['id']) ?>" data-p-part="title"><?= htmlspecialchars($product['title']['UA']) ?></h3>
                            <p class="product__slide-descr" data-p-id="<?= htmlspecialchars($product['id']) ?>" data-p-part="descr"><?= htmlspecialchars($product['descr']['UA']) ?></p>
                            <p class="product__slide-volume"><span data-i18n="product.volumeLabel">Об'єм</span> <?= htmlspecialchars($product['volume']) ?></p>
                            <p class="product__slide-price"><?= htmlspecialchars($product['price']) ?> <span data-i18n="product.uah">грн.</span></p>
                            <div class="product__slide-block">
                                <a class="product__slide-more product-more-open" href="#" data-product-id="<?= htmlspecialchars($product['id']) ?>" data-i18n="product.more">Детальніше</a>
                                <button class="product__slide-buy cart-open btn" data-product-id="<?= htmlspecialchars($product['id']) ?>" data-i18n="product.buy">Купити</button>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
            <button class="product__slider-next">
                <svg xmlns="http://www.w3.org/2000/svg" width="104" height="104" viewBox="0 0 104 104" fill="none">
                    <circle opacity="0.6" cx="52" cy="52" r="51.5" stroke="white"/>
                    <circle opacity="0.04" cx="52" cy="52" r="52" fill="white"/>
                    <path opacity="0.4" d="M43 21L60 51.5L43 82" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <span class="product__slider-pagination"></span>
        </div>
    </div>
</section>
