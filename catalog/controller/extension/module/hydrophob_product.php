<?php
/**
 * Секція "product" (слайдер каталогу) головної сторінки — content_top модуль.
 * Дані: живий каталог OpenCart (oc_product), тільки активні товари з наявністю.
 */
class ControllerExtensionModuleHydrophobProduct extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_product');
		$this->load->model('catalog/product');

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
