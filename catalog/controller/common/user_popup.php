<?php
// Email OTP для чекаута (порт патерна hydrophob.net): sendCode шле 6-значний код,
// verifyCode перевіряє і або створює покупця (passwordless), або логінить наявного.
// Код живе в OC-сесії з TTL, повторна відправка обмежена, спроби введення — теж.
// Тип визначається сам: email існує в oc_customer -> вхід, нема -> реєстрація.
class ControllerCommonUserPopup extends Controller {
	public function status() {
		$json = array(
			'logged' => $this->customer->isLogged(),
			'email'  => $this->customer->isLogged() ? $this->customer->getEmail() : '',
		);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function sendCode() {
		$this->load->language('common/user_popup');

		$json = array();

		$email = isset($this->request->post['email']) ? trim($this->request->post['email']) : '';
		$firstname = isset($this->request->post['firstname']) ? trim($this->request->post['firstname']) : '';
		$lastname = isset($this->request->post['lastname']) ? trim($this->request->post['lastname']) : '';
		$telephone = isset($this->request->post['telephone']) ? trim($this->request->post['telephone']) : '';

		if ((utf8_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		$this->load->model('account/customer');

		$exists = !$json && $this->model_account_customer->getTotalCustomersByEmail($email);
		$type = $exists ? 'login' : 'register';

		if (!$json && $type == 'register') {
			if ((utf8_strlen($firstname) < 1) || (utf8_strlen($firstname) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}
			if ((utf8_strlen($telephone) < 3) || (utf8_strlen($telephone) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}
		}

		// Ліміт повторної відправки
		if (!$json && isset($this->session->data['otp']) && $this->session->data['otp']['email'] == $email && (time() - $this->session->data['otp']['sent']) < 55) {
			$json['error']['email'] = $this->language->get('error_too_often');
		}

		if (!$json) {
			$code = (string)rand(100000, 999999);

			$this->session->data['otp'] = array(
				'email'    => $email,
				'code'     => $code,
				'type'     => $type,
				'data'     => array('firstname' => $firstname, 'lastname' => $lastname, 'telephone' => $telephone),
				'expires'  => time() + 600,
				'attempts' => 0,
				'sent'     => time(),
			);

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($email);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_mail_subject'), $code), ENT_QUOTES, 'UTF-8'));
			$mail->setText(sprintf($this->language->get('text_mail_body'), $code));
			$mail->send();

			$json['success'] = true;
			$json['mode'] = $type;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function verifyCode() {
		$this->load->language('common/user_popup');

		$json = array();

		$email = isset($this->request->post['email']) ? trim($this->request->post['email']) : '';
		$code = isset($this->request->post['code']) ? trim($this->request->post['code']) : '';

		$otp = isset($this->session->data['otp']) ? $this->session->data['otp'] : false;

		if (!$otp || $otp['email'] != $email) {
			$json['error']['code'] = $this->language->get('error_code_expired');
		} elseif ($otp['expires'] < time()) {
			unset($this->session->data['otp']);
			$json['error']['code'] = $this->language->get('error_code_expired');
		} elseif ($otp['attempts'] >= 5) {
			unset($this->session->data['otp']);
			$json['error']['code'] = $this->language->get('error_code_attempts');
		} elseif ($otp['code'] !== $code) {
			$this->session->data['otp']['attempts']++;
			$json['error']['code'] = $this->language->get('error_code_wrong');
		}

		if (!$json) {
			$this->load->model('account/customer');

			if ($otp['type'] == 'register' && !$this->model_account_customer->getTotalCustomersByEmail($email)) {
				$this->model_account_customer->addCustomer(array(
					'customer_group_id' => (int)$this->config->get('config_customer_group_id') ?: 1,
					'firstname'         => $otp['data']['firstname'],
					'lastname'          => $otp['data']['lastname'],
					'email'             => $email,
					'telephone'         => $otp['data']['telephone'],
					'password'          => token(20),
					'newsletter'        => 0,
				));
			}

			$this->customer->login($email, '', true);

			unset($this->session->data['otp']);

			$json['success'] = true;
			$json['message'] = ($otp['type'] == 'register') ? $this->language->get('text_registered') : $this->language->get('text_logged_in');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
