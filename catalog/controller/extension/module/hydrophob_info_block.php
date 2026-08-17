<?php
/**
 * Секція "infoBlock" (лінійка продукції, 3 вкладки) головної сторінки — content_top модуль.
 * Живий товар з БД (id/назва/ціна) доповнений редакційним контентом (details.blocks/tabTitle/subtitle)
 * з data/products.json, якого ще нема в схемі OpenCart — так само, як робив старий common/home.php.
 */
class ControllerExtensionModuleHydrophobInfoBlock extends Controller {
	private $tabDefs = array(
		'Automobile' => 'p2524537265',
		'Textile'    => 'p2523866690',
		'Industrial' => 'p2524531368',
	);

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_info_block');
		$this->load->model('catalog/product');

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

		$tabs = array();
		foreach ($this->tabDefs as $tabKey => $pid) {
			$product = $productsById[$pid] ?? null;
			if (!$product) {
				continue;
			}

			$details = $product['details'] ?? array();
			$blocks = array();
			foreach (($details['blocks'] ?? array()) as $block) {
				$blocks[] = array(
					'title' => $this->uaValue($block['title'] ?? ''),
					'html'  => $this->uaValue($block['html'] ?? ''),
				);
			}

			$tabs[] = array(
				'key'      => $tabKey,
				'id'       => $pid,
				'product'  => $product,
				'tabTitle' => $this->uaValue($details['tabTitle'] ?? ('Hydrophob ' . $tabKey)),
				'subtitle' => $this->uaValue($details['subtitle'] ?? ''),
				'blocks'   => $blocks,
				'media'    => array(
					'poster' => $images['infoBlock'][$tabKey]['poster'] ?? '',
					'video'  => $images['infoBlock'][$tabKey]['video'] ?? '',
					'alt'    => $images['infoBlock'][$tabKey]['alt'] ?? '',
				),
			);
		}

		$data['info_tabs'] = $tabs;

		return $this->load->view('extension/module/hydrophob_info_block', $data);
	}

	/** Мультимовне поле {UA,RU,EN} чи звичайний рядок -> UA (поточна SSR-мова). */
	private function uaValue($field) {
		if (is_array($field)) {
			return $field['UA'] ?? '';
		}
		return (string)$field;
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
