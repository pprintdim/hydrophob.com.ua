<?php
/**
 * Секція "product" (слайдер каталогу) головної сторінки — content_top модуль.
 * Товари — з живого каталогу OpenCart (oc_product). Заголовок блоку — oc_setting (module_hydrophob_product_name).
 */
class ControllerExtensionModuleHydrophobProduct extends Controller {
	private $code = 'module_hydrophob_product';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_product');
		$this->load->model('catalog/product');

		$lang_id = (int)$this->config->get('config_language_id');

		$name_setting = $this->config->get($this->code . '_name');
		$data['name'] = (is_array($name_setting) && !empty($name_setting[$lang_id])) ? $name_setting[$lang_id] : 'Hydrophob';

		$products = array();

		foreach ($this->model_catalog_product->getProducts(array('filter_status' => 1)) as $product) {
			if (!$product['status'] || (int)$product['quantity'] <= 0) {
				continue;
			}

			$products[] = array(
				'id'     => $product['model'],
				'image'  => $product['image'] ? 'image/' . $product['image'] : '',
				'title'  => $product['name'],
				'descr'  => strip_tags($product['description']),
				'volume' => $product['tag'],
				'price'  => (float)$product['price'],
			);
		}

		$data['products'] = $products;

		return $this->load->view('extension/module/hydrophob_product', $data);
	}
}
