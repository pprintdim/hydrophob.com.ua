<?php
/**
 * Секція "delivery" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Заголовок/опис (мультимовно) + 6 фіксованих перевізників (назва мультимовно + іконка).
 * Дані: oc_setting (module_hydrophob_delivery_*), фолбек — data/strings.json -> delivery.*.
 */
class ControllerExtensionModuleHydrophobDelivery extends Controller {
	private $code = 'module_hydrophob_delivery';

	/** Фіксовані ключі перевізників (використовуються в data-delivery на фронті — JS попапів). */
	private $carrierKeys = array(
		'np'        => array('legacy' => 'np',      'defaultIcon' => 'hydrophob/delivery/1.webp'),
		'ukrposhta' => array('legacy' => 'ukr',      'defaultIcon' => 'hydrophob/delivery/2.webp'),
		'meest'     => array('legacy' => 'meest',    'defaultIcon' => 'hydrophob/delivery/3.webp'),
		'other'     => array('legacy' => 'other',    'defaultIcon' => 'hydrophob/delivery/4.svg'),
		'pickup'    => array('legacy' => 'pickup',   'defaultIcon' => 'hydrophob/delivery/5.svg'),
		'courier'   => array('legacy' => 'courier',  'defaultIcon' => 'hydrophob/delivery/6.svg'),
	);

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_delivery');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$d = $strings['delivery'] ?? array();

		$data['title_html'] = $this->localized('title_html', $lang_id, $d['titleHtml'] ?? array());
		$data['descr']      = $this->localized('descr', $lang_id, $d['descr'] ?? array());

		$carriersSetting = $this->config->get($this->code . '_carriers');

		$carriers = array();
		foreach ($this->carrierKeys as $key => $info) {
			$icon = $carriersSetting[$key]['icon'] ?? '';
			$name = $this->localizedFromArray($carriersSetting[$key]['name'] ?? array(), $lang_id);

			if ($name === '') {
				$name = $this->localized('legacy_' . $key, $lang_id, $d[$info['legacy']] ?? array());
			}

			$carriers[$key] = array(
				'key'  => $key,
				'name' => $name,
				'icon' => $icon ? 'image/' . $icon : 'image/' . $info['defaultIcon'],
			);
		}

		$data['carriers'] = $carriers;

		return $this->load->view('extension/module/hydrophob_delivery', $data);
	}

	private function localizedFromArray($values, $language_id) {
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}
		return '';
	}

	private function localized($field, $language_id, $legacy_fallback) {
		$values = $this->config->get($this->code . '_' . $field);
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}

		$key = $this->legacyLangKey();
		if (is_array($legacy_fallback) && !empty($legacy_fallback[$key])) {
			return $legacy_fallback[$key];
		}

		return $legacy_fallback['UA'] ?? '';
	}

	private function legacyLangKey() {
		$code = $this->session->data['language'] ?? 'uk-ua';
		if ($code === 'ru-ru') {
			return 'RU';
		}
		if ($code === 'en-gb') {
			return 'EN';
		}
		return 'UA';
	}

	private function readJson($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}
}
