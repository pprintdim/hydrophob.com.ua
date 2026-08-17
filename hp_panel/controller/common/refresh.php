<?php
// Quick cache / modification refresh from the admin header. Returns the
// user to the exact page the button was clicked on (via session-stored
// referer, since the stock modification refresh can only redirect to a
// bare route).
class ControllerCommonRefresh extends Controller {
	public function index() {
		if (isset($this->request->server['HTTP_REFERER']) && strpos($this->request->server['HTTP_REFERER'], 'route=common/refresh') === false) {
			$this->session->data['refresh_return'] = $this->request->server['HTTP_REFERER'];
		}

		$what = isset($this->request->get['what']) ? $this->request->get['what'] : 'all';

		if ($what == 'cache' || $what == 'all') {
			$files = glob(DIR_CACHE . 'cache.*');

			if ($files) {
				foreach ($files as $file) {
					if (is_file($file)) {
						unlink($file);
					}
				}
			}
		}

		if (($what == 'mod' || $what == 'all') && $this->user->hasPermission('modify', 'marketplace/modification')) {
			// The stock refresh rebuilds DIR_MODIFICATION and then redirects
			// to $data['redirect'] — we point it back at our back() action.
			$this->load->controller('marketplace/modification/refresh', array('redirect' => 'common/refresh/back'));
		}

		$this->back();
	}

	public function back() {
		if (!empty($this->session->data['refresh_return'])) {
			$this->response->redirect($this->session->data['refresh_return']);
		} else {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}
	}
}
