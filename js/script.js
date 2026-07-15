document.addEventListener('DOMContentLoaded', function() {
    const langSelectedAll = document.querySelectorAll('.header__lang-selected');
    langSelectedAll.forEach(langSelected => {
        const langBlock = langSelected.closest('.header__lang');
        langSelected.addEventListener('click', function(e) {
            e.preventDefault();
            langBlock.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!langBlock.contains(e.target)) {
                langBlock.classList.remove('active');
            }
        });
    });
});

const headerBurger = document.querySelector('.header__burger');
const headerMenu = document.querySelector('.header__menu');
headerBurger.addEventListener('click', () => {
    headerMenu.classList.toggle('active');
});



const endDate = new Date(2025, 6, 15, 18, 30, 0);

let ticksDays, ticksHours, ticksMinutes, ticksSeconds;

function initTicks() {
    const containers = ['ticks-days', 'ticks-hours', 'ticks-minutes', 'ticks-seconds'];

    containers.forEach(id => {
        const container = document.getElementById(id);
        container.innerHTML = '';
    });

    ticksDays = createTicks('ticks-days');
    ticksHours = createTicks('ticks-hours');
    ticksMinutes = createTicks('ticks-minutes');
    ticksSeconds = createTicks('ticks-seconds');
}
initTicks();
window.addEventListener('resize', () => {
    initTicks();
});



function createTicks(containerId) {
    const container = document.getElementById(containerId);
    const tickElements = [];
    const translateY = window.innerWidth < 768 ? 35 : -20;
    for (let i = 0; i < 60; i++) {
        const tick = document.createElement('span');
        tick.style.transform = `rotate(${i * 6}deg) translate(-50%, ${translateY}px)`;
        container.appendChild(tick);
        tickElements.push(tick);
    }
    return tickElements;
}


function updateTicks(tickElements, value) {
    for (let i = 0; i < 60; i++) {
        let opacity = 0.52;

        if (i > value) {
            const diff = i - value;
            if (diff === 1) opacity = 0.42;
            else if (diff === 2) opacity = 0.32;
            else if (diff === 3) opacity = 0.22;
            else if (diff === 4) opacity = 0.12;
            else if (diff === 5) opacity = 0.02;
            else opacity = 0;
        }

        tickElements[i].style.opacity = opacity;
    }
}

function createCircleSvg(parent, id) {
    const NS = "http://www.w3.org/2000/svg";
    const svg = document.createElementNS(NS, "svg");
    svg.setAttribute("viewBox", "0 0 200 200");

    const defs = document.createElementNS(NS, "defs");
    const gradient = document.createElementNS(NS, "linearGradient");
    gradient.setAttribute("id", `gradient-${id}`);
    gradient.setAttribute("x1", "0%");
    gradient.setAttribute("y1", "0%");
    gradient.setAttribute("x2", "26%");
    gradient.setAttribute("y2", "97%");
    const stops = [{
            offset: "39.13%",
            color: "#FFFF00"
        },
        {
            offset: "42.74%",
            color: "#FCD908"
        },
        {
            offset: "46.11%",
            color: "#F9BE0E"
        },
        {
            offset: "49.06%",
            color: "#F8AD12"
        },
        {
            offset: "51.29%",
            color: "#F7A713"
        },
        {
            offset: "54.85%",
            color: "#F7A514"
        },
        {
            offset: "56.72%",
            color: "#F69D15"
        },
        {
            offset: "58.19%",
            color: "#F58F18"
        },
        {
            offset: "59.45%",
            color: "#F47C1C"
        },
        {
            offset: "60.57%",
            color: "#F26422"
        },
        {
            offset: "60.94%",
            color: "#F15A24"
        }
    ];
    stops.forEach(s => {
        const stop = document.createElementNS(NS, "stop");
        stop.setAttribute("offset", s.offset);
        stop.setAttribute("stop-color", s.color);
        gradient.appendChild(stop);
    });


    defs.appendChild(gradient);
    svg.appendChild(defs);
    const bg = document.createElementNS(NS, "circle");
    bg.setAttribute("cx", "100");
    bg.setAttribute("cy", "100");
    bg.setAttribute("r", "90");
    bg.setAttribute("stroke", "white");
    bg.setAttribute("stroke-width", "2");
    bg.setAttribute("fill", "none");
    svg.appendChild(bg);

    const progress = document.createElementNS(NS, "circle");
    progress.setAttribute("cx", "100");
    progress.setAttribute("cy", "100");
    progress.setAttribute("r", "90");
    progress.setAttribute("stroke", `url(#gradient-${id})`);
    progress.setAttribute("stroke-width", "2");
    progress.setAttribute("stroke-linecap", "round");
    progress.setAttribute("fill", "none");
    progress.setAttribute("stroke-dasharray", "565.48");
    progress.setAttribute("stroke-dashoffset", "565.48");
    progress.setAttribute("transform", "rotate(-90 100 100)");
    svg.appendChild(progress);

    parent.appendChild(svg);

    return progress;
}

