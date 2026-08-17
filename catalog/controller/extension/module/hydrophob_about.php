<?php
/**
 * Секція "about" головної сторінки (hydrophob.net.ua) — content_top модуль.
 * Розмітка повністю статична, текст локалізується клієнтським i18n (data-i18n у hydrophob.js).
 */
class ControllerExtensionModuleHydrophobAbout extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_about');

		return $this->load->view('extension/module/hydrophob_about', array());
	}
}
