<section class="hero" id="home">
    <div class="container">
        <div class="hero__inner">
            <h1 class="hero__title" data-i18n-html="hero.titleHtml">Виходь сухим із води<span>Hydrophob</span></h1>
        </div>
        <div class="hero__video">
            <img class="hero__video-image" src="<?= htmlspecialchars($IMAGES['hero']['poster'] ?? 'img/hero-poster.webp') ?>" alt="<?= htmlspecialchars($IMAGES['hero']['alt'] ?? 'Hydrophob') ?>" fetchpriority="high">
            <button class="hero__video-play play-btn play-btn-open" data-video="<?= htmlspecialchars($IMAGES['hero']['video'] ?? 'video/hero.mp4') ?>">Play reels</button>
        </div>
    </div>
</section>
