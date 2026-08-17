<?php
/**
 * Секція "reviews" (слайдер відгуків) головної сторінки — content_top модуль.
 * Повторюваний список: постер (зображення) + відео + текст відгуку (мультимовно).
 * Дані: oc_setting (module_hydrophob_reviews_*), фолбек — data/images.json + data/strings.json.
 */
class ControllerExtensionModuleHydrophobReviews extends Controller {
	private $code = 'module_hydrophob_reviews';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_reviews');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');

		$data['title_html'] = $this->localized('title_html', $lang_id, $strings['reviews']['titleHtml'] ?? array());

		$itemsSetting = $this->config->get($this->code . '_items');
		$items = array();

		if (is_array($itemsSetting) && !empty($itemsSetting)) {
			$index = 0;
			foreach ($itemsSetting as $item) {
				$index++;
				$poster = $item['poster'] ?? '';
				$items[] = array(
					'poster'  => $poster ? 'image/' . $poster : '',
					'alt'     => $this->localizedFromArray($item['alt'] ?? array(), $lang_id),
					'video'   => $this->videoPath($item['video'] ?? ''),
					'index'   => $index,
					'message' => $this->localizedFromArray($item['message'] ?? array(), $lang_id),
				);
			}
		} else {
			// Фолбек на старий формат data/images.json (reviews[]) + data/strings.json (reviews.messageN).
			$images = $this->fixImagePaths($this->readJson('data/images.json'));
			$index = 0;
			foreach (($images['reviews'] ?? array()) as $reviewItem) {
				$index++;
				$items[] = array(
					'poster'  => $reviewItem['poster'] ?? '',
					'alt'     => $reviewItem['alt'] ?? 'Відгук про Hydrophob',
					'video'   => $this->videoPath($reviewItem['video'] ?? ''),
					'index'   => $index,
					'message' => $this->localized('legacy_message' . $index, $lang_id, $strings['reviews']['message' . $index] ?? array()),
				);
			}
		}

		$data['items'] = $items;

		return $this->load->view('extension/module/hydrophob_reviews', $data);
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

	/** Шлях відео: з медіатеки приходить відносно image/ (напр. catalog/x.mp4), легасі — video/... як є. */
	private function videoPath($value) {
		if (!$value || strpos($value, 'video/') === 0 || strpos($value, 'http') === 0 || strpos($value, 'image/') === 0) {
			return $value;
		}
		return 'image/' . $value;
	}
}
