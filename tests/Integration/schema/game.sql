-- Schema of the jloco_game tables JLoco-Web uses, dumped from the JLoco-Game database
-- (mariadb-dump --no-data). Charsets are kept on purpose: the latin1 / utf8mb3 columns are part of what the tests check.
CREATE TABLE `gifts` (
  `id` int(11) NOT NULL,
  `objects` varchar(1028) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `maps` (
  `id` int(11) NOT NULL,
  `date` varchar(50) NOT NULL,
  `width` int(11) NOT NULL DEFAULT -1,
  `heigth` int(11) NOT NULL DEFAULT -1,
  `places` varchar(300) NOT NULL DEFAULT '|',
  `key` text NOT NULL,
  `mapData` text NOT NULL,
  `monsters` text NOT NULL,
  `capabilities` int(11) NOT NULL,
  `mappos` varchar(15) NOT NULL DEFAULT '0,0,0',
  `numgroup` int(11) NOT NULL DEFAULT 3,
  `minSize` int(11) NOT NULL DEFAULT 1,
  `fixSize` int(11) NOT NULL DEFAULT -1,
  `maxSize` int(11) NOT NULL DEFAULT 8,
  `forbidden` varchar(20) NOT NULL DEFAULT '0;0;0;0;0;0;0' COMMENT 'noMarchand;noCollector;noPrism;noTP;noDefie;noAgro;noCanal',
  `sniffed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `jobs_data` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `tools` varchar(300) NOT NULL COMMENT 'outils utilisables',
  `crafts` text NOT NULL COMMENT 'templateID craftable',
  `skills` varchar(255) DEFAULT NULL,
  `AP` text NOT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `drops` (
  `monsterName` varchar(255) NOT NULL DEFAULT '',
  `monsterId` int(10) unsigned NOT NULL,
  `objectName` varchar(255) NOT NULL DEFAULT '',
  `objectId` int(10) unsigned NOT NULL,
  `percentGrade1` decimal(6,3) unsigned NOT NULL,
  `percentGrade2` decimal(6,3) unsigned NOT NULL,
  `percentGrade3` decimal(6,3) unsigned NOT NULL,
  `percentGrade4` decimal(6,3) unsigned NOT NULL,
  `percentGrade5` decimal(6,3) unsigned NOT NULL,
  `ceil` smallint(5) unsigned NOT NULL COMMENT 'Prospection ceil',
  `action` varchar(255) NOT NULL DEFAULT '1',
  `level` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`monsterId`,`objectId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `monsters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gfxID` int(11) NOT NULL,
  `align` int(11) NOT NULL,
  `grades` text NOT NULL,
  `colors` varchar(30) NOT NULL DEFAULT '-1,-1,-1',
  `stats` text NOT NULL COMMENT 'For,Sag,Int,Cha,Agi',
  `statsInfos` varchar(200) NOT NULL DEFAULT '0;0;0;1' COMMENT 'dmg;%dmg;soins;créainv',
  `spells` text NOT NULL,
  `pdvs` varchar(200) NOT NULL DEFAULT '1|1|1|1|1|1|1|1|1|1',
  `points` varchar(200) NOT NULL DEFAULT '1;1|1;1|1;1|1;1|1;1|1;1|1;1|1;1|1;1|1;1',
  `inits` varchar(200) NOT NULL DEFAULT '1|1|1|1|1|1|1|1|1|1',
  `minKamas` int(11) NOT NULL DEFAULT 0,
  `maxKamas` int(11) NOT NULL DEFAULT 0,
  `exps` varchar(200) NOT NULL DEFAULT '1|1|1|1|1|1|1|1|1|1',
  `AI_Type` int(11) NOT NULL DEFAULT 1 COMMENT '0: poutch 1: Agressif 2: Fuyarde 3: Soutient 4: Spécial',
  `capturable` int(11) NOT NULL DEFAULT 1,
  `type` int(11) NOT NULL DEFAULT 1 COMMENT '1 : Monster, 2 : Mascotte, 3 : Archi monster',
  `aggroDistance` tinyint(4) DEFAULT 0,
  `isBoss` tinyint(4) DEFAULT 0,
  `isArchmonster` tinyint(4) DEFAULT 0,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `item_template` (
  `id` int(11) NOT NULL DEFAULT -1,
  `type` int(11) NOT NULL DEFAULT -1,
  `name` varchar(50) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT 1,
  `statsTemplate` varchar(300) NOT NULL DEFAULT '',
  `pod` int(11) NOT NULL DEFAULT 0,
  `panoplie` int(11) NOT NULL DEFAULT -1,
  `prix` int(11) NOT NULL DEFAULT 0 COMMENT 'prix de vente PAR un Npc',
  `conditions` varchar(100) NOT NULL DEFAULT '',
  `armesInfos` varchar(100) NOT NULL DEFAULT '',
  `sold` int(11) NOT NULL DEFAULT 0,
  `avgPrice` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 0,
  `exchangesObject` int(11) NOT NULL DEFAULT 0,
  `newPrice` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
CREATE TABLE `objectsactions` (
  `template` int(11) NOT NULL DEFAULT 0,
  `type` varchar(100) NOT NULL DEFAULT '',
  `args` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`template`) USING BTREE,
  KEY `template` (`template`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
