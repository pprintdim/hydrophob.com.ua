<?php
/**
 * Секція "action" (акційний таймер) головної сторінки — content_top модуль.
 * Дані таймера беруться з .env (як у старому sections/action.php).
 */
class ControllerExtensionModuleHydrophobAction extends Controller {
	public function index($setting = array()) {
		$this->load->language('extension/module/hydrophob_action');

		$env = $this->readEnv('.env');
		$timer = $this->buildActionTimer($env);

		$data['timer_start'] = $timer['ACTION_TIMER_START_ISO'];
		$data['timer_end']   = $timer['ACTION_TIMER_END_ISO'];

		return $this->load->view('extension/module/hydrophob_action', $data);
	}

	/** Таймер акції (data-timer-start/end, ISO 8601). */
	private function buildActionTimer($env) {
		$start = $env['ACTION_TIMER_START'] ?? '2026-07-15 00:00:00';
		$end = $env['ACTION_TIMER_END'] ?? '2027-07-15 18:30:00';
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
}
