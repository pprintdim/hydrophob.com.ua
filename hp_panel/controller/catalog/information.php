<?php
/**
 * Каталог → Інформація: редагування інформаційних сторінок ленду
 * (політика конфіденційності, повернення, оферта) — data/legal.json,
 * звідки їх рендерить information/legal на фронті (/privacy, /returns, /offer).
 */
class ControllerCatalogInformation extends Controller {
	private $error = array();
	private $pages = array('privacy', 'returns', 'offer');

	public function index() {
		$this->load->language('catalog/information');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->savePages($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'], true));
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
			'href' => $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'], true);

		$legal = $this->readJson($this->legalFile());

		$data['pages'] = array();

		foreach ($this->pages as $page) {
			$data['pages'][$page] = array(
				'label'            => $this->language->get('text_page_' . $page),
				'url'              => '/' . $page,
				'title'            => $legal[$page]['title'] ?? '',
				'html'             => $legal[$page]['html'] ?? '',
				'meta_title'       => $legal[$page]['metaTitle'] ?? '',
				'meta_description' => $legal[$page]['metaDescription'] ?? '',
				'meta_keywords'    => $legal[$page]['metaKeywords'] ?? '',
			);
		}

		$data['summernote'] = 'uk-UA';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/information', $data));
	}

	private function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/information')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->pages as $page) {
			if (trim((string)($this->request->post['pages'][$page]['title'] ?? '')) === '') {
				$this->error['warning'] = $this->language->get('error_title');
			}
		}

		return !$this->error;
	}

	private function savePages($post) {
		$file = $this->legalFile();
		$legal = $this->readJson($file);

		foreach ($this->pages as $page) {
			$legal[$page]['title'] = trim((string)($post['pages'][$page]['title'] ?? ''));
			$legal[$page]['html'] = (string)($post['pages'][$page]['html'] ?? '');
			$legal[$page]['metaTitle'] = trim((string)($post['pages'][$page]['meta_title'] ?? ''));
			$legal[$page]['metaDescription'] = trim((string)($post['pages'][$page]['meta_description'] ?? ''));
			$legal[$page]['metaKeywords'] = trim((string)($post['pages'][$page]['meta_keywords'] ?? ''));
		}

		file_put_contents($file, json_encode($legal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}

	private function legalFile() {
		return DIR_APPLICATION . '../data/legal.json';
	}

	private function readJson($file) {
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}
}
