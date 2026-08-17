<?php
/**
 * Адмін-форма content_top модуля "hydrophob_about" (hydrophob.net.ua).
 * Мультимовні поля: name, title, descr, read_more.
 */
class ControllerExtensionModuleHydrophobAbout extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_about';

	public function index() {
		$this->load->language('extension/module/hydrophob_about');
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
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/hydrophob_about', 'user_token=' . $this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/hydrophob_about', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['status']    = $this->field('status', 1);
		$data['name']      = $this->field('name', array());
		$data['title']     = $this->field('title', array());
		$data['descr']     = $this->field('descr', array());
		$data['read_more'] = $this->field('read_more', array());

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_about', $data));
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
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_about')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		// перевірка обовʼязкових укр (дефолтна мова) полів
		if (isset($this->request->post['module_hydrophob_about_title']) && is_array($this->request->post['module_hydrophob_about_title']) && trim(strip_tags((string)($this->request->post['module_hydrophob_about_title'][2] ?? ''))) === '') {
			$this->error['warning'] = 'Поле «Заголовок» обовʼязкове українською (мова за замовчуванням).';
		}
		if (isset($this->request->post['module_hydrophob_about_descr']) && is_array($this->request->post['module_hydrophob_about_descr']) && trim(strip_tags((string)($this->request->post['module_hydrophob_about_descr'][2] ?? ''))) === '') {
			$this->error['warning'] = 'Поле «Опис» обовʼязкове українською (мова за замовчуванням).';
		}

		return !$this->error;
	}
}
