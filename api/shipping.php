<?php
// Довідники доставки. Пріоритет: deliveries.json (конфіг перевізників) -> живі API.
// Нова пошта — публічне API без ключа. Укрпошта/Meest — за наявності токенів у .env,
// інакше повертаємо manual:true (кошик показує ручні поля місто/відділення).
//
// GET ?provider=np|ukrposhta|meest & action=cities|warehouses & search=... [& city_ref=...]

require __DIR__ . '/bootstrap.php';

$provider = $_GET['provider'] ?? 'np';
$action   = $_GET['action'] ?? '';
$search   = trim((string)($_GET['search'] ?? ''));
$cityRef  = trim((string)($_GET['city_ref'] ?? ''));

if (!in_array($action, ['cities', 'warehouses'], true)) {
    hydro_json(['ok' => false, 'error' => 'bad action'], 400);
}

function cache_get(string $key, int $ttl, callable $fetch): array
{
    $file = hydro_storage_dir('cache') . '/' . md5($key) . '.json';
    if (is_file($file) && time() - filemtime($file) < $ttl) {
        $c = json_decode((string)file_get_contents($file), true);
        if (is_array($c)) return $c;
    }
    $data = $fetch();
    if ($data) file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    return $data;
}

/* ---------- Нова пошта (публічне API, без ключа) ---------- */
function np_request(array $payload): array
{
    $payload['apiKey'] = hydro_env('NP_API_KEY');
    $ch = curl_init('https://api.novaposhta.ua/v2.0/json/');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $data = json_decode((string)$resp, true);
    return is_array($data) ? $data : [];
}

function np_cities(string $search): array
{
    return cache_get('np:cities:' . mb_strtolower($search), 86400, function () use ($search) {
        $resp = np_request([
            'modelName' => 'Address',
            'calledMethod' => 'searchSettlements',
            'methodProperties' => ['CityName' => $search, 'Limit' => '15', 'Page' => '1'],
        ]);
        $out = [];
        foreach (($resp['data'][0]['Addresses'] ?? []) as $a) {
            $out[] = ['ref' => $a['DeliveryCity'] ?? ($a['Ref'] ?? ''), 'name' => $a['Present'] ?? ''];
        }
        return $out;
    });
}

function np_warehouses(string $cityRef, string $search): array
{
    return cache_get('np:wh:' . $cityRef . ':' . mb_strtolower($search), 86400, function () use ($cityRef, $search) {
        $props = ['CityRef' => $cityRef, 'Limit' => '50', 'Page' => '1'];
        if ($search !== '') $props['FindByString'] = $search;
        $resp = np_request([
            'modelName' => 'AddressGeneral',
            'calledMethod' => 'getWarehouses',
            'methodProperties' => $props,
        ]);
        $out = [];
        foreach (($resp['data'] ?? []) as $w) {
            $out[] = ['ref' => $w['Ref'] ?? '', 'name' => $w['Description'] ?? ''];
        }
        return $out;
    });
}

/* ---------- Укрпошта (address-classifier-ws, потрібен bearer) ---------- */
function up_bearer(): string
{
    return hydro_env('UKRPOSHTA_ECOM_BEARER') ?: hydro_env('UKRPOSHTA_USER_TOKEN');
}

function up_request(string $method): array
{
    $ch = curl_init('https://www.ukrposhta.ua/address-classifier-ws/' . $method);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . up_bearer()],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $data = json_decode((string)$resp, true);
    return is_array($data) ? $data : [];
}

function up_cities(string $search): array
{
    return cache_get('up:cities:' . mb_strtolower($search), 86400, function () use ($search) {
        $data = up_request('get_city_by_region_id_and_district_id_and_city_ua?city_ua=' . rawurlencode($search));
        $out = [];
        foreach (($data['Entries']['Entry'] ?? []) as $v) {
            $label = trim(($v['CITY_UA'] ?? '') . ', ' . ($v['DISTRICT_UA'] ?? '') . ', ' . ($v['REGION_UA'] ?? ''), ', ');
            $out[] = ['ref' => (string)($v['CITY_ID'] ?? ''), 'name' => $label];
        }
        return $out;
    });
}

function up_offices(string $cityId, string $search): array
{
    return cache_get('up:off:' . $cityId . ':' . mb_strtolower($search), 86400, function () use ($cityId) {
        $data = up_request('get_postoffices_by_city_id?city_id=' . rawurlencode($cityId));
        $out = [];
        foreach (($data['Entries']['Entry'] ?? []) as $v) {
            $name = trim(($v['POSTOFFICE_UA'] ?? ($v['POSTINDEX'] ?? '')) . ' (' . ($v['POSTINDEX'] ?? '') . ')');
            $out[] = ['ref' => (string)($v['POSTOFFICE_ID'] ?? ($v['POSTINDEX'] ?? '')), 'name' => $name];
        }
        return $out;
    });
}

