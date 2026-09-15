UPDATE `website_shop_objects_templates`
SET `description` = CASE `id`
    WHEN 26001 THEN 'Coffre argent. Trois objets aléatoires de votre niveau (niveau 150 maximum) et une bourse de 500 à 1 500 Kamas.'
    WHEN 26002 THEN 'Coffre or. Cinq objets aléatoires de votre niveau (niveau 150 maximum) et une bourse de 1 500 à 3 000 Kamas.'
    WHEN 26003 THEN 'Coffre géant. Dix objets aléatoires de votre niveau (niveau 150 maximum) et une bourse de 3 000 à 5 000 Kamas.'
    WHEN 26004 THEN 'Coffre magique. Dix objets aléatoires de votre niveau, dont cinq en jet parfait, et une bourse de 5 000 à 10 000 Kamas.'
    WHEN 26005 THEN 'Coffre légendaire. Dix objets aléatoires de votre niveau, tous en jet parfait, et une bourse de 10 000 à 150 000 Kamas.'
END
WHERE `id` BETWEEN 26001 AND 26005;

-- These custom reward names have no one-to-one official Retro item. Use only authentic client
-- artwork selected from the closest named chests, crates and gifts in the Retro catalogue.
UPDATE `website_shop_objects_templates`
SET
    `type` = CASE `id`
        WHEN 26001 THEN 89  -- Cadeau Panoplie d'agilité (silver box)
        WHEN 26002 THEN 89  -- Cadeau Panoplie d'intelligence (gold box)
        WHEN 26003 THEN 24  -- Caisse de cadeaux
        WHEN 26004 THEN 89  -- Cadeau Panoplie de force (magic box)
        WHEN 26005 THEN 15  -- Coffret maudit du Flib
        ELSE 89
    END,
    `skin` = CASE `id`
        WHEN 26001 THEN 45
        WHEN 26002 THEN 46
        WHEN 26003 THEN 44
        WHEN 26004 THEN 47
        WHEN 26005 THEN 13
        WHEN 26006 THEN 32
        WHEN 26007 THEN 44
        WHEN 26008 THEN 3
        WHEN 26009 THEN 4
        WHEN 26010 THEN 1
        WHEN 26011 THEN 2
        WHEN 26012 THEN 42
    END
WHERE `id` BETWEEN 26001 AND 26012;
