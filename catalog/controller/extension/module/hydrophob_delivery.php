<?php
/**
 * Секція "delivery" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Заголовок/опис (мультимовно) + перевізники в повторювачі (назва мультимовно + іконка +
 * опис у попапі, редаговані з адмінки). Дані: oc_setting (module_hydrophob_delivery_items).
 * Фолбек на старий формат (module_hydrophob_delivery_carriers + data/strings.json), поки
 * не насіяно нові дані.
 */
class ControllerExtensionModuleHydrophobDelivery extends Controller {
	private $code = 'module_hydrophob_delivery';

	/** data-i18n у <li> списку — тільки для «рідних» шести перевізників (клієнтський перемикач мов). */
	private $legacyDataI18n = array(
		'np'        => 'delivery.np',
		'ukrposhta' => 'delivery.ukr',
		'meest'     => 'delivery.meest',
		'other'     => 'delivery.other',
		'pickup'    => 'delivery.pickup',
		'courier'   => 'delivery.courier',
	);

	/** Фіксовані ключі перевізників для старого фолбек-шляху (доки не насіяно module_hydrophob_delivery_items). */
	private $carrierKeys = array(
		'np'        => array('legacy' => 'np',      'defaultIcon' => 'hydrophob/delivery/1.webp'),
		'ukrposhta' => array('legacy' => 'ukr',      'defaultIcon' => 'hydrophob/delivery/2.webp'),
		'meest'     => array('legacy' => 'meest',    'defaultIcon' => 'hydrophob/delivery/3.webp'),
		'other'     => array('legacy' => 'other',    'defaultIcon' => 'hydrophob/delivery/4.svg'),
		'pickup'    => array('legacy' => 'pickup',   'defaultIcon' => 'hydrophob/delivery/5.svg'),
		'courier'   => array('legacy' => 'courier',  'defaultIcon' => 'hydrophob/delivery/6.svg'),
	);

	/** id мов проєкту (фіксовані, hydrophob.net.ua): 1=en-gb(EN), 2=uk-ua(UA), 3=ru-ru(RU). */
	private $langIds = array('UA' => 2, 'RU' => 3, 'EN' => 1);

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_delivery');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$d = $strings['delivery'] ?? array();

		$data['title_html'] = $this->localized('title_html', $lang_id, $d['titleHtml'] ?? array());
		$data['descr']      = $this->localized('descr', $lang_id, $d['descr'] ?? array());

		$items = $this->config->get($this->code . '_items');

		if (is_array($items) && !empty($items)) {
			list($carriers, $info) = $this->buildFromItems($items, $lang_id);
		} else {
			list($carriers, $info) = $this->buildLegacy($lang_id, $strings);
		}

		$data['carriers']            = $carriers;
		$data['delivery_info_json']  = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $this->load->view('extension/module/hydrophob_delivery', $data);
	}

	/** Новий формат: module_hydrophob_delivery_items — довільна кількість рядів {key, icon, name, info_title, info_html}. */
	private function buildFromItems($items, $lang_id) {
		$carriers = array();
		$info = array();

		$i = 0;
		foreach ($items as $item) {
			$i++;
			$key  = !empty($item['key']) ? $item['key'] : ('row' . $i);
			$icon = $item['icon'] ?? '';
			$name = $this->localizedFromArray($item['name'] ?? array(), $lang_id);

			if ($icon === '') {
				$icon = $this->carrierKeys[$key]['defaultIcon'] ?? 'placeholder.png';
			}

			$carriers[] = array(
				'key'       => $key,
				'name'      => $name,
				'icon'      => 'image/' . $icon,
				'data_i18n' => $this->legacyDataI18n[$key] ?? '',
			);

			$info[$key] = array(
				'title' => array(
					'UA' => $this->localizedFromArray($item['info_title'] ?? array(), $this->langIds['UA']),
					'RU' => $this->localizedFromArray($item['info_title'] ?? array(), $this->langIds['RU']),
					'EN' => $this->localizedFromArray($item['info_title'] ?? array(), $this->langIds['EN']),
				),
				'text' => array(
					'UA' => $this->localizedFromArray($item['info_html'] ?? array(), $this->langIds['UA']),
					'RU' => $this->localizedFromArray($item['info_html'] ?? array(), $this->langIds['RU']),
					'EN' => $this->localizedFromArray($item['info_html'] ?? array(), $this->langIds['EN']),
				),
			);
		}

		return array($carriers, $info);
	}

	/** Фолбек: старий формат module_hydrophob_delivery_carriers + data/strings.json (deliveryInfo) — доки нема items. */
	private function buildLegacy($lang_id, $strings) {
		$d = $strings['delivery'] ?? array();
		$deliveryInfo = $strings['deliveryInfo'] ?? array();
		$carriersSetting = $this->config->get($this->code . '_carriers');

		$carriers = array();
		$info = array();

		foreach ($this->carrierKeys as $key => $meta) {
			$icon = $carriersSetting[$key]['icon'] ?? '';
			$name = $this->localizedFromArray($carriersSetting[$key]['name'] ?? array(), $lang_id);

			if ($name === '') {
				$name = $this->localized('legacy_' . $key, $lang_id, $d[$meta['legacy']] ?? array());
			}

			$carriers[] = array(
				'key'       => $key,
				'name'      => $name,
				'icon'      => $icon ? 'image/' . $icon : 'image/' . $meta['defaultIcon'],
				'data_i18n' => $this->legacyDataI18n[$key] ?? '',
			);

			$info[$key] = array(
				'title' => $deliveryInfo[$key]['title'] ?? array('UA' => '', 'RU' => '', 'EN' => ''),
				'text'  => $deliveryInfo[$key]['text'] ?? array('UA' => '', 'RU' => '', 'EN' => ''),
			);
		}

		return array($carriers, $info);
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
