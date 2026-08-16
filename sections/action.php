
<?php
require_once __DIR__ . '/../api/bootstrap.php';

$HYDRO_ACTION_TIMER_START = hydro_env('ACTION_TIMER_START', '2026-07-15 00:00:00');
$HYDRO_ACTION_TIMER_END = hydro_env('ACTION_TIMER_END', '2027-07-15 18:30:00');
$HYDRO_ACTION_TIMER_TIMEZONE = hydro_env('ACTION_TIMER_TIMEZONE', 'Europe/Kyiv');

try {
    $timerTimezone = new DateTimeZone($HYDRO_ACTION_TIMER_TIMEZONE);
    $timerStart = new DateTimeImmutable($HYDRO_ACTION_TIMER_START, $timerTimezone);
    $timerEnd = new DateTimeImmutable($HYDRO_ACTION_TIMER_END, $timerTimezone);
    $HYDRO_ACTION_TIMER_START_ISO = $timerStart->format(DATE_ATOM);
    $HYDRO_ACTION_TIMER_END_ISO = $timerEnd->format(DATE_ATOM);
} catch (Throwable $e) {
    $HYDRO_ACTION_TIMER_START_ISO = '';
    $HYDRO_ACTION_TIMER_END_ISO = '';
}
?>
<section
    class="action"
    data-timer-start="<?= htmlspecialchars($HYDRO_ACTION_TIMER_START_ISO, ENT_QUOTES, 'UTF-8') ?>"
    data-timer-end="<?= htmlspecialchars($HYDRO_ACTION_TIMER_END_ISO, ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="container">
        <div class="action__inner">
            <div class="action__content">
                <h2 class="action__title" data-i18n="action.title">Акція</h2>
                <p class="action__name" data-i18n="action.name">"Безкоштовна доставка"</p>
                <p class="action__descr" data-i18n="action.descr">При оформленні замовлення сьогодні - доставка за наш рахунок *</p>
            </div>
            <div class="action__block">
                <div class="action__item">
                    <div class="action__item-circle" id="days">

                        <span class="circle-svg"></span>
                        <span class="dot-container">
                            <img src="img/dot.svg" class="dot-image" alt="" loading="lazy" decoding="async" />
                        </span>
                        <span class="white-mask"></span>
                        <div class="ticks" id="ticks-days"></div>
                        <span class="pointer"></span>
                        <div class="number" id="days-number">00</div>
                    </div>
                    <p class="action__item-name" data-i18n="action.days">День</p>
                </div>
                <div class="action__item">
                    <div class="action__item-circle" id="hours">

                        <span class="circle-svg"></span>
                        <span class="dot-container">
                            <img src="img/dot.svg" class="dot-image" alt="" loading="lazy" decoding="async" />
                        </span>
                        <span class="white-mask"></span>
                        <div class="ticks" id="ticks-hours"></div>
                        <span class="pointer"></span>
                        <div class="number" id="hours-number">00</div>
                    </div>
                    <p class="action__item-name" data-i18n="action.hours">Години</p>
                </div>
                <div class="action__item">
                    <div class="action__item-circle" id="minutes">

                        <span class="circle-svg"></span>
                        <span class="dot-container">
                            <img src="img/dot.svg" class="dot-image" alt="" loading="lazy" decoding="async" />
                        </span>
                        <span class="white-mask"></span>
                        <div class="ticks" id="ticks-minutes"></div>
                        <span class="pointer"></span>
                        <div class="number" id="minutes-number">00</div>
                    </div>
                    <p class="action__item-name" data-i18n="action.minutes">Хвилини</p>
                </div>
                <div class="action__item">
                    <div class="action__item-circle" id="seconds">

                        <span class="circle-svg"></span>
                        <span class="dot-container">
                            <img src="img/dot.svg" class="dot-image" alt="" loading="lazy" decoding="async" />
                        </span>
                        <span class="white-mask"></span>
                        <div class="ticks" id="ticks-seconds"></div>
                        <span class="pointer"></span>
                        <div class="number" id="seconds-number">00</div>
                    </div>

                    <p class="action__item-name" data-i18n="action.seconds">Секунди</p>
                </div>
            </div>
        </div>
    </div>
</section>