function updateDotPosition(dotImg, percent) {
    const angle = (percent / 100) * 360 - 90;
    const radians = angle * (Math.PI / 180);
    const r = 90;
    const cx = 100 + r * Math.cos(radians);
    const cy = 100 + r * Math.sin(radians);
    dotImg.style.left = `${cx / 200 * 100}%`;
    dotImg.style.top = `${cy / 200 * 100}%`;
}
const progressCircles = {
    days: createCircleSvg(document.querySelector("#days .circle-svg"), "days"),
    hours: createCircleSvg(document.querySelector("#hours .circle-svg"), "hours"),
    minutes: createCircleSvg(document.querySelector("#minutes .circle-svg"), "minutes"),
    seconds: createCircleSvg(document.querySelector("#seconds .circle-svg"), "seconds")
};

function updateTimer() {
    const now = new Date();
    const diff = endDate - now;
    if (diff <= 0) {
        document.querySelectorAll('.number').forEach(el => el.textContent = '00');
        return;
    }
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    const minutes = Math.floor((diff / (1000 * 60)) % 60);
    const seconds = Math.floor((diff / 1000) % 60);
    document.getElementById('days-number').textContent = String(days).padStart(2, '0');
    document.getElementById('hours-number').textContent = String(hours).padStart(2, '0');
    document.getElementById('minutes-number').textContent = String(minutes).padStart(2, '0');
    document.getElementById('seconds-number').textContent = String(seconds).padStart(2, '0');
    updateTicks(ticksDays, days);
    updateTicks(ticksHours, hours);
    updateTicks(ticksMinutes, minutes);
    updateTicks(ticksSeconds, seconds);

    function setProgress(circle, percent) {
        const circumference = 2 * Math.PI * 90;
        const offset = circumference - (percent / 100) * circumference;
        circle.setAttribute("stroke-dashoffset", offset);
    }
    setProgress(progressCircles.days, days > 60 ? 0 : (days / 60) * 100);
    setProgress(progressCircles.hours, (hours / 60) * 100);
    setProgress(progressCircles.minutes, (minutes / 60) * 100);
    setProgress(progressCircles.seconds, (seconds / 60) * 100);
    updateDotPosition(document.querySelector("#days .dot-image"), (days / 60) * 100);
    updateDotPosition(document.querySelector("#hours .dot-image"), (hours / 60) * 100);
    updateDotPosition(document.querySelector("#minutes .dot-image"), (minutes / 60) * 100);
    updateDotPosition(document.querySelector("#seconds .dot-image"), (seconds / 60) * 100);

}

setInterval(updateTimer, 1000);
updateTimer();




document.addEventListener('DOMContentLoaded', function() {
    const playButtons = document.querySelectorAll('.play-btn-open');
    const popup = document.querySelector('.popupVideo');
    const popupInner = document.querySelector('.popupVideo__inner');
    const closeBtn = document.querySelector('.popupVideo__close');
    const videoElement = popup.querySelector('video');
    const videoSource = videoElement.querySelector('source');
    const popupPlayBtn = popup.querySelector('.popupVideo__play');
    playButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const videoUrl = this.dataset.video;
            videoSource.src = videoUrl;
            videoElement.load();
            popup.classList.add('active');
            popupPlayBtn.style.display = 'inline-block';
        });
    });
    popupPlayBtn.addEventListener('click', function() {
        videoElement.setAttribute('controls', 'controls');
        videoElement.play();
        popupPlayBtn.style.display = 'none';
    });
    closeBtn.addEventListener('click', function() {
        closePopup();
    });
    popup.addEventListener('click', function(e) {
        if (!popupInner.contains(e.target)) {
            closePopup();
        }
    });

    function closePopup() {
        videoElement.pause();
        videoElement.removeAttribute('controls');
        popup.classList.remove('active');
        setTimeout(() => {
            videoSource.src = '';
            videoElement.load();
            popupPlayBtn.style.display = 'inline-block';
        }, 200);
    }
});

let productSwiper = new Swiper(".product__slider", {
    loop: true,
    spaceBetween: 20,
    slidesPerView: 3,
    centeredSlides: true,
    roundLengths: true,
    navigation: {
        nextEl: ".product__slider-next",
        prevEl: ".product__slider-prev",
    },
    pagination: {
        el: ".product__slider-pagination",
        clickable: true,
    },
});

