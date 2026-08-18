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
		if (is_array($itemsSetting) && $itemsSetting) {
			// репітер: довільна кількість плиток з адмінки
			foreach (array_values($itemsSetting) as $i => $row) {
				$tile = !empty($row['tile']) ? 'image/' . $row['tile'] : ($legacyItems[$i]['tile'] ?? '');
				if ($tile === '') {
					continue;
				}
				$alt = $this->localizedFromArray($row['alt'] ?? array(), $lang_id, $legacyItems[$i]['alt'] ?? '');
				$video = !empty($row['video']) ? $this->videoPath($row['video']) : '';
				// постер відео — завжди кадр самого відео (детермінований, як у адмінці)
				if ($video) {
					$poster = $this->videoPoster($video);
					if ($poster) {
						$tile = $poster;
					}
				}
				$items[] = array('tile' => $tile, 'alt' => $alt, 'video' => $video);
			}
		} else {
			foreach ($legacyItems as $legacy) {
				$items[] = array('tile' => $legacy['tile'] ?? '', 'alt' => $legacy['alt'] ?? '');
			}
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

	/**
	 * Постер = кадр відео, схема імен та сама, що в адмінці (common/video_poster):
	 * catalog/video-posters/<імʼя>-<md5(relative)6>.webp; генерується один раз.
	 */
	private function videoPoster($video) {
		if (strpos($video, 'image/') === 0) {
			$relative = substr($video, 6);
		} elseif (strpos($video, 'video/') === 0) {
			$relative = 'catalog/' . $video;
		} else {
			$relative = $video;
		}

		$source = DIR_IMAGE . $relative;
		if (!is_file($source)) {
			return '';
		}

		$name = pathinfo($relative, PATHINFO_FILENAME) . '-' . substr(md5($relative), 0, 6) . '.webp';
		$poster = DIR_IMAGE . 'catalog/video-posters/' . $name;

		if (!is_file($poster)) {
			$dir = dirname($poster);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			exec('ffmpeg -y -ss 1 -i ' . escapeshellarg($source) . ' -frames:v 1 -q:v 4 ' . escapeshellarg($poster) . ' 2>/dev/null');
		}

		return is_file($poster) ? 'image/catalog/video-posters/' . $name : '';
	}

	/** Шлях відео: з медіатеки приходить відносно image/, легасі video/... — як є. */
	private function videoPath($value) {
		if (!$value || strpos($value, 'video/') === 0 || strpos($value, 'http') === 0 || strpos($value, 'image/') === 0) {
			return $value;
		}
		return 'image/' . $value;
	}
}
