/* ===== Сторінка чекауту hydrophob.net.ua (checkout/hydro_checkout) =====
 * Кошик читається з того самого localStorage ('hydrophob_cart'), що й на головній.
 * Логіка адаптована з catalog/view/theme/default/javascript/hydrophob.js (крокова форма
 * попапу кошика), винесена в окрему сторінку: список товарів -> контактні дані + доставка
 * (НП/Meest/Укрпошта через api/shipping.php) -> перевірка -> сабміт у api/order.php.
 */
document.addEventListener('DOMContentLoaded', function () {
    var cfg = window.HYDRO_CHECKOUT || {};

    /* ---- Хедер: бургер-меню + перемикач мови (дублікат мінімальної логіки з hydrophob.js,
       щоб не тягнути на цю сторінку код, завʼязаний на елементи головної, яких тут нема). ---- */
    var headerBurger = document.querySelector('.header__burger');
    var headerMenu = document.querySelector('.header__menu');
    if (headerBurger && headerMenu) {
        headerBurger.addEventListener('click', function () {
            headerMenu.classList.toggle('active');
        });
    }
    document.querySelectorAll('.header__lang-selected').forEach(function (langSelected) {
        var langBlock = langSelected.closest('.header__lang');
        langSelected.addEventListener('click', function (e) {
            e.preventDefault();
            langBlock.classList.toggle('active');
        });
        document.addEventListener('click', function (e) {
            if (!langBlock.contains(e.target)) langBlock.classList.remove('active');
        });
    });

    var page = document.querySelector('.checkoutPage');
    if (!page) return;

    var productsBlock = page.querySelector('.cart__products-block');
    var productsNumber = page.querySelector('.cart__products-number');
    var totals = page.querySelectorAll('.cart__products-bottom .cart__products-total p:last-child');
    var contents = Array.from(page.querySelectorAll('.cart-content'));
    var bottoms = Array.from(page.querySelectorAll('.cart-bottom'));
    var nextButtons = page.querySelectorAll('.cart__next');
    var backButtons = page.querySelectorAll('.cart__back');

    var PRODUCTS = [];
    var cart = [];
    try {
        cart = JSON.parse(localStorage.getItem('hydrophob_cart')) || [];
    } catch (e) { cart = []; }

    // Порожній кошик на сторінці чекауту робити нічого — повертаємо на головну.
    if (!cart.length) {
        window.location.href = cfg.homeUrl || 'index.php';
        return;
    }

    function pv(field) {
        if (field === null || field === undefined) return '';
        if (typeof field !== 'object') return field;
        return field.UA !== undefined ? field.UA : field;
    }

    function cartTotal() {
        return cart.reduce(function (sum, item) {
            var p = getProduct(item.id);
            return p ? sum + p.price * item.qty : sum;
        }, 0);
    }

    function getProduct(id) {
        return PRODUCTS.find(function (p) { return p.id === id; });
    }

    function saveCart() {
        localStorage.setItem('hydrophob_cart', JSON.stringify(cart));
    }

    function plural(n) {
        var forms = ['товар', 'товари', 'товарів'];
        var mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return forms[0];
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return forms[1];
        return forms[2];
    }

    function renderCart() {
        productsBlock.innerHTML = '';
        cart.forEach(function (item) {
            var p = getProduct(item.id);
            if (!p) return;
            var el = document.createElement('div');
            el.className = 'cart__products-item';
            el.dataset.id = p.id;
            el.innerHTML =
                '<div class="cart__products-image"><img src="' + p.image + '" alt=""></div>' +
                '<div class="cart__products-content">' +
                    '<h3 class="cart__products-title">' + pv(p.title) + '</h3>' +
                    '<p class="cart__products-descr">' + pv(p.descr) + '</p>' +
                    '<p class="cart__products-volume">Об’єм <span>' + (p.volume || '—') + '</span></p>' +
                    '<div class="cart__products-info">' +
                        '<p class="cart__products-avaliable">' + (p.available ? 'Є в наявності' : 'Немає в наявності') + '</p>' +
                        '<div class="cart__products-quantity">' +
                            '<button class="quantity-minus" type="button"></button>' +
                            '<input class="quantity-input" type="number" value="' + item.qty + '" min="1" max="100">' +
                            '<button class="quantity-plus" type="button"></button>' +
                        '</div>' +
                        '<p class="cart__products-price"><span>' + (p.price * item.qty) + '</span> грн</p>' +
                    '</div>' +
                '</div>' +
                '<button class="cart__products-delete" type="button">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">' +
                        '<path d="M17.0098 5.51758L12.1465 10.3789L17.0078 15.2402L15.2402 17.0088L10.3789 12.1465L5.51855 17.0078L3.75098 15.2402L8.61133 10.3789L3.75 5.51758L5.51758 3.75L10.3789 8.61133L15.2422 3.75L17.0098 5.51758Z" fill="#161616"/>' +
                    '</svg>' +
                '</button>';
            productsBlock.appendChild(el);
        });

        var count = cart.reduce(function (n, item) { return n + item.qty; }, 0);
        productsNumber.textContent = count + ' ' + plural(count);

        // порожній кошик на цій сторінці більше нема сенсу тримати відкритим
        if (count === 0) {
            window.location.href = cfg.homeUrl || 'index.php';
            return;
        }

        var total = cartTotal();
        totals.forEach(function (el) { el.textContent = total + ' грн'; });

        if (checkItems1.length >= 3) {
            checkItems1[0].textContent = cart.map(function (item) {
                var p = getProduct(item.id);
                return p ? pv(p.title) : '';
            }).filter(Boolean).join(', ') || '—';
            checkItems1[1].textContent = count;
            checkItems1[2].textContent = total + ' грн';
        }
    }

    fetch(cfg.productsUrl || 'index.php?route=extension/module/catalog_api/products')
        .then(function (r) { return r.json(); })
        .then(function (list) {
            PRODUCTS = list;
            cart = cart.filter(function (item) { return PRODUCTS.some(function (p) { return p.id === item.id; }); });
            if (!cart.length) { window.location.href = cfg.homeUrl || 'index.php'; return; }
            renderCart();
        })
        .catch(function () { PRODUCTS = []; });

    productsBlock.addEventListener('click', function (e) {
        var itemEl = e.target.closest('.cart__products-item');
        if (!itemEl) return;
        var id = itemEl.dataset.id;
        var input = itemEl.querySelector('.quantity-input');
        var item = cart.find(function (i) { return i.id === id; });
        if (!item) return;
        if (e.target.closest('.quantity-plus')) {
            item.qty = Math.min(item.qty + 1, 100);
        } else if (e.target.closest('.quantity-minus')) {
            item.qty = Math.max(item.qty - 1, 1);
        } else if (e.target.closest('.cart__products-delete')) {
            cart = cart.filter(function (i) { return i.id !== id; });
        }
        saveCart();
        renderCart();
    });
    productsBlock.addEventListener('change', function (e) {
        if (!e.target.classList.contains('quantity-input')) return;
        var itemEl = e.target.closest('.cart__products-item');
        var item = cart.find(function (i) { return i.id === itemEl.dataset.id; });
        if (!item) return;
        var qty = parseInt(e.target.value, 10);
        item.qty = Math.min(Math.max(isNaN(qty) ? 1 : qty, 1), 100);
        saveCart();
        renderCart();
    });

    /* ---- Крокова навігація (продукти -> дані -> перевірка) ---- */
    nextButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var currentBottom = btn.closest('.cart-bottom');
            var idx = bottoms.findIndex(function (b) { return b === currentBottom; });
            if (idx === -1) return;
            if (idx === bottoms.length - 1) {
                submitOrder(btn);
                return;
            }
            if (idx === 1) fillCheck(); // переходимо на крок "Перевірка"
            contents[idx].classList.add('hide-block');
            bottoms[idx].classList.add('hide-block');
            contents[idx + 1].classList.remove('hide-block');
            bottoms[idx + 1].classList.remove('hide-block');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
    backButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var currentBottom = btn.closest('.cart-bottom');
            var idx = bottoms.findIndex(function (b) { return b === currentBottom; });
            if (idx === -1) return;
            contents[idx].classList.add('hide-block');
            bottoms[idx].classList.add('hide-block');
            contents[idx - 1].classList.remove('hide-block');
            bottoms[idx - 1].classList.remove('hide-block');
        });
    });

    /* ---- Валідація кроку "Контактні дані" ---- */
    var cartData = page.querySelector('.cart__data');
    var cartDataInputs = cartData.querySelectorAll('input, select');
    var cartDataNextBtn = cartData.nextElementSibling.querySelector('.cart__next');
    function validateCartData() {
        var valid = true;
        cartDataInputs.forEach(function (el) {
            if (el.tagName.toLowerCase() === 'select') {
                if (el.value === '') valid = false;
            } else if (el.name !== 'tel-full' && el.value.trim() === '') {
                valid = false;
            }
        });
        var telFull = cartData.querySelector('input[name="tel-full"]');
        if (telFull && telFull.value.trim() === '') valid = false;
        cartDataNextBtn.disabled = !valid;
        cartDataNextBtn.classList.toggle('btn-disabled', !valid);
    }
    cartDataInputs.forEach(function (el) {
        el.addEventListener('input', validateCartData);
        el.addEventListener('change', validateCartData);
    });
    validateCartData();

    /* ---- Іконки перевізників ---- */
    var deliverySelect = page.querySelector('select[name="delivery"]');
    var deliveryIcons = page.querySelectorAll('.cart__data-deivery--icon img');
    function syncIcons() {
        deliveryIcons.forEach(function (img) {
            img.classList.toggle('active', img.dataset.carrier === deliverySelect.value);
        });
    }
    if (deliverySelect) {
        deliverySelect.addEventListener('change', syncIcons);
        syncIcons();
    }

    /* ---- Автокомпліт міста/відділення (Нова пошта, Meest, Укрпошта) ---- */
    var deliveryType = page.querySelector('select[name="delivery-type"]');
    var cityInput = page.querySelector('input[name="delivery-city"]');
    var branchInput = page.querySelector('input[name="delivery-branch"]');
    var state = { cityRef: '', cityName: '', branchName: '' };
    var manualProviders = {};

    function isCourier() { return deliveryType && deliveryType.value === 'courier'; }
    function provider() { return deliverySelect ? deliverySelect.value : ''; }
    function listOf(input) { return input.closest('.cart__suggest').querySelector('.cart__suggest-list'); }
    function hideList(input) { listOf(input).classList.remove('active'); }
    function showList(input, items, onPick) {
        var ul = listOf(input);
        ul.innerHTML = '';
        if (!items.length) { ul.classList.remove('active'); return; }
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.textContent = item.name;
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                onPick(item);
                ul.classList.remove('active');
            });
            ul.appendChild(li);
        });
        ul.classList.add('active');
    }

    var cityTimer = null, branchTimer = null;
    function fetchItems(params, cb) {
        params.provider = provider();
        fetch((cfg.shippingUrl || 'api/shipping.php') + '?' + new URLSearchParams(params))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.manual) manualProviders[params.provider] = true;
                cb(d && d.ok ? (d.items || []) : []);
            })
            .catch(function () { cb([]); });
    }

    if (cityInput && branchInput && deliverySelect) {
        cityInput.addEventListener('input', function () {
            state.cityRef = ''; state.cityName = '';
            if (manualProviders[provider()]) return;
            clearTimeout(cityTimer);
            var q = cityInput.value.trim();
            if (q.length < 2) { hideList(cityInput); return; }
            cityTimer = setTimeout(function () {
                fetchItems({ action: 'cities', search: q }, function (items) {
                    showList(cityInput, items, function (picked) {
                        cityInput.value = picked.name;
                        state.cityRef = picked.ref;
                        state.cityName = picked.name;
                        branchInput.value = '';
                        cityInput.dispatchEvent(new Event('change'));
                        branchInput.focus();
                    });
                });
            }, 300);
        });

        function loadBranches() {
            if (!state.cityRef || manualProviders[provider()] || isCourier()) return;
            clearTimeout(branchTimer);
            branchTimer = setTimeout(function () {
                fetchItems({ action: 'warehouses', city_ref: state.cityRef, search: branchInput.value.trim() }, function (items) {
                    showList(branchInput, items, function (picked) {
                        branchInput.value = picked.name;
                        state.branchName = picked.name;
                        branchInput.dispatchEvent(new Event('change'));
                    });
                });
            }, 250);
        }
        branchInput.addEventListener('input', loadBranches);
        branchInput.addEventListener('focus', loadBranches);
        [cityInput, branchInput].forEach(function (inp) {
            inp.addEventListener('blur', function () { setTimeout(function () { hideList(inp); }, 150); });
        });

        function applyDeliveryMode() {
            var placeholder = isCourier() ? 'Адреса: вулиця, будинок, квартира'
                : (manualProviders[provider()] ? 'Відділення або індекс' : 'Відділення');
            branchInput.setAttribute('placeholder', placeholder);
            hideList(cityInput); hideList(branchInput);
        }
        deliverySelect.addEventListener('change', applyDeliveryMode);
        if (deliveryType) deliveryType.addEventListener('change', applyDeliveryMode);

        deliverySelect.addEventListener('change', function () {
            cityInput.value = ''; branchInput.value = '';
            state.cityRef = ''; state.cityName = ''; state.branchName = '';
            hideList(cityInput); hideList(branchInput);
        });
        if (deliveryType) deliveryType.addEventListener('change', function () {
            branchInput.value = ''; state.branchName = '';
            hideList(branchInput);
        });
    }

    /* ---- Крок "Перевірка даних" ---- */
    var checkBlocks = page.querySelectorAll('.cart__check-block');
    var checkItems1 = checkBlocks.length ? checkBlocks[0].querySelectorAll('.cart__check-descr') : [];
    function fillCheck() {
        if (checkBlocks.length < 3) return;
        var nameInput = page.querySelector('input[name="name"]');
        var telInput = page.querySelector('input[name="tel"]');
        var telFullInput = page.querySelector('input[name="tel-full"]');
        var emailInput = page.querySelector('input[name="email"]');
        var b2 = checkBlocks[1].querySelectorAll('.cart__check-descr');
        if (b2.length >= 3) {
            b2[0].textContent = nameInput.value || '—';
            b2[1].textContent = (telFullInput && telFullInput.value) || telInput.value || '—';
            b2[2].textContent = emailInput.value || '—';
        }
        var b3 = checkBlocks[2].querySelectorAll('.cart__check-descr');
        if (b3.length >= 2 && deliverySelect) {
            b3[0].textContent = deliverySelect.options[deliverySelect.selectedIndex].text + (isCourier() ? ' (кур’єр)' : ' (відділення)');
            b3[1].textContent = (cityInput.value ? cityInput.value + ', ' : '') + (branchInput.value || '—');
        }
    }

    /* ---- Сабміт замовлення ---- */
    function submitOrder(btn) {
        if (!cart.length) return;
        var telFullInput = page.querySelector('input[name="tel-full"]');
        var telInput = page.querySelector('input[name="tel"]');
        var payload = {
            items: cart,
            contact: {
                name: page.querySelector('input[name="name"]').value.trim(),
                phone: ((telFullInput && telFullInput.value) || telInput.value).trim(),
                email: page.querySelector('input[name="email"]').value.trim(),
            },
            delivery: {
                method: deliverySelect ? deliverySelect.value : '',
                type: deliveryType ? deliveryType.value : 'branch',
                city: cityInput ? cityInput.value.trim() : '',
                branch: branchInput ? branchInput.value.trim() : '',
            },
            payment: (page.querySelector('input[name="payment_method"]:checked') || { value: 'cod' }).value,
        };
        btn.setAttribute('disabled', 'disabled');
        btn.classList.add('btn-disabled');
        fetch(cfg.orderUrl || 'api/order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.ok && d.pay && d.pay.url) {
                    // WayForPay: збираємо підписану форму і відправляємо покупця на сторінку оплати
                    localStorage.removeItem('hydrophob_cart');
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = d.pay.url;
                    form.acceptCharset = 'utf-8';
                    Object.keys(d.pay.fields).forEach(function (key) {
                        var val = d.pay.fields[key];
                        (Array.isArray(val) ? val : [val]).forEach(function (v) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = Array.isArray(val) ? key + '[]' : key;
                            inp.value = v;
                            form.appendChild(inp);
                        });
                    });
                    document.body.appendChild(form);
                    form.submit();
                } else if (d && d.ok && d.token) {
                    localStorage.removeItem('hydrophob_cart');
                    window.location.href = (cfg.successUrl || 'index.php?route=checkout/hydro_success') + '&token=' + d.token;
                } else {
                    throw new Error((d && d.error) || 'fail');
                }
            })
            .catch(function () {
                alert('Не вдалося оформити замовлення. Спробуйте ще раз або зателефонуйте нам.');
                btn.removeAttribute('disabled');
                btn.classList.remove('btn-disabled');
            });
    }

    /* ---- Телефон з кодом країни (той самий патерн, що й на головній) ---- */
    (function () {
        var countrySelect = page.querySelector('.cart__phone-country');
        var phoneInput = page.querySelector('.cart__phone input[name="tel"]');
        var phoneFull = page.querySelector('.cart__phone input[name="tel-full"]');
        if (!countrySelect || !phoneInput || !phoneFull) return;

        var CTRY = [["UA","🇺🇦",380,9,9],["PL","🇵🇱",48,9,9],["DE","🇩🇪",49,10,11],["GB","🇬🇧",44,10,10],["US","🇺🇸",1,10,10],["CA","🇨🇦",1,10,10],["CZ","🇨🇿",420,9,9],["SK","🇸🇰",421,9,9],["FR","🇫🇷",33,9,9],["IT","🇮🇹",39,9,10],["ES","🇪🇸",34,9,9],["NL","🇳🇱",31,9,9],["AT","🇦🇹",43,10,11],["CH","🇨🇭",41,9,9],["SE","🇸🇪",46,9,9],["NO","🇳🇴",47,8,8],["DK","🇩🇰",45,8,8],["FI","🇫🇮",358,9,10],["LT","🇱🇹",370,8,8],["LV","🇱🇻",371,8,8],["EE","🇪🇪",372,7,8],["MD","🇲🇩",373,8,8],["RO","🇷🇴",40,9,9],["BG","🇧🇬",359,8,9],["TR","🇹🇷",90,10,10],["GE","🇬🇪",995,9,9],["IL","🇮🇱",972,9,9],["AE","🇦🇪",971,9,9],["KZ","🇰🇿",7,10,10],["AZ","🇦🇿",994,9,9]];

        countrySelect.innerHTML = CTRY.map(function (c) { return '<option value="' + c[0] + '">' + c[1] + ' +' + c[2] + '</option>'; }).join('');
        var def = (countrySelect.dataset.defaultCountry || 'UA').toUpperCase();
        countrySelect.value = CTRY.some(function (c) { return c[0] === def; }) ? def : 'UA';

        function cur() { return CTRY.find(function (c) { return c[0] === countrySelect.value; }) || CTRY[0]; }

        function format() {
            var c = cur();
            var d = phoneInput.value.replace(/\D/g, '');
            var code = String(c[2]);
            if (d.indexOf(code) === 0 && d.length > c[4]) d = d.slice(code.length);
            if (c[0] === 'UA' && d.charAt(0) === '0') d = d.slice(1);
            d = d.slice(0, c[4]);
            var groups = c[0] === 'UA' ? [2, 3, 2, 2] : [3, 3, 5];
            var out = '', pos = 0;
            for (var g = 0; g < groups.length && pos < d.length; g++) {
                if (out) out += ' ';
                out += d.slice(pos, pos + groups[g]);
                pos += groups[g];
            }
            phoneInput.value = out;
            var valid = d.length >= c[3] && d.length <= c[4];
            phoneFull.value = valid ? '+' + code + d : '';
            phoneInput.classList.toggle('phone-invalid', d.length > 0 && !valid);
            phoneFull.dispatchEvent(new Event('change'));
        }

        phoneInput.addEventListener('input', format);
        countrySelect.addEventListener('change', function () { format(); phoneInput.focus(); });
    })();
});