document.addEventListener('DOMContentLoaded', function() {
    const infoBlockMoreItems = document.querySelectorAll('.infoBlock__more-item');

    infoBlockMoreItems.forEach(item => {
        const title = item.querySelector('.infoBlock__more-title');
        title.addEventListener('click', function() {
            if (item.classList.contains('active')) {
                item.classList.remove('active');
            } else {
                infoBlockMoreItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            }
        });
    });

    const infoBlockSelectButtons = document.querySelectorAll('.infoBlock__select-btn');
    const infoBlockSelectResults = document.querySelectorAll('.infoBlock__result-content');

    infoBlockSelectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = button.dataset.infoblockBtn;
            infoBlockSelectButtons.forEach(btn => btn.classList.remove('active'));
            infoBlockSelectResults.forEach(res => res.classList.remove('active'));
            button.classList.add('active');
            const targetResult = document.querySelector(`.infoBlock__result-content[data-infoBlock-result="${target}"]`);
            if (targetResult) {
                targetResult.classList.add('active');
            }
        });
    });


    const faqItems = document.querySelectorAll('.faq__item');
    faqItems.forEach(item => {
        const title = item.querySelector('.faq__item-title');
        title.addEventListener('click', function() {
            if (item.classList.contains('active')) {
                item.classList.remove('active');
            } else {
                faqItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            }
        });
    });
});



async function initMap() {
    const map = new google.maps.Map(document.getElementById("map"), {
        center: {
            lat: 50.4436, // вул. Якова Гніздовського, 15 (приблизно; уточнити при підключенні API)
            lng: 30.6613
        },
        zoom: 14
    });
    new google.maps.Marker({
        position: {
            lat: 50.4436,
            lng: 30.6613
        },
        map: map,
        icon: {
            url: "../img/point.svg",
            scaledSize: new google.maps.Size(80, 96)
        }
    });
}
window.initMap = initMap;



document.addEventListener('DOMContentLoaded', function() {
    const thumbSwiper = new Swiper('.popupPhoto__bottom', {
        spaceBetween: 24,
        slidesPerView: 'auto',
        watchSlidesProgress: true,
    });

    const mainSwiper = new Swiper('.popupPhoto__content-image', {
        spaceBetween: 24,
        slidesPerView: 1,
        navigation: {
            nextEl: '.popupPhoto__content-next',
            prevEl: '.popupPhoto__content-prev',
        },
        thumbs: {
            swiper: thumbSwiper,
        },
    });

    const imagesBlockItems = document.querySelectorAll('.imagesBlock__item');
    const popupPhoto = document.querySelector('.popupPhoto');
    const popupPhotoInner = document.querySelector('.popupPhoto__inner');
    const popupPhotoClose = document.querySelector('.popupPhoto__close');

    imagesBlockItems.forEach(function(item) {
        item.addEventListener('click', function() {
            popupPhoto.classList.add('active');
        });
    });
    popupPhotoClose.addEventListener('click', function() {
        popupPhoto.classList.remove('active');
    });
    popupPhoto.addEventListener('click', function(e) {
        if (!popupPhotoInner.contains(e.target)) {
            popupPhoto.classList.remove('active');
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    const reviewsSwiper = new Swiper('.reviews__slider', {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: false,
        on: {
            init: function(swiper) {
                updateCounter(swiper);
            },
            slideChange: function(swiper) {
                updateCounter(swiper);
            },
        },
    });

    function updateCounter(swiper) {
        const current = swiper.realIndex + 1;
        const total = swiper.slides.length;
        const slidesCounter = document.querySelector('.reviews__slides');
        if (slidesCounter) {
            slidesCounter.querySelector('span').textContent = current.toString().padStart(2, '0');
            slidesCounter.innerHTML = `<span>${current.toString().padStart(2, '0')}</span>/${total.toString().padStart(2, '0')}`;
        }
    }
});




document.addEventListener("DOMContentLoaded", function() {
    const cartSection = document.querySelector('.cart');
    const cartThanks = cartSection.querySelector('.cart__thanks');
    const cartInner = cartSection.querySelector('.cart__inner');
    const cartOpenButtons = document.querySelectorAll('.cart-open');
    const cartCloseButtons = cartSection.querySelectorAll('.cart-close-btn, .cart__closed');
    const cartThanksBtn = cartSection.querySelector('.cart__thanks-btn');
    const nextButtons = cartSection.querySelectorAll('.cart__next');
    const backButtons = cartSection.querySelectorAll('.cart__back');

    const contents = Array.from(cartSection.querySelectorAll('.cart-content'));
    const bottoms = Array.from(cartSection.querySelectorAll('.cart-bottom'));
    cartOpenButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            cartSection.classList.add('active');
        });
    });
    cartCloseButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            cartSection.classList.remove('active');
            setTimeout(() => {
                resetCart();
            }, 200);
        });
    });
    document.addEventListener('click', (e) => {
        if (
            cartSection.classList.contains('active') &&
            e.target.isConnected &&
            !e.target.closest('.cart__inner') &&
            !e.target.closest('.cart__thanks') &&
            !e.target.closest('.cart-open')
        ) {
            cartSection.classList.remove('active');
            setTimeout(() => {
                resetCart();
            }, 200);
        }
    });
    nextButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const currentBottom = btn.closest('.cart-bottom');
            let idx = bottoms.findIndex(b => b === currentBottom);

            if (idx === -1) return;
            if (idx === bottoms.length - 1) {
                cartInner.classList.add('hide-block');
                cartThanks.classList.remove('hide-block');
                return;
            }
            contents[idx].classList.add('hide-block');
            bottoms[idx].classList.add('hide-block');
            contents[idx + 1].classList.remove('hide-block');
            bottoms[idx + 1].classList.remove('hide-block');
        });
    });
    backButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const currentBottom = btn.closest('.cart-bottom');
            let idx = bottoms.findIndex(b => b === currentBottom);

            if (idx === -1) return;
            contents[idx].classList.add('hide-block');
            bottoms[idx].classList.add('hide-block');
            contents[idx - 1].classList.remove('hide-block');
            bottoms[idx - 1].classList.remove('hide-block');
        });
    });
    if (cartThanksBtn) {
        cartThanksBtn.addEventListener('click', () => {
            cartSection.classList.remove('active');
            setTimeout(() => {
                resetCart();
            }, 200);
        });
    }

    function resetCart() {
        contents.forEach(c => c.classList.add('hide-block'));
        bottoms.forEach(b => b.classList.add('hide-block'));
        contents[0].classList.remove('hide-block');
        bottoms[0].classList.remove('hide-block');
        cartThanks.classList.add('hide-block');
        cartInner.classList.remove('hide-block');
    }
});


