<?php
class ControllerExtensionModuleCatalogApi extends Controller {

	/** мапа мова OpenCart -> код фронтового перемикача (UA/RU/EN) */
	private $langKey = array(1 => 'EN', 2 => 'UA', 3 => 'RU');

	public function products() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		// Базовий (мовонезалежний) список товарів: model/price/image/quantity/status/sort_order.
		// 'name'/'description'/'tag'/'meta_*' з моделі НЕ використовуємо — там лише мова поточної сесії,
		// їх тягнемо власним запитом одразу по всіх мовах нижче.
		$products = $this->model_catalog_product->getProducts(array('filter_status' => 1));

		$this->response->addHeader('Content-Type: application/json');

		if (!$products) {
			$this->response->setOutput(json_encode(array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$productIds = array_map('intval', array_keys($products));
		$idsSql = implode(',', $productIds);

		// Категорії (усі, для назв) — один запит по всіх мовах.
		$categoryNames = array();
		foreach ($this->db->query("SELECT category_id, language_id, name FROM " . DB_PREFIX . "category_description")->rows as $row) {
			if (!isset($this->langKey[$row['language_id']])) {
				continue;
			}
			$categoryNames[$row['category_id']][$this->langKey[$row['language_id']]] = $row['name'];
		}

		// Прив'язка товар -> категорії (перша категорія кожного) — один запит на всі товари.
		$productCategories = array();
		foreach ($this->db->query("SELECT product_id, category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (" . $idsSql . ")")->rows as $row) {
			$productCategories[$row['product_id']][] = $row['category_id'];
		}

		// Опис товару (name/description/tag/meta_description) по всіх мовах — один запит на всі товари.
		$descByProduct = array();
		foreach ($this->db->query("SELECT product_id, language_id, name, description, tag, meta_description FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . $idsSql . ")")->rows as $row) {
			if (!isset($this->langKey[$row['language_id']])) {
				continue;
			}
			$descByProduct[$row['product_id']][$this->langKey[$row['language_id']]] = $row;
		}

		// Атрибути товару (усі мови, з назвою атрибута), збережено порядок sort_order — один запит на всі товари.
		$attrsByProduct = array();
		$attrRows = $this->db->query("
			SELECT pa.product_id, pa.attribute_id, pa.language_id, pa.text, ad.name, a.sort_order
			FROM " . DB_PREFIX . "product_attribute pa
			LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id)
			LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = pa.language_id)
			WHERE pa.product_id IN (" . $idsSql . ")
			ORDER BY pa.product_id, a.sort_order, pa.attribute_id
		")->rows;
		foreach ($attrRows as $row) {
			if (!isset($this->langKey[$row['language_id']])) {
				continue;
			}
			$lang = $this->langKey[$row['language_id']];
			$pid  = $row['product_id'];
			$aid  = $row['attribute_id'];
			if (!isset($attrsByProduct[$pid][$aid])) {
				$attrsByProduct[$pid][$aid] = array('name' => array(), 'value' => array());
			}
			$attrsByProduct[$pid][$aid]['name'][$lang]  = $row['name'];
			$attrsByProduct[$pid][$aid]['value'][$lang] = $row['text'];
		}

		// Редакційний контент (details.tabTitle/subtitle/blocks, attrs), якого ще нема в схемі OpenCart —
		// доповнюємо ним живі дані з БД зі старого data/products.json, за ключем model.
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
			$pid   = $product['product_id'];
			$extra = $staticById[$product['model']] ?? array();

			$langRows = $descByProduct[$pid] ?? array();
			$title = $descr = $descriptionHtml = $tag = array();
			foreach (array('UA', 'RU', 'EN') as $k) {
				$row  = $langRows[$k] ?? null;
				$html = $row ? $row['description'] : '';
				// Опис товару в БД буває html-entity-encoded (частина товарів імпортована з іншої OC-бази) —
				// декодуємо тільки якщо треба, щоб не зіпсувати вже "сирий" HTML (prom-імпорт).
				if ($html && strpos($html, '&lt;') !== false) {
					$html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
				}
				$title[$k]           = $row ? $row['name'] : '';
				$descriptionHtml[$k] = $html;
				$descr[$k]           = ($row && $row['meta_description']) ? $row['meta_description'] : mb_substr(strip_tags($html), 0, 200);
				$tag[$k]              = $row ? $row['tag'] : '';
			}

			// Категорія товару — перша з прив'язаних, назва мультимовна.
			$categoryIds = $productCategories[$pid] ?? array();
			$categoryId  = $categoryIds[0] ?? null;
			$categoryName = ($categoryId && isset($categoryNames[$categoryId]))
				? $categoryNames[$categoryId]
				: array('UA' => '', 'RU' => '', 'EN' => '');

			// Атрибути — реальні з БД (oc_product_attribute), мультимовні, порядок за sort_order
			// (включно з "Виробником"). Фолбек — старий data/products.json (там значення лише UA,
			// тому дублюємо в усі мови, щоб не показати порожнечу при перемиканні).
			$attrs = array();
			foreach (($attrsByProduct[$pid] ?? array()) as $attribute) {
				$attrs[] = array('name' => $attribute['name'], 'value' => $attribute['value']);
			}
			if (!$attrs && !empty($extra['attrs'])) {
				foreach ($extra['attrs'] as $attribute) {
					$attrs[] = array(
						'name'  => array('UA' => $attribute['name'], 'RU' => $attribute['name'], 'EN' => $attribute['name']),
						'value' => array('UA' => $attribute['value'], 'RU' => $attribute['value'], 'EN' => $attribute['value']),
					);
				}
			}

			// Об'єм — з атрибута "Об'єм" (мультимовний), фолбек — tag.
			$volumeAttr = null;
			foreach ($attrs as $attribute) {
				if (($attribute['name']['UA'] ?? '') === "Об'єм") {
					$volumeAttr = $attribute['value'];
					break;
				}
			}
			$volume = array();
			foreach (array('UA', 'RU', 'EN') as $k) {
				$volume[$k] = $volumeAttr[$k] ?? ($tag[$k] ?: '');
			}

			// Ленд-контент (tabTitle/subtitle/blocks) — тепер з oc_product_details (усі 3 мови),
			// фолбек — старий data/products.json.
			$details = $this->model_catalog_product->getProductDetailsMultilang($pid);
			if ($details === null) {
				$details = $extra['details'] ?? null;
			}

			$data[] = array(
				'id'              => $product['model'],
				'category'        => $categoryName,
				'categoryId'      => $categoryId,
				'title'           => $title,
				'descr'           => $descr,
				'descriptionHtml' => $descriptionHtml,
				'volume'          => $volume,
				'price'           => (float)$product['price'],
				'image'           => $product['image'] ? 'image/' . $product['image'] : '',
				'available'       => (bool)$product['status'] && $product['quantity'] > 0,
				'details'         => $details,
				'attrs'           => $attrs,
			);
		}

		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function categories() {
		$this->load->model('catalog/category');

		// Мультимовні name/description для всіх категорій — один запит.
		$byCategory = array();
		foreach ($this->db->query("SELECT category_id, language_id, name, description FROM " . DB_PREFIX . "category_description")->rows as $row) {
			if (!isset($this->langKey[$row['language_id']])) {
				continue;
			}
			$byCategory[$row['category_id']][$this->langKey[$row['language_id']]] = $row;
		}

		$data = array();
		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$categoryId = $category['category_id'];
			$langRows   = $byCategory[$categoryId] ?? array();

			$name = $description = array();
			foreach (array('UA', 'RU', 'EN') as $k) {
				$row  = $langRows[$k] ?? null;
				$desc = $row ? ($row['description'] ?? '') : '';
				if ($desc && strpos($desc, '&lt;') !== false) {
					$desc = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');
				}
				$name[$k]        = $row ? $row['name'] : '';
				$description[$k] = $desc;
			}

			$data[] = array(
				'id'          => $categoryId,
				'name'        => $name,
				'description' => $description,
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
