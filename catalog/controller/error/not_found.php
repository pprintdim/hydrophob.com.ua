<?php
class ControllerErrorNotFound extends Controller {
	public function index() {
		$this->load->language('error/not_found');

		// Метатеги — з Design → SEO (data/seo.json, metaPages.error)
		$seoFile = DIR_APPLICATION . '../data/seo.json';
		$seoJson = is_file($seoFile) ? (json_decode(file_get_contents($seoFile), true) ?: array()) : array();
		$langCodeMap = array(1 => 'EN', 2 => 'UA', 3 => 'RU');
		$metaLang = $langCodeMap[(int)$this->config->get('config_language_id')] ?? 'UA';
		$pageMeta = $seoJson['metaPages']['error'][$metaLang] ?? array();

		$this->document->setTitle(($pageMeta['title'] ?? '') !== '' ? $pageMeta['title'] : $this->language->get('heading_title'));

		if (!empty($pageMeta['description'])) {
			$this->document->setDescription($pageMeta['description']);
		}

		if (!empty($pageMeta['keywords'])) {
			$this->document->setKeywords($pageMeta['keywords']);
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['route'])) {
			$url_data = $this->request->get;

			unset($url_data['_route_']);

			$route = $url_data['route'];

			unset($url_data['route']);

			$url = '';

			if ($url_data) {
				$url = '&' . urldecode(http_build_query($url_data, '', '&'));
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link($route, $url, $this->request->server['HTTPS'])
			);
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}