document.addEventListener("DOMContentLoaded", function() {

  const cartData = document.querySelector('.cart__data');
  const cartDataInputs = cartData.querySelectorAll('input, select');
  const cartDataNextBtn = cartData.nextElementSibling.querySelector('.cart__next');

  function validateCartData() {
    let valid = true;

    cartDataInputs.forEach(el => {
      if (el.tagName.toLowerCase() === 'select') {
        if (el.value === '') {
          valid = false;
        }
      } else {
        if (el.value.trim() === '') {
          valid = false;
        }
      }
    });

    if (valid) {
      cartDataNextBtn.removeAttribute('disabled');
      cartDataNextBtn.classList.remove('btn-disabled');
    } else {
      cartDataNextBtn.setAttribute('disabled', 'disabled');
      cartDataNextBtn.classList.add('btn-disabled');
    }
  }

  cartDataInputs.forEach(el => {
    el.addEventListener('input', validateCartData);
    el.addEventListener('change', validateCartData);
  });

  validateCartData();

  const cartOrder = document.querySelector('.cart__order');
  const cartOrderInputs = cartOrder.querySelectorAll('input');
  const cartOrderNextBtn = cartOrder.nextElementSibling.querySelector('.cart__next');

  function validateCartOrder() {
    let valid = true;

    cartOrderInputs.forEach(input => {
      if (input.value.trim() === '') {
        valid = false;
      }
    });

    if (valid) {
      cartOrderNextBtn.removeAttribute('disabled');
      cartOrderNextBtn.classList.remove('btn-disabled');
    } else {
      cartOrderNextBtn.setAttribute('disabled', 'disabled');
      cartOrderNextBtn.classList.add('btn-disabled');
    }
  }

  cartOrderInputs.forEach(input => {
    input.addEventListener('input', validateCartOrder);
  });

  validateCartOrder();
});



document.addEventListener("DOMContentLoaded", function() {
    const deliverySelect = document.querySelector('select[name="delivery"]');
    const deliveryIcons = document.querySelectorAll('.cart__data-deivery--icon img');

    deliverySelect.addEventListener('change', () => {
        const selected = deliverySelect.value;
        deliveryIcons.forEach(img => img.classList.remove('active'));
        if (selected === 'np') {
            deliveryIcons[0].classList.add('active');
        } else if (selected === 'Ukrposhta') {
            deliveryIcons[1].classList.add('active');
        } else if (selected === 'Meest') {
            deliveryIcons[2].classList.add('active');
        }
    });
});

/* ===== Анкорна навігація: точний скрол з урахуванням реальної висоти хедера ===== */
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.header');
    const HEADER_GAP = 12; // невеликий повітряний зазор під хедером

    function scrollToSection(target) {
        const headerHeight = header ? header.offsetHeight : 0;
        const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - HEADER_GAP;
        window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
    }

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        const id = link.getAttribute('href').slice(1);
        if (!id) return;
        link.addEventListener('click', (e) => {
            const target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            headerMenu.classList.remove('active');
            scrollToSection(target);
            history.replaceState(null, '', '#' + id);
        });
    });

    // пряме відкриття з хешем (site.com/#delivery) — доводимо позицію після рендера
    if (location.hash) {
        const target = document.getElementById(location.hash.slice(1));
        if (target) {
            setTimeout(() => scrollToSection(target), 100);
        }
    }
});


