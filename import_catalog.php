<?php
// Одноразовий імпорт data/products.json у категорії/товари OpenCart.
// Запуск: php import_catalog.php

$dbHost = '127.0.0.1';
$dbPort = 33066;
$dbUser = 'hydrophob-oc3';
$dbPass = 'TIzrkNNhydW1sihKgMW5';
$dbName = 'hydrophob-oc3';
$languageId = 1; // English (єдина встановлена мова), контент — українською як тимчасовий дефолт

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_error) {
    die('DB connect error: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$products = json_decode(file_get_contents(__DIR__ . '/../data/products.json'), true);

$categoryIds = [];
$sort = 0;
foreach ($products as $p) {
    $catName = $p['category'];
    if (isset($categoryIds[$catName])) {
        continue;
    }
    $sort += 1;

    $mysqli->query("INSERT INTO oc_category (image, parent_id, top, `column`, sort_order, status, date_added, date_modified)
        VALUES (NULL, 0, 1, 1, $sort, 1, NOW(), NOW())");
    $categoryId = $mysqli->insert_id;
    $categoryIds[$catName] = $categoryId;

    $mysqli->query("INSERT INTO oc_category_to_store (category_id, store_id) VALUES ($categoryId, 0)");
    $mysqli->query("INSERT INTO oc_category_path (category_id, path_id, level) VALUES ($categoryId, $categoryId, 0)");

    $nameEsc = $mysqli->real_escape_string($catName);
    $mysqli->query("INSERT INTO oc_category_description (category_id, language_id, name, description, meta_title, meta_description, meta_keyword)
        VALUES ($categoryId, $languageId, '$nameEsc', '', '$nameEsc', '', '')");
}

echo "Categories imported: " . count($categoryIds) . "\n";

$productSort = 0;
foreach ($products as $p) {
    $productSort += 1;
    $model = $mysqli->real_escape_string($p['id']);
    $price = (float)$p['price'];
    $status = !empty($p['available']) ? 1 : 0;
    $image = 'catalog/hydrophob/' . basename($p['image']);
    $imageEsc = $mysqli->real_escape_string($image);

    $mysqli->query("INSERT INTO oc_product
        (model, sku, upc, ean, jan, isbn, mpn, location, quantity, stock_status_id, image,
         manufacturer_id, shipping, price, points, tax_class_id, date_available, weight,
         weight_class_id, length, width, height, length_class_id, subtract, minimum,
         sort_order, status, date_added, date_modified)
        VALUES ('$model', '$model', '', '', '', '', '', '', 100, 7, '$imageEsc',
         0, 1, $price, 0, 0, NOW(), 0,
         1, 0, 0, 0, 1, 1, 1,
         $productSort, $status, NOW(), NOW())");
    $productId = $mysqli->insert_id;

    $mysqli->query("INSERT INTO oc_product_to_store (product_id, store_id) VALUES ($productId, 0)");

    $categoryId = $categoryIds[$p['category']];
    $mysqli->query("INSERT INTO oc_product_to_category (product_id, category_id) VALUES ($productId, $categoryId)");

    $name = $mysqli->real_escape_string($p['title']['UA'] ?? '');
    $descrShort = $mysqli->real_escape_string($p['descr']['UA'] ?? '');
    $descrHtml = $mysqli->real_escape_string($p['descriptionHtml']['UA'] ?? $descrShort);
    $tag = $mysqli->real_escape_string($p['volume'] ?? '');

    $mysqli->query("INSERT INTO oc_product_description
        (product_id, language_id, name, description, tag, meta_title, meta_description, meta_keyword)
        VALUES ($productId, $languageId, '$name', '$descrHtml', '$tag', '$name', '$descrShort', '')");
}

echo "Products imported: " . count($products) . "\n";
$mysqli->close();