/* ---------- Meest (потрібні креденшли; інакше ручний ввід) ---------- */
function meest_configured(): bool
{
    return hydro_env('MEEST_USERNAME') !== '' && hydro_env('MEEST_PASSWORD') !== '';
}

/* ---------- Роутинг ---------- */
if ($provider === 'np') {
    if ($action === 'cities') {
        if (mb_strlen($search) < 2) hydro_json(['ok' => true, 'items' => []]);
        hydro_json(['ok' => true, 'items' => np_cities($search)]);
    }
    if ($cityRef === '') hydro_json(['ok' => false, 'error' => 'city_ref required'], 400);
    hydro_json(['ok' => true, 'items' => np_warehouses($cityRef, $search)]);
}

if ($provider === 'ukrposhta') {
    // З bearer-токеном — жива address-classifier API; без токена — публічний снапшот postindex.
    if (up_bearer() !== '') {
        if ($action === 'cities') {
            if (mb_strlen($search) < 2) hydro_json(['ok' => true, 'items' => []]);
            hydro_json(['ok' => true, 'items' => up_cities($search)]);
        }
        if ($cityRef === '') hydro_json(['ok' => false, 'error' => 'city_ref required'], 400);
        hydro_json(['ok' => true, 'items' => up_offices($cityRef, $search)]);
    }

    $snapFile = HYDRO_ROOT . '/data/ukrposhta-offices.json';
    if (!is_file($snapFile)) {
        hydro_json(['ok' => true, 'manual' => true, 'items' => []]);
    }
    static $upSnap = null;
    if ($upSnap === null) {
        $upSnap = json_decode((string)file_get_contents($snapFile), true) ?: ['cities' => [], 'offices' => []];
    }
    if ($action === 'cities') {
        if (mb_strlen($search) < 2) hydro_json(['ok' => true, 'items' => []]);
        $out = [];
        foreach ($upSnap['cities'] as $c) {
            if (mb_stripos($c['name'], $search) !== false) {
                $label = $c['name'] . ($c['region'] !== '' ? ', ' . $c['region'] . ' обл.' : '');
                $out[] = ['ref' => $c['ref'], 'name' => $label];
                if (count($out) >= 15) break;
            }
        }
        hydro_json(['ok' => true, 'items' => $out]);
    }
    if ($cityRef === '') hydro_json(['ok' => false, 'error' => 'city_ref required'], 400);
    $list = $upSnap['offices'][$cityRef] ?? [];
    if ($search !== '') {
        $list = array_values(array_filter($list, function ($w) use ($search) {
            return mb_stripos($w, $search) !== false;
        }));
    }
    $out = array_map(function ($w) { return ['ref' => $w, 'name' => $w]; }, array_slice($list, 0, 50));
    hydro_json(['ok' => true, 'items' => $out]);
}

if ($provider === 'meest') {
    // Публічний довідник Meest (снапшот data/meest-branches.json з publicapi.meest.com — без ключа).
    $snapFile = HYDRO_ROOT . '/data/meest-branches.json';
    if (!is_file($snapFile)) {
        hydro_json(['ok' => true, 'manual' => true, 'items' => []]);
    }
    static $meestSnap = null;
    if ($meestSnap === null) {
        $meestSnap = json_decode((string)file_get_contents($snapFile), true) ?: ['cities' => [], 'branches' => []];
    }
    if ($action === 'cities') {
        if (mb_strlen($search) < 2) hydro_json(['ok' => true, 'items' => []]);
        $q = mb_strtolower($search);
        $out = [];
        foreach ($meestSnap['cities'] as $c) {
            if (mb_stripos($c['name'], $search) !== false
                || mb_stripos($c['name_ru'] ?? '', $search) !== false) {
                $out[] = ['ref' => $c['ref'], 'name' => $c['name']];
                if (count($out) >= 15) break;
            }
        }
        hydro_json(['ok' => true, 'items' => $out]);
    }
    // warehouses
    if ($cityRef === '') hydro_json(['ok' => false, 'error' => 'city_ref required'], 400);
    $list = $meestSnap['branches'][$cityRef] ?? [];
    if ($search !== '') {
        $list = array_values(array_filter($list, function ($w) use ($search) {
            return mb_stripos($w['name'], $search) !== false;
        }));
    }
    $out = array_map(function ($w) { return ['ref' => $w['ref'], 'name' => $w['name']]; }, array_slice($list, 0, 50));
    hydro_json(['ok' => true, 'items' => $out]);
}

hydro_json(['ok' => false, 'error' => 'unknown provider'], 400);
