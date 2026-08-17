<?php
/**
 * Секція "action" (акційний таймер) головної сторінки — content_top модуль.
 * Тексти: oc_setting (module_hydrophob_action_*) з фолбеком на data/strings.json -> action.*.
 * Таймер: oc_setting (timer_start/timer_end), фолбек — .env (як у старому sections/action.php).
 */
class ControllerExtensionModuleHydrophobAction extends Controller {
	private $code = 'module_hydrophob_action';

	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_action');

		$lang_id = (int)$this->config->get('config_language_id');
		$strings = $this->readJson('data/strings.json');

		foreach (array('title', 'name', 'descr', 'days', 'hours', 'minutes', 'seconds') as $field) {
			$data[$field] = $this->localized($field, $lang_id, $strings['action'][$field] ?? array());
		}

		$env = $this->readEnv('.env');
		$timer = $this->buildActionTimer($env);

		$data['timer_start'] = $timer['ACTION_TIMER_START_ISO'];
		$data['timer_end']   = $timer['ACTION_TIMER_END_ISO'];

		return $this->load->view('extension/module/hydrophob_action', $data);
	}

	private function localized($field, $language_id, $legacy_fallback) {
		$values = $this->config->get($this->code . '_' . $field);
		if (is_array($values) && isset($values[$language_id]) && $values[$language_id] !== '') {
			return $values[$language_id];
		}

		$key = $this->legacyLangKey();
		if (is_array($legacy_fallback) && !empty($legacy_fallback[$key])) {
			return $legacy_fallback[$key];
		}

		return $legacy_fallback['UA'] ?? '';
	}

	private function legacyLangKey() {
		$code = $this->session->data['language'] ?? 'uk-ua';
		if ($code === 'ru-ru') {
			return 'RU';
		}
		if ($code === 'en-gb') {
			return 'EN';
		}
		return 'UA';
	}

	/** Таймер акції (data-timer-start/end, ISO 8601). Пріоритет: oc_setting -> .env -> дефолт. */
	private function buildActionTimer($env) {
		$start = $this->config->get($this->code . '_timer_start');
		$end   = $this->config->get($this->code . '_timer_end');

		if (empty($start)) {
			$start = $env['ACTION_TIMER_START'] ?? '2026-07-15 00:00:00';
		}
		if (empty($end)) {
			$end = $env['ACTION_TIMER_END'] ?? '2027-07-15 18:30:00';
		}

		$tz = $env['ACTION_TIMER_TIMEZONE'] ?? 'Europe/Kyiv';

		try {
			$timezone = new DateTimeZone($tz);
			$timerStart = new DateTimeImmutable($start, $timezone);
			$timerEnd = new DateTimeImmutable($end, $timezone);
			return array(
				'ACTION_TIMER_START_ISO' => $timerStart->format(DATE_ATOM),
				'ACTION_TIMER_END_ISO'   => $timerEnd->format(DATE_ATOM),
			);
		} catch (Throwable $e) {
			return array('ACTION_TIMER_START_ISO' => '', 'ACTION_TIMER_END_ISO' => '');
		}
	}

	private function readEnv($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
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

	private function readJson($relativePath) {
		$file = DIR_APPLICATION . '../' . $relativePath;
		if (!is_file($file)) {
			return array();
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}
}