/* ===== Мультимовність: перемикач UA/RU/EN + рядки з strings.json ===== */
document.addEventListener('DOMContentLoaded', function() {
    const LANGS = ['UA', 'RU', 'EN'];
    const HTML_LANG = { UA: 'uk', RU: 'ru', EN: 'en' };
    const langBlocks = document.querySelectorAll('.header__lang');
    let STRINGS = null;
    let currentLang = localStorage.getItem('hydrophob_lang') || 'UA';
    if (!LANGS.includes(currentLang)) currentLang = 'UA';

    // Глобальний доступ до рядків (використовує кошик та ін.)
    window.hydroLang = function() { return currentLang; };
    window.hydroT = function(key) {
        if (!STRINGS) return null;
        const dot = key.indexOf('.');
        const entry = STRINGS[key.slice(0, dot)] && STRINGS[key.slice(0, dot)][key.slice(dot + 1)];
        if (!entry) return null;
        return entry[currentLang] !== undefined ? entry[currentLang] : entry.UA;
    };

    function applyStrings() {
        if (!STRINGS) return;
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const val = window.hydroT(el.dataset.i18n);
            if (val !== null) el.textContent = val;
        });
        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            const val = window.hydroT(el.dataset.i18nHtml);
            if (val !== null) el.innerHTML = val;
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const val = window.hydroT(el.dataset.i18nPlaceholder);
            if (val !== null) el.setAttribute('placeholder', val);
        });
    }

    function applyLang(lang) {
        if (!LANGS.includes(lang)) lang = 'UA';
        currentLang = lang;
        document.documentElement.lang = HTML_LANG[lang];
        localStorage.setItem('hydrophob_lang', lang);
        langBlocks.forEach(block => {
            const selected = block.querySelector('.header__lang-selected');
            const content = block.querySelector('.header__lang-content');
            if (!selected || !content) return;
            selected.textContent = lang;
            content.innerHTML = '';
            LANGS.filter(l => l !== lang).forEach(l => {
                const a = document.createElement('a');
                a.href = '#';
                a.textContent = l;
                content.appendChild(a);
            });
            block.classList.remove('active');
        });
        applyStrings();
        window.dispatchEvent(new CustomEvent('hydro:lang', { detail: { lang: lang } }));
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('.header__lang-content a');
        if (!link) return;
        e.preventDefault();
        applyLang(link.textContent.trim());
    });

    applyLang(currentLang);
    fetch('strings.json')
        .then(r => r.json())
        .then(data => {
            STRINGS = data;
            applyLang(currentLang);
        })
        .catch(() => {});
});


