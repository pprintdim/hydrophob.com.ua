<?php
/**
 * Секція "guarantee" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * 4 фіксовані пункти (title/message мультимовно). Іконки статичні (не редагуються).
 * Дані: oc_setting (module_hydrophob_guarantee_*), фолбек — data/strings.json -> guarantee.*.
 */
class ControllerExtensionModuleHydrophobGuarantee extends Controller {
	private $code = 'module_hydrophob_guarantee';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_guarantee');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$g = $strings['guarantee'] ?? array();

		$data['title'] = $this->localized('title', $lang_id, $g['title'] ?? array());

		$data['items'] = array();
		$titleKeys = array(1 => 'item1', 2 => 'item2Html', 3 => 'item3', 4 => 'item4');
		for ($i = 1; $i <= 4; $i++) {
			$data['items'][$i] = array(
				'title'   => $this->localized('item' . $i . '_title', $lang_id, $g[$titleKeys[$i]] ?? array()),
				'message' => $this->localized('item' . $i . '_message', $lang_id, $g['message' . $i] ?? array()),
			);
		}

		return $this->load->view('extension/module/hydrophob_guarantee', $data);
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
