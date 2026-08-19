<?php
/**
 * Design → Меню: пункти меню хедера і двох колонок футера — повторювачі
 * з мультимовними назвами та посиланнями (якорі #секція, шляхи /privacy, зовнішні URL).
 * Зберігається в oc_setting (code=menu_hydrophob), фронт читає через model design/hydro_menu.
 */
class ControllerDesignMenu extends Controller {
	private $error = array();
	private $menus = array('header', 'footer_info', 'footer_products');

	public function index() {
		$this->load->language('design/menu_hydro');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->saveMenus($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('design/menu', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['error_warning'] = $this->error['warning'] ?? '';

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/menu', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('design/menu', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['menus'] = array();

		foreach ($this->menus as $menu) {
			$items = $this->config->get('menu_hydrophob_' . $menu);
			$data['menus'][$menu] = array(
				'label' => $this->language->get('text_menu_' . $menu),
				'items' => is_array($items) ? array_values($items) : array(),
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/menu', $data));
	}

	private function validateForm() {
		if (!$this->user->hasPermission('modify', 'design/menu')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->menus as $menu) {
			foreach ((array)($this->request->post['menus'][$menu] ?? array()) as $item) {
				$link = trim((string)($item['link'] ?? ''));
				$uk = trim((string)($item['label'][2] ?? ''));
				// повністю порожні ряди тихо викидаються при збереженні
				if (($link === '') !== ($uk === '')) {
					$this->error['warning'] = $this->language->get('error_item');
				}
			}
		}

		return !$this->error;
	}

	private function saveMenus($post) {
		$settings = array();

		foreach ($this->menus as $menu) {
			$rows = array();
			foreach ((array)($post['menus'][$menu] ?? array()) as $item) {
				$link = trim((string)($item['link'] ?? ''));
				$uk = trim((string)($item['label'][2] ?? ''));
				if ($link === '' || $uk === '') {
					continue;
				}
				$labels = array();
				foreach ((array)($item['label'] ?? array()) as $langId => $label) {
					$labels[(int)$langId] = trim((string)$label);
				}
				$rows[] = array(
					'link'  => $link,
					'tab'   => trim((string)($item['tab'] ?? '')),
					'label' => $labels,
				);
			}
			$settings['menu_hydrophob_' . $menu] = $rows;
		}

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('menu_hydrophob', $settings);
	}
}
