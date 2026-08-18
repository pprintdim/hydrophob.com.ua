<?php
/**
 * Секція "hero" головної сторінки (hydrophob.net.ua) — окремий content_top модуль.
 * Дані: oc_setting (module_hydrophob_hero_*), з фолбеком на data/images.json + data/strings.json.
 */
class ControllerExtensionModuleHydrophobHero extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_hero');

		$lang_id = (int)$this->config->get('config_language_id');

		$images = $this->fixImagePaths($this->readJson('data/images.json'));
		$strings = $this->readJson('data/strings.json');

		$title_html_setting = $this->config->get('module_hydrophob_hero_title_html');
		$data['title_html'] = $this->getLocalizedValue($title_html_setting, $lang_id, $strings['hero']['titleHtml'] ?? array());

		$poster_setting = $this->config->get('module_hydrophob_hero_poster');
		$data['poster'] = $poster_setting ? 'image/' . $poster_setting : ($images['hero']['poster'] ?? 'image/hydrophob/hero-poster.webp');

		$alt_setting = $this->config->get('module_hydrophob_hero_alt');
		$data['alt'] = $alt_setting !== null && $alt_setting !== '' ? $alt_setting : ($images['hero']['alt'] ?? 'Hydrophob');

		$video_setting = $this->config->get('module_hydrophob_hero_video');
		$data['video'] = $video_setting !== null && $video_setting !== '' ? $this->videoPath($video_setting) : ($images['hero']['video'] ?? 'video/hero.mp4');

		// Слайдер: ряди {video, poster} з адмінки; шляхи нормалізуємо (медіатека -> image/)
		$slides = array();
		$slidesSetting = $this->config->get('module_hydrophob_hero_slides');
		if (is_array($slidesSetting)) {
			foreach ($slidesSetting as $slide) {
				if (empty($slide['video'])) {
					continue;
				}
				$slides[] = array(
					'video'  => $this->videoPath($slide['video']),
					'poster' => !empty($slide['poster']) ? 'image/' . $slide['poster'] : '',
				);
			}
		}
		$data['slides'] = $slides;

		return $this->load->view('extension/module/hydrophob_hero', $data);
	}

	/** Мультимовне значення з oc_setting (масив за language_id) з фолбеком на масив {UA,RU,EN} зі старого data/*.json. */
	private function getLocalizedValue($values, $language_id, $legacy_fallback = array()) {
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}

		$legacy_key = $this->legacyLangKey($language_id);
		if (is_array($legacy_fallback) && !empty($legacy_fallback[$legacy_key])) {
			return $legacy_fallback[$legacy_key];
		}

		return $legacy_fallback['UA'] ?? '';
	}

	/** language_id поточної сесії -> ключ UA/RU/EN у старих data/*.json (код мови вже визначений у startup.php). */
	private function legacyLangKey($language_id) {
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

	/** img/... -> image/hydrophob/... рекурсивно. */
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
