<?php
/**
 * Адмін-форма content_top модуля "hydrophob_faq" (hydrophob.net.ua).
 * Повторюваний список питання/відповідь, мультимовно.
 */
class ControllerExtensionModuleHydrophobFaq extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_faq';

	public function index() {
		$this->load->language('extension/module/hydrophob_faq');
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
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/hydrophob_faq', 'user_token=' . $this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/hydrophob_faq', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['status']     = $this->field('status', 1);
		$data['title_html'] = $this->field('title_html', array());
		$data['items']      = $this->field('items', array());

		$data['next_index'] = 0;
		foreach ($data['items'] as $key => $item) {
			if ((int)$key >= $data['next_index']) {
				$data['next_index'] = (int)$key + 1;
			}
		}

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_faq', $data));
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
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_faq')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		// перевірка обовʼязкових укр (дефолтна мова) полів
		if (isset($this->request->post['module_hydrophob_faq_title_html']) && is_array($this->request->post['module_hydrophob_faq_title_html']) && trim(strip_tags((string)($this->request->post['module_hydrophob_faq_title_html'][2] ?? ''))) === '') {
			$this->error['warning'] = 'Поле «Заголовок» обовʼязкове українською (мова за замовчуванням).';
		}
		if (!empty($this->request->post['module_hydrophob_faq_items']) && is_array($this->request->post['module_hydrophob_faq_items'])) {
			foreach ($this->request->post['module_hydrophob_faq_items'] as $row) {
				if (trim(strip_tags((string)($row['question'][2] ?? ''))) === '') {
					$this->error['warning'] = 'У кожному рядку поле «Питання» обовʼязкове українською.';
				}
				if (trim(strip_tags((string)($row['answer'][2] ?? ''))) === '') {
					$this->error['warning'] = 'У кожному рядку поле «Відповідь» обовʼязкове українською.';
				}
			}
		}

		return !$this->error;
	}
}
