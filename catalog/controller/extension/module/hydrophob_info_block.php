<?php
/**
 * Секція "infoBlock" (лінійка продукції) головної сторінки — content_top модуль.
 * Ряди адмінюються як "категорія -> товар" (module_hydrophob_info_block_rows, повторюваний
 * список: category_id/product_id/video/poster). Товар (id/назва/ціна/об'єм) — з живого каталогу
 * OpenCart, ленд-контент вкладки (tabTitle/subtitle/blocks) — з oc_product_details поточною
 * мовою (getLocalizedValue-фолбек на UA), медіа — з налаштувань ряду.
 *
 * Фолбек: якщо module_hydrophob_info_block_rows порожній (модуль ще не перенастроєний) —
 * стара логіка з 3 фіксованими вкладками (Automobile/Textile/Industrial), джерело даних —
 * data/products.json (details.*) + data/images.json (infoBlock.*), як у попередній версії.
 *
 * Розмітка twig НЕ змінюється — структура масиву info_tabs (key/id/product/tabTitle/subtitle/
 * blocks/media) лишається такою самою.
 */
class ControllerExtensionModuleHydrophobInfoBlock extends Controller {
	private $code = 'module_hydrophob_info_block';

	/** Фіксовані ключі вкладок фолбек-режиму -> id товару (як на фронті data-infoBlock-btn). */
	private $tabDefs = array(
		'Automobile' => 'p2524537265',
		'Textile'    => 'p2523866690',
		'Industrial' => 'p2524531368',
	);

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_info_block');
		$this->load->model('catalog/product');

		$lang_id = (int)$this->config->get('config_language_id');

		$rowsSetting = $this->config->get($this->code . '_rows');

		$tabs = array();
		if (is_array($rowsSetting) && !empty($rowsSetting)) {
			$tabs = $this->buildRowsTabs($rowsSetting, $lang_id);
		}

		if (!$tabs) {
			$tabs = $this->buildLegacyTabs($lang_id);
		}

		$data['info_tabs'] = $tabs;

