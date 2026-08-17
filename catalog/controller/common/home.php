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

		$lang = 'UA';
		$meta = ($seo['meta'][$lang] ?? null) ?: array('title' => 'Hydrophob', 'description' => '', 'keywords' => '');

		$this->document->setTitle($meta['title']);
		$this->document->setDescription($meta['description']);
		$this->document->setKeywords($meta['keywords']);

		// ---- Попап-галерея фото (popup_photo.twig) — ті самі кадри, що й imagesBlock ----
		$images = $this->fixImagePaths($this->readJson('data/images.json'));

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
			'carriers'      => $carriers,
			'env'           => $env,
			'checkout_url'  => $this->url->link('checkout/hydro_checkout'),
		);

		$partials = array('header', 'popup_video', 'popup_photo', 'popup_product', 'popup_about', 'popup_delivery', 'cart', 'footer', 'cookie');
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
		$data['asset_version'] = (string)@filemtime(DIR_APPLICATION . '../catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		$this->response->setOutput($this->load->view('common/home', $data));
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
}
