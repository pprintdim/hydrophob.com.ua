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

		$metaTitle = ($page['metaTitle'] ?? '') !== '' ? $page['metaTitle'] : $page['title'] . ' — Hydrophob';
		$metaDescription = ($page['metaDescription'] ?? '') !== '' ? $page['metaDescription'] : $page['title'] . ' інтернет-магазину Hydrophob.';

		$this->document->setTitle($metaTitle);
		$this->document->setDescription($metaDescription);

		$data['meta_title'] = $metaTitle;
		$data['meta_description'] = $metaDescription;
		$data['meta_keywords'] = $page['metaKeywords'] ?? '';
		$data['og_image'] = $baseUrl . ltrim($seo['ogImage'] ?? 'img/og-image.jpg', '/');

		$data['title'] = $page['title'];
		$data['content'] = $page['html'];
		$data['canonical'] = $baseUrl . $key;
		$data['home'] = $this->url->link('common/home');

		// Фавікон — з налаштувань, як на решті сторінок
		$configIcon = (string)$this->config->get('config_icon');
		$data['favicon'] = $configIcon !== '' ? 'image/' . $configIcon : 'image/hydrophob/favicon.png';

		// Модулі з Макетів (route information/legal, позиція content_top)
		$data['content_top'] = $this->load->controller('common/content_top');

		$this->load->model('design/hydro_menu');
		$menuVars = array(
			'menu_header'          => $this->model_design_hydro_menu->getMenu('header', false),
			'menu_footer_info'     => $this->model_design_hydro_menu->getMenu('footer_info', false),
			'menu_footer_products' => $this->model_design_hydro_menu->getMenu('footer_products', false),
		);
		$data['header'] = $this->load->view('common/home/header', array_merge(array('hydro_lang' => 'UA'), $menuVars));
		$data['footer'] = $this->load->view('common/home/footer', $menuVars);
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
