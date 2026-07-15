
<section class="reviews">
    <div class="container">
        <div class="reviews__inner">
            <div class="reviews__left">
                <h2 class="reviews__title" data-i18n-html="reviews.titleHtml">Відгуки <span>наших клієнтів</span></h2>
                <div class="reviews__slides"><span>01</span>/06</div>
            </div>
            <div class="reviews__content">
                <div class="reviews__slider">
                    <div class="swiper-wrapper">
<?php
$REVIEWS_I18N = json_decode(file_get_contents(__DIR__ . '/../strings.json'), true);
$reviewIndex = 0;
foreach (($IMAGES['reviews'] ?? []) as $reviewItem):
    $reviewIndex++;
    $reviewText = $REVIEWS_I18N['reviews']['message' . $reviewIndex]['UA'] ?? '';
?>
                        <div class="reviews__slide swiper-slide">
                            <div class="reviews__slide-video">
                                <img class="reviews__slide-image" src="<?= htmlspecialchars($reviewItem['poster']) ?>" alt="">
                                <button class="reviews__slide-play play-btn play-btn-open" data-video="<?= htmlspecialchars($reviewItem['video']) ?>">Play reels</button>
                            </div>
                            <p class="reviews__slide-message" data-i18n="reviews.message<?= $reviewIndex ?>"><?= htmlspecialchars($reviewText) ?></p>
                        </div>
<?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
