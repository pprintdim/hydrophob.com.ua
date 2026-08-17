<?php
/**
 * Порт лендінгу hydrophob.net.ua у OpenCart 3.
 * Рендерить головну сторінку одним набором twig-партиалів (catalog/view/theme/default/template/common/home/*.twig),
 * зібраних докупи в catalog/view/theme/default/template/common/home.twig.
 *
 * Джерела контенту:
 *  - data/*.json (страва зі старого статичного лендінгу, скопійована 1:1) — тексти, seo, зображення, доставка;
 *  - каталог товарів OpenCart (oc_product) — id/назва/опис/ціна/наявність/фото для секції "product" та "infoBlock";
 *  - .env — таймер акції, код країни за замовчуванням для телефону (як у старому api/bootstrap.php).
 *
 * Багатомовність (RU/EN) свідомо відкладена: увесь SSR-контент віддається українською (UA),
 * так само якklient-side i18n у catalog/view/theme/default/javascript/hydrophob.js бере UA як фолбек.
 */
class ControllerCommonHome extends Controller {
	/** @var string корінь проєкту (openCart3/) */
	private $root;

	public function index() {
		$this->root = DIR_APPLICATION . '../';

		$seo = $this->readJson('data/seo.json');
		$strings = $this->readJson('data/strings.json');
		$images = $this->fixImagePaths($this->readJson('data/images.json'));
		$deliveries = $this->readJson('data/deliveries.json');
		$staticProducts = $this->readJson('data/products.json');
		$env = $this->readEnv('.env');

		$lang = 'UA';
		$meta = ($seo['meta'][$lang] ?? null) ?: array('title' => 'Hydrophob', 'description' => '', 'keywords' => '');

		$this->document->setTitle($meta['title']);
		$this->document->setDescription($meta['description']);
		$this->document->setKeywords($meta['keywords']);

		// ---- Живий каталог із БД (замінює старий data/products.json для секцій product/infoBlock) ----
		$products = $this->getLiveProducts($staticProducts);
		$productsById = array();
		foreach ($products as $p) {
			$productsById[$p['id']] = $p;
		}

		// ---- Акційний таймер (як у sections/action.php, дані з .env) ----
		$actionTimer = $this->buildActionTimer($env);

		// ---- Перевізники доставки (cart.twig) ----
		$carriers = array();
		foreach (($deliveries['carriers'] ?? array()) as $carrier) {
			$carriers[] = array(
				'id'   => $carrier['id'] ?? '',
				'icon' => 'image/hydrophob/' . ltrim(str_replace('img/', '', $carrier['icon'] ?? ''), '/'),
				'name' => $this->uaValue($carrier['name'] ?? ''),
			);
		}

		// ---- Вкладки блоку "лінійка продукції" (info_block.twig) ----
		$infoTabs = $this->buildInfoTabs($productsById);

		// ---- Спільні дані для всіх секцій ----
		$shared = array(
			'images'     => $images,
			'strings'    => $strings,
			'products'   => $products,
			'carriers'   => $carriers,
			'info_tabs'  => $infoTabs,
			'env'        => array_merge($env, $actionTimer),
		);

		$sections = array();
		$sectionFiles = array(
			'header', 'hero', 'popup_video', 'popup_photo', 'popup_product', 'popup_about', 'popup_delivery',
			'about', 'action', 'images_block', 'product', 'info_block', 'reviews', 'guarantee', 'faq',
			'delivery', 'contacts', 'cart', 'footer', 'cookie',
		);
		foreach ($sectionFiles as $section) {
			$sections[$section] = $this->load->view('common/home/' . $section, $shared);
		}

		$org = $seo['org'] ?? array();
		$baseUrl = rtrim($seo['url'] ?? '', '/') . '/';

		$data = $sections;
		$data['html_lang'] = $seo['htmlLang'][$lang] ?? 'uk';
		$data['meta'] = $meta;
		$data['canonical'] = $baseUrl;
		$data['og_locale'] = $seo['locales'][$lang] ?? 'uk_UA';
		$data['site_name'] = $seo['siteName'] ?? 'Hydrophob';
		$data['og_image'] = $baseUrl . ($seo['ogImage'] ?? 'img/og-image.jpg');
		$data['analytics'] = array(
			'mode'     => ($env['ANALYTICS_MODE'] ?? 'test') === 'production' ? 'production' : 'test',
			'ga4'      => $env['GA4_ID'] ?? '',
			'ads'      => $env['GOOGLE_ADS_ID'] ?? '',
			'adsLabel' => $env['GOOGLE_ADS_PURCHASE_LABEL'] ?? '',
		);
		$data['asset_version'] = (string)@filemtime($this->root . 'catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	/**
	 * Товари з БД (live) доповнені редакційним контентом (details/attrs), якого ще нема в схемі OpenCart —
	 * той самий підхід, що й у extension/module/catalog_api.php.
	 */
	private function getLiveProducts($staticProducts) {
		$this->load->model('catalog/product');

		$staticById = array();
		foreach ((array)$staticProducts as $sp) {
			if (isset($sp['id'])) {
				$staticById[$sp['id']] = $sp;
			}
		}

		$dbProducts = $this->model_catalog_product->getProducts(array('filter_status' => 1));

		$products = array();
		foreach ($dbProducts as $product) {
			if (!$product['status'] || (int)$product['quantity'] <= 0) {
				continue;
			}
			$extra = $staticById[$product['model']] ?? array();
			$products[] = array(
				'id'              => $product['model'],
				'title'           => $product['name'],
				'descr'           => strip_tags($product['description']),
				'descriptionHtml' => $product['description'],
				'volume'          => $product['tag'],
				'price'           => (float)$product['price'],
				'image'           => $product['image'] ? 'image/' . $product['image'] : '',
				'details'         => $extra['details'] ?? null,
				'attrs'           => $extra['attrs'] ?? array(),
			);
		}

		return $products;
	}

	/**
	 * Вкладки infoBlock: три конкретні товари (Automobile/Textile/Industrial), як у sections/info-block.php.
	 * tabTitle/subtitle/blocks — редакційний контент, якого нема в БД, тому береться зі статичного products.json.
	 */
	private function buildInfoTabs($productsById) {
		$tabDefs = array(
			'Automobile' => 'p2524537265',
			'Textile'    => 'p2523866690',
			'Industrial' => 'p2524531368',
		);

		$images = $this->fixImagePaths($this->readJson('data/images.json'));

		$tabs = array();
		foreach ($tabDefs as $tabKey => $pid) {
			$product = $productsById[$pid] ?? null;
			if (!$product) {
				continue;
			}
			$details = $product['details'] ?? array();
			$blocks = array();
			foreach (($details['blocks'] ?? array()) as $block) {
				$blocks[] = array(
					'title' => $this->uaValue($block['title'] ?? ''),
					'html'  => $this->uaValue($block['html'] ?? ''),
				);
			}
			$tabs[] = array(
				'key'      => $tabKey,
				'id'       => $pid,
				'product'  => $product,
				'tabTitle' => $this->uaValue($details['tabTitle'] ?? ('Hydrophob ' . $tabKey)),
				'subtitle' => $this->uaValue($details['subtitle'] ?? ''),
				'blocks'   => $blocks,
				'media'    => array(
					'poster' => $images['infoBlock'][$tabKey]['poster'] ?? '',
					'video'  => $images['infoBlock'][$tabKey]['video'] ?? '',
					'alt'    => $images['infoBlock'][$tabKey]['alt'] ?? '',
				),
			);
		}

		return $tabs;
	}

	/** Таймер акції (data-timer-start/end, ISO 8601) — як у sections/action.php. */
	private function buildActionTimer($env) {
		$start = $env['ACTION_TIMER_START'] ?? '2026-07-15 00:00:00';
		$end = $env['ACTION_TIMER_END'] ?? '2027-07-15 18:30:00';
		$tz = $env['ACTION_TIMER_TIMEZONE'] ?? 'Europe/Kyiv';

		try {
			$timezone = new DateTimeZone($tz);
			$timerStart = new DateTimeImmutable($start, $timezone);
			$timerEnd = new DateTimeImmutable($end, $timezone);
			return array(
				'ACTION_TIMER_START_ISO' => $timerStart->format(DATE_ATOM),
				'ACTION_TIMER_END_ISO'   => $timerEnd->format(DATE_ATOM),
			);
		} catch (Throwable $e) {
			return array('ACTION_TIMER_START_ISO' => '', 'ACTION_TIMER_END_ISO' => '');
		}
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
		$file = $this->root . $relativePath;
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}

	private function readEnv($relativePath) {
		$file = $this->root . $relativePath;
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
}
