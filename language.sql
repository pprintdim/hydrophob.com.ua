-- hydrophob.net.ua — перемикання дефолтної мови вітрини на uk-ua (частина 3 порту).
-- Виконати ПІСЛЯ підтвердження користувача і ПІСЛЯ того, як каталог
-- catalog/language/uk-ua/ вже лежить на сервері (він фізично вже створений у цьому порту:
-- copy en-gb -> uk-ua + переклад ключових файлів, див. підсумок).
--
-- Адмінку (hp_panel) НЕ перемикаємо: повного uk-ua пакету для admin не знайдено
-- (лише готові uk-ua-переклади для наших 11 кастомних content_top модулів), тому
-- config_admin_language свідомо лишається 'en-gb', як і дозволяє fallback-сценарій ТЗ.

UPDATE `oc_setting` SET `value` = 'uk-ua' WHERE `key` = 'config_language' AND `store_id` = 0;

-- Перевірка після виконання:
--   SELECT `key`,`value` FROM oc_setting WHERE `key` IN ('config_language','config_admin_language');
