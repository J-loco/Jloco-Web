-- Schema of the jloco_login tables JLoco-Web uses, dumped from the JLoco-Game database
-- (mariadb-dump --no-data). Charsets are kept on purpose: the latin1 / utf8mb3 columns are part of what the tests check.
CREATE TABLE `world_accounts` (
  `guid` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(30) DEFAULT NULL,
  `pass` text DEFAULT NULL,
  `muteTime` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT NULL,
  `lastIP` varchar(25) DEFAULT NULL,
  `lastConnectionDate` varchar(100) DEFAULT NULL,
  `migration` tinyint(1) NOT NULL DEFAULT 0,
  `question` varchar(100) NOT NULL DEFAULT 'supprimer ?',
  `reponse` varchar(100) NOT NULL DEFAULT 'oui',
  `pseudo` varchar(30) DEFAULT NULL,
  `banned` tinyint(4) NOT NULL DEFAULT 0,
  `bannedTime` bigint(20) NOT NULL DEFAULT 0,
  `reload_needed` tinyint(1) DEFAULT 1,
  `friends` varchar(255) DEFAULT NULL,
  `enemy` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `logged` int(11) NOT NULL DEFAULT 0,
  `subscribe` bigint(20) NOT NULL DEFAULT 0,
  `vip` int(11) NOT NULL DEFAULT 0,
  `muteRaison` text DEFAULT NULL,
  `mutePseudo` text DEFAULT NULL,
  `lastConnectDay` text DEFAULT NULL,
  `dateRegister` varchar(10) DEFAULT NULL,
  `lastVoteIP` varchar(255) DEFAULT NULL,
  `heurevote` bigint(20) NOT NULL DEFAULT 0,
  `totalVotes` int(11) NOT NULL DEFAULT 0,
  `twitter` text DEFAULT NULL,
  `facebook` text DEFAULT NULL,
  `google` text DEFAULT NULL,
  `votes` int(11) NOT NULL DEFAULT 0,
  `showOrHide` tinyint(1) NOT NULL DEFAULT 1,
  `showOrHidePos` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`guid`) USING BTREE,
  UNIQUE KEY `account` (`account`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `world_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `account` int(11) NOT NULL,
  `groupe` int(11) NOT NULL DEFAULT 0,
  `sexe` tinyint(4) NOT NULL,
  `class` smallint(6) NOT NULL,
  `color1` int(11) NOT NULL,
  `color2` int(11) NOT NULL,
  `color3` int(11) NOT NULL,
  `kamas` bigint(20) NOT NULL,
  `spellboost` int(11) NOT NULL,
  `capital` int(11) NOT NULL,
  `energy` int(11) NOT NULL DEFAULT 10000,
  `level` int(11) NOT NULL,
  `xp` bigint(20) NOT NULL DEFAULT 0,
  `size` int(11) NOT NULL,
  `gfx` int(11) NOT NULL,
  `alignement` int(11) NOT NULL DEFAULT 0,
  `honor` int(11) NOT NULL DEFAULT 0,
  `deshonor` int(11) NOT NULL DEFAULT 0,
  `alvl` int(11) NOT NULL DEFAULT 0 COMMENT 'Niveau alignement',
  `vitalite` int(11) NOT NULL DEFAULT 101,
  `force` int(11) NOT NULL DEFAULT 101,
  `sagesse` int(11) NOT NULL DEFAULT 101,
  `intelligence` int(11) NOT NULL DEFAULT 101,
  `chance` int(11) NOT NULL DEFAULT 101,
  `agilite` int(11) NOT NULL DEFAULT 101,
  `seeFriend` tinyint(4) NOT NULL DEFAULT 1,
  `seeAlign` tinyint(4) NOT NULL DEFAULT 0,
  `seeSeller` tinyint(4) NOT NULL DEFAULT 0,
  `canaux` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '*#%!pi$:?',
  `map` int(11) NOT NULL,
  `cell` int(11) NOT NULL,
  `pdvper` int(11) NOT NULL DEFAULT 100,
  `spells` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `objets` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `storeObjets` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `savepos` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '10298,314',
  `zaaps` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `jobs` varchar(300) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `mountxpgive` int(11) NOT NULL DEFAULT 0,
  `mount` int(11) NOT NULL DEFAULT -1,
  `title` int(11) NOT NULL DEFAULT 0,
  `wife` int(11) NOT NULL DEFAULT 0,
  `morphMode` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `emotes` varchar(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '',
  `prison` bigint(20) NOT NULL DEFAULT 0,
  `server` int(11) NOT NULL,
  `logged` int(11) DEFAULT 0,
  `allTitle` varchar(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `parcho` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `timeDeblo` bigint(20) DEFAULT NULL,
  `noall` tinyint(4) NOT NULL DEFAULT 0,
  `cms_view_armor` tinyint(1) DEFAULT 0,
  `cms_message` varchar(255) DEFAULT 'Aucun message',
  `cms_price` int(11) DEFAULT 0,
  `cms_on_market` tinyint(1) DEFAULT 0,
  `deadInformation` varchar(255) NOT NULL DEFAULT '0,0,0,0',
  `deathCount` int(11) NOT NULL DEFAULT 0,
  `totalKills` int(11) NOT NULL DEFAULT 0,
  `revive` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `world_guilds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `emblem` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `lvl` int(11) NOT NULL DEFAULT 1,
  `xp` bigint(20) NOT NULL DEFAULT 0,
  `capital` int(11) NOT NULL DEFAULT 0,
  `maxCollectors` int(11) NOT NULL DEFAULT 1,
  `spells` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '462;0|461;0|460;0|459;0|458;0|457;0|456;0|455;0|454;0|453;0|452;0|451;0|',
  `stats` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '176;100|158;1000|124;100|',
  `date` bigint(20) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `world_objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `stats` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `puit` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `guid` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `world_servers` (
  `id` int(11) NOT NULL DEFAULT 0,
  `key` text DEFAULT NULL,
  `population` int(11) DEFAULT 0,
  `isSubscriberServer` int(11) DEFAULT 1,
  `uptime` bigint(20) NOT NULL DEFAULT 0,
  `name` text NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `world_base_sub_areas` (
  `id` int(11) NOT NULL,
  `area` int(11) NOT NULL,
  `name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `nearest_sub_areas` varchar(200) NOT NULL DEFAULT '',
  KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_timeline_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `title_en` varchar(100) NOT NULL,
  `title_es` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `content_en` text NOT NULL,
  `content_es` text NOT NULL,
  `date` datetime NOT NULL,
  `img` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `client_rss_news` (
  `id` int(11) NOT NULL,
  `title_fr` varchar(50) NOT NULL DEFAULT '',
  `title_en` varchar(50) NOT NULL DEFAULT '',
  `date` bigint(20) NOT NULL,
  `icon` text NOT NULL,
  `link` text NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_users_votes` (
  `ip` varchar(20) NOT NULL,
  `date` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_shop_categories` (
  `id` tinyint(4) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_shop_objects` (
  `name` text NOT NULL,
  `template` int(11) NOT NULL,
  `jp` tinyint(1) NOT NULL DEFAULT 0,
  `price` int(11) NOT NULL DEFAULT 1000000,
  `category` int(11) NOT NULL DEFAULT -1,
  `server` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '1',
  `active` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`template`,`server`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_shop_objects_templates` (
  `id` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `name` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `skin` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `effects` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `website_shop_objects_purchases` (
  `account` int(11) DEFAULT NULL,
  `template` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `server` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=FIXED;
CREATE TABLE `website_shop_points_purchases` (
  `account` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `code` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `pays` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `type` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
