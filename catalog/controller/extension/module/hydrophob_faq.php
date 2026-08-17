<?php
/**
 * Секція "faq" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Повторюваний список питання/відповідь (мультимовно).
 * Дані: oc_setting (module_hydrophob_faq_*), фолбек — data/strings.json -> faq.*.
 */
class ControllerExtensionModuleHydrophobFaq extends Controller {
	private $code = 'module_hydrophob_faq';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_faq');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');
		$f = $strings['faq'] ?? array();

		$data['title_html'] = $this->localized('title_html', $lang_id, $f['titleHtml'] ?? array());

		$itemsSetting = $this->config->get($this->code . '_items');
		$items = array();

		if (is_array($itemsSetting) && !empty($itemsSetting)) {
			foreach ($itemsSetting as $item) {
				$items[] = array(
					'question' => $this->localizedFromArray($item['question'] ?? array(), $lang_id),
					'answer'   => $this->localizedFromArray($item['answer'] ?? array(), $lang_id),
				);
			}
		} else {
			// Фолбек на старий фіксований набір q1..q6/a1..a6 з data/strings.json.
			for ($i = 1; $i <= 6; $i++) {
				if (empty($f['q' . $i])) {
					continue;
				}
				$items[] = array(
					'question' => $this->localized('legacy_q' . $i, $lang_id, $f['q' . $i] ?? array()),
					'answer'   => $this->localized('legacy_a' . $i, $lang_id, $f['a' . $i] ?? array()),
				);
			}
		}

		$data['items'] = $items;

		return $this->load->view('extension/module/hydrophob_faq', $data);
	}

	private function localizedFromArray($values, $language_id) {
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}
		return is_array($values) && !empty($values) ? reset($values) : '';
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
