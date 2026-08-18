<?php
/**
 * Окрема сторінка чекауту hydrophob.net.ua (route: checkout/hydro_checkout).
 * Кошик (localStorage) + усі етапи оформлення (контактні дані, доставка НП/Meest/Укрпошта
 * через api/shipping.php, підтвердження) — раніше все це було всередині попапу кошика
 * (common/home/cart.twig), тепер попап показує лише список товарів і веде сюди.
 * Сабміт лишається на api/order.php (бекенд не змінюється), редірект — на checkout/hydro_success.
 */
class ControllerCheckoutHydroCheckout extends Controller {
	public function index() {
		$root = DIR_APPLICATION . '../';

		$deliveries = $this->readJson($root . 'data/deliveries.json');
		$carriers = array();
		foreach (($deliveries['carriers'] ?? array()) as $carrier) {
			$carriers[] = array(
				'id'   => $carrier['id'] ?? '',
				'icon' => 'image/hydrophob/' . ltrim(str_replace('img/', '', $carrier['icon'] ?? ''), '/'),
				'name' => $this->uaValue($carrier['name'] ?? ''),
			);
		}

		$env = $this->readEnv($root . '.env');

		// Метатеги — з Design → SEO (data/seo.json, metaPages.checkout), за мовою сесії
		$seoJson = $this->readJson($root . 'data/seo.json');
		$langCodeMap = array(1 => 'EN', 2 => 'UA', 3 => 'RU');
		$metaLang = $langCodeMap[(int)$this->config->get('config_language_id')] ?? 'UA';
		$pageMeta = $seoJson['metaPages']['checkout'][$metaLang] ?? array();
		$metaTitle = ($pageMeta['title'] ?? '') !== '' ? $pageMeta['title'] : 'Оформлення замовлення — Hydrophob';
		$metaDescription = $pageMeta['description'] ?? '';
		$metaKeywords = $pageMeta['keywords'] ?? '';

		$this->document->setTitle($metaTitle);
		$this->document->setDescription($metaDescription);

		$data['meta_title'] = $metaTitle;
		$data['meta_description'] = $metaDescription;
		$data['meta_keywords'] = $metaKeywords;

		$data['header'] = $this->load->view('common/home/header', array('hydro_lang' => 'UA'));
		$data['footer'] = $this->load->view('common/home/footer', array());
		$data['carriers'] = $carriers;

		// Активні способи оплати (список синхронний з api/order.php -> hydro_payment_methods)
		$payments = array();
		$defs = array(
			'cod'           => array('title' => 'Накладений платіж', 'setting_title' => ''),
			'wayforpay'     => array('title' => 'Оплата карткою онлайн', 'setting_title' => 'payment_wayforpay_title'),
			'bank_transfer' => array('title' => 'Банківський переказ', 'setting_title' => ''),
		);
		$lang_id = (int)$this->config->get('config_language_id');
		foreach ($defs as $code => $def) {
			if (!$this->config->get('payment_' . $code . '_status')) {
				continue;
			}
			$title = $def['title'];
			if ($def['setting_title'] && $this->config->get($def['setting_title'] . $lang_id)) {
				$title = $this->config->get($def['setting_title'] . $lang_id);
			}
			$payments[$code] = $title;
		}
		$data['payments'] = $payments;
		$data['default_phone_country'] = $env['DEFAULT_PHONE_COUNTRY'] ?? 'UA';
		$data['home_url'] = $this->url->link('common/home');
		$data['success_url'] = '/success';
		$data['products_url'] = $this->url->link('extension/module/catalog_api/products');
		$data['shipping_url'] = 'api/shipping.php';
		$data['order_url'] = 'api/order.php';
		$data['asset_version'] = (string)@filemtime($root . 'catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		$this->response->setOutput($this->load->view('checkout/hydro_checkout', $data));
	}

	private function uaValue($field) {
		if (is_array($field)) {
			return $field['UA'] ?? '';
		}
		return (string)$field;
	}

	private function readJson($file) {
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}

	private function readEnv($file) {
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