/* ===== Анімації появи: друкування заголовків + вспливання (fade-in-up) ===== */
document.addEventListener('DOMContentLoaded', function() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Вспливання: перший екран та декоративний надпис каталогу
    const FADE_SELECTOR = '.hero__title, .product__name';
    const fadeEls = Array.from(document.querySelectorAll(FADE_SELECTOR));
    if (!reduceMotion && fadeEls.length) {
        fadeEls.forEach(el => el.classList.add('hydro-fade'));
        // повторюване вспливання: клас знімається, коли елемент виходить з в'юпорта
        const fadeObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                entry.target.classList.toggle('hydro-fade-visible', entry.isIntersecting);
            });
        }, { threshold: 0.1 });
        fadeEls.forEach(el => fadeObserver.observe(el));
    }

    // Друкування: заголовки секцій (без hero і без великого заголовка "гарантуємо") + пункти гарантій + заголовки вкладок infoBlock
    const SELECTOR = '.about__title, .action__title, .reviews__title, .infoBlock__title, ' +
        '.guarantee__item-title, .faq__title, .delivery__title, .contacts__title';
    const headings = Array.from(document.querySelectorAll(SELECTOR));
    if (!headings.length) return;
    if (reduceMotion) return;

    const CHAR_DELAY = 45;
    const states = new Map(); // el -> {timer, typed}

    function collectTextNodes(node, list) {
        node.childNodes.forEach(child => {
            if (child.nodeType === Node.TEXT_NODE) {
                if (child.nodeValue.trim() !== '') list.push(child);
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                collectTextNodes(child, list);
            }
        });
    }

    function cancelTyping(el) {
        const st = states.get(el);
        if (st && st.timer) clearTimeout(st.timer);
        // відновити повний текст перерваного сеансу, щоб не лишався обрізаний
        if (st && st.nodes) {
            st.nodes.forEach((n, i) => { n.nodeValue = st.texts[i]; });
        }
        el.classList.remove('is-typing');
        el.classList.remove('typing-no-caret');
        el.style.minHeight = '';
        states.set(el, { timer: null, typed: st ? st.typed : false, nodes: null, texts: null });
    }

    function typeElement(el) {
        cancelTyping(el);
        const textNodes = [];
        collectTextNodes(el, textNodes);
        const fullTexts = textNodes.map(n => n.nodeValue);
        // текст з переносами: фіксуємо висоту повного тексту, щоб верстка не стрибала під час друку
        el.style.visibility = 'visible';
        el.style.minHeight = el.offsetHeight + 'px';
        textNodes.forEach(n => { n.nodeValue = ''; });
        el.classList.add('is-typing');
        if (/flex|grid/.test(getComputedStyle(el).display)) {
            el.classList.add('typing-no-caret');
        }

        let nodeIdx = 0, charIdx = 0;
        function step() {
            if (nodeIdx >= textNodes.length) {
                el.classList.remove('is-typing');
                el.classList.remove('typing-no-caret');
                el.style.minHeight = '';
                states.set(el, { timer: null, typed: true, nodes: null, texts: null });
                return;
            }
            const full = fullTexts[nodeIdx];
            charIdx++;
            textNodes[nodeIdx].nodeValue = full.slice(0, charIdx);
            if (charIdx >= full.length) { nodeIdx++; charIdx = 0; }
            const timer = setTimeout(step, CHAR_DELAY);
            states.set(el, { timer: timer, typed: false, nodes: textNodes, texts: fullTexts });
        }
        step();
    }

    // ховаємо текст до першого показу, щоб не було "миготіння" повного тексту
    headings.forEach(el => { el.style.visibility = 'hidden'; states.set(el, { timer: null, typed: false }); });

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            const st = states.get(entry.target) || { timer: null, typed: false };
            if (entry.isIntersecting) {
                if (st.typed || st.timer) return;
                typeElement(entry.target);
            } else {
                // вийшов з в'юпорта — відновлюємо повний текст, наступного разу друкуємо знову
                cancelTyping(entry.target);
                states.set(entry.target, { timer: null, typed: false, nodes: null, texts: null });
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -5% 0px' });

    headings.forEach(el => observer.observe(el));

    // перемикання вкладок infoBlock: заголовок нової вкладки друкується заново
    document.querySelectorAll('.infoBlock__select-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const activeTitle = document.querySelector('.infoBlock__result-content.active .infoBlock__title');
            if (activeTitle) typeElement(activeTitle);
        });
    });

    // при зміні мови: передрукувати видимі заголовки, решту — знову в чергу
    window.addEventListener('hydro:lang', () => {
        headings.forEach(el => {
            cancelTyping(el);
            states.set(el, { timer: null, typed: false });
            const rect = el.getBoundingClientRect();
            const inView = rect.top < window.innerHeight && rect.bottom > 0;
            if (inView) {
                typeElement(el);
            } else {
                observer.observe(el);
            }
        });
    });
});