		return $this->load->view('extension/module/hydrophob_info_block', $data);
	}

	/** Новий режим: ряди [{category_id, product_id, video, poster}, ...] з адмінки. */
	private function buildRowsTabs($rowsSetting, $lang_id) {
		$tabs = array();

		foreach ($rowsSetting as $key => $row) {
			$product_id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
			if (!$product_id) {
				continue;
			}

			$product = $this->model_catalog_product->getProduct($product_id);
			if (!$product || !$product['status'] || (int)$product['quantity'] <= 0) {
				continue;
			}

			$details = $this->model_catalog_product->getProductDetailsLocalized($product_id, $lang_id);

			if ($details) {
				$tabTitle = $details['tab_title'];
				$subtitle = $details['subtitle'];
				$blocks   = $details['blocks'];
			} else {
				// Фолбек на легасі data/products.json для товарів без oc_product_details.
				$extra  = $this->staticProductByModel($product['model']);
				$legacy = $extra['details'] ?? array();

				$tabTitle = $this->uaLike($legacy['tabTitle'] ?? $product['name'], $lang_id);
				$subtitle = $this->uaLike($legacy['subtitle'] ?? '', $lang_id);

				$blocks = array();
				foreach (($legacy['blocks'] ?? array()) as $block) {
					$blocks[] = array(
						'title' => $this->uaLike($block['title'] ?? '', $lang_id),
						'html'  => $this->uaLike($block['html'] ?? '', $lang_id),
					);
				}
			}

			$volume = $this->model_catalog_product->getProductAttributeValue($product_id, "Об'єм", $lang_id);
			if ($volume === null || $volume === '') {
				$volume = $product['tag'];
			}

			$posterSetting = $row['poster'] ?? '';
			$poster = $posterSetting ? 'image/' . $posterSetting : '';
			$video = $row['video'] ?? '';

			$tabs[] = array(
				'key'      => 'row' . $key,
				'id'       => $product['model'],
				'product'  => array(
					'id'     => $product['model'],
					'title'  => $product['name'],
					'volume' => $volume,
					'price'  => (float)$product['price'],
				),
				'tabTitle' => $tabTitle,
				'subtitle' => $subtitle,
				'blocks'   => $blocks,
				'media'    => array('poster' => $poster, 'video' => $video, 'alt' => $product['name']),
			);
		}

		return $tabs;
	}

	/** Старий фолбек-режим: 3 фіксовані вкладки з data/products.json + data/images.json. */
	private function buildLegacyTabs($lang_id) {
		$staticById = array();
		foreach ((array)$this->readJson('data/products.json') as $sp) {
			if (isset($sp['id'])) {
				$staticById[$sp['id']] = $sp;
			}
		}

		$productsById = array();
		foreach ($this->model_catalog_product->getProducts(array('filter_status' => 1)) as $product) {
			if (!$product['status'] || (int)$product['quantity'] <= 0) {
				continue;
			}
			$extra = $staticById[$product['model']] ?? array();
			$productsById[$product['model']] = array(
				'id'      => $product['model'],
				'title'   => $product['name'],
				'volume'  => $product['tag'],
				'price'   => (float)$product['price'],
				'details' => $extra['details'] ?? null,
			);
		}

		$images = $this->fixImagePaths($this->readJson('data/images.json'));
		$tabsSetting = $this->config->get($this->code . '_tabs');

		$tabs = array();
		foreach ($this->tabDefs as $tabKey => $pid) {
			$product = $productsById[$pid] ?? null;
			if (!$product) {
				continue;
			}

			$legacyDetails = $product['details'] ?? array();
			$tabSetting = $tabsSetting[$tabKey] ?? array();

			$tabTitle = $this->localizedFromArray($tabSetting['tab_title'] ?? array(), $lang_id);
			if ($tabTitle === '') {
				$tabTitle = $this->uaLike($legacyDetails['tabTitle'] ?? ('Hydrophob ' . $tabKey), $lang_id);
			}

			$subtitle = $this->localizedFromArray($tabSetting['subtitle'] ?? array(), $lang_id);
			if ($subtitle === '') {
				$subtitle = $this->uaLike($legacyDetails['subtitle'] ?? '', $lang_id);
			}

			$blocksSetting = $tabSetting['blocks'] ?? null;
			$blocks = array();
			if (is_array($blocksSetting) && !empty($blocksSetting)) {
				foreach ($blocksSetting as $block) {
					$blocks[] = array(
						'title' => $this->localizedFromArray($block['title'] ?? array(), $lang_id),
						'html'  => $this->localizedFromArray($block['html'] ?? array(), $lang_id),
					);
				}
			} else {
				foreach (($legacyDetails['blocks'] ?? array()) as $block) {
					$blocks[] = array(
						'title' => $this->uaLike($block['title'] ?? '', $lang_id),
						'html'  => $this->uaLike($block['html'] ?? '', $lang_id),
					);
				}
			}

			$posterSetting = $tabSetting['poster'] ?? '';
			$poster = $posterSetting ? 'image/' . $posterSetting : ($images['infoBlock'][$tabKey]['poster'] ?? '');
			$video = !empty($tabSetting['video']) ? $tabSetting['video'] : ($images['infoBlock'][$tabKey]['video'] ?? '');
			$alt = $this->localizedFromArray($tabSetting['alt'] ?? array(), $lang_id);
			if ($alt === '') {
				$alt = $images['infoBlock'][$tabKey]['alt'] ?? '';
			}

			$tabs[] = array(
				'key'      => $tabKey,
				'id'       => $pid,
				'product'  => $product,
				'tabTitle' => $tabTitle,
				'subtitle' => $subtitle,
				'blocks'   => $blocks,
				'media'    => array('poster' => $poster, 'video' => $video, 'alt' => $alt),
			);
		}

		return $tabs;
	}

	private function staticProductByModel($model) {
		foreach ((array)$this->readJson('data/products.json') as $sp) {
			if (($sp['id'] ?? null) === $model) {
				return $sp;
			}
		}
		return array();
	}

	private function localizedFromArray($values, $language_id) {
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}
		return '';
	}

	/** Мультимовне поле {UA,RU,EN} чи звичайний рядок зі старого data/products.json -> поточна мова. */
	private function uaLike($field, $language_id) {
		if (is_array($field)) {
			$key = $this->legacyLangKey();
			return $field[$key] ?? ($field['UA'] ?? '');
		}
		return (string)$field;
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
}
