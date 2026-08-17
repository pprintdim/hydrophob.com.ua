<?php
class ControllerExtensionModuleCatalogApi extends Controller {
	public function products() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$categories = array();
		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$categories[$category['category_id']] = $category['name'];
		}

		$products = $this->model_catalog_product->getProducts(array('filter_status' => 1));

		$data = array();

		foreach ($products as $product) {
			$categoryNames = array();
			foreach ($this->model_catalog_product->getCategories($product['product_id']) as $pc) {
				if (isset($categories[$pc['category_id']])) {
					$categoryNames[] = $categories[$pc['category_id']];
				}
			}

			$data[] = array(
				'id'          => $product['model'],
				'category'    => $categoryNames[0] ?? '',
				'title'       => $product['name'],
				'descr'       => strip_tags($product['description']),
				'descriptionHtml' => $product['description'],
				'volume'      => $product['tag'],
				'price'       => (float)$product['price'],
				'image'       => $product['image'] ? 'image/' . $product['image'] : '',
				'available'   => (bool)$product['status'] && $product['quantity'] > 0,
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function categories() {
		$this->load->model('catalog/category');

		$data = array();
		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$data[] = array(
				'id'   => $category['category_id'],
				'name' => $category['name'],
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
