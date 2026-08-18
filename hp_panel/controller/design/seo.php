<?php
/**
 * Design → SEO: метатеги головної (title/description/keywords ×3 мови, data/seo.json)
 * + аналітика (GA4/Ads, .env). Ленд односторінковий — цього достатньо.
 */
class ControllerDesignSeo extends Controller {
	private $error = array();
	private $langs = array('UA', 'RU', 'EN');

	public function index() {
		$this->load->language('design/seo');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->saveSettings($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('design/seo', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

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
			'href' => $this->url->link('design/seo', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('design/seo', 'user_token=' . $this->session->data['user_token'], true);

		$seo = $this->readJson($this->seoFile());

		$data['langs'] = $this->langs;
		$data['pages'] = array();

		// три сторінки ленду: головна (meta), чекаут і 404 (metaPages)
		foreach (array('home', 'checkout', 'error') as $page) {
			foreach ($this->langs as $lang) {
				$src = $page === 'home' ? ($seo['meta'][$lang] ?? array()) : ($seo['metaPages'][$page][$lang] ?? array());
				$data['pages'][$page][$lang] = array(
					'title'       => $src['title'] ?? '',
					'description' => $src['description'] ?? '',
					'keywords'    => $src['keywords'] ?? '',
				);
			}
		}

		$data['ga4_id'] = (string)$this->config->get('config_analytics_ga4');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/seo', $data));
	}

	private function validateForm() {
		if (!$this->user->hasPermission('modify', 'design/seo')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// укр (дефолтна мова) title головної обовʼязковий
		$title = trim((string)($this->request->post['pages']['home']['UA']['title'] ?? ''));

		if ($title === '') {
			$this->error['warning'] = $this->language->get('error_title');
		}

		return !$this->error;
	}

	private function saveSettings($post) {
		// метатеги -> data/seo.json
		$file = $this->seoFile();
		$seo = $this->readJson($file);

		foreach (array('home', 'checkout', 'error') as $page) {
			foreach ($this->langs as $lang) {
				$row = array(
					'title'       => trim((string)($post['pages'][$page][$lang]['title'] ?? '')),
					'description' => trim((string)($post['pages'][$page][$lang]['description'] ?? '')),
					'keywords'    => trim((string)($post['pages'][$page][$lang]['keywords'] ?? '')),
				);
				if ($page === 'home') {
					$seo['meta'][$lang] = array_merge($seo['meta'][$lang] ?? array(), $row);
				} else {
					$seo['metaPages'][$page][$lang] = $row;
				}
			}
		}

		file_put_contents($file, json_encode($seo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

		// аналітика -> oc_setting (по-опенкартівськи); режим на фронті сам: є ID — production
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSettingValue('config', 'config_analytics_ga4', trim((string)($post['ga4_id'] ?? '')));
	}

	private function seoFile() {
		return DIR_APPLICATION . '../data/seo.json';
	}

	private function readJson($file) {
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}


}