/* ===== Кошик: додавання товару, зміна кількості, видалення, суми ===== */
document.addEventListener('DOMContentLoaded', function() {
    const cartSection = document.querySelector('.cart');
    const productsBlock = cartSection.querySelector('.cart__products-block');
    const productsNumber = cartSection.querySelector('.cart__products-number');
    const totals = cartSection.querySelectorAll('.cart__products-bottom .cart__products-total p:last-child');
    const firstNextBtn = cartSection.querySelector('.cart-bottom .cart__next');
    const checkItems = cartSection.querySelectorAll('.cart__check-block:first-child .cart__check-descr');

    let PRODUCTS = [];
    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem('hydrophob_cart')) || [];
    } catch (e) { cart = []; }

    fetch('products.json')
        .then(r => r.json())
        .then(list => {
            PRODUCTS = list;
            window.HYDRO_PRODUCTS = list;
            cart = cart.filter(item => PRODUCTS.some(p => p.id === item.id));
            renderCart();
            window.dispatchEvent(new CustomEvent('hydro:products'));
        })
        .catch(() => { PRODUCTS = []; });

    function getProduct(id) {
        return PRODUCTS.find(p => p.id === id);
    }

    function t(key, fallback) {
        const val = window.hydroT ? window.hydroT(key) : null;
        return val !== null && val !== undefined ? val : fallback;
    }

    function pv(field) { // мультимовне поле товару: {UA,RU,EN} або рядок
        if (field === null || field === undefined) return '';
        if (typeof field !== 'object') return field;
        const lang = window.hydroLang ? window.hydroLang() : 'UA';
        return field[lang] !== undefined ? field[lang] : field.UA;
    }

    function saveCart() {
        localStorage.setItem('hydrophob_cart', JSON.stringify(cart));
    }

    function plural(n) {
        const forms = t('cart.countForms', ['товар', 'товари', 'товарів']);
        if ((window.hydroLang ? window.hydroLang() : 'UA') === 'EN') {
            return n === 1 ? forms[0] : forms[1];
        }
        const mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return forms[0];
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return forms[1];
        return forms[2];
    }

    function cartTotal() {
        return cart.reduce((sum, item) => {
            const p = getProduct(item.id);
            return p ? sum + p.price * item.qty : sum;
        }, 0);
    }

    function addToCart(id) {
        if (!getProduct(id)) return;
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty = Math.min(item.qty + 1, 100);
        } else {
            cart.push({ id: id, qty: 1 });
        }
        saveCart();
        renderCart();
    }

    function setQty(id, qty) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty = Math.min(Math.max(qty, 1), 100);
        saveCart();
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        saveCart();
        renderCart();
    }

    function renderCart() {
        productsBlock.innerHTML = '';
        cart.forEach(item => {
            const p = getProduct(item.id);
            if (!p) return;
            const el = document.createElement('div');
            el.className = 'cart__products-item';
            el.dataset.id = p.id;
            el.innerHTML =
                '<div class="cart__products-image"><img src="' + p.image + '" alt=""></div>' +
                '<div class="cart__products-content">' +
                    '<h3 class="cart__products-title">' + pv(p.title) + '</h3>' +
                    '<p class="cart__products-descr">' + pv(p.descr) + '</p>' +
                    '<p class="cart__products-volume">' + t('cart.volumeLabel', 'Об’єм') + ' <span>' + (p.volume || '—') + '</span></p>' +
                    '<div class="cart__products-info">' +
                        '<p class="cart__products-avaliable">' + (p.available ? t('cart.available', 'Є в наявності') : t('cart.notAvailable', 'Немає в наявності')) + '</p>' +
                        '<div class="cart__products-quantity">' +
                            '<button class="quantity-minus"></button>' +
                            '<input class="quantity-input" type="number" value="' + item.qty + '" min="1" max="100">' +
                            '<button class="quantity-plus"></button>' +
                        '</div>' +
                        '<p class="cart__products-price"><span>' + (p.price * item.qty) + '</span> ' + t('cart.uahShort', 'грн') + '</p>' +
                    '</div>' +
                '</div>' +
                '<button class="cart__products-delete">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">' +
                        '<path d="M17.0098 5.51758L12.1465 10.3789L17.0078 15.2402L15.2402 17.0088L10.3789 12.1465L5.51855 17.0078L3.75098 15.2402L8.61133 10.3789L3.75 5.51758L5.51758 3.75L10.3789 8.61133L15.2422 3.75L17.0098 5.51758Z" fill="#161616"/>' +
                    '</svg>' +
                '</button>';
            productsBlock.appendChild(el);
        });

        const count = cart.reduce((n, item) => n + item.qty, 0);
        productsNumber.textContent = count + ' ' + plural(count);

        const total = cartTotal();
        totals.forEach(el => { el.textContent = total + ' ' + t('cart.uahShort', 'грн'); });

        if (firstNextBtn) {
            if (cart.length === 0) {
                firstNextBtn.setAttribute('disabled', 'disabled');
                firstNextBtn.classList.add('btn-disabled');
            } else {
                firstNextBtn.removeAttribute('disabled');
                firstNextBtn.classList.remove('btn-disabled');
            }
        }

        if (checkItems.length >= 3) {
            checkItems[0].textContent = cart
                .map(item => { const p = getProduct(item.id); return p ? pv(p.title) : ''; })
                .filter(Boolean).join(', ') || '—';
            checkItems[1].textContent = count;
            checkItems[2].textContent = total + ' ' + t('cart.uahShort', 'грн');
        }
    }

    // Кнопки "Купити" з data-product-id додають товар (кошик відкриває наявний обробник .cart-open)
    document.addEventListener('click', function(e) {
        const buyBtn = e.target.closest('.cart-open[data-product-id]');
        if (buyBtn) {
            addToCart(buyBtn.dataset.productId);
        }
    });

    // Кількість і видалення (делегування по блоку товарів)
    productsBlock.addEventListener('click', function(e) {
        const itemEl = e.target.closest('.cart__products-item');
        if (!itemEl) return;
        const id = itemEl.dataset.id;
        const input = itemEl.querySelector('.quantity-input');
        if (e.target.closest('.quantity-plus')) {
            setQty(id, parseInt(input.value, 10) + 1);
        } else if (e.target.closest('.quantity-minus')) {
            setQty(id, parseInt(input.value, 10) - 1);
        } else if (e.target.closest('.cart__products-delete')) {
            removeFromCart(id);
        }
    });
    window.addEventListener('hydro:lang', renderCart);

    productsBlock.addEventListener('change', function(e) {
        if (!e.target.classList.contains('quantity-input')) return;
        const itemEl = e.target.closest('.cart__products-item');
        const qty = parseInt(e.target.value, 10);
        setQty(itemEl.dataset.id, isNaN(qty) ? 1 : qty);
    });
});


