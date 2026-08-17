<?php
/**
 * Секція "faq" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Розмітка повністю статична, текст локалізується клієнтським i18n (data-i18n у hydrophob.js).
 */
class ControllerExtensionModuleHydrophobFaq extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_faq');

		return $this->load->view('extension/module/hydrophob_faq', array());
	}
}
