-- Use authentic client sprites matched to each custom shop product. The delivery proxies from
-- migration 006 may deliberately use a different gift container so the client permits opening it.
UPDATE `website_shop_objects_templates`
SET
    `type` = CASE `id`
        WHEN 15009 THEN 26 WHEN 15010 THEN 26 WHEN 15011 THEN 26 WHEN 15012 THEN 26
        ELSE 89
    END,
    `skin` = CASE `id`
        WHEN 15009 THEN 30
        WHEN 15010 THEN 21
        WHEN 15011 THEN 22
        WHEN 15012 THEN 27
        WHEN 15013 THEN 42
        WHEN 15014 THEN 47
        WHEN 15015 THEN 46
        WHEN 15016 THEN 44
        WHEN 15017 THEN 45
        WHEN 15018 THEN 47
        WHEN 15019 THEN 46
    END
WHERE `id` BETWEEN 15009 AND 15019;

UPDATE `website_shop_objects_templates`
SET
    `type` = CASE `id`
        WHEN 26003 THEN 24
        WHEN 26005 THEN 15
        ELSE 89
    END,
    `skin` = CASE `id`
        WHEN 26001 THEN 45
        WHEN 26002 THEN 46
        WHEN 26003 THEN 44
        WHEN 26004 THEN 47
        WHEN 26005 THEN 13
        WHEN 26006 THEN 42
        WHEN 26007 THEN 2
        WHEN 26008 THEN 2
        WHEN 26009 THEN 2
        WHEN 26010 THEN 3
        WHEN 26011 THEN 3
        WHEN 26012 THEN 42
    END
WHERE `id` BETWEEN 26001 AND 26012;
