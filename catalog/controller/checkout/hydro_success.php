<?php
/**
 * Сторінка успішного замовлення hydrophob.net.ua.
 * Route: checkout/hydro_success&token=...
 * Відкривається ОДИН раз за унікальним токеном (api/storage/orders/<token>.json), повторний
 * візит чи невалідний токен -> 404.
 */
class ControllerCheckoutHydroSuccess extends Controller {
	public function index() {
		$token = (string)($this->request->get['token'] ?? '');

		if (!preg_match('/^[a-f0-9]{32,80}$/', $token)) {
			return $this->notFound();
		}

		$root = DIR_APPLICATION . '../';
		$file = $root . 'api/storage/orders/' . $token . '.json';
		$order = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;

		if (!$order || !empty($order['used'])) {
			return $this->notFound();
		}

		// позначаємо використаним — вдруге сторінка не відкриється
		$order['used'] = true;
		$order['used_at'] = date('c');
		file_put_contents($file, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

		$env = $this->readEnv($root . '.env');
		$anMode = ($env['ANALYTICS_MODE'] ?? 'test') === 'production' ? 'production' : 'test';

		$data['order_id'] = $order['order_id'] ?? '';
		$data['total'] = (string)($order['total'] ?? '');
		$data['home'] = $this->url->link('common/home');
		$data['analytics'] = array(
			'mode'     => $anMode,
			'ga4'      => trim($env['GA4_ID'] ?? ''),
			'ads'      => trim($env['GOOGLE_ADS_ID'] ?? ''),
			'adsLabel' => trim($env['GOOGLE_ADS_PURCHASE_LABEL'] ?? ''),
		);
		$data['asset_version'] = (string)@filemtime($root . 'catalog/view/theme/default/stylesheet/hydrophob.css') ?: '1';

		$this->document->setTitle('Замовлення прийнято — Hydrophob');

		$this->response->setOutput($this->load->view('checkout/hydro_success', $data));
	}

	private function readEnv($file) {
		$env = array();
		if (is_file($file)) {
			foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$line = trim($line);
				if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
					continue;
				}
				list($k, $v) = explode('=', $line, 2);
				$v = trim($v);
				if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
					$v = trim($v, $v[0]);
				}
				$env[trim($k)] = $v;
			}
		}
		return $env;
	}

	/** Невалідний або вже використаний токен — чесний 404. */
	private function notFound() {
		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
		return new Action('error/not_found');
	}
}
