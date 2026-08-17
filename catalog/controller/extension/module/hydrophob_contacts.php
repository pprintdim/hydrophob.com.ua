<?php
/**
 * Секція "contacts" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Адреса/графік — мультимовно; телефон/email/соцмережі/координати — спільні для всіх мов.
 * Дані: oc_setting (module_hydrophob_contacts_*), фолбек — data/strings.json -> contacts.* + хардкод з верстки.
 */
class ControllerExtensionModuleHydrophobContacts extends Controller {
	private $code = 'module_hydrophob_contacts';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_contacts');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$c = $strings['contacts'] ?? array();

		$data['title']   = $this->localized('title', $lang_id, $c['title'] ?? array());
		$data['address'] = $this->localized('address', $lang_id, $c['address'] ?? array());
		$data['time']    = $this->localized('time', $lang_id, $c['time'] ?? array());

		$data['phone']    = $this->single('phone', '+380 (73) 108-12-12');
		$data['phone_href'] = preg_replace('/[^0-9+]/', '', $data['phone']);
		$data['email']    = $this->single('email', 'hydrophob@ukr.net');
		$data['tiktok']   = $this->single('tiktok', 'https://www.tiktok.com/@hydrophob.ua');
		$data['telegram'] = $this->single('telegram', 'https://t.me/Hydrophob1');
		$data['viber']    = $this->single('viber', '+380731081212');

		$data['lat'] = $this->single('lat', '50.4542');
		$data['lng'] = $this->single('lng', '30.6402');

		return $this->load->view('extension/module/hydrophob_contacts', $data);
	}

	private function single($field, $default) {
		$value = $this->config->get($this->code . '_' . $field);
		return ($value !== null && $value !== '') ? $value : $default;
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
