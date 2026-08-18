<?php
/**
 * Головна сторінка hydrophob.net.ua у OpenCart 3.
 *
 * Контентні секції (hero, about, action, images_block, product, info_block,
 * reviews, guarantee, faq, delivery, contacts) винесені в окремі content_top
 * модулі — catalog/controller/extension/module/hydrophob_<section>.php,
 * підключені штатним механізмом Design → Layout (oc_layout_module,
 * position=content_top, route common/home) і рендеряться через
 * common/content_top.php (див. layout.sql).
 *
 * Тут лишаються тільки глобальні для сторінки partial-и, які НЕ модулі
 * layout'а: header, попапи (video/photo/product/about/delivery), cart
 * (попап кошика) і cookie-банер.
 */
class ControllerCommonHome extends Controller {
	public function index() {
		$seo = $this->readJson('data/seo.json');
		$env = $this->readEnv('.env');

		// Мова сторінки (URL-версії /, /en, /ru) — від неї метатеги, html lang і канонікал
		$langCodeMap = array(1 => 'EN', 2 => 'UA', 3 => 'RU');
		$lang = $langCodeMap[(int)$this->config->get('config_language_id')] ?? 'UA';
		$meta = ($seo['meta'][$lang] ?? null) ?: array('title' => 'Hydrophob', 'description' => '', 'keywords' => '');

		$this->document->setTitle($meta['title']);
		$this->document->setDescription($meta['description']);
		$this->document->setKeywords($meta['keywords']);

		// ---- Попап-галерея фото (popup_photo.twig) — ті самі кадри, що й imagesBlock ----
		$images = $this->fixImagePaths($this->readJson('data/images.json'));

		// Слайди попапу: спершу плитки imagesBlock (відео — з постером-кадром відео),
		// потім додаткові повнорозмірні кадри галереї, що не увійшли.
		$galleryItems = array();
		$usedImages = array();
		$legacyItems = $images['imagesBlock']['items'] ?? array();
		$itemsSetting = $this->config->get('module_hydrophob_images_block_items');
		$langId = (int)$this->config->get('config_language_id');

		if (is_array($itemsSetting) && $itemsSetting) {
			foreach (array_values($itemsSetting) as $i => $row) {
				$alt = (is_array($row['alt'] ?? null) && !empty($row['alt'][$langId])) ? $row['alt'][$langId] : ($legacyItems[$i]['alt'] ?? '');

				if (!empty($row['video'])) {
					$video = (strpos($row['video'], 'video/') === 0 || strpos($row['video'], 'image/') === 0) ? $row['video'] : 'image/' . $row['video'];
					$tile = !empty($row['tile']) ? 'image/' . $row['tile'] : ($legacyItems[$i]['tile'] ?? '');
					$poster = $this->videoPoster($video);
					$galleryItems[] = array('type' => 'video', 'src' => $video, 'poster' => $poster ?: $tile, 'alt' => $alt);
				} else {
					$full = $legacyItems[$i]['full'] ?? (!empty($row['tile']) ? 'image/' . $row['tile'] : '');
					if ($full) {
						$galleryItems[] = array('type' => 'image', 'src' => $full, 'poster' => '', 'alt' => $alt);
						$usedImages[] = $full;
					}
				}
			}
		}

		foreach ($legacyItems as $legacy) {
			$full = $legacy['full'] ?? '';
			if ($full && !in_array($full, $usedImages)) {
				$galleryItems[] = array('type' => 'image', 'src' => $full, 'poster' => '', 'alt' => $legacy['alt'] ?? '');
				$usedImages[] = $full;
			}
		}

		// ---- Кошик (cart.twig): перевізники + дефолтний код країни для телефону ----
		$deliveries = $this->readJson('data/deliveries.json');
		$carriers = array();
		foreach (($deliveries['carriers'] ?? array()) as $carrier) {
			$carriers[] = array(
				'id'   => $carrier['id'] ?? '',
				'icon' => 'image/hydrophob/' . ltrim(str_replace('img/', '', $carrier['icon'] ?? ''), '/'),
				'name' => $this->uaValue($carrier['name'] ?? ''),
			);
		}

		$shared = array(
			'images'        => $images,
			'photo_gallery' => $galleryItems,
			'carriers'      => $carriers,
			'env'           => $env,
			'checkout_url'  => '/checkout',
		);

		// Поточна мова сторінки (URL-версії /, /en, /ru)
		$hydroLang = $lang;
		$langPath = array('UA' => '', 'EN' => '/en', 'RU' => '/ru');
		$shared['hydro_lang'] = $hydroLang;

		$partials = array('header', 'popup_video', 'popup_photo', 'popup_product', 'popup_about', 'popup_category', 'popup_delivery', 'cart', 'footer', 'cookie');
		$sections = array();
		foreach ($partials as $section) {
			$sections[$section] = $this->load->view('common/home/' . $section, $shared);
		}

		// ---- Контентні секції content_top (hero...contacts) — штатний layout-механізм ----
		$sections['content_top'] = $this->load->controller('common/content_top');

		$org = $seo['org'] ?? array();
		$baseUrl = rtrim($seo['url'] ?? '', '/') . '/';

		$data = $sections;
		$data['html_lang'] = $seo['htmlLang'][$lang] ?? 'uk';
		$data['meta'] = $meta;

		$data['hydro_lang'] = $hydroLang;
		$data['canonical'] = rtrim($baseUrl, '/') . ($langPath[$hydroLang] ?: '/');
		if ($hydroLang !== 'UA') {
			$data['canonical'] = rtrim($baseUrl, '/') . $langPath[$hydroLang];
		}
		$data['og_locale'] = $seo['locales'][$lang] ?? 'uk_UA';
		$data['site_name'] = $seo['siteName'] ?? 'Hydrophob';
		$data['og_image'] = $baseUrl . ($seo['ogImage'] ?? 'img/og-image.jpg');
		$data['analytics'] = array(
			'mode'     => ($env['ANALYTICS_MODE'] ?? 'test') === 'production' ? 'production' : 'test',
			'ga4'      => $env['GA4_ID'] ?? '',
			'ads'      => $env['GOOGLE_ADS_ID'] ?? '',
			'adsLabel' => $env['GOOGLE_ADS_PURCHASE_LABEL'] ?? '',
		);
		$data['asset_version'] = (string)@filemtime(DIR_APPLICATION . '../catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		// Фавікон — з налаштувань (config_icon), фолбек — старий шлях
		$configIcon = (string)$this->config->get('config_icon');
		$data['favicon'] = $configIcon !== '' ? 'image/' . $configIcon : 'image/hydrophob/favicon.png';

		// Preload постера першого hero-слайда — це LCP-елемент (саме відео вантажиться після onload)
		$heroSlides = $this->config->get('module_hydrophob_hero_slides');
		$data['preload_poster'] = (is_array($heroSlides) && !empty($heroSlides[0]['poster'])) ? 'image/' . $heroSlides[0]['poster'] : '';

		// ---- Правки з адмін-модулів (data-i18n на сторінці) мають виграти в JS-перемикачі мов ----
		$data['schema_json'] = $this->buildSchemaJson($baseUrl);

		$data['hydro_strings_overrides_json'] = json_encode($this->buildStringsOverrides(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$data['hydro_contacts_json'] = json_encode(array(
			'lat'     => $this->config->get('module_hydrophob_contacts_lat'),
			'lng'     => $this->config->get('module_hydrophob_contacts_lng'),
			'address' => $this->localizedContactAddress(),
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	/**
	 * Значення полів, редагованих у content_top модулях, які мають data-i18n у розмітці —
	 * щоб клієнтський switcher (hydrophob.js, fetch data/strings.json) не перезаписував
	 * SSR-контент старими текстами з JSON після зміни адмінки.
	 * Формат: 'секція.ключ' => {UA:..., RU:..., EN:...} (як у data/strings.json).
	 */
	private function buildStringsOverrides() {
		$this->load->model('localisation/language');
		$langs = $this->model_localisation_language->getLanguages();
		$ukId = $langs['uk-ua']['language_id'] ?? 0;
		$ruId = $langs['ru-ru']['language_id'] ?? 0;
		$enId = $langs['en-gb']['language_id'] ?? 0;

		$ml3 = function($code, $field) use ($ukId, $ruId, $enId) {
			$v = $this->config->get($code . '_' . $field);
			if (!is_array($v)) {
				return null;
			}
			return array('UA' => $v[$ukId] ?? '', 'RU' => $v[$ruId] ?? '', 'EN' => $v[$enId] ?? '');
		};

		$overrides = array();

		// hero
		if (($v = $ml3('module_hydrophob_hero', 'title_html')) !== null) $overrides['hero.titleHtml'] = $v;

		// about
		foreach (array('name' => 'about.name', 'title' => 'about.title', 'descr' => 'about.descr', 'read_more' => 'about.readMore') as $field => $dotKey) {
			if (($v = $ml3('module_hydrophob_about', $field)) !== null) $overrides[$dotKey] = $v;
		}

		// action
		foreach (array('title', 'name', 'descr') as $field) {
			if (($v = $ml3('module_hydrophob_action', $field)) !== null) $overrides['action.' . $field] = $v;
		}

		// guarantee
		$gMap = array('title' => 'guarantee.title', 'item1_title' => 'guarantee.item1', 'item2_title' => 'guarantee.item2Html', 'item3_title' => 'guarantee.item3', 'item4_title' => 'guarantee.item4',
			'item1_message' => 'guarantee.message1', 'item2_message' => 'guarantee.message2', 'item3_message' => 'guarantee.message3', 'item4_message' => 'guarantee.message4');
		foreach ($gMap as $field => $dotKey) {
			if (($v = $ml3('module_hydrophob_guarantee', $field)) !== null) $overrides[$dotKey] = $v;
		}

		// faq
		if (($v = $ml3('module_hydrophob_faq', 'title_html')) !== null) $overrides['faq.titleHtml'] = $v;
		$faqItems = $this->config->get('module_hydrophob_faq_items');
		if (is_array($faqItems)) {
			$i = 0;
			foreach ($faqItems as $item) {
				$i++;
				if (isset($item['question']) && is_array($item['question'])) {
					$overrides['faq.q' . $i] = array('UA' => $item['question'][$ukId] ?? '', 'RU' => $item['question'][$ruId] ?? '', 'EN' => $item['question'][$enId] ?? '');
				}
				if (isset($item['answer']) && is_array($item['answer'])) {
					$overrides['faq.a' . $i] = array('UA' => $item['answer'][$ukId] ?? '', 'RU' => $item['answer'][$ruId] ?? '', 'EN' => $item['answer'][$enId] ?? '');
				}
			}
		}

		// delivery
		if (($v = $ml3('module_hydrophob_delivery', 'title_html')) !== null) $overrides['delivery.titleHtml'] = $v;
		if (($v = $ml3('module_hydrophob_delivery', 'descr')) !== null) $overrides['delivery.descr'] = $v;
		$carrierDotKeys = array('np' => 'delivery.np', 'ukrposhta' => 'delivery.ukr', 'meest' => 'delivery.meest', 'other' => 'delivery.other', 'pickup' => 'delivery.pickup', 'courier' => 'delivery.courier');
		$deliveryItems = $this->config->get('module_hydrophob_delivery_items');
		if (is_array($deliveryItems)) {
			foreach ($deliveryItems as $item) {
				$key = $item['key'] ?? '';
				$dotKey = $carrierDotKeys[$key] ?? null;
				$name = $item['name'] ?? null;
				if ($dotKey !== null && is_array($name)) {
					$overrides[$dotKey] = array('UA' => $name[$ukId] ?? '', 'RU' => $name[$ruId] ?? '', 'EN' => $name[$enId] ?? '');
				}
			}
		} else {
			// фолбек: старий формат, доки не насіяно module_hydrophob_delivery_items
			$carriers = $this->config->get('module_hydrophob_delivery_carriers');
			if (is_array($carriers)) {
				foreach ($carrierDotKeys as $key => $dotKey) {
					$name = $carriers[$key]['name'] ?? null;
					if (is_array($name)) {
						$overrides[$dotKey] = array('UA' => $name[$ukId] ?? '', 'RU' => $name[$ruId] ?? '', 'EN' => $name[$enId] ?? '');
					}
				}
			}
		}

		// contacts
		foreach (array('title' => 'contacts.title', 'address' => 'contacts.address', 'time' => 'contacts.time') as $field => $dotKey) {
			if (($v = $ml3('module_hydrophob_contacts', $field)) !== null) $overrides[$dotKey] = $v;
		}

		// reviews
		if (($v = $ml3('module_hydrophob_reviews', 'title_html')) !== null) $overrides['reviews.titleHtml'] = $v;
		$reviewItems = $this->config->get('module_hydrophob_reviews_items');
		if (is_array($reviewItems)) {
			$i = 0;
			foreach ($reviewItems as $item) {
				$i++;
				if (isset($item['message']) && is_array($item['message'])) {
					$overrides['reviews.message' . $i] = array('UA' => $item['message'][$ukId] ?? '', 'RU' => $item['message'][$ruId] ?? '', 'EN' => $item['message'][$enId] ?? '');
				}
			}
		}

		return $overrides;
	}

	/** Адреса контактів (для попапу мапи в JS) поточною SSR-мовою (UA, як і решта home.php). */
	private function localizedContactAddress() {
		$v = $this->config->get('module_hydrophob_contacts_address');
		if (is_array($v)) {
			$this->load->model('localisation/language');
			$langs = $this->model_localisation_language->getLanguages();
			$ukId = $langs['uk-ua']['language_id'] ?? 0;
			if (!empty($v[$ukId])) {
				return $v[$ukId];
			}
		}
		return 'Київ, вул. Якова Гніздовського, 15';
	}

	/** Мультимовне поле {UA,RU,EN} чи звичайний рядок -> UA (поточна SSR-мова). */
	private function uaValue($field) {
		if (is_array($field)) {
			return $field['UA'] ?? '';
		}
		return (string)$field;
	}

	/** img/... -> image/hydrophob/... рекурсивно (щоб не переписувати кожен шлях вручну в data/*.json). */
	private function fixImagePaths($value) {
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $this->fixImagePaths($item);
			}
			return $value;
		}
		if (is_string($value) && strpos($value, 'img/') === 0) {
			return 'image/hydrophob/' . substr($value, 4);
		}
		return $value;
	}

	private function readJson($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}

	/**
	 * Постер = кадр відео, схема імен та сама, що в адмінці (common/video_poster):
	 * catalog/video-posters/<імʼя>-<md5(relative)6>.webp; генерується один раз.
	 */
	private function videoPoster($video) {
		if (strpos($video, 'image/') === 0) {
			$relative = substr($video, 6);
		} elseif (strpos($video, 'video/') === 0) {
			$relative = 'catalog/' . $video;
		} else {
			$relative = $video;
		}

		$source = DIR_IMAGE . $relative;
		if (!is_file($source)) {
			return '';
		}

		$name = pathinfo($relative, PATHINFO_FILENAME) . '-' . substr(md5($relative), 0, 6) . '.webp';
		$poster = DIR_IMAGE . 'catalog/video-posters/' . $name;

		if (!is_file($poster)) {
			$dir = dirname($poster);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			exec('ffmpeg -y -ss 1 -i ' . escapeshellarg($source) . ' -frames:v 1 -q:v 4 ' . escapeshellarg($poster) . ' 2>/dev/null');
		}

		return is_file($poster) ? 'image/catalog/video-posters/' . $name : '';
	}

	private function readEnv($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
		$env = array();
		if (is_file($file)) {
			foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$line = trim($line);
				if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
					continue;
				}
				list($k, $v) = explode('=', $line, 2);
				$v = trim($v);
				if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
					$v = trim($v, $v[0]);
				}
				$env[trim($k)] = $v;
			}
		}
		return $env;
	}

	/**
	 * JSON-LD (Organization+Store, WebSite, ItemList товарів з БД, FAQPage з модуля) —
	 * відновлення розмітки зі старого статичного index.php, але з живих даних.
	 */
	private function buildSchemaJson($baseUrl) {
		$seo = $this->readJson('data/seo.json');
		$org = $seo['org'] ?? array();
		$rating = $seo['rating'] ?? array();

		$schema = array();

		$organization = array(
			'@context' => 'https://schema.org',
			'@type' => array('Organization', 'Store'),
			'name' => $this->config->get('config_name') ?: 'Hydrophob',
			'url' => $baseUrl,
			'logo' => $baseUrl . 'image/hydrophob/logo.svg',
			'email' => $this->config->get('config_hydro_email') ?: ($org['email'] ?? ''),
			'telephone' => $this->config->get('config_telephone') ?: ($org['telephoneDisplay'] ?? ''),
			'priceRange' => '₴₴',
			'openingHours' => $org['openHours'] ?? 'Mo-Su 08:00-22:00',
			'address' => array(
				'@type' => 'PostalAddress',
				'streetAddress' => $org['streetAddress'] ?? '',
				'addressLocality' => $org['addressLocality'] ?? 'Київ',
				'addressCountry' => 'UA',
			),
		);
		$geocode = (string)$this->config->get('config_geocode');
		if (strpos($geocode, ',') !== false) {
			list($lat, $lng) = array_map('trim', explode(',', $geocode, 2));
			$organization['geo'] = array('@type' => 'GeoCoordinates', 'latitude' => (float)$lat, 'longitude' => (float)$lng);
		}
		if (!empty($rating['value'])) {
			$organization['aggregateRating'] = array(
				'@type' => 'AggregateRating',
				'ratingValue' => $rating['value'],
				'reviewCount' => $rating['count'] ?? '1',
				'bestRating' => $rating['best'] ?? '5',
			);
		}
		$socials = array_filter(array($this->config->get('config_hydro_tiktok'), $this->config->get('config_hydro_telegram')));
		if ($socials) {
			$organization['sameAs'] = array_values($socials);
		}
		$schema[] = $organization;

		$schema[] = array(
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => $this->config->get('config_name') ?: 'Hydrophob',
			'url' => $baseUrl,
			'inLanguage' => 'uk_UA',
		);

		// ItemList товарів з живого каталогу
		$this->load->model('catalog/product');
		$items = array();
		$position = 0;
		foreach ($this->model_catalog_product->getProducts(array('filter_status' => 1)) as $product) {
			$position++;
			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'item' => array(
					'@type' => 'Product',
					'name' => $product['name'],
					'description' => mb_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, 200),
					'image' => $product['image'] ? $baseUrl . 'image/' . $product['image'] : '',
					'sku' => $product['model'],
					'brand' => array('@type' => 'Brand', 'name' => 'Hydrophob'),
					'offers' => array(
						'@type' => 'Offer',
						'price' => (string)round((float)$product['price']),
						'priceCurrency' => 'UAH',
						'availability' => ((int)$product['quantity'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
						'url' => $baseUrl,
					),
				),
			);
		}
		if ($items) {
			$schema[] = array('@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $items);
		}

		// FAQPage з модуля faq (укр)
		$faqItems = $this->config->get('module_hydrophob_faq_items');
		if (is_array($faqItems)) {
			$main = array();
			foreach ($faqItems as $item) {
				$q = trim(strip_tags((string)($item['question'][2] ?? '')));
				$a = trim(strip_tags((string)($item['answer'][2] ?? '')));
				if ($q !== '' && $a !== '') {
					$main[] = array(
						'@type' => 'Question',
						'name' => $q,
						'acceptedAnswer' => array('@type' => 'Answer', 'text' => $a),
					);
				}
			}
			if ($main) {
				$schema[] = array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $main);
			}
		}

		return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
