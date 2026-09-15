-- database: game
-- The 150xx/260xx shop-only templates are not present in the 1.29 client language files. Attach
-- their reward actions to authentic, otherwise actionless Retro consumables and convert gifts
-- already waiting on accounts. ShopService applies the same delivery aliases to new purchases.
INSERT INTO `objectsactions` (`template`, `type`, `args`) VALUES
    (12018, '34', 'TERRE'),
    (12023, '34', 'FEU'),
    (12024, '34', 'EAU'),
    (12025, '34', 'AIR'),
    (9964, '35', ''),
    (12010, '26', '806,25;807,25;808,29;810,11'),
    (12011, '26', '802,25;803,25;804,29;805,11'),
    (12012, '26', '683,25;795,25;796,29;797,11'),
    (12013, '26', '686,25;815,25;816,29;817,11'),
    (12014, '26', '809,25;811,25;812,29;814,11'),
    (12015, '26', '798,25;799,25;800,29;801,11'),
    (12017, '36', '3;500-1500;0'),
    (12019, '36', '5;1500-3000;0'),
    (8340, '36', '10;3000-5000;0'),
    (12022, '36', '10;5000-10000;5'),
    (12839, '36', '10;10000-150000;10'),
    (12777, '38', ''),
    (10912, '37', '2'),
    (10913, '37', '3'),
    (10914, '37', '4'),
    (8337, '37', '5'),
    (8339, '37', '6'),
    (10910, '33', '5')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `args` = VALUES(`args`);

UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15009,', ';12018,'), 2) WHERE `objects` REGEXP '(^|;)15009,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15010,', ';12023,'), 2) WHERE `objects` REGEXP '(^|;)15010,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15011,', ';12024,'), 2) WHERE `objects` REGEXP '(^|;)15011,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15012,', ';12025,'), 2) WHERE `objects` REGEXP '(^|;)15012,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15013,', ';9964,'), 2) WHERE `objects` REGEXP '(^|;)15013,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15014,', ';12010,'), 2) WHERE `objects` REGEXP '(^|;)15014,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15015,', ';12011,'), 2) WHERE `objects` REGEXP '(^|;)15015,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15016,', ';12012,'), 2) WHERE `objects` REGEXP '(^|;)15016,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15017,', ';12013,'), 2) WHERE `objects` REGEXP '(^|;)15017,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15018,', ';12014,'), 2) WHERE `objects` REGEXP '(^|;)15018,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';15019,', ';12015,'), 2) WHERE `objects` REGEXP '(^|;)15019,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26001,', ';12017,'), 2) WHERE `objects` REGEXP '(^|;)26001,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26002,', ';12019,'), 2) WHERE `objects` REGEXP '(^|;)26002,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26003,', ';8340,'), 2) WHERE `objects` REGEXP '(^|;)26003,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26004,', ';12022,'), 2) WHERE `objects` REGEXP '(^|;)26004,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26005,', ';12839,'), 2) WHERE `objects` REGEXP '(^|;)26005,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26006,', ';12777,'), 2) WHERE `objects` REGEXP '(^|;)26006,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26007,', ';10912,'), 2) WHERE `objects` REGEXP '(^|;)26007,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26008,', ';10913,'), 2) WHERE `objects` REGEXP '(^|;)26008,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26009,', ';10914,'), 2) WHERE `objects` REGEXP '(^|;)26009,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26010,', ';8337,'), 2) WHERE `objects` REGEXP '(^|;)26010,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26011,', ';8339,'), 2) WHERE `objects` REGEXP '(^|;)26011,';
UPDATE `gifts` SET `objects` = SUBSTRING(REPLACE(CONCAT(';', `objects`), ';26012,', ';10910,'), 2) WHERE `objects` REGEXP '(^|;)26012,';

DELETE FROM `objectsactions` WHERE `template` BETWEEN 15009 AND 15019 OR `template` BETWEEN 26001 AND 26012;
DELETE FROM `item_template` WHERE `id` BETWEEN 15009 AND 15019 OR `id` BETWEEN 26001 AND 26012;
