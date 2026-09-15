-- database: game
-- Active virtual shop rewards were removed from the game seed while the login catalogue kept
-- selling them. Restore the lightweight inventory templates and their existing ObjectAction
-- behaviors so queued purchases become visible and usable again.
INSERT INTO `item_template` (`id`, `type`, `name`) VALUES
    (15009, 12, 'Potion fm terre'),
    (15010, 12, 'Potion fm feu'),
    (15011, 12, 'Potion fm eau'),
    (15012, 12, 'Potion fm air'),
    (15013, 12, 'Potion caméléon'),
    (15014, 89, 'Pack de Parchemin de Vitalité'),
    (15015, 89, 'Pack de Parchemin de Sagesse'),
    (15016, 89, 'Pack de Parchemin de Force'),
    (15017, 89, 'Pack de Parchemin d''Intelligence'),
    (15018, 89, 'Pack de Parchemin de Chance'),
    (15019, 89, 'Pack de Parchemin d''Agilité'),
    (26001, 12, 'Coffre Argent (3)'),
    (26002, 12, 'Coffre Or (5)'),
    (26003, 12, 'Coffre Géant (10)'),
    (26004, 12, 'Coffre Magique (10, moitié JP)'),
    (26005, 12, 'Coffre Légendaire (10, full JP)'),
    (26006, 12, 'Coffre dragodinde (castré)'),
    (26007, 12, 'Coffre Sort aléatoire'),
    (26008, 12, 'Coffre Maîtrise aléatoire'),
    (26009, 12, 'Coffre obvijevant'),
    (26010, 12, 'Coffre Familier aléatoire'),
    (26011, 12, 'Coffre Forgemagie'),
    (26012, 12, 'Coffre de point Boutique')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `name` = VALUES(`name`);

INSERT INTO `objectsactions` (`template`, `type`, `args`) VALUES
    (15009, '34', 'TERRE'),
    (15010, '34', 'FEU'),
    (15011, '34', 'EAU'),
    (15012, '34', 'AIR'),
    (15013, '35', ''),
    (15014, '26', '806,25;807,25;808,29;810,11'),
    (15015, '26', '802,25;803,25;804,29;805,11'),
    (15016, '26', '683,25;795,25;796,29;797,11'),
    (15017, '26', '686,25;815,25;816,29;817,11'),
    (15018, '26', '809,25;811,25;812,29;814,11'),
    (15019, '26', '798,25;799,25;800,29;801,11'),
    (26001, '36', '3;500-1500;0'),
    (26002, '36', '5;1500-3000;0'),
    (26003, '36', '10;3000-5000;0'),
    (26004, '36', '10;5000-10000;5'),
    (26005, '36', '10;10000-150000;10'),
    (26006, '38', ''),
    (26007, '37', '2'),
    (26008, '37', '3'),
    (26009, '37', '4'),
    (26010, '37', '5'),
    (26011, '37', '6'),
    (26012, '33', '5')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `args` = VALUES(`args`);
