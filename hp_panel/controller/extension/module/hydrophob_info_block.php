<?php
/**
 * Адмін-форма content_top модуля "hydrophob_info_block" (hydrophob.net.ua).
 * 3 фіксовані вкладки (Automobile/Textile/Industrial, прив'язані до конкретних товарів каталогу):
 * назва вкладки + підзаголовок + медіа (постер/відео/alt) + повторювані блоки опису (назва+контент).
 */
class ControllerExtensionModuleHydrophobInfoBlock extends Controller {
	private $error = array();
	private $code = 'module_hydrophob_info_block';

	private $tabKeys = array('Automobile', 'Textile', 'Industrial');

	public function index() {
		$this->load->language('extension/module/hydrophob_info_block');
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
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/hydrophob_info_block', 'user_token=' . $this->session->data['user_token'], true));

		$data['action'] = $this->url->link('extension/module/hydrophob_info_block', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('placeholder.png', 200, 150);

		$data['status'] = $this->field('status', 1);

		$tabsPost = $this->field('tabs', array());
		$data['tabs'] = array();
		foreach ($this->tabKeys as $tabKey) {
			$tab = $tabsPost[$tabKey] ?? array();
			$poster = $tab['poster'] ?? '';

			$blocks = array();
			foreach (($tab['blocks'] ?? array()) as $bKey => $block) {
				$blocks[$bKey] = array(
					'title' => $block['title'] ?? array(),
					'html'  => $block['html'] ?? array(),
				);
			}

			$nextBlockIndex = 0;
			foreach ($blocks as $bKey => $block) {
				if ((int)$bKey >= $nextBlockIndex) {
					$nextBlockIndex = (int)$bKey + 1;
				}
			}

			$data['tabs'][$tabKey] = array(
				'key'             => $tabKey,
				'label'           => $this->language->get('tab_' . $tabKey),
				'tab_title'       => $tab['tab_title'] ?? array(),
				'subtitle'        => $tab['subtitle'] ?? array(),
				'poster'          => $poster,
				'thumb_poster'    => $poster ? $this->model_tool_image->resize($poster, 160, 120) : $data['placeholder'],
				'video'           => $tab['video'] ?? '',
				'alt'             => $tab['alt'] ?? array(),
				'blocks'          => $blocks,
				'next_block_index' => $nextBlockIndex,
			);
		}

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/hydrophob_info_block', $data));
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
		if (!$this->user->hasPermission('modify', 'extension/module/hydrophob_info_block')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
