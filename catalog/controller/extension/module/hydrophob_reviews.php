<?php
/**
 * Секція "reviews" (слайдер відгуків) головної сторінки — content_top модуль.
 * Дані: data/images.json -> reviews[] (постер/відео) + data/strings.json -> reviews.message<N>.UA (текст).
 */
class ControllerExtensionModuleHydrophobReviews extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_reviews');

		$images = $this->fixImagePaths($this->readJson('data/images.json'));
		$strings = $this->readJson('data/strings.json');

		$items = array();
		$index = 0;

		foreach (($images['reviews'] ?? array()) as $reviewItem) {
			$index++;
			$items[] = array(
				'poster'  => $reviewItem['poster'] ?? '',
				'alt'     => $reviewItem['alt'] ?? 'Відгук про Hydrophob',
				'video'   => $reviewItem['video'] ?? '',
				'index'   => $index,
				'message' => $strings['reviews']['message' . $index]['UA'] ?? '',
			);
		}

		$data['items'] = $items;

		return $this->load->view('extension/module/hydrophob_reviews', $data);
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
