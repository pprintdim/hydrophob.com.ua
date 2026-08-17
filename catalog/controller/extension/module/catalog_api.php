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

		// Редакційний контент (details.tabTitle/subtitle/blocks, attrs), якого ще нема в схемі OpenCart —
		// доповнюємо ним живі дані з БД зі старого data/products.json, за ключем model/id.
		$staticById = array();
		$staticFile = DIR_APPLICATION . '../data/products.json';
		if (is_file($staticFile)) {
			$staticProducts = json_decode(file_get_contents($staticFile), true);
			if (is_array($staticProducts)) {
				foreach ($staticProducts as $sp) {
					if (isset($sp['id'])) {
						$staticById[$sp['id']] = $sp;
					}
				}
			}
		}

		$data = array();

		foreach ($products as $product) {
			$categoryIds = array();
			$categoryNames = array();
			foreach ($this->model_catalog_product->getCategories($product['product_id']) as $pc) {
				$categoryIds[] = $pc['category_id'];
				if (isset($categories[$pc['category_id']])) {
					$categoryNames[] = $categories[$pc['category_id']];
				}
			}

			$extra = $staticById[$product['model']] ?? array();

			// Опис товару в БД буває html-entity-encoded (частина товарів імпортована з іншої OC-бази) —
			// декодуємо тільки якщо треба, щоб не зіпсувати вже "сирий" HTML (prom-імпорт).
			$descriptionHtml = $product['description'];
			if (strpos($descriptionHtml, '&lt;') !== false) {
				$descriptionHtml = html_entity_decode($descriptionHtml, ENT_QUOTES, 'UTF-8');
			}

			// Атрибути — реальні з БД (oc_product_attribute), порядок за sort_order; "Виробник" не показуємо.
			// Фолбек — старий data/products.json (теж без "Виробника"), якщо в БД атрибутів нема.
			$attrs = array();
			foreach ($this->model_catalog_product->getProductAttributes($product['product_id']) as $group) {
				foreach ($group['attribute'] as $attribute) {
					if ($attribute['name'] === 'Виробник') {
						continue;
					}
					$attrs[] = array('name' => $attribute['name'], 'value' => $attribute['text']);
				}
			}
			if (!$attrs) {
				foreach (($extra['attrs'] ?? array()) as $attribute) {
					if (($attribute['name'] ?? '') === 'Виробник') {
						continue;
					}
					$attrs[] = $attribute;
				}
			}

			// Ленд-контент (tabTitle/subtitle/blocks) — тепер з oc_product_details (усі 3 мови),
			// фолбек — старий data/products.json.
			$details = $this->model_catalog_product->getProductDetailsMultilang($product['product_id']);
			if ($details === null) {
				$details = $extra['details'] ?? null;
			}

			$data[] = array(
				'id'          => $product['model'],
				'category'    => $categoryNames[0] ?? '',
				'categoryId'  => $categoryIds[0] ?? null,
				'title'       => $product['name'],
				'descr'       => $product['meta_description'] ?: mb_substr(strip_tags($descriptionHtml), 0, 200),
				'descriptionHtml' => $descriptionHtml,
				'volume'      => $product['tag'],
				'price'       => (float)$product['price'],
				'image'       => $product['image'] ? 'image/' . $product['image'] : '',
				'available'   => (bool)$product['status'] && $product['quantity'] > 0,
				'details'     => $details,
				'attrs'       => $attrs,
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function categories() {
		$this->load->model('catalog/category');

		$data = array();
		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$description = $category['description'] ?? '';
			if (strpos($description, '&lt;') !== false) {
				$description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
			}

			$data[] = array(
				'id'          => $category['category_id'],
				'name'        => $category['name'],
				'description' => $description,
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