/* ===== Мультимовність товарних текстів (слайдер каталогу + infoBlock) ===== */
document.addEventListener('DOMContentLoaded', function() {
    function applyProductLang() {
        const products = window.HYDRO_PRODUCTS;
        if (!products) return;
        const lang = window.hydroLang ? window.hydroLang() : 'UA';
        const val = f => {
            if (f === null || f === undefined) return '';
            if (typeof f !== 'object') return f;
            return f[lang] !== undefined ? f[lang] : f.UA;
        };
        document.querySelectorAll('[data-p-id][data-p-part]').forEach(el => {
            const p = products.find(x => x.id === el.dataset.pId);
            if (!p) return;
            const part = el.dataset.pPart;
            if (part === 'title' && el.dataset.pBlock === undefined) {
                el.textContent = val(p.title);
            } else if (part === 'descr') {
                el.textContent = val(p.descr);
            } else if (part === 'subtitle') {
                el.textContent = val(p.details && p.details.subtitle);
            } else if (el.dataset.pBlock !== undefined) {
                const block = p.details && p.details.blocks && p.details.blocks[el.dataset.pBlock];
                if (!block) return;
                if (part === 'title') el.textContent = ' ' + val(block.title);
                if (part === 'html') el.innerHTML = val(block.html);
            }
        });
    }
    window.addEventListener('hydro:lang', applyProductLang);
    window.addEventListener('hydro:products', applyProductLang);
});


/* ===== Попап "Детальніше": опис товару з products.json ===== */
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.querySelector('.popupProduct');
    if (!popup) return;
    const inner = popup.querySelector('.popupProduct__inner');
    const closeBtn = popup.querySelector('.popupProduct__close');
    const img = popup.querySelector('.popupProduct__image');
    const title = popup.querySelector('.popupProduct__title');
    const volumeWrap = popup.querySelector('.popupProduct__volume');
    const volumeValue = popup.querySelector('.popupProduct__volume-value');
    const priceValue = popup.querySelector('.popupProduct__price-value');
    const buyBtn = popup.querySelector('.popupProduct__buy');
    const specsWrap = popup.querySelector('.popupProduct__specs');
    const specsList = popup.querySelector('.popupProduct__specs-list');
    const descrWrap = popup.querySelector('.popupProduct__descr');
    const descrContent = popup.querySelector('.popupProduct__descr-content');
    let currentId = null;

    function val(f) {
        if (f === null || f === undefined) return '';
        if (typeof f !== 'object') return f;
        const lang = window.hydroLang ? window.hydroLang() : 'UA';
        return f[lang] !== undefined ? f[lang] : f.UA;
    }

    function fillPopup(id) {
        const products = window.HYDRO_PRODUCTS || [];
        const p = products.find(x => x.id === id);
        if (!p) return false;
        currentId = id;
        img.src = p.image;
        img.alt = val(p.title);
        title.textContent = val(p.title);
        volumeValue.textContent = p.volume || '—';
        volumeWrap.style.display = p.volume ? '' : 'none';
        priceValue.textContent = p.price;
        buyBtn.dataset.productId = p.id;
        specsList.innerHTML = '';
        (p.attrs || []).forEach(a => {
            const li = document.createElement('li');
            const label = document.createElement('span');
            label.textContent = a.name + ': ';
            li.appendChild(label);
            li.appendChild(document.createTextNode(a.value));
            specsList.appendChild(li);
        });
        specsWrap.style.display = (p.attrs && p.attrs.length) ? '' : 'none';
        const html = val(p.descriptionHtml);
        descrContent.innerHTML = html || '';
        descrWrap.style.display = html ? '' : 'none';
        return true;
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('.product-more-open[data-product-id]');
        if (!link) return;
        e.preventDefault();
        if (fillPopup(link.dataset.productId)) {
            popup.classList.add('active');
            document.body.classList.add('no-scroll');
        }
    });

    function closePopup() {
        popup.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }
    closeBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', function(e) {
        if (!inner.contains(e.target)) closePopup();
    });
    // купівля з попапа: додається в кошик наявним обробником; попап закриваємо, щоб було видно кошик
    buyBtn.addEventListener('click', closePopup);
    // зміна мови при відкритому попапі
    window.addEventListener('hydro:lang', function() {
        if (popup.classList.contains('active') && currentId) fillPopup(currentId);
    });
});
