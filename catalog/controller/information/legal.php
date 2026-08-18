<?php
/**
 * Юридичні сторінки (privacy/returns/offer) hydrophob.net.ua.
 * Route: information/legal&page=privacy|returns|offer
 * Контент — data/legal.json (як у старому кореневому legal.php), хедер/футер — ті самі
 * партиали common/home/header.twig та common/home/footer.twig, що й на головній.
 */
class ControllerInformationLegal extends Controller {
	public function index() {
		$key = preg_replace('/[^a-z]/', '', (string)($this->request->get['page'] ?? ''));

		$legal = $this->readJson('data/legal.json');
		$page = $legal[$key] ?? null;

		if (!$page) {
			$this->response->redirect($this->url->link('common/home'));
			return;
		}

		$seo = $this->readJson('data/seo.json');
		$baseUrl = rtrim($seo['url'] ?? '', '/') . '/';

		$this->document->setTitle($page['title'] . ' — Hydrophob');
		$this->document->setDescription($page['title'] . ' інтернет-магазину Hydrophob.');

		$data['title'] = $page['title'];
		$data['content'] = $page['html'];
		$data['canonical'] = $baseUrl . $key;
		$data['home'] = $this->url->link('common/home');

		$data['header'] = $this->load->view('common/home/header', array('hydro_lang' => 'UA'));
		$data['footer'] = $this->load->view('common/home/footer', array());
		$data['asset_version'] = (string)@filemtime(DIR_APPLICATION . '../catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		$this->response->setOutput($this->load->view('information/legal', $data));
	}

	private function readJson($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}
}
