<?php
/**
 * Секція "hero" головної сторінки (hydrophob.net.ua) — окремий content_top модуль.
 * Дані: data/images.json (постер/відео hero-блоку).
 */
class ControllerExtensionModuleHydrophobHero extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_hero');

		$images = $this->fixImagePaths($this->readJson('data/images.json'));

		$data['poster'] = $images['hero']['poster'] ?? 'image/hydrophob/hero-poster.webp';
		$data['alt']    = $images['hero']['alt'] ?? 'Hydrophob';
		$data['video']  = $images['hero']['video'] ?? 'video/hero.mp4';

		return $this->load->view('extension/module/hydrophob_hero', $data);
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
}
