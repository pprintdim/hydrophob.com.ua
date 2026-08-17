-- hydrophob.net.ua — реєстрація секцій-модулів головної сторінки як content_top layout-модулів
-- (частина 1 порту: sections -> catalog/controller/extension/module/hydrophob_<section>.php).
-- Виконати ПІСЛЯ підтвердження користувача. Route common/home вже прив'язаний до layout_id=1
-- (oc_layout_route, layout_route_id=42) — тут лише додаються самі content_top модулі.

START TRANSACTION;

-- 1) прибрати демо-модулі OpenCart (slideshow/featured/carousel), які інакше теж почнуть
--    рендеритись у content_top разом із секціями hydrophob, як тільки home.php перейде
--    на штатний layout-механізм (common/content_top).
DELETE FROM `oc_layout_module` WHERE `layout_id` = 1 AND `position` = 'content_top';

-- 2) зареєструвати кожну секцію як розширення типу module (без per-instance налаштувань,
--    code = 'hydrophob_<section>' без суфікса '.<module_id>').
INSERT INTO `oc_extension` (`type`, `code`) VALUES
	('module', 'hydrophob_hero'),
	('module', 'hydrophob_about'),
	('module', 'hydrophob_action'),
	('module', 'hydrophob_images_block'),
	('module', 'hydrophob_product'),
	('module', 'hydrophob_info_block'),
	('module', 'hydrophob_reviews'),
	('module', 'hydrophob_guarantee'),
	('module', 'hydrophob_faq'),
	('module', 'hydrophob_delivery'),
	('module', 'hydrophob_contacts');

-- 3) підвʼязати модулі до layout_id=1 (Home), position=content_top, у ТОЧНОМУ порядку
--    поточної верстки (hero, about, action, images_block, product, info_block, reviews,
--    guarantee, faq, delivery, contacts).
INSERT INTO `oc_layout_module` (`layout_id`, `code`, `position`, `sort_order`) VALUES
	(1, 'hydrophob_hero', 'content_top', 1),
	(1, 'hydrophob_about', 'content_top', 2),
	(1, 'hydrophob_action', 'content_top', 3),
	(1, 'hydrophob_images_block', 'content_top', 4),
	(1, 'hydrophob_product', 'content_top', 5),
	(1, 'hydrophob_info_block', 'content_top', 6),
	(1, 'hydrophob_reviews', 'content_top', 7),
	(1, 'hydrophob_guarantee', 'content_top', 8),
	(1, 'hydrophob_faq', 'content_top', 9),
	(1, 'hydrophob_delivery', 'content_top', 10),
	(1, 'hydrophob_contacts', 'content_top', 11);

-- 4) увімкнути кожен модуль (common/content_top.php рендерить модуль лише якщо
--    config->get('module_<code>_status') істинний).
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
	(0, 'module_hydrophob_hero', 'module_hydrophob_hero_status', '1', 0),
	(0, 'module_hydrophob_about', 'module_hydrophob_about_status', '1', 0),
	(0, 'module_hydrophob_action', 'module_hydrophob_action_status', '1', 0),
	(0, 'module_hydrophob_images_block', 'module_hydrophob_images_block_status', '1', 0),
	(0, 'module_hydrophob_product', 'module_hydrophob_product_status', '1', 0),
	(0, 'module_hydrophob_info_block', 'module_hydrophob_info_block_status', '1', 0),
	(0, 'module_hydrophob_reviews', 'module_hydrophob_reviews_status', '1', 0),
	(0, 'module_hydrophob_guarantee', 'module_hydrophob_guarantee_status', '1', 0),
	(0, 'module_hydrophob_faq', 'module_hydrophob_faq_status', '1', 0),
	(0, 'module_hydrophob_delivery', 'module_hydrophob_delivery_status', '1', 0),
	(0, 'module_hydrophob_contacts', 'module_hydrophob_contacts_status', '1', 0);

COMMIT;

-- Примітка: route common/home вже прив'язаний до layout_id=1 (rows: oc_layout_route.layout_route_id=42).
-- Перевірка після виконання:
--   SELECT * FROM oc_layout_module WHERE layout_id=1 ORDER BY sort_order;
--   SELECT `key`,`value` FROM oc_setting WHERE `key` LIKE 'module_hydrophob_%_status';
