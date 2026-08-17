<?php
/**
 * Адмін-форма content_top модуля "hydrophob_hero" (hydrophob.net.ua).
 * Мультимовний заголовок (HTML), постер, відео, alt.
 */
class ControllerExtensionModuleHydrophobHero extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_hero';

	public function index() {
		$this->load->language('extension/module/hydrophob_hero');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting($this->code, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/hydrophob_hero', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/hydrophob_hero', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('placeholder.png', 200, 150);

		$data['status']     = $this->field('status', 1);
		$data['title_html'] = $this->field('title_html', array());
		$data['alt']        = $this->field('alt', '');
		$data['video']      = $this->field('video', '');
		$data['poster']     = $this->field('poster', '');

		$data['thumb_poster'] = $data['poster'] ? $this->model_tool_image->resize($data['poster'], 200, 150) : $data['placeholder'];

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_hero', $data));
	}

	/** POST (при валідаційній помилці) -> oc_setting -> дефолт. Ключ форми завжди <code>_<field>. */
	private function field($field, $default) {
		$key = $this->code . '_' . $field;
		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		}
		$value = $this->config->get($key);
		return $value !== null ? $value : $default;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_hero')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
