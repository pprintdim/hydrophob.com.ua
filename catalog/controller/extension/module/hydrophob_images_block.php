<?php
/**
 * Секція "imagesBlock" (галерея-тизер) головної сторінки — content_top модуль.
 * Дані: data/images.json -> imagesBlock.items[0..3] + imagesBlock.logo.
 */
class ControllerExtensionModuleHydrophobImagesBlock extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_images_block');

		$images = $this->fixImagePaths($this->readJson('data/images.json'));

		$data['items'] = $images['imagesBlock']['items'] ?? array();
		$data['logo']  = $images['imagesBlock']['logo'] ?? 'image/hydrophob/imagesBlock/logo.webp';

		return $this->load->view('extension/module/hydrophob_images_block', $data);
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
