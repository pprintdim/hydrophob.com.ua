<?php
/**
 * Секція "imagesBlock" (галерея-тизер) головної сторінки — content_top модуль.
 * Дані: oc_setting (module_hydrophob_images_block_*), фолбек — data/images.json -> imagesBlock.*.
 */
class ControllerExtensionModuleHydrophobImagesBlock extends Controller {
	private $code = 'module_hydrophob_images_block';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_images_block');

		$lang_id = (int)$this->config->get('config_language_id');
		$images = $this->fixImagePaths($this->readJson('data/images.json'));

		$legacyItems = $images['imagesBlock']['items'] ?? array();
		$itemsSetting = $this->config->get($this->code . '_items');

		$items = array();
		for ($i = 0; $i < 4; $i++) {
			$tileSetting = $itemsSetting[$i]['tile'] ?? '';
			$tile = $tileSetting ? 'image/' . $tileSetting : ($legacyItems[$i]['tile'] ?? '');

			$altSetting = $itemsSetting[$i]['alt'] ?? array();
			$alt = $this->localizedFromArray($altSetting, $lang_id, $legacyItems[$i]['alt'] ?? '');

			$items[] = array('tile' => $tile, 'alt' => $alt);
		}

		$data['items'] = $items;

		$logoSetting = $this->config->get($this->code . '_logo');
		$data['logo'] = $logoSetting ? 'image/' . $logoSetting : ($images['imagesBlock']['logo'] ?? 'image/hydrophob/imagesBlock/logo.webp');

		return $this->load->view('extension/module/hydrophob_images_block', $data);
	}

	/** Мультимовний масив за language_id, фолбек — один рядок (як у старому data/images.json). */
	private function localizedFromArray($values, $language_id, $legacy_string) {
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}
		return (string)$legacy_string;
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
}
