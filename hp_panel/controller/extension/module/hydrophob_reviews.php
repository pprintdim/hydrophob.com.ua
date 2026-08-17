<?php
/**
 * Адмін-форма content_top модуля "hydrophob_reviews" (hydrophob.net.ua).
 * Повторюваний список: постер (зображення) + відео + текст відгуку (мультимовно).
 */
class ControllerExtensionModuleHydrophobReviews extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_reviews';

	public function index() {
		$this->load->language('extension/module/hydrophob_reviews');
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
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/hydrophob_reviews', 'user_token=' . $this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/hydrophob_reviews', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('placeholder.png', 200, 150);

		$data['status']     = $this->field('status', 1);
		$data['title_html'] = $this->field('title_html', array());

		$items = $this->field('items', array());
		$data['items'] = array();
		foreach ($items as $key => $item) {
			$poster = $item['poster'] ?? '';
			$data['items'][$key] = array(
				'poster'       => $poster,
				'thumb_poster' => $poster ? $this->model_tool_image->resize($poster, 160, 120) : $data['placeholder'],
				'video'        => $item['video'] ?? '',
				'alt'          => $item['alt'] ?? array(),
				'message'      => $item['message'] ?? array(),
			);
		}

		$data['next_index'] = 0;
		foreach ($data['items'] as $key => $item) {
			if ((int)$key >= $data['next_index']) {
				$data['next_index'] = (int)$key + 1;
			}
		}

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_reviews', $data));
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
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_reviews')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
