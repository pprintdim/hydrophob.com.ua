<?php
/**
 * Меню ленду (хедер + дві колонки футера) — з налаштувань (oc_setting, code=menu_hydrophob),
 * редагується в адмінці Design → Меню. Мультимовні лейбли за language_id,
 * якірні посилання (#секція) на підсторінках отримують префікс "/" — ведуть на головну.
 */
class ModelDesignHydroMenu extends Model {
	public function getMenu($key, $is_home = true) {
		$items = $this->config->get('menu_hydrophob_' . $key);

		if (!is_array($items)) {
			return array();
		}

		$langId = (int)$this->config->get('config_language_id');
		$result = array();

		foreach ($items as $item) {
			$link = trim((string)($item['link'] ?? ''));
			$label = '';
			if (is_array($item['label'] ?? null)) {
				$label = $item['label'][$langId] ?? ($item['label'][2] ?? '');
			}
			if ($link === '' || $label === '') {
				continue;
			}

			$href = $link;
			if ($link[0] === '#' && !$is_home) {
				$href = '/' . $link;
			}

			$result[] = array(
				'href'  => $href,
				'label' => $label,
				'tab'   => trim((string)($item['tab'] ?? '')),
			);
		}

		return $result;
	}
}
