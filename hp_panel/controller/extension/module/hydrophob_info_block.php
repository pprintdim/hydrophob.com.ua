<?php
/**
 * Адмін-форма content_top модуля "hydrophob_info_block" (hydrophob.net.ua).
 * Повторюваний список рядів "категорія -> товар": селект категорії, селект товару
 * (фільтрується на JS за обраною категорією), відео (шлях до файлу) і постер (image-picker).
 * Ленд-контент вкладки (tabTitle/subtitle/blocks) більше не редагується тут — він живе на
 * товарі (закладка "Ленд", catalog/product).
 */
class ControllerExtensionModuleHydrophobInfoBlock extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_info_block';

	public function index() {
		$this->load->language('extension/module/hydrophob_info_block');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting($this->code, $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/hydrophob_info_block', 'user_token=' . $this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/hydrophob_info_block', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('placeholder.png', 200, 150);

		$data['status'] = $this->field('status', 1);

		// Категорії (укр. назви, поточна мова адмінки) для селекта.
		// Всі товари одним списком; категорія показується в дужках (вибирається лише товар)
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$catNames = array();
		foreach ($this->model_catalog_category->getCategories(array()) as $category) {
			$catNames[$category['category_id']] = $category['name'];
		}
		$data['products'] = array();
		foreach ($this->model_catalog_product->getProducts(array()) as $product) {
			$catIds = $this->model_catalog_product->getProductCategories($product['product_id']);
			$catLabel = ($catIds && isset($catNames[$catIds[0]])) ? $catNames[$catIds[0]] : '';
			$data['products'][] = array(
				'product_id' => (int)$product['product_id'],
				'name'       => $product['name'] . ($catLabel ? ' — ' . $catLabel : ''),
			);
		}

		$rowsPost = $this->field('rows', array());
		$data['rows'] = array();
		foreach ($rowsPost as $rKey => $row) {
			$poster = $row['poster'] ?? '';
			$data['rows'][] = array(
				'index'        => $rKey,
				'category_id'  => $row['category_id'] ?? '',
				'product_id'   => $row['product_id'] ?? '',
				'video'        => $row['video'] ?? '',
				'poster'       => $poster,
				'thumb_poster' => $poster ? $this->model_tool_image->resize($poster, 160, 120) : $data['placeholder'],
			);
		}

		if (!$data['rows']) {
			$data['rows'][] = array(
				'index'        => 0,
				'category_id'  => '',
				'product_id'   => '',
				'video'        => '',
				'poster'       => '',
				'thumb_poster' => $data['placeholder'],
			);
		}

		$data['next_row_index'] = count($data['rows']);

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_info_block', $data));
	}

	private function field($field, $default) {
		$key = $this->code . '_' . $field;
		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		}
		$value = $this->config->get($key);
		return $value !== null ? $value : $default;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_info_block')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		// перевірка обовʼязкових укр (дефолтна мова) полів
		if (!empty($this->request->post['module_hydrophob_info_block_rows']) && is_array($this->request->post['module_hydrophob_info_block_rows'])) {
			foreach ($this->request->post['module_hydrophob_info_block_rows'] as $row) {
				if (empty($row['category_id']) || empty($row['product_id'])) {
					$this->error['warning'] = 'У кожному рядку виберіть категорію і товар.';
				}
			}
		}

		return !$this->error;
	}
}
