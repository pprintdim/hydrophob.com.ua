<?php
/**
 * Секція "contacts" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Заголовок — з налаштувань модуля (єдине його поле); решта даних — із системних
 * налаштувань магазину (Система → Налаштування): config_address / config_open
 * (мультимовні serialized-масиви за language_id), config_telephone, config_geocode,
 * config_hydro_email / _tiktok / _telegram / _viber. Фолбек — data/strings.json.
 */
class ControllerExtensionModuleHydrophobContacts extends Controller {
	private $code = 'module_hydrophob_contacts';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_contacts');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$c = $strings['contacts'] ?? array();

		$data['title']   = $this->localizedModule('title', $lang_id, $c['title'] ?? array());
		$data['address'] = $this->localizedConfig('config_address', $lang_id, $c['address'] ?? array());
		$data['time']    = $this->localizedConfig('config_open', $lang_id, $c['time'] ?? array());

		$data['phone']    = $this->configOr('config_telephone', '+380 (73) 108-12-12');
		$data['phone_href'] = preg_replace('/[^0-9+]/', '', $data['phone']);
		$data['email']    = $this->configOr('config_hydro_email', 'hydrophob@ukr.net');
		$data['tiktok']   = $this->configOr('config_hydro_tiktok', 'https://www.tiktok.com/@hydrophob.ua');
		$data['telegram'] = $this->configOr('config_hydro_telegram', 'https://t.me/Hydrophob1');
		$data['viber']    = $this->configOr('config_hydro_viber', '+380731081212');

		$geocode = (string)$this->config->get('config_geocode');
		$parts = array_map('trim', explode(',', $geocode));
		$data['lat'] = (isset($parts[0]) && $parts[0] !== '') ? $parts[0] : '50.4542';
		$data['lng'] = (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : '30.6402';

		return $this->load->view('extension/module/hydrophob_contacts', $data);
	}

	private function configOr($key, $default) {
		$value = $this->config->get($key);
		return ($value !== null && $value !== '') ? $value : $default;
	}

	private function localizedModule($field, $language_id, $legacy_fallback) {
		return $this->pickLocalized($this->config->get($this->code . '_' . $field), $language_id, $legacy_fallback);
	}

	private function localizedConfig($key, $language_id, $legacy_fallback) {
		return $this->pickLocalized($this->config->get($key), $language_id, $legacy_fallback);
	}

	private function pickLocalized($values, $language_id, $legacy_fallback) {
		if (is_array($values)) {
			if (isset($values[$language_id]) && $values[$language_id] !== '') {
				return $values[$language_id];
			}
			foreach ($values as $value) {
				if ($value !== '') {
					return $value;
				}
			}
		} elseif ($values !== null && $values !== '') {
			// колишнє одномовне значення (plain string) — показуємо як є
			return $values;
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
