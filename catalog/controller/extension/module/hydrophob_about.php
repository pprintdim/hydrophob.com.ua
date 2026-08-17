<?php
/**
 * Секція "about" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Дані: oc_setting (module_hydrophob_about_*), фолбек — data/strings.json -> about.*.
 */
class ControllerExtensionModuleHydrophobAbout extends Controller {
	private $code = 'module_hydrophob_about';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_about');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');

		$data['name']      = $this->localized('name', $lang_id, $strings['about']['name'] ?? array());
		$data['title']     = $this->localized('title', $lang_id, $strings['about']['title'] ?? array());
		$data['descr']     = $this->localized('descr', $lang_id, $strings['about']['descr'] ?? array());
		$data['read_more'] = $this->localized('read_more', $lang_id, $strings['about']['readMore'] ?? array());

		return $this->load->view('extension/module/hydrophob_about', $data);
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
