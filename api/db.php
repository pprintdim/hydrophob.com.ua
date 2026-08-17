<?php
// Доступ api-шару до БД OpenCart: конекшн з config.php + читання oc_setting.

require_once HYDRO_ROOT . '/config.php';

function hydro_db(): mysqli {
    static $db = null;
    if ($db === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
        $db->set_charset('utf8mb4');
    }
    return $db;
}

/** Значення oc_setting (плоске або serialized). */
function hydro_setting(string $key, $default = null) {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $stmt = hydro_db()->prepare("SELECT value, serialized FROM " . DB_PREFIX . "setting WHERE store_id = 0 AND `key` = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            $cache[$key] = null;
        } else {
            $cache[$key] = $row['serialized'] ? json_decode($row['value'], true) : $row['value'];
        }
    }
    return $cache[$key] !== null ? $cache[$key] : $default;
}

/** Активні способи оплати лендінгу: [code => назва за language_id]. */
function hydro_payment_methods(int $language_id): array {
    $defs = [
        'cod'           => ['uk' => 'Накладений платіж', 'setting_title' => null],
        'wayforpay'     => ['uk' => 'Оплата карткою онлайн', 'setting_title' => 'payment_wayforpay_title'],
        'bank_transfer' => ['uk' => 'Банківський переказ', 'setting_title' => null],
    ];
    $out = [];
    foreach ($defs as $code => $def) {
        if ((string)hydro_setting('payment_' . $code . '_status', '0') !== '1') {
            continue;
        }
        $title = $def['uk'];
        if ($def['setting_title']) {
            $custom = hydro_setting($def['setting_title'] . $language_id);
            if ($custom) {
                $title = $custom;
            }
        }
        $out[$code] = $title;
    }
    return $out;
}
