
SET NAMES utf8mb4;



DROP TABLE IF EXISTS `object_localized_data_2`;
CREATE TABLE `object_localized_data_2` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `shortText` longtext,
  `text` longtext,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_data_5`;
CREATE TABLE `object_localized_data_5` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `text` longtext,
  `tags` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`),
  KEY `p_index_tags` (`tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_data_6`;
CREATE TABLE `object_localized_data_6` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_2_de`;
CREATE TABLE `object_localized_query_2_de` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `shortText` longtext,
  `text` longtext,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_2_en`;
CREATE TABLE `object_localized_query_2_en` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `shortText` longtext,
  `text` longtext,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_5_de`;
CREATE TABLE `object_localized_query_5_de` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `text` longtext,
  `tags` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`),
  KEY `p_index_tags` (`tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_5_en`;
CREATE TABLE `object_localized_query_5_en` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(190) DEFAULT NULL,
  `text` longtext,
  `tags` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`),
  KEY `p_index_tags` (`tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_6_de`;
CREATE TABLE `object_localized_query_6_de` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_localized_query_6_en`;
CREATE TABLE `object_localized_query_6_en` (
  `ooo_id` int(11) NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(190) DEFAULT NULL,
  PRIMARY KEY (`ooo_id`,`language`),
  KEY `ooo_id` (`ooo_id`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_2`;
CREATE TABLE `object_query_2` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '2',
  `oo_className` varchar(255) DEFAULT 'news',
  `date` bigint(20) DEFAULT NULL,
  `image_1` int(11) DEFAULT NULL,
  `image_2` int(11) DEFAULT NULL,
  `image_3` int(11) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_3`;
CREATE TABLE `object_query_3` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '3',
  `oo_className` varchar(255) DEFAULT 'inquiry',
  `person__id` int(11) DEFAULT NULL,
  `person__type` enum('document','asset','object') DEFAULT NULL,
  `date` bigint(20) DEFAULT NULL,
  `message` longtext,
  `terms` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_4`;
CREATE TABLE `object_query_4` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '4',
  `oo_className` varchar(255) DEFAULT 'persons',
  `gender` varchar(190) DEFAULT NULL,
  `firstname` varchar(190) DEFAULT NULL,
  `lastname` varchar(190) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `newsletterActive` tinyint(1) DEFAULT NULL,
  `newsletterConfirmed` tinyint(1) DEFAULT NULL,
  `dateRegister` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_5`;
CREATE TABLE `object_query_5` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '5',
  `oo_className` varchar(255) DEFAULT 'blogArticle',
  `date` bigint(20) DEFAULT NULL,
  `categories` text,
  `posterImage__image` int(11) DEFAULT NULL,
  `posterImage__hotspots` text,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_6`;
CREATE TABLE `object_query_6` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '6',
  `oo_className` varchar(255) DEFAULT 'blogCategory',
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_query_7`;
CREATE TABLE `object_query_7` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `oo_classId` int(11) DEFAULT '7',
  `oo_className` varchar(255) DEFAULT 'user',
  `username` varchar(190) DEFAULT NULL,
  `password` varchar(190) DEFAULT NULL,
  `roles` text,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_2`;
CREATE TABLE `object_relations_2` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_3`;
CREATE TABLE `object_relations_3` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_4`;
CREATE TABLE `object_relations_4` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_5`;
CREATE TABLE `object_relations_5` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_6`;
CREATE TABLE `object_relations_6` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_relations_7`;
CREATE TABLE `object_relations_7` (
  `src_id` int(11) NOT NULL DEFAULT '0',
  `dest_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `fieldname` varchar(70) NOT NULL DEFAULT '0',
  `index` int(11) unsigned NOT NULL DEFAULT '0',
  `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
  `ownername` varchar(70) NOT NULL DEFAULT '',
  `position` varchar(70) NOT NULL DEFAULT '0',
  PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
  KEY `index` (`index`),
  KEY `src_id` (`src_id`),
  KEY `dest_id` (`dest_id`),
  KEY `fieldname` (`fieldname`),
  KEY `position` (`position`),
  KEY `ownertype` (`ownertype`),
  KEY `type` (`type`),
  KEY `ownername` (`ownername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_2`;
CREATE TABLE `object_store_2` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `date` bigint(20) DEFAULT NULL,
  `image_1` int(11) DEFAULT NULL,
  `image_2` int(11) DEFAULT NULL,
  `image_3` int(11) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_3`;
CREATE TABLE `object_store_3` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `date` bigint(20) DEFAULT NULL,
  `message` longtext,
  `terms` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_4`;
CREATE TABLE `object_store_4` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `gender` varchar(190) DEFAULT NULL,
  `firstname` varchar(190) DEFAULT NULL,
  `lastname` varchar(190) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `newsletterActive` tinyint(1) DEFAULT NULL,
  `newsletterConfirmed` tinyint(1) DEFAULT NULL,
  `dateRegister` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_5`;
CREATE TABLE `object_store_5` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `date` bigint(20) DEFAULT NULL,
  `posterImage__image` int(11) DEFAULT NULL,
  `posterImage__hotspots` text,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_6`;
CREATE TABLE `object_store_6` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



DROP TABLE IF EXISTS `object_store_7`;
CREATE TABLE `object_store_7` (
  `oo_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(190) DEFAULT NULL,
  `password` varchar(190) DEFAULT NULL,
  `roles` text,
  PRIMARY KEY (`oo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



INSERT INTO `assets` VALUES ('1','0','folder','','/','','1368522989','1368522989','1','1','','0');
INSERT INTO `assets` VALUES ('3','1','folder','portal-sujets','/','','1368530371','1368632469','0','0','','0');
INSERT INTO `assets` VALUES ('4','3','image','slide-01.jpg','/portal-sujets/','image/jpeg','1368530684','1370432846','0','0','','0');
INSERT INTO `assets` VALUES ('5','3','image','slide-02.jpg','/portal-sujets/','image/jpeg','1368530764','1370432868','0','0','','0');
INSERT INTO `assets` VALUES ('6','3','image','slide-03.jpg','/portal-sujets/','image/jpeg','1368530764','1370432860','0','0','','0');
INSERT INTO `assets` VALUES ('7','1','folder','examples','/','','1368531816','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('17','7','folder','panama','/examples/','','1368532826','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('18','17','image','img_0117.jpg','/examples/panama/','image/jpeg','1368532831','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('19','17','image','img_0201.jpg','/examples/panama/','image/jpeg','1368532832','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('20','17','image','img_0089.jpg','/examples/panama/','image/jpeg','1368532833','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('21','17','image','img_0037.jpg','/examples/panama/','image/jpeg','1368532834','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('22','17','image','img_0399.jpg','/examples/panama/','image/jpeg','1368532836','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('23','17','image','img_0411.jpg','/examples/panama/','image/jpeg','1368532837','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('24','17','image','img_0410.jpg','/examples/panama/','image/jpeg','1368532838','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('25','17','image','img_0160.jpg','/examples/panama/','image/jpeg','1368532839','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('26','1','folder','videos','/','','1368542684','1368632471','0','0','','0');
INSERT INTO `assets` VALUES ('27','26','video','home-trailer-english.mp4','/videos/','video/mp4','1368542794','1405922844','0','0','','0');
INSERT INTO `assets` VALUES ('29','1','folder','documents','/','','1368548619','1368632467','0','0','','0');
INSERT INTO `assets` VALUES ('34','1','folder','screenshots','/','','1368560793','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('35','34','image','glossary.png','/screenshots/','image/png','1368560809','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('36','29','document','pimcore_t-mobile.pdf','/documents/','application/pdf','1368562442','1368632467','0','0','','0');
INSERT INTO `assets` VALUES ('37','7','folder','italy','/examples/','','1368596763','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('38','37','image','dsc04346.jpg','/examples/italy/','image/jpeg','1368596767','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('39','37','image','dsc04344.jpg','/examples/italy/','image/jpeg','1368596768','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('40','37','image','dsc04462.jpg','/examples/italy/','image/jpeg','1368596769','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('41','37','image','dsc04399.jpg','/examples/italy/','image/jpeg','1368596770','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('42','7','folder','south-africa','/examples/','','1368596785','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('43','42','image','img_1414.jpg','/examples/south-africa/','image/jpeg','1368596789','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('44','42','image','img_2133.jpg','/examples/south-africa/','image/jpeg','1368596791','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('45','42','image','img_2240.jpg','/examples/south-africa/','image/jpeg','1368596793','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('46','42','image','img_1752.jpg','/examples/south-africa/','image/jpeg','1368596795','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('47','42','image','img_1739.jpg','/examples/south-africa/','image/jpeg','1368596798','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('48','42','image','img_0391.jpg','/examples/south-africa/','image/jpeg','1368596800','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('49','42','image','img_2155.jpg','/examples/south-africa/','image/jpeg','1368596801','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('50','42','image','img_1544.jpg','/examples/south-africa/','image/jpeg','1368596804','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('51','42','image','img_1842.jpg','/examples/south-africa/','image/jpeg','1368596806','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('52','42','image','img_1920.jpg','/examples/south-africa/','image/jpeg','1368596808','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('53','42','image','img_0322.jpg','/examples/south-africa/','image/jpeg','1368596810','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('54','7','folder','singapore','/examples/','','1368596871','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('55','54','image','dsc03778.jpg','/examples/singapore/','image/jpeg','1368597116','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('56','54','image','dsc03807.jpg','/examples/singapore/','image/jpeg','1368597117','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('57','54','image','dsc03835.jpg','/examples/singapore/','image/jpeg','1368597119','1368632468','0','0','','0');
INSERT INTO `assets` VALUES ('59','34','image','thumbnail-configuration.png','/screenshots/','image/png','1368606782','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('60','34','image','website-translations.png','/screenshots/','image/png','1368608949','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('61','34','image','properties-1.png','/screenshots/','image/png','1368616805','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('62','34','image','properties-2.png','/screenshots/','image/png','1368616805','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('63','34','image','properties-3.png','/screenshots/','image/png','1368616847','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('64','34','image','tag-snippet-management.png','/screenshots/','image/png','1368617634','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('65','34','image','objects-forms.png','/screenshots/','image/png','1368623266','1368632470','0','0','','0');
INSERT INTO `assets` VALUES ('66','29','document','example-excel.xlsx','/documents/','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','1378992590','1378992590','0','0','','0');
INSERT INTO `assets` VALUES ('67','29','document','example.docx','/documents/','application/vnd.openxmlformats-officedocument.wordprocessingml.document','1378992591','1378992591','0','0','','0');
INSERT INTO `assets` VALUES ('68','29','document','example.pptx','/documents/','application/vnd.openxmlformats-officedocument.presentationml.presentation','1378992592','1378992592','0','0','','0');
INSERT INTO `assets` VALUES ('69','34','image','e-commerce1.png','/screenshots/','image/png','1388740480','1388740490','0','0','','0');
INSERT INTO `assets` VALUES ('70','34','image','pim1.png','/screenshots/','image/png','1388740572','1388740580','0','0','','0');
INSERT INTO `classes` VALUES ('5','blogArticle');
INSERT INTO `classes` VALUES ('6','blogCategory');
INSERT INTO `classes` VALUES ('3','inquiry');
INSERT INTO `classes` VALUES ('2','news');
INSERT INTO `classes` VALUES ('4','person');
INSERT INTO `classes` VALUES ('7','user');
INSERT INTO `dependencies` VALUES ('document','1','asset','4');
INSERT INTO `dependencies` VALUES ('document','1','document','5');
INSERT INTO `dependencies` VALUES ('document','1','asset','5');
INSERT INTO `dependencies` VALUES ('document','1','document','6');
INSERT INTO `dependencies` VALUES ('document','1','asset','6');
INSERT INTO `dependencies` VALUES ('document','1','document','15');
INSERT INTO `dependencies` VALUES ('document','1','document','16');
INSERT INTO `dependencies` VALUES ('document','1','document','17');
INSERT INTO `dependencies` VALUES ('document','1','asset','18');
INSERT INTO `dependencies` VALUES ('document','1','document','19');
INSERT INTO `dependencies` VALUES ('document','1','asset','19');
INSERT INTO `dependencies` VALUES ('document','1','document','20');
INSERT INTO `dependencies` VALUES ('document','1','asset','22');
INSERT INTO `dependencies` VALUES ('document','1','asset','23');
INSERT INTO `dependencies` VALUES ('document','1','asset','24');
INSERT INTO `dependencies` VALUES ('document','1','asset','27');
INSERT INTO `dependencies` VALUES ('document','1','document','40');
INSERT INTO `dependencies` VALUES ('document','1','asset','55');
INSERT INTO `dependencies` VALUES ('document','1','document','57');
INSERT INTO `dependencies` VALUES ('document','1','document','60');
INSERT INTO `dependencies` VALUES ('document','3','document','7');
INSERT INTO `dependencies` VALUES ('document','3','document','18');
INSERT INTO `dependencies` VALUES ('document','3','document','19');
INSERT INTO `dependencies` VALUES ('document','3','document','20');
INSERT INTO `dependencies` VALUES ('document','3','document','21');
INSERT INTO `dependencies` VALUES ('document','3','asset','22');
INSERT INTO `dependencies` VALUES ('document','3','document','24');
INSERT INTO `dependencies` VALUES ('document','3','document','25');
INSERT INTO `dependencies` VALUES ('document','3','document','26');
INSERT INTO `dependencies` VALUES ('document','3','document','27');
INSERT INTO `dependencies` VALUES ('document','3','document','28');
INSERT INTO `dependencies` VALUES ('document','3','document','29');
INSERT INTO `dependencies` VALUES ('document','3','asset','39');
INSERT INTO `dependencies` VALUES ('document','3','document','40');
INSERT INTO `dependencies` VALUES ('document','3','asset','40');
INSERT INTO `dependencies` VALUES ('document','3','asset','41');
INSERT INTO `dependencies` VALUES ('document','3','asset','44');
INSERT INTO `dependencies` VALUES ('document','3','asset','45');
INSERT INTO `dependencies` VALUES ('document','3','asset','46');
INSERT INTO `dependencies` VALUES ('document','3','asset','47');
INSERT INTO `dependencies` VALUES ('document','3','asset','50');
INSERT INTO `dependencies` VALUES ('document','3','asset','55');
INSERT INTO `dependencies` VALUES ('document','3','asset','56');
INSERT INTO `dependencies` VALUES ('document','3','asset','57');
INSERT INTO `dependencies` VALUES ('document','3','document','60');
INSERT INTO `dependencies` VALUES ('document','4','document','5');
INSERT INTO `dependencies` VALUES ('document','4','document','15');
INSERT INTO `dependencies` VALUES ('document','4','document','16');
INSERT INTO `dependencies` VALUES ('document','4','document','17');
INSERT INTO `dependencies` VALUES ('document','4','asset','22');
INSERT INTO `dependencies` VALUES ('document','4','asset','24');
INSERT INTO `dependencies` VALUES ('document','4','document','40');
INSERT INTO `dependencies` VALUES ('document','4','document','59');
INSERT INTO `dependencies` VALUES ('document','4','document','60');
INSERT INTO `dependencies` VALUES ('document','5','document','40');
INSERT INTO `dependencies` VALUES ('document','5','document','60');
INSERT INTO `dependencies` VALUES ('document','5','document','69');
INSERT INTO `dependencies` VALUES ('document','6','document','40');
INSERT INTO `dependencies` VALUES ('document','6','document','57');
INSERT INTO `dependencies` VALUES ('document','6','document','60');
INSERT INTO `dependencies` VALUES ('document','7','document','3');
INSERT INTO `dependencies` VALUES ('document','7','asset','27');
INSERT INTO `dependencies` VALUES ('document','7','document','40');
INSERT INTO `dependencies` VALUES ('document','7','document','57');
INSERT INTO `dependencies` VALUES ('document','7','document','60');
INSERT INTO `dependencies` VALUES ('document','9','document','5');
INSERT INTO `dependencies` VALUES ('document','9','asset','65');
INSERT INTO `dependencies` VALUES ('document','10','document','40');
INSERT INTO `dependencies` VALUES ('document','12','document','40');
INSERT INTO `dependencies` VALUES ('document','15','document','1');
INSERT INTO `dependencies` VALUES ('document','15','document','3');
INSERT INTO `dependencies` VALUES ('document','15','asset','21');
INSERT INTO `dependencies` VALUES ('document','16','document','1');
INSERT INTO `dependencies` VALUES ('document','16','document','5');
INSERT INTO `dependencies` VALUES ('document','16','asset','20');
INSERT INTO `dependencies` VALUES ('document','17','document','1');
INSERT INTO `dependencies` VALUES ('document','17','document','6');
INSERT INTO `dependencies` VALUES ('document','17','asset','18');
INSERT INTO `dependencies` VALUES ('document','18','document','3');
INSERT INTO `dependencies` VALUES ('document','18','asset','36');
INSERT INTO `dependencies` VALUES ('document','18','document','40');
INSERT INTO `dependencies` VALUES ('document','18','document','57');
INSERT INTO `dependencies` VALUES ('document','18','document','60');
INSERT INTO `dependencies` VALUES ('document','19','document','3');
INSERT INTO `dependencies` VALUES ('document','19','asset','17');
INSERT INTO `dependencies` VALUES ('document','19','asset','38');
INSERT INTO `dependencies` VALUES ('document','19','asset','39');
INSERT INTO `dependencies` VALUES ('document','19','document','40');
INSERT INTO `dependencies` VALUES ('document','19','asset','40');
INSERT INTO `dependencies` VALUES ('document','19','asset','41');
INSERT INTO `dependencies` VALUES ('document','19','asset','43');
INSERT INTO `dependencies` VALUES ('document','19','asset','46');
INSERT INTO `dependencies` VALUES ('document','19','asset','47');
INSERT INTO `dependencies` VALUES ('document','19','asset','48');
INSERT INTO `dependencies` VALUES ('document','19','asset','49');
INSERT INTO `dependencies` VALUES ('document','19','asset','50');
INSERT INTO `dependencies` VALUES ('document','19','asset','51');
INSERT INTO `dependencies` VALUES ('document','19','asset','52');
INSERT INTO `dependencies` VALUES ('document','19','asset','53');
INSERT INTO `dependencies` VALUES ('document','19','document','57');
INSERT INTO `dependencies` VALUES ('document','19','document','60');
INSERT INTO `dependencies` VALUES ('document','20','document','3');
INSERT INTO `dependencies` VALUES ('document','20','asset','35');
INSERT INTO `dependencies` VALUES ('document','20','document','40');
INSERT INTO `dependencies` VALUES ('document','20','document','57');
INSERT INTO `dependencies` VALUES ('document','20','document','60');
INSERT INTO `dependencies` VALUES ('document','21','document','3');
INSERT INTO `dependencies` VALUES ('document','21','document','40');
INSERT INTO `dependencies` VALUES ('document','21','asset','53');
INSERT INTO `dependencies` VALUES ('document','21','document','57');
INSERT INTO `dependencies` VALUES ('document','21','asset','59');
INSERT INTO `dependencies` VALUES ('document','21','document','60');
INSERT INTO `dependencies` VALUES ('document','22','document','3');
INSERT INTO `dependencies` VALUES ('document','22','document','23');
INSERT INTO `dependencies` VALUES ('document','22','document','40');
INSERT INTO `dependencies` VALUES ('document','22','document','57');
INSERT INTO `dependencies` VALUES ('document','22','document','60');
INSERT INTO `dependencies` VALUES ('document','22','asset','60');
INSERT INTO `dependencies` VALUES ('document','23','document','41');
INSERT INTO `dependencies` VALUES ('document','24','document','3');
INSERT INTO `dependencies` VALUES ('document','24','document','7');
INSERT INTO `dependencies` VALUES ('document','24','document','21');
INSERT INTO `dependencies` VALUES ('document','24','asset','22');
INSERT INTO `dependencies` VALUES ('document','24','asset','24');
INSERT INTO `dependencies` VALUES ('document','24','document','26');
INSERT INTO `dependencies` VALUES ('document','24','document','27');
INSERT INTO `dependencies` VALUES ('document','24','asset','27');
INSERT INTO `dependencies` VALUES ('document','24','document','40');
INSERT INTO `dependencies` VALUES ('document','24','asset','44');
INSERT INTO `dependencies` VALUES ('document','24','asset','48');
INSERT INTO `dependencies` VALUES ('document','24','asset','49');
INSERT INTO `dependencies` VALUES ('document','24','asset','51');
INSERT INTO `dependencies` VALUES ('document','24','asset','52');
INSERT INTO `dependencies` VALUES ('document','24','asset','53');
INSERT INTO `dependencies` VALUES ('document','24','document','57');
INSERT INTO `dependencies` VALUES ('document','24','document','60');
INSERT INTO `dependencies` VALUES ('document','25','document','3');
INSERT INTO `dependencies` VALUES ('document','25','document','15');
INSERT INTO `dependencies` VALUES ('document','25','document','19');
INSERT INTO `dependencies` VALUES ('document','25','document','20');
INSERT INTO `dependencies` VALUES ('document','25','document','21');
INSERT INTO `dependencies` VALUES ('document','25','asset','27');
INSERT INTO `dependencies` VALUES ('document','25','document','40');
INSERT INTO `dependencies` VALUES ('document','25','asset','44');
INSERT INTO `dependencies` VALUES ('document','25','asset','45');
INSERT INTO `dependencies` VALUES ('document','25','asset','47');
INSERT INTO `dependencies` VALUES ('document','25','asset','51');
INSERT INTO `dependencies` VALUES ('document','25','asset','54');
INSERT INTO `dependencies` VALUES ('document','25','document','57');
INSERT INTO `dependencies` VALUES ('document','25','document','60');
INSERT INTO `dependencies` VALUES ('document','26','document','3');
INSERT INTO `dependencies` VALUES ('document','26','document','40');
INSERT INTO `dependencies` VALUES ('document','26','document','57');
INSERT INTO `dependencies` VALUES ('document','27','document','3');
INSERT INTO `dependencies` VALUES ('document','27','document','40');
INSERT INTO `dependencies` VALUES ('document','27','document','57');
INSERT INTO `dependencies` VALUES ('document','27','document','60');
INSERT INTO `dependencies` VALUES ('document','28','document','3');
INSERT INTO `dependencies` VALUES ('document','28','asset','61');
INSERT INTO `dependencies` VALUES ('document','28','asset','62');
INSERT INTO `dependencies` VALUES ('document','28','asset','63');
INSERT INTO `dependencies` VALUES ('document','29','document','3');
INSERT INTO `dependencies` VALUES ('document','29','document','40');
INSERT INTO `dependencies` VALUES ('document','29','document','57');
INSERT INTO `dependencies` VALUES ('document','29','document','60');
INSERT INTO `dependencies` VALUES ('document','29','asset','64');
INSERT INTO `dependencies` VALUES ('document','30','document','5');
INSERT INTO `dependencies` VALUES ('document','30','document','40');
INSERT INTO `dependencies` VALUES ('document','30','asset','53');
INSERT INTO `dependencies` VALUES ('document','30','document','60');
INSERT INTO `dependencies` VALUES ('document','30','document','69');
INSERT INTO `dependencies` VALUES ('document','31','document','5');
INSERT INTO `dependencies` VALUES ('document','31','document','30');
INSERT INTO `dependencies` VALUES ('document','31','document','40');
INSERT INTO `dependencies` VALUES ('document','31','document','60');
INSERT INTO `dependencies` VALUES ('document','31','document','69');
INSERT INTO `dependencies` VALUES ('document','32','document','3');
INSERT INTO `dependencies` VALUES ('document','33','document','3');
INSERT INTO `dependencies` VALUES ('document','33','document','5');
INSERT INTO `dependencies` VALUES ('document','34','document','5');
INSERT INTO `dependencies` VALUES ('document','35','document','5');
INSERT INTO `dependencies` VALUES ('document','35','asset','51');
INSERT INTO `dependencies` VALUES ('document','35','asset','53');
INSERT INTO `dependencies` VALUES ('document','36','document','5');
INSERT INTO `dependencies` VALUES ('document','36','document','40');
INSERT INTO `dependencies` VALUES ('document','36','document','57');
INSERT INTO `dependencies` VALUES ('document','37','document','5');
INSERT INTO `dependencies` VALUES ('document','37','document','38');
INSERT INTO `dependencies` VALUES ('document','38','document','5');
INSERT INTO `dependencies` VALUES ('document','39','document','1');
INSERT INTO `dependencies` VALUES ('document','40','document','1');
INSERT INTO `dependencies` VALUES ('document','41','asset','4');
INSERT INTO `dependencies` VALUES ('document','41','document','5');
INSERT INTO `dependencies` VALUES ('document','41','asset','5');
INSERT INTO `dependencies` VALUES ('document','41','document','6');
INSERT INTO `dependencies` VALUES ('document','41','asset','6');
INSERT INTO `dependencies` VALUES ('document','41','asset','18');
INSERT INTO `dependencies` VALUES ('document','41','asset','19');
INSERT INTO `dependencies` VALUES ('document','41','asset','27');
INSERT INTO `dependencies` VALUES ('document','41','document','47');
INSERT INTO `dependencies` VALUES ('document','41','document','48');
INSERT INTO `dependencies` VALUES ('document','41','document','49');
INSERT INTO `dependencies` VALUES ('document','41','asset','55');
INSERT INTO `dependencies` VALUES ('document','41','document','58');
INSERT INTO `dependencies` VALUES ('document','42','document','40');
INSERT INTO `dependencies` VALUES ('document','43','document','40');
INSERT INTO `dependencies` VALUES ('document','44','document','40');
INSERT INTO `dependencies` VALUES ('document','45','document','40');
INSERT INTO `dependencies` VALUES ('document','46','document','40');
INSERT INTO `dependencies` VALUES ('document','47','document','3');
INSERT INTO `dependencies` VALUES ('document','47','asset','21');
INSERT INTO `dependencies` VALUES ('document','47','document','40');
INSERT INTO `dependencies` VALUES ('document','48','document','5');
INSERT INTO `dependencies` VALUES ('document','48','asset','20');
INSERT INTO `dependencies` VALUES ('document','48','document','40');
INSERT INTO `dependencies` VALUES ('document','49','document','6');
INSERT INTO `dependencies` VALUES ('document','49','asset','18');
INSERT INTO `dependencies` VALUES ('document','49','document','40');
INSERT INTO `dependencies` VALUES ('document','50','document','5');
INSERT INTO `dependencies` VALUES ('document','50','asset','22');
INSERT INTO `dependencies` VALUES ('document','50','asset','24');
INSERT INTO `dependencies` VALUES ('document','50','document','41');
INSERT INTO `dependencies` VALUES ('document','50','document','47');
INSERT INTO `dependencies` VALUES ('document','50','document','48');
INSERT INTO `dependencies` VALUES ('document','50','document','49');
INSERT INTO `dependencies` VALUES ('document','51','document','3');
INSERT INTO `dependencies` VALUES ('document','51','document','41');
INSERT INTO `dependencies` VALUES ('document','52','document','5');
INSERT INTO `dependencies` VALUES ('document','52','document','41');
INSERT INTO `dependencies` VALUES ('document','53','document','41');
INSERT INTO `dependencies` VALUES ('document','57','document','15');
INSERT INTO `dependencies` VALUES ('document','57','document','40');
INSERT INTO `dependencies` VALUES ('document','58','document','41');
INSERT INTO `dependencies` VALUES ('document','58','document','47');
INSERT INTO `dependencies` VALUES ('document','58','document','49');
INSERT INTO `dependencies` VALUES ('document','58','document','57');
INSERT INTO `dependencies` VALUES ('document','59','document','15');
INSERT INTO `dependencies` VALUES ('document','59','document','16');
INSERT INTO `dependencies` VALUES ('document','59','document','40');
INSERT INTO `dependencies` VALUES ('document','60','document','5');
INSERT INTO `dependencies` VALUES ('document','60','document','40');
INSERT INTO `dependencies` VALUES ('document','60','document','69');
INSERT INTO `dependencies` VALUES ('document','61','document','5');
INSERT INTO `dependencies` VALUES ('document','61','document','40');
INSERT INTO `dependencies` VALUES ('document','61','document','57');
INSERT INTO `dependencies` VALUES ('document','62','document','40');
INSERT INTO `dependencies` VALUES ('document','62','document','57');
INSERT INTO `dependencies` VALUES ('document','63','document','5');
INSERT INTO `dependencies` VALUES ('document','63','document','40');
INSERT INTO `dependencies` VALUES ('document','63','document','57');
INSERT INTO `dependencies` VALUES ('document','64','document','5');
INSERT INTO `dependencies` VALUES ('document','64','document','40');
INSERT INTO `dependencies` VALUES ('document','64','document','57');
INSERT INTO `dependencies` VALUES ('document','65','document','5');
INSERT INTO `dependencies` VALUES ('document','65','document','40');
INSERT INTO `dependencies` VALUES ('document','65','document','57');
INSERT INTO `dependencies` VALUES ('document','66','document','5');
INSERT INTO `dependencies` VALUES ('document','66','document','40');
INSERT INTO `dependencies` VALUES ('document','66','document','57');
INSERT INTO `dependencies` VALUES ('document','67','asset','22');
INSERT INTO `dependencies` VALUES ('document','67','document','40');
INSERT INTO `dependencies` VALUES ('document','67','document','57');
INSERT INTO `dependencies` VALUES ('document','68','document','5');
INSERT INTO `dependencies` VALUES ('document','68','document','40');
INSERT INTO `dependencies` VALUES ('document','68','document','57');
INSERT INTO `dependencies` VALUES ('document','69','document','5');
INSERT INTO `dependencies` VALUES ('document','69','document','40');
INSERT INTO `dependencies` VALUES ('document','69','document','57');
INSERT INTO `dependencies` VALUES ('document','69','document','60');
INSERT INTO `dependencies` VALUES ('document','70','document','5');
INSERT INTO `dependencies` VALUES ('document','70','document','40');
INSERT INTO `dependencies` VALUES ('document','70','document','60');
INSERT INTO `dependencies` VALUES ('document','70','document','69');
INSERT INTO `dependencies` VALUES ('document','70','asset','70');
INSERT INTO `dependencies` VALUES ('document','71','document','5');
INSERT INTO `dependencies` VALUES ('document','71','document','40');
INSERT INTO `dependencies` VALUES ('document','71','document','60');
INSERT INTO `dependencies` VALUES ('document','71','document','69');
INSERT INTO `dependencies` VALUES ('document','71','asset','69');
INSERT INTO `dependencies` VALUES ('document','72','document','5');
INSERT INTO `dependencies` VALUES ('document','72','document','40');
INSERT INTO `dependencies` VALUES ('document','72','document','60');
INSERT INTO `dependencies` VALUES ('document','72','document','69');
INSERT INTO `dependencies` VALUES ('document','73','document','3');
INSERT INTO `dependencies` VALUES ('document','73','document','40');
INSERT INTO `dependencies` VALUES ('document','73','document','57');
INSERT INTO `dependencies` VALUES ('document','73','document','60');
INSERT INTO `dependencies` VALUES ('object','3','document','19');
INSERT INTO `dependencies` VALUES ('object','3','document','24');
INSERT INTO `dependencies` VALUES ('object','3','asset','43');
INSERT INTO `dependencies` VALUES ('object','3','asset','49');
INSERT INTO `dependencies` VALUES ('object','3','asset','52');
INSERT INTO `dependencies` VALUES ('object','4','document','3');
INSERT INTO `dependencies` VALUES ('object','4','document','27');
INSERT INTO `dependencies` VALUES ('object','4','asset','51');
INSERT INTO `dependencies` VALUES ('object','6','asset','25');
INSERT INTO `dependencies` VALUES ('object','7','asset','18');
INSERT INTO `dependencies` VALUES ('object','8','asset','20');
INSERT INTO `dependencies` VALUES ('object','9','asset','21');
INSERT INTO `dependencies` VALUES ('object','29','object','28');
INSERT INTO `dependencies` VALUES ('object','31','object','30');
INSERT INTO `dependencies` VALUES ('object','35','object','37');
INSERT INTO `dependencies` VALUES ('object','35','object','38');
INSERT INTO `dependencies` VALUES ('object','39','asset','23');
INSERT INTO `dependencies` VALUES ('object','39','object','38');
INSERT INTO `dependencies` VALUES ('object','40','asset','20');
INSERT INTO `dependencies` VALUES ('object','40','asset','21');
INSERT INTO `dependencies` VALUES ('object','40','object','36');
INSERT INTO `documents` VALUES ('1','0','page','','/','999999','1','1368522989','1395151306','1',NULL);
INSERT INTO `documents` VALUES ('3','40','page','basic-examples','/en/','1','1','1368523212','1388738504','0','0');
INSERT INTO `documents` VALUES ('4','40','page','introduction','/en/','0','1','1368523285','1395042868','0',NULL);
INSERT INTO `documents` VALUES ('5','40','page','advanced-examples','/en/','2','1','1368523389','1388738496','0','0');
INSERT INTO `documents` VALUES ('6','40','page','experiments','/en/','3','1','1368523410','1395043974','0',NULL);
INSERT INTO `documents` VALUES ('7','3','page','html5-video','/en/basic-examples/','1','1','1368525394','1395042970','0',NULL);
INSERT INTO `documents` VALUES ('9','5','page','creating-objects-using-forms','/en/advanced-examples/','1','1','1368525933','1382956042','0','0');
INSERT INTO `documents` VALUES ('10','40','folder','shared','/en/','5','1','1368527956','1382956831','0','0');
INSERT INTO `documents` VALUES ('11','10','folder','includes','/en/shared/','1','1','1368527961','1382956831','0','0');
INSERT INTO `documents` VALUES ('12','11','snippet','footer','/en/shared/includes/','1','1','1368527967','1382956852','0','0');
INSERT INTO `documents` VALUES ('13','10','folder','teasers','/en/shared/','2','1','1368531657','1382956831','0','0');
INSERT INTO `documents` VALUES ('14','13','folder','standard','/en/shared/teasers/','1','1','1368531665','1382956831','0','0');
INSERT INTO `documents` VALUES ('15','14','snippet','basic-examples','/en/shared/teasers/standard/','1','1','1368531692','1382956831','0','0');
INSERT INTO `documents` VALUES ('16','14','snippet','advanced-examples','/en/shared/teasers/standard/','2','1','1368534298','1382956831','0','0');
INSERT INTO `documents` VALUES ('17','14','snippet','experiments','/en/shared/teasers/standard/','3','1','1368534344','1382956831','0','0');
INSERT INTO `documents` VALUES ('18','3','page','pdf-viewer','/en/basic-examples/','2','1','1368548449','1395042961','0',NULL);
INSERT INTO `documents` VALUES ('19','3','page','galleries','/en/basic-examples/','3','1','1368549805','1395043436','0',NULL);
INSERT INTO `documents` VALUES ('20','3','page','glossary','/en/basic-examples/','4','1','1368559903','1395043487','0',NULL);
INSERT INTO `documents` VALUES ('21','3','page','thumbnails','/en/basic-examples/','5','1','1368602443','1395043532','0',NULL);
INSERT INTO `documents` VALUES ('22','3','page','website-translations','/en/basic-examples/','7','1','1368607207','1395043561','0',NULL);
INSERT INTO `documents` VALUES ('23','51','page','website-uebersetzungen','/de/einfache-beispiele/','0','1','1368608357','1382958135','0','0');
INSERT INTO `documents` VALUES ('24','3','page','content-page','/en/basic-examples/','0','1','1368609059','1405923178','0',NULL);
INSERT INTO `documents` VALUES ('25','3','page','editable-roundup','/en/basic-examples/','8','1','1368609569','1395043587','0',NULL);
INSERT INTO `documents` VALUES ('26','3','page','form','/en/basic-examples/','9','1','1368610663','1388733533','0','0');
INSERT INTO `documents` VALUES ('27','3','page','news','/en/basic-examples/','10','1','1368613137','1395043614','0',NULL);
INSERT INTO `documents` VALUES ('28','3','page','properties','/en/basic-examples/','11','1','1368615986','1382956040','0','0');
INSERT INTO `documents` VALUES ('29','3','page','tag-and-snippet-management','/en/basic-examples/','12','1','1368617118','1395043636','0',NULL);
INSERT INTO `documents` VALUES ('30','5','page','content-inheritance','/en/advanced-examples/','2','1','1368623726','1395043816','0',NULL);
INSERT INTO `documents` VALUES ('31','30','page','content-inheritance','/en/advanced-examples/content-inheritance/','2','1','1368623866','1395043901','0',NULL);
INSERT INTO `documents` VALUES ('32','3','link','pimcore.org','/en/basic-examples/','13','1','1368626404','1382956040','0','0');
INSERT INTO `documents` VALUES ('33','34','hardlink','basic-examples','/en/advanced-examples/hard-link/','0','1','1368626461','1382956042','0','0');
INSERT INTO `documents` VALUES ('34','5','page','hard-link','/en/advanced-examples/','3','1','1368626655','1382956042','0','0');
INSERT INTO `documents` VALUES ('35','5','page','image-with-hotspots-and-markers','/en/advanced-examples/','4','1','1368626888','1382956042','0','0');
INSERT INTO `documents` VALUES ('36','5','page','search','/en/advanced-examples/','5','1','1368629524','1388733927','0','0');
INSERT INTO `documents` VALUES ('37','5','page','contact-form','/en/advanced-examples/','6','1','1368630444','1382956042','0','0');
INSERT INTO `documents` VALUES ('38','37','email','email','/en/advanced-examples/contact-form/','1','1','1368631410','1382956042','0','0');
INSERT INTO `documents` VALUES ('39','1','page','error','/','3','1','1369854325','1369854422','0','0');
INSERT INTO `documents` VALUES ('40','1','link','en','/','0','1','1382956013','1382956551','0','0');
INSERT INTO `documents` VALUES ('41','1','page','de','/','2','1','1382956716','1382962917','0','0');
INSERT INTO `documents` VALUES ('42','41','folder','shared','/de/','4','1','1382956884','1382956887','0','0');
INSERT INTO `documents` VALUES ('43','42','folder','includes','/de/shared/','1','1','1382956885','1382956888','0','0');
INSERT INTO `documents` VALUES ('44','42','folder','teasers','/de/shared/','2','1','1382956885','1382956888','0','0');
INSERT INTO `documents` VALUES ('45','44','folder','standard','/de/shared/teasers/','1','1','1382956885','1382956888','0','0');
INSERT INTO `documents` VALUES ('46','43','snippet','footer','/de/shared/includes/','1','1','1382956886','1382956919','0','0');
INSERT INTO `documents` VALUES ('47','45','snippet','basic-examples','/de/shared/teasers/standard/','1','1','1382956886','1382957000','0','0');
INSERT INTO `documents` VALUES ('48','45','snippet','advanced-examples','/de/shared/teasers/standard/','2','1','1382956886','1382957114','0','0');
INSERT INTO `documents` VALUES ('49','45','snippet','experiments','/de/shared/teasers/standard/','3','1','1382956887','1382957197','0','0');
INSERT INTO `documents` VALUES ('50','41','page','einfuehrung','/de/','0','1','1382957658','1382957760','0','0');
INSERT INTO `documents` VALUES ('51','41','page','einfache-beispiele','/de/','1','1','1382957793','1382957910','0','0');
INSERT INTO `documents` VALUES ('52','41','page','beispiele-fur-fortgeschrittene','/de/','2','1','1382957961','1382957999','0','0');
INSERT INTO `documents` VALUES ('53','51','page','neuigkeiten','/de/einfache-beispiele/','9','1','1382958188','1382958240','0','0');
INSERT INTO `documents` VALUES ('57','40','snippet','sidebar','/en/','4','1','1382962826','1388735598','0','0');
INSERT INTO `documents` VALUES ('58','41','snippet','sidebar','/de/','3','1','1382962891','1382962906','0','0');
INSERT INTO `documents` VALUES ('59','4','snippet','sidebar','/en/introduction/','1','1','1382962940','1388738272','0','0');
INSERT INTO `documents` VALUES ('60','5','page','blog','/en/advanced-examples/','0','1','1388391128','1395043669','7',NULL);
INSERT INTO `documents` VALUES ('61','5','page','sitemap','/en/advanced-examples/','7','1','1388406334','1388406406','0','0');
INSERT INTO `documents` VALUES ('62','1','folder','newsletters','/','5','1','1388409377','1388409377','0','0');
INSERT INTO `documents` VALUES ('63','5','page','newsletter','/en/advanced-examples/','8','1','1388409438','1388409571','0','0');
INSERT INTO `documents` VALUES ('64','63','page','confirm','/en/advanced-examples/newsletter/','1','1','1388409594','1388409641','0','0');
INSERT INTO `documents` VALUES ('65','63','page','unsubscribe','/en/advanced-examples/newsletter/','2','1','1388409614','1388412346','0','0');
INSERT INTO `documents` VALUES ('66','63','email','confirmation-email','/en/advanced-examples/newsletter/','3','1','1388409670','1388412587','0','0');
INSERT INTO `documents` VALUES ('67','62','newsletter','example-mailing','/newsletters/','1','1','1388412605','1388412917','0','0');
INSERT INTO `documents` VALUES ('68','5','page','asset-thumbnail-list','/en/advanced-examples/','9','1','1388414727','1388414883','0','0');
INSERT INTO `documents` VALUES ('69','5','snippet','sidebar','/en/advanced-examples/','13','1','1388734403','1388738477','0','0');
INSERT INTO `documents` VALUES ('70','5','page','product-information-management','/en/advanced-examples/','12','1','1388740191','1388740585','0','0');
INSERT INTO `documents` VALUES ('71','5','page','e-commerce','/en/advanced-examples/','11','1','1388740265','1388740613','0','0');
INSERT INTO `documents` VALUES ('72','5','page','sub-modules','/en/advanced-examples/','10','1','1419933647','1419933980','32','32');
INSERT INTO `documents` VALUES ('73','3','page','social-contents','/en/basic-examples/','6','1','1459501213','1459501429','39','39');
INSERT INTO `documents_elements` VALUES ('1','authorcontent3','input','Albert Einstein');
INSERT INTO `documents_elements` VALUES ('1','carouselSlides','select','3');
INSERT INTO `documents_elements` VALUES ('1','cHeadline_0','input','Ready to be impressed?');
INSERT INTO `documents_elements` VALUES ('1','cHeadline_1','input','It\'ll blow your mind.');
INSERT INTO `documents_elements` VALUES ('1','cHeadline_2','input','Oh yeah, it\'s that good');
INSERT INTO `documents_elements` VALUES ('1','cImage_0','image','a:9:{s:2:\"id\";i:4;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','cImage_1','image','a:9:{s:2:\"id\";i:5;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','cImage_2','image','a:9:{s:2:\"id\";i:6;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','cLink_0','link','a:14:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:18:\"/en/basic-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:3;s:12:\"internalType\";s:8:\"document\";}');
INSERT INTO `documents_elements` VALUES ('1','cLink_1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('1','cLink_2','link','a:15:{s:4:\"text\";s:9:\"Checkmate\";s:4:\"path\";s:12:\"/experiments\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:6;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('1','content','areablock','a:7:{i:0;a:2:{s:3:\"key\";s:1:\"6\";s:4:\"type\";s:15:\"icon-teaser-row\";}i:1;a:2:{s:3:\"key\";s:1:\"5\";s:4:\"type\";s:15:\"horizontal-line\";}i:2;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:10:\"featurette\";}i:3;a:2:{s:3:\"key\";s:1:\"7\";s:4:\"type\";s:18:\"tabbed-slider-text\";}i:4;a:2:{s:3:\"key\";s:1:\"8\";s:4:\"type\";s:15:\"horizontal-line\";}i:5;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:6;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:16:\"gallery-carousel\";}}');
INSERT INTO `documents_elements` VALUES ('1','content:1.block','block','a:3:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";}');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.content','wysiwyg','<p>In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi.</p>\n');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.headline','input','Lorem ipsum.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.image','image','a:9:{s:2:\"id\";i:55;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.position','select','');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.subline','input','Cum sociis.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:1.type','select','');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.content','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>\n');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.headline','input','Oh yeah, it\'s that good.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.position','select','left');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.subline','input','See for yourself.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.type','select','video');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:2.video','video','a:5:{s:2:\"id\";i:27;s:4:\"type\";s:5:\"asset\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:6:\"poster\";N;}');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.content','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>\n');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.headline','input','And lastly, this one.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.image','image','a:9:{s:2:\"id\";i:19;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.position','select','');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.subline','input','Checkmate.');
INSERT INTO `documents_elements` VALUES ('1','content:1.block:3.type','select','');
INSERT INTO `documents_elements` VALUES ('1','content:2.teaser_0','snippet','15');
INSERT INTO `documents_elements` VALUES ('1','content:2.teaser_1','snippet','16');
INSERT INTO `documents_elements` VALUES ('1','content:2.teaser_2','snippet','17');
INSERT INTO `documents_elements` VALUES ('1','content:2.type_0','select','');
INSERT INTO `documents_elements` VALUES ('1','content:2.type_1','select','');
INSERT INTO `documents_elements` VALUES ('1','content:2.type_2','select','');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-text-0','textarea','Isla Colón, Bocas del Toro, Republic of Panama');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-text-1','textarea','');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-text-2','textarea','');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-title-0','input','Bocas del Toro');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-title-1','input','');
INSERT INTO `documents_elements` VALUES ('1','content:3.caption-title-2','input','');
INSERT INTO `documents_elements` VALUES ('1','content:3.image_0','image','a:9:{s:2:\"id\";i:22;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:3.image_1','image','a:9:{s:2:\"id\";i:24;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:3.image_2','image','a:9:{s:2:\"id\";i:23;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:3.showPreviews','checkbox','1');
INSERT INTO `documents_elements` VALUES ('1','content:3.slides','select','3');
INSERT INTO `documents_elements` VALUES ('1','content:6.icon_0','select','phone');
INSERT INTO `documents_elements` VALUES ('1','content:6.icon_1','select','bullhorn');
INSERT INTO `documents_elements` VALUES ('1','content:6.icon_2','select','screenshot');
INSERT INTO `documents_elements` VALUES ('1','content:6.link_0','link','a:15:{s:4:\"text\";s:9:\"Read More\";s:4:\"path\";s:27:\"/en/basic-examples/glossary\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:20;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('1','content:6.link_1','link','a:15:{s:4:\"text\";s:9:\"Read More\";s:4:\"path\";s:28:\"/en/basic-examples/galleries\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:19;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('1','content:6.link_2','link','a:15:{s:4:\"text\";s:9:\"Read More\";s:4:\"path\";s:28:\"/en/basic-examples/galleries\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:19;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('1','content:6.text_0','textarea','This demo is based on the Bootstrap framework which is the most popular, intuitive and powerful front-end framework available.');
INSERT INTO `documents_elements` VALUES ('1','content:6.text_1','textarea','HTML5, Javascript, CSS3, jQuery as well as concepts like responsive, mobile-apps or non-linear design-patterns.');
INSERT INTO `documents_elements` VALUES ('1','content:6.text_2','textarea','Content is created by simply dragging & dropping blocks, that can be editited in-place and wysiwyg in a very intuitive and comfortable way. ');
INSERT INTO `documents_elements` VALUES ('1','content:6.title_0','input','Fully Responsive');
INSERT INTO `documents_elements` VALUES ('1','content:6.title_1','input','100% Buzzword Compatible');
INSERT INTO `documents_elements` VALUES ('1','content:6.title_2','input','Drag & Drop Interface');
INSERT INTO `documents_elements` VALUES ('1','content:7.description_0','textarea','pimcore is the only open-source multi-channel experience and engagement management platform available. ');
INSERT INTO `documents_elements` VALUES ('1','content:7.description_1','textarea','With complete creative freedom, flexibility, and agility, pimcore is a dream come true for designers and developers.');
INSERT INTO `documents_elements` VALUES ('1','content:7.description_2','textarea','pimcore makes it easier to manage large international sites with key features like advanced content reuse, inheritance, and distribution. pimcore is based on UTF-8 standards and is compatible to any language including Right-to-Left (RTL).');
INSERT INTO `documents_elements` VALUES ('1','content:7.headline_0','input','About Us');
INSERT INTO `documents_elements` VALUES ('1','content:7.headline_1','input','100% Flexible 100% Editable');
INSERT INTO `documents_elements` VALUES ('1','content:7.headline_2','input','International and Multi-site');
INSERT INTO `documents_elements` VALUES ('1','content:7.image_0','image','a:9:{s:2:\"id\";N;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:7.image_1','image','a:9:{s:2:\"id\";i:5;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:7.image_2','image','a:9:{s:2:\"id\";N;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-small_0','input','What is pimcore?');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-small_1','input','and enjoy creative freedom');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-small_2','input','中國人嗎？沒問題。');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-title_0','input','About us');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-title_1','input','Think different');
INSERT INTO `documents_elements` VALUES ('1','content:7.pill-title_2','input','International and Multi-site');
INSERT INTO `documents_elements` VALUES ('1','content:7.slides','select','3');
INSERT INTO `documents_elements` VALUES ('1','cText_0','textarea','Check out our examples and dive into the next generation of digital data management.');
INSERT INTO `documents_elements` VALUES ('1','cText_1','textarea','See for yourself.');
INSERT INTO `documents_elements` VALUES ('1','cText_2','textarea','See for yourself');
INSERT INTO `documents_elements` VALUES ('1','headlinecontent4','input','Good looking and completely custom galleries');
INSERT INTO `documents_elements` VALUES ('1','imagecontent_blockcontent11_2','image','a:9:{s:2:\"id\";i:18;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('1','imagePositioncontent_blockcontent11_1','select','');
INSERT INTO `documents_elements` VALUES ('1','imagePositioncontent_blockcontent11_2','select','left');
INSERT INTO `documents_elements` VALUES ('1','imagePositioncontent_blockcontent11_3','select','');
INSERT INTO `documents_elements` VALUES ('1','leadcontent4','wysiwyg','<p>Are integrated within minutes</p>\n');
INSERT INTO `documents_elements` VALUES ('1','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('1','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('1','myDate','date',NULL);
INSERT INTO `documents_elements` VALUES ('1','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('1','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('1','myInput','input','');
INSERT INTO `documents_elements` VALUES ('1','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('1','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('1','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('1','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('1','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('1','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('1','quotecontent3','input','We can\'t solve problems by using the same kind of thinking we used when we created them.');
INSERT INTO `documents_elements` VALUES ('3','content','areablock','a:4:{i:0;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:2;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:3;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:19:\"standard-teaser-row\";}}');
INSERT INTO `documents_elements` VALUES ('3','content:1.circle1','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:1.circle2','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:1.circle3','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:1.headline1','input','HTML5 Video');
INSERT INTO `documents_elements` VALUES ('3','content:1.headline2','input','PDF Viewer');
INSERT INTO `documents_elements` VALUES ('3','content:1.headline3','input','Galleries');
INSERT INTO `documents_elements` VALUES ('3','content:1.image1','image','a:9:{s:2:\"id\";i:41;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:1.image2','image','a:9:{s:2:\"id\";i:39;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:1.image3','image','a:9:{s:2:\"id\";i:40;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:1.link1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:27:\"/basic-examples/html5-video\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:7;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:1.link2','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:26:\"/basic-examples/pdf-viewer\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:18;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:1.link3','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:25:\"/basic-examples/galleries\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:19;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:1.text1','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:1.text2','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:1.text3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:1.type_0','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:1.type_1','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:1.type_2','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:2.circle1','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:2.circle2','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:2.circle3','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:2.headline1','input','Glossary');
INSERT INTO `documents_elements` VALUES ('3','content:2.headline2','input','Thumbnails');
INSERT INTO `documents_elements` VALUES ('3','content:2.headline3','input','Website Translations');
INSERT INTO `documents_elements` VALUES ('3','content:2.image1','image','a:9:{s:2:\"id\";i:55;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:2.image2','image','a:9:{s:2:\"id\";i:56;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:2.image3','image','a:9:{s:2:\"id\";i:57;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:2.link1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:24:\"/basic-examples/glossary\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:20;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:2.link2','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:26:\"/basic-examples/thumbnails\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:21;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:2.link3','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('3','content:2.text1','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:2.text2','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:2.text3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:2.type_0','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:2.type_1','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:2.type_2','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:3.circle1','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:3.circle2','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:3.circle3','checkbox','1');
INSERT INTO `documents_elements` VALUES ('3','content:3.headline1','input','Simple Content');
INSERT INTO `documents_elements` VALUES ('3','content:3.headline2','input','Round-Up');
INSERT INTO `documents_elements` VALUES ('3','content:3.headline3','input','Simple Form');
INSERT INTO `documents_elements` VALUES ('3','content:3.image1','image','a:9:{s:2:\"id\";i:50;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:3.image2','image','a:9:{s:2:\"id\";i:45;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:3.image3','image','a:9:{s:2:\"id\";i:44;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:3.link1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:28:\"/basic-examples/content-page\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:24;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:3.link2','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:32:\"/basic-examples/editable-roundup\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:25;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:3.link3','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:20:\"/basic-examples/form\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:26;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:3.text1','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:3.text2','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:3.text3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:3.type_0','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:3.type_1','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:3.type_2','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:4.circle1','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:4.circle2','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:4.circle3','checkbox','');
INSERT INTO `documents_elements` VALUES ('3','content:4.headline1','input','News');
INSERT INTO `documents_elements` VALUES ('3','content:4.headline2','input','Properties');
INSERT INTO `documents_elements` VALUES ('3','content:4.headline3','input','Tag Manager');
INSERT INTO `documents_elements` VALUES ('3','content:4.image1','image','a:9:{s:2:\"id\";i:47;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:4.image2','image','a:9:{s:2:\"id\";i:46;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:4.image3','image','a:9:{s:2:\"id\";i:22;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('3','content:4.link1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:20:\"/basic-examples/news\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:27;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:4.link2','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:26:\"/basic-examples/properties\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:28;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:4.link3','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:42:\"/basic-examples/tag-and-snippet-management\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:29;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('3','content:4.text1','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:4.text2','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:4.text3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('3','content:4.type_0','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:4.type_1','select','direct');
INSERT INTO `documents_elements` VALUES ('3','content:4.type_2','select','direct');
INSERT INTO `documents_elements` VALUES ('3','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('3','headline','input','Basic Examples');
INSERT INTO `documents_elements` VALUES ('3','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('4','circle2content1','checkbox','');
INSERT INTO `documents_elements` VALUES ('4','content','areablock','a:4:{i:0;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:7:\"wysiwyg\";}i:2;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:3;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:10:\"featurette\";}}');
INSERT INTO `documents_elements` VALUES ('4','content:1.teaser_0','snippet','15');
INSERT INTO `documents_elements` VALUES ('4','content:1.teaser_1','snippet','16');
INSERT INTO `documents_elements` VALUES ('4','content:1.teaser_2','snippet','17');
INSERT INTO `documents_elements` VALUES ('4','content:1.type_0','select','');
INSERT INTO `documents_elements` VALUES ('4','content:1.type_1','select','snippet');
INSERT INTO `documents_elements` VALUES ('4','content:1.type_2','select','snippet');
INSERT INTO `documents_elements` VALUES ('4','content:2.block','block','a:1:{i:0;s:1:\"1\";}');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.content','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo.</p>\n');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.headline','input','Ullamcorper Scelerisque ');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.image','image','a:9:{s:2:\"id\";i:24;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.position','select','');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.subline','input','');
INSERT INTO `documents_elements` VALUES ('4','content:2.block:1.type','select','');
INSERT INTO `documents_elements` VALUES ('4','content:3.content','wysiwyg','<p>Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. <a href=\"/basic-examples\">Etiam rhoncus</a>.</p>\n\n<p>&nbsp;</p>\n\n<ul>\n	<li>Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.</li>\n	<li>Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem.</li>\n	<li>Maecenas nec odio et ante tincidunt tempus.</li>\n	<li><a href=\"/basic-examples\">Donec vitae sapien ut libero venenatis faucibus.</a></li>\n	<li>Nullam quis ante.</li>\n	<li>Etiam sit amet orci eget eros <a href=\"/advanced-examples\">faucibus </a>tincidunt.</li>\n</ul>\n\n<p>&nbsp;</p>\n\n<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform <a href=\"/experiments\">grammatica</a>, pronunciation e plu sommun paroles.</p>\n\n<p>&nbsp;</p>\n\n<ol>\n	<li>It va esser tam simplic quam Occidental in fact, it va esser Occidental.</li>\n	<li>A un Angleso it va semblar un simplificat Angles, quam un skeptic <a href=\"/introduction\">Cambridge </a>amico dit me que Occidental es.</li>\n	<li>Li Europan lingues es membres del sam familie.</li>\n	<li>Lor separat existentie es un myth.</li>\n	<li>Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</li>\n	<li>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules.</li>\n</ol>\n\n<p>&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('4','content:4.headline','input','Maecenas tempus, tellus eget condimentum rhoncu');
INSERT INTO `documents_elements` VALUES ('4','content:4.lead','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('4','headDescription','input','Overview of the project and how to get started with a simple template.');
INSERT INTO `documents_elements` VALUES ('4','headline','input','Introduction');
INSERT INTO `documents_elements` VALUES ('4','headline2content1','input','');
INSERT INTO `documents_elements` VALUES ('4','headTitle','input','Getting started');
INSERT INTO `documents_elements` VALUES ('4','image2content1','image','a:9:{s:2:\"id\";N;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('4','imagecontent1','image','a:9:{s:2:\"id\";i:22;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('4','imagePositioncontent_blockcontent22_1','select','');
INSERT INTO `documents_elements` VALUES ('4','leadcontent3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('4','link2content1','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('4','linkcontent1','link','a:14:{s:4:\"text\";s:12:\"Etiam rhoncu\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";}');
INSERT INTO `documents_elements` VALUES ('4','text2content1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('4','textcontent1','wysiwyg','<p>Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna.</p>\n');
INSERT INTO `documents_elements` VALUES ('5','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('5','content:1.content','wysiwyg','<p>The following list is generated automatically. See controller/action to see how it\'s done.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('5','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('5','headline','input','Advanced Examples');
INSERT INTO `documents_elements` VALUES ('5','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('5','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('6','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('6','content:1.content','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt.</p>\n\n<p>&nbsp;</p>\n\n<p>Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet.</p>\n\n<p>&nbsp;</p>\n\n<p>Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `documents_elements` VALUES ('6','content:2.headline','input','This space is reserved for your individual experiments & tests.');
INSERT INTO `documents_elements` VALUES ('6','content:2.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('6','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('6','headline','input','Experiments');
INSERT INTO `documents_elements` VALUES ('6','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('6','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('7','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:5:\"video\";}}');
INSERT INTO `documents_elements` VALUES ('7','content:1.video','video','a:5:{s:2:\"id\";i:27;s:4:\"type\";s:5:\"asset\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:6:\"poster\";N;}');
INSERT INTO `documents_elements` VALUES ('7','content:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('7','content:2.lead','wysiwyg','<p>Just drop an video from your assets, the video will be automatically converted to the different HTML5 formats and to the correct size.</p>\n');
INSERT INTO `documents_elements` VALUES ('7','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('7','headline','input','HTML5 Video is just as simple as that ....');
INSERT INTO `documents_elements` VALUES ('7','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('7','leadcontent1','wysiwyg','<p>Just drop an video from your assets, the video will be automatically converted to the different HTML5 formats and to the correct size.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('9','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('9','content:1.content','wysiwyg','<p>&nbsp;</p>\n\n<p>In this example we dynamically create objects out of the data submitted via the form.</p>\n\n<p>The you can use the same approach to create objects using a <strong>commandline script</strong>, or wherever you need it.</p>\n\n<p>After submitting the form you\'ll find the data in \"Objects\" <em>/crm</em> and <em>/inquiries</em>.&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p><img pimcore_disable_thumbnail=\"true\" pimcore_id=\"65\" pimcore_type=\"asset\" src=\"/screenshots/objects-forms.png\" style=\"width:308px\" /></p>\n\n<p>&nbsp;</p>\n\n<hr />\n<h2><strong>And here\'s the form:&nbsp;</strong></h2>\n');
INSERT INTO `documents_elements` VALUES ('9','errorMessage','input','Please fill all fields and accept the terms of use. ');
INSERT INTO `documents_elements` VALUES ('9','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('9','headline','input','Creating Objects & Assets with a Form');
INSERT INTO `documents_elements` VALUES ('9','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('9','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('12','links','block','a:3:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";}');
INSERT INTO `documents_elements` VALUES ('12','links:1.link','link','a:11:{s:4:\"text\";s:11:\"pimcore.org\";s:4:\"path\";s:23:\"http://www.pimcore.org/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('12','links:2.link','link','a:11:{s:4:\"text\";s:13:\"Documentation\";s:4:\"path\";s:28:\"http://www.pimcore.org/wiki/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('12','links:3.link','link','a:11:{s:4:\"text\";s:11:\"Bug Tracker\";s:4:\"path\";s:30:\"http://www.pimcore.org/issues/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('12','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('12','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('12','myDate','date','');
INSERT INTO `documents_elements` VALUES ('12','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('12','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('12','myInput','input','');
INSERT INTO `documents_elements` VALUES ('12','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('12','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('12','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('12','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('12','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('12','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('12','text','wysiwyg','<p>Designed and built with all the love in the world by&nbsp;<a href=\"http://twitter.com/mdo\" target=\"_blank\">@mdo</a>&nbsp;and&nbsp;<a href=\"http://twitter.com/fat\" target=\"_blank\">@fat</a>.</p>\n\n<p>Code licensed under&nbsp;<a href=\"http://www.apache.org/licenses/LICENSE-2.0\" target=\"_blank\">Apache License v2.0</a>,&nbsp;<a href=\"http://glyphicons.com/\">Glyphicons Free</a>&nbsp;licensed under&nbsp;<a href=\"http://creativecommons.org/licenses/by/3.0/\">CC BY 3.0</a>.</p>\n\n<p><strong>© Templates pimcore.org licensed under BSD License</strong></p>\n');
INSERT INTO `documents_elements` VALUES ('15','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('15','headline','input','Fully Responsive');
INSERT INTO `documents_elements` VALUES ('15','image','image','a:9:{s:2:\"id\";i:21;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('15','link','link','a:15:{s:4:\"text\";s:11:\"Lorem ipsum\";s:4:\"path\";s:15:\"/basic-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:3;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('15','text','wysiwyg','<p>This demo is based on Bootstrap, the most popular, intuitive, and powerful front-end framework.</p>\n');
INSERT INTO `documents_elements` VALUES ('16','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('16','headline','input','Drag & Drop Interface 😻');
INSERT INTO `documents_elements` VALUES ('16','image','image','a:9:{s:2:\"id\";i:20;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('16','link','link','a:15:{s:4:\"text\";s:12:\"Etiam rhoncu\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('16','text','wysiwyg','<p>Content is created by simply dragging &amp; dropping blocks, that can&nbsp;be editited in-place and wysiwyg.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('17','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('17','headline','input','HTML5 omnipresent');
INSERT INTO `documents_elements` VALUES ('17','image','image','a:9:{s:2:\"id\";i:18;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('17','link','link','a:15:{s:4:\"text\";s:14:\"Quisque rutrum\";s:4:\"path\";s:12:\"/experiments\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:6;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('17','text','wysiwyg','<p>Drag &amp; drop upload directly&nbsp;into the asset tree, automatic html5 video transcoding, and much more ...</p>\n');
INSERT INTO `documents_elements` VALUES ('18','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:3:\"pdf\";}}');
INSERT INTO `documents_elements` VALUES ('18','content:1.pdf','pdf','a:4:{s:2:\"id\";i:36;s:8:\"hotspots\";a:0:{}s:5:\"texts\";a:0:{}s:8:\"chapters\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('18','content:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('18','content:2.lead','wysiwyg','<p>Just drop a PDF, doc(x), xls(x) or many other formats, et voilá ...</p>\n');
INSERT INTO `documents_elements` VALUES ('18','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('18','headline','input','Isn\'t that amazing?');
INSERT INTO `documents_elements` VALUES ('18','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('18','leadcontent1','wysiwyg','<p>Just drop a PDF, doc(x), xls(x) or many other formats, et voilá ...&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('19','content','areablock','a:6:{i:0;a:2:{s:3:\"key\";s:1:\"5\";s:4:\"type\";s:16:\"gallery-carousel\";}i:1;a:2:{s:3:\"key\";s:1:\"6\";s:4:\"type\";s:16:\"gallery-carousel\";}i:2;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:9:\"headlines\";}i:3;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:14:\"gallery-folder\";}i:4;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:9:\"headlines\";}i:5;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:21:\"gallery-single-images\";}}');
INSERT INTO `documents_elements` VALUES ('19','content:1.gallery','renderlet','a:3:{s:2:\"id\";i:17;s:4:\"type\";s:5:\"asset\";s:7:\"subtype\";s:6:\"folder\";}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery','block','a:7:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"4\";i:4;s:1:\"5\";i:5;s:1:\"6\";i:6;s:1:\"7\";}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:1.image','image','a:9:{s:2:\"id\";i:48;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:2.image','image','a:9:{s:2:\"id\";i:43;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:3.image','image','a:9:{s:2:\"id\";i:50;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:4.image','image','a:9:{s:2:\"id\";i:47;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:5.image','image','a:9:{s:2:\"id\";i:46;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:6.image','image','a:9:{s:2:\"id\";i:51;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:2.gallery:7.image','image','a:9:{s:2:\"id\";i:52;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:3.headline','input','Autogenerated Gallery (using Renderlet)');
INSERT INTO `documents_elements` VALUES ('19','content:3.lead','wysiwyg','<p>Drag an asset folder on the following drop area, and the \"renderlet\" will create automatically a gallery out of the images in the folder.</p>\n');
INSERT INTO `documents_elements` VALUES ('19','content:4.headline','input','Custom assembled Gallery');
INSERT INTO `documents_elements` VALUES ('19','content:4.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-text-0','textarea','White beaches and the indian ocean');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-text-1','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-text-2','textarea','National Nature Reserve');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-text-3','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-text-4','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-title-0','input','Plettenberg Bay');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-title-1','input','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-title-2','input','The Robberg');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-title-3','input','');
INSERT INTO `documents_elements` VALUES ('19','content:5.caption-title-4','input','');
INSERT INTO `documents_elements` VALUES ('19','content:5.image_0','image','a:9:{s:2:\"id\";i:48;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:5.image_1','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:5.image_2','image','a:9:{s:2:\"id\";i:46;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:5.image_3','image','a:9:{s:2:\"id\";i:49;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:5.image_4','image','a:9:{s:2:\"id\";i:47;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:5.showPreviews','checkbox','1');
INSERT INTO `documents_elements` VALUES ('19','content:5.slides','select','5');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-text-0','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-text-1','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-text-2','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-text-3','textarea','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-title-0','input','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-title-1','input','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-title-2','input','');
INSERT INTO `documents_elements` VALUES ('19','content:6.caption-title-3','input','');
INSERT INTO `documents_elements` VALUES ('19','content:6.image_0','image','a:9:{s:2:\"id\";i:39;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:6.image_1','image','a:9:{s:2:\"id\";i:38;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:6.image_2','image','a:9:{s:2:\"id\";i:41;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:6.image_3','image','a:9:{s:2:\"id\";i:40;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('19','content:6.showPreviews','checkbox','');
INSERT INTO `documents_elements` VALUES ('19','content:6.slides','select','4');
INSERT INTO `documents_elements` VALUES ('19','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('19','headline','input','Creating custom galleries is very simple');
INSERT INTO `documents_elements` VALUES ('19','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('19','leadcontent1','wysiwyg','<p>Drag an asset folder on the following drop area, and the \"renderlet\" will create automatically a gallery out of the images in the folder.</p>\n');
INSERT INTO `documents_elements` VALUES ('19','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('20','content','areablock','a:4:{i:0;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:2;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:9:\"headlines\";}i:3;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('20','content:1.content','wysiwyg','<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n\n<p>&nbsp;</p>\n\n<p>Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.</p>\n\n<p>&nbsp;</p>\n\n<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `documents_elements` VALUES ('20','content:2.image','image','a:9:{s:2:\"id\";i:35;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('20','content:3.headline','input','');
INSERT INTO `documents_elements` VALUES ('20','content:3.lead','wysiwyg','<p>... makes it very simple to automatically link keywords, abbreviation and acronyms. This is not only perfect for SEO but also makes it super easy to navigate in the content.</p>\n');
INSERT INTO `documents_elements` VALUES ('20','content:4.headline','input','');
INSERT INTO `documents_elements` VALUES ('20','content:4.lead','wysiwyg','<p>... this is how it looks in the admin interface.</p>\n');
INSERT INTO `documents_elements` VALUES ('20','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('20','headline','input','The Glossary ...');
INSERT INTO `documents_elements` VALUES ('20','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('20','leadcontent1','wysiwyg','<p>... makes it very simple to automatically link keywords, abbreviation and acronyms. This is not only perfect for SEO but also makes it super easy to navigate in the content.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('20','leadcontent2','wysiwyg','<p>&nbsp;</p>\n\n<p>... this is how it looks in the admin interface.</p>\n');
INSERT INTO `documents_elements` VALUES ('21','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('21','content:2.image','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','content:3.headline','input','This is the original image');
INSERT INTO `documents_elements` VALUES ('21','content:3.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('21','contentcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('21','content_bottom','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('21','content_bottom:1.image','image','a:9:{s:2:\"id\";i:59;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('21','headline','input','Incredible Possibilities');
INSERT INTO `documents_elements` VALUES ('21','headlinecontent_bottom1','input','This is how it looks in the admin interface ... ');
INSERT INTO `documents_elements` VALUES ('21','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('21','image','image','a:9:{s:2:\"id\";N;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img1','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img10','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img11','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img12','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img2','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img3','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img4','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img5','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img6','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img7','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img8','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','img9','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('21','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('21','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('21','leadcontent_bottom1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('21','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('21','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('21','myDate','date',NULL);
INSERT INTO `documents_elements` VALUES ('21','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('21','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('21','myInput','input','');
INSERT INTO `documents_elements` VALUES ('21','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('21','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('21','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('21','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('21','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('21','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('22','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('22','content:1.content','wysiwyg','<p>&nbsp;</p>\n\n<p><a href=\"/de/einfache-beispiele/website-uebersetzungen\" pimcore_id=\"23\" pimcore_type=\"document\">Please visit this page to see the German translation of this page.</a></p>\n\n<p>&nbsp;</p>\n\n<p>Following some examples:&nbsp;</p>\n\n<p>&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('22','content:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('22','content:2.lead','wysiwyg','<p>Common used terms across the website can be translated centrally, hassle-free and comfortable.</p>\n');
INSERT INTO `documents_elements` VALUES ('22','contentBottom','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('22','contentBottom:1.image','image','a:9:{s:2:\"id\";i:60;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('22','contentBottom:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('22','contentBottom:2.lead','wysiwyg','<p>This is how it looks in the admin interface ...</p>\n');
INSERT INTO `documents_elements` VALUES ('22','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('22','headline','input','Website Translations');
INSERT INTO `documents_elements` VALUES ('22','headlinecontentBottom1','input','');
INSERT INTO `documents_elements` VALUES ('22','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('22','leadcontent1','wysiwyg','<p>Common used terms across the website can be translated centrally, hassle-free and comfortable.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('22','leadcontentBottom1','wysiwyg','<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>This is how it looks in the admin interface ...&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('22','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('22','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('22','myDate','date',NULL);
INSERT INTO `documents_elements` VALUES ('22','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('22','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('22','myInput','input','');
INSERT INTO `documents_elements` VALUES ('22','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('22','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('22','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('22','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('22','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('22','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('23','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('23','content:1.content','wysiwyg','<p>Folgend ein paar Beispiele:&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('23','contentBottom','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('23','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('23','headline','input','Website Übersetzungen');
INSERT INTO `documents_elements` VALUES ('23','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('23','leadcontent1','wysiwyg','<p>Häufig genutzte Begriffe auf der gesamten Website können komfortabel, zentral und einfach übersetzt werden.</p>\n');
INSERT INTO `documents_elements` VALUES ('23','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('23','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('23','myDate','date','');
INSERT INTO `documents_elements` VALUES ('23','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('23','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('23','myInput','input','');
INSERT INTO `documents_elements` VALUES ('23','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('23','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('23','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('23','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('23','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('23','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('24','content','areablock','a:11:{i:0;a:2:{s:3:\"key\";s:1:\"6\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:2:\"11\";s:4:\"type\";s:19:\"wysiwyg-with-images\";}i:2;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:21:\"gallery-single-images\";}i:3;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:7:\"wysiwyg\";}i:4;a:2:{s:3:\"key\";s:1:\"5\";s:4:\"type\";s:10:\"blockquote\";}i:5;a:2:{s:3:\"key\";s:1:\"9\";s:4:\"type\";s:15:\"horizontal-line\";}i:6;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:10:\"featurette\";}i:7;a:2:{s:3:\"key\";s:1:\"8\";s:4:\"type\";s:15:\"horizontal-line\";}i:8;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:5:\"image\";}i:9;a:2:{s:3:\"key\";s:1:\"7\";s:4:\"type\";s:14:\"text-accordion\";}i:10;a:2:{s:3:\"key\";s:2:\"10\";s:4:\"type\";s:15:\"icon-teaser-row\";}}');
INSERT INTO `documents_elements` VALUES ('24','content:1.block','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.content','wysiwyg','<p>Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.headline','input','Lorem ipsum.');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.image','image','a:9:{s:2:\"id\";i:48;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.position','select','');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.subline','input','Dolor sit amet.');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:1.type','select','');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.content','wysiwyg','<p>Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.headline','input','Etiam ultricies.');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.position','select','left');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.subline','input','Nam eget dui.');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.type','select','video');
INSERT INTO `documents_elements` VALUES ('24','content:1.block:2.video','video','a:5:{s:2:\"id\";i:27;s:4:\"type\";s:5:\"asset\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:6:\"poster\";i:49;}');
INSERT INTO `documents_elements` VALUES ('24','content:10.icon_0','select','thumbs-up');
INSERT INTO `documents_elements` VALUES ('24','content:10.icon_1','select','qrcode');
INSERT INTO `documents_elements` VALUES ('24','content:10.icon_2','select','trash');
INSERT INTO `documents_elements` VALUES ('24','content:10.link_0','link','a:15:{s:4:\"text\";s:13:\"See in Action\";s:4:\"path\";s:30:\"/en/basic-examples/html5-video\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:7;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('24','content:10.link_1','link','a:15:{s:4:\"text\";s:9:\"Read More\";s:4:\"path\";s:29:\"/en/basic-examples/thumbnails\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:21;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('24','content:10.link_2','link','a:15:{s:4:\"text\";s:10:\"Try it now\";s:4:\"path\";s:23:\"/en/basic-examples/news\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:27;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('24','content:10.text_0','textarea','At solmen va esser necessi far uniform grammatica.');
INSERT INTO `documents_elements` VALUES ('24','content:10.text_1','textarea',' Curabitur ullamcorper ultricies nisi. Nam eget dui.');
INSERT INTO `documents_elements` VALUES ('24','content:10.text_2','textarea','On refusa continuar payar custosi traductores.');
INSERT INTO `documents_elements` VALUES ('24','content:10.title_0','input','Social Media Integration');
INSERT INTO `documents_elements` VALUES ('24','content:10.title_1','input','QR-Code Management');
INSERT INTO `documents_elements` VALUES ('24','content:10.title_2','input','Recycle Bin');
INSERT INTO `documents_elements` VALUES ('24','content:11.content','wysiwyg','<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca:</p>\n\n<p>&nbsp;</p>\n\n<p>On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental.</p>\n\n<p>&nbsp;</p>\n\n<p>A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:11.images','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('24','content:11.images:1.image','image','a:9:{s:2:\"id\";i:22;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:11.images:2.image','image','a:9:{s:2:\"id\";i:24;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:2.gallery','block','a:4:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"4\";}');
INSERT INTO `documents_elements` VALUES ('24','content:2.gallery:1.image','image','a:9:{s:2:\"id\";i:51;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:2.gallery:2.image','image','a:9:{s:2:\"id\";i:52;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:2.gallery:3.image','image','a:9:{s:2:\"id\";i:44;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:2.gallery:4.image','image','a:9:{s:2:\"id\";i:49;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:3.content','wysiwyg','<p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.</p>\n\n<p>&nbsp;</p>\n\n<ul>\n	<li>Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus.</li>\n	<li>Phasellus viverra nulla ut metus varius laoreet.</li>\n	<li>Quisque rutrum. Aenean imperdiet.</li>\n</ul>\n\n<p>&nbsp;</p>\n\n<p>Etiam ultricies nisi vel augue. Curabitur <a href=\"/basic-examples/galleries\">ullamcorper </a>ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:4.image','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('24','content:5.author','input','Albert Einstein');
INSERT INTO `documents_elements` VALUES ('24','content:5.quote','input','We can\'t solve problems by using the same kind of thinking we used when we created them.');
INSERT INTO `documents_elements` VALUES ('24','content:6.headline','input','Where some Content-Blocks are mixed together.');
INSERT INTO `documents_elements` VALUES ('24','content:6.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion','block','a:4:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"4\";}');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:1.headline','input','Lorem ipsum dolor sit amet');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:1.text','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean <a href=\"/en/basic-examples/thumbnails\" pimcore_id=\"21\" pimcore_type=\"document\">commodo </a>ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim.</p>\n\n<p>&nbsp;</p>\n\n<p>Donec pede justo, fringilla vel, aliquet nec, <strong>vulputate </strong>eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus <a href=\"/en/basic-examples/form\" pimcore_id=\"26\" pimcore_type=\"document\">elementum </a>semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet.</p>\n\n<p>&nbsp;</p>\n\n<p>Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget <u>condimentum </u>rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:2.headline','input',' Cum sociis natoque penatibus et magnis dis parturient montes');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:2.text','wysiwyg','<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca:</p>\n\n<p>&nbsp;</p>\n\n<p>On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental.</p>\n\n<p>&nbsp;</p>\n\n<p>A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:3.headline','input','Donec pede justo, fringilla vel');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:3.text','wysiwyg','<p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:4.headline','input','Maecenas tempus, tellus eget condimentum rhoncus');
INSERT INTO `documents_elements` VALUES ('24','content:7.accordion:4.text','wysiwyg','<p>It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth.</p>\n\n<p>&nbsp;</p>\n\n<p>Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `documents_elements` VALUES ('24','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('24','headline','input','This is just a simple Content-Page ...');
INSERT INTO `documents_elements` VALUES ('24','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('24','leadcontent2','wysiwyg','<p>African Animals</p>\n');
INSERT INTO `documents_elements` VALUES ('24','leadcontent3','wysiwyg','<p>Donec pede justo, fringilla vel, aliquet nec</p>\n');
INSERT INTO `documents_elements` VALUES ('24','leadcontent4','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('25','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}}');
INSERT INTO `documents_elements` VALUES ('25','content:2.headline','input','Please view this page in the editmode (admin interface)!');
INSERT INTO `documents_elements` VALUES ('25','content:2.lead','wysiwyg','<p>... nothing to see here ;-)</p>\n');
INSERT INTO `documents_elements` VALUES ('25','contentcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('25','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('25','headline','input','This is an overview of all available \"editables\" (except area/areablock/block)');
INSERT INTO `documents_elements` VALUES ('25','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('25','leadcontent1','wysiwyg','<p>... nothing to see here ;-)&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('25','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('25','myCheckbox','checkbox','1');
INSERT INTO `documents_elements` VALUES ('25','myDate','date','1368662400');
INSERT INTO `documents_elements` VALUES ('25','myHref','href','a:3:{s:2:\"id\";i:21;s:4:\"type\";s:8:\"document\";s:7:\"subtype\";s:4:\"page\";}');
INSERT INTO `documents_elements` VALUES ('25','myImage','image','a:9:{s:2:\"id\";i:47;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('25','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('25','myInput','input','Some Text');
INSERT INTO `documents_elements` VALUES ('25','myLink','link','a:15:{s:4:\"text\";s:7:\"My Link\";s:4:\"path\";s:25:\"/basic-examples/galleries\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:19;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('25','myMultiHref','multihref','a:6:{i:0;a:4:{s:2:\"id\";i:20;s:4:\"path\";s:27:\"/en/basic-examples/glossary\";s:4:\"type\";s:8:\"document\";s:7:\"subtype\";s:4:\"page\";}i:1;a:4:{s:2:\"id\";i:21;s:4:\"path\";s:29:\"/en/basic-examples/thumbnails\";s:4:\"type\";s:8:\"document\";s:7:\"subtype\";s:4:\"page\";}i:2;a:4:{s:2:\"id\";i:25;s:4:\"path\";s:35:\"/en/basic-examples/editable-roundup\";s:4:\"type\";s:8:\"document\";s:7:\"subtype\";s:4:\"page\";}i:3;a:4:{s:2:\"id\";i:51;s:4:\"path\";s:35:\"/examples/south-africa/img_1842.jpg\";s:4:\"type\";s:5:\"asset\";s:7:\"subtype\";s:5:\"image\";}i:4;a:4:{s:2:\"id\";i:44;s:4:\"path\";s:35:\"/examples/south-africa/img_2133.jpg\";s:4:\"type\";s:5:\"asset\";s:7:\"subtype\";s:5:\"image\";}i:5;a:4:{s:2:\"id\";i:45;s:4:\"path\";s:35:\"/examples/south-africa/img_2240.jpg\";s:4:\"type\";s:5:\"asset\";s:7:\"subtype\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('25','myMultiselect','multiselect','a:2:{i:0;s:6:\"value2\";i:1;s:6:\"value4\";}');
INSERT INTO `documents_elements` VALUES ('25','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('25','myNumeric','numeric','123');
INSERT INTO `documents_elements` VALUES ('25','myRenderlet','renderlet','a:3:{s:2:\"id\";i:54;s:4:\"type\";s:5:\"asset\";s:7:\"subtype\";s:6:\"folder\";}');
INSERT INTO `documents_elements` VALUES ('25','mySelect','select','option2');
INSERT INTO `documents_elements` VALUES ('25','mySnippet','snippet','15');
INSERT INTO `documents_elements` VALUES ('25','myTextarea','textarea','Some Text');
INSERT INTO `documents_elements` VALUES ('25','myVideo','video','a:5:{s:2:\"id\";i:27;s:4:\"type\";s:5:\"asset\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:6:\"poster\";N;}');
INSERT INTO `documents_elements` VALUES ('25','myWysiwyg','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt.</p>\n\n<p>&nbsp;</p>\n\n<p>Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui.</p>\n\n<p>&nbsp;</p>\n\n<p>Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `documents_elements` VALUES ('25','tableName','table','a:2:{i:0;a:3:{i:0;s:7:\"Value 1\";i:1;s:7:\"Value 2\";i:2;s:7:\"Value 3\";}i:1;a:3:{i:0;s:4:\"this\";i:1;s:2:\"is\";i:2;s:4:\"test\";}}');
INSERT INTO `documents_elements` VALUES ('26','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('26','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('26','headline','input','Just a simple form');
INSERT INTO `documents_elements` VALUES ('26','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('26','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('26','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('26','myDate','date','');
INSERT INTO `documents_elements` VALUES ('26','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('26','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('26','myInput','input','');
INSERT INTO `documents_elements` VALUES ('26','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('26','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('26','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('26','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('26','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('26','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('27','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}}');
INSERT INTO `documents_elements` VALUES ('27','content:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('27','content:2.lead','wysiwyg','<p>Any kind of structured data is stored in \"Objects\".</p>\n');
INSERT INTO `documents_elements` VALUES ('27','contentcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('27','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('27','headline','input','News');
INSERT INTO `documents_elements` VALUES ('27','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('27','leadcontent1','wysiwyg','<p>Any kind of structured data is stored in \"Objects\".&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('27','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('27','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('27','myDate','date',NULL);
INSERT INTO `documents_elements` VALUES ('27','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('27','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('27','myInput','input','');
INSERT INTO `documents_elements` VALUES ('27','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('27','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('27','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('27','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('27','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('27','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('28','content','areablock','a:4:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:1;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}i:2;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:5:\"image\";}i:3;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('28','content:1.content','wysiwyg','<p>On this page we use \"Properties\" to hide the navigation on the left and to change the color of the header to blue.&nbsp;</p>\n\n<p>Properties are very useful to control the behavior or to store meta data of documents, assets and objects. And the best: they are inheritable.&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>On the following screens you can see how this is done in this example.</p>\n');
INSERT INTO `documents_elements` VALUES ('28','content:2.image','image','a:9:{s:2:\"id\";i:61;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('28','content:3.image','image','a:9:{s:2:\"id\";i:62;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('28','content:4.image','image','a:9:{s:2:\"id\";i:63;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('28','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('28','headline','input','Properties');
INSERT INTO `documents_elements` VALUES ('28','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('28','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('28','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('28','leadcontent3','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('28','leadcontent4','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('29','content','areablock','a:3:{i:0;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:2;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('29','content:1.content','wysiwyg','<p>This page demonstrates how to use the \"Tag &amp; Snippet Management\" to inject codes into the HTML source code. This functionality can be used to easily integrate tracking codes, conversion codes, social plugins and whatever that needs to go into the HTML.</p>\n\n<p>&nbsp;</p>\n\n<p>The functionality is similar to this products:&nbsp;</p>\n\n<p><a href=\"http://www.google.com/tagmanager/\">http://www.google.com/tagmanager/</a>&nbsp;</p>\n\n<p><a href=\"http://www.searchdiscovery.com/satellite/\">http://www.searchdiscovery.com/satellite/&nbsp;</a></p>\n\n<p><a href=\"http://www.tagcommander.com/en/\">http://www.tagcommander.com/en/</a></p>\n\n<p>&nbsp;</p>\n\n<p>In our example we use it to integrate a facebook social plugin.</p>\n');
INSERT INTO `documents_elements` VALUES ('29','content:2.image','image','a:9:{s:2:\"id\";i:64;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('29','content:3.headline','input','... gives all the freedom back to the marketing dept.');
INSERT INTO `documents_elements` VALUES ('29','content:3.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('29','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('29','headline','input','Tag & Snippet Management');
INSERT INTO `documents_elements` VALUES ('29','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('29','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('29','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('30','content','areablock','a:5:{i:0;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:2;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}i:3;a:2:{s:3:\"key\";s:1:\"5\";s:4:\"type\";s:9:\"headlines\";}i:4;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('30','content:1.content','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('30','content:2.image','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('30','content:3.content','wysiwyg','<p>Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `documents_elements` VALUES ('30','content:4.headline','input','First Headline');
INSERT INTO `documents_elements` VALUES ('30','content:4.lead','wysiwyg','<p>This is the Master Document</p>\n');
INSERT INTO `documents_elements` VALUES ('30','content:5.headline','input','Second Headline');
INSERT INTO `documents_elements` VALUES ('30','content:5.lead','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('30','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('30','headline','input','Content Inheritance');
INSERT INTO `documents_elements` VALUES ('30','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('30','leadcontent1','wysiwyg','<p>This is the Master Document</p>\n');
INSERT INTO `documents_elements` VALUES ('30','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('30','leadcontent3','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('31','content','areablock','a:5:{i:0;a:2:{s:3:\"key\";s:1:\"5\";s:4:\"type\";s:9:\"headlines\";}i:1;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:7:\"wysiwyg\";}i:2;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}i:3;a:2:{s:3:\"key\";s:1:\"4\";s:4:\"type\";s:9:\"headlines\";}i:4;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('31','content:4.lead','wysiwyg','<p>This is the Slave Document</p>\n');
INSERT INTO `documents_elements` VALUES ('31','content:5.lead','wysiwyg','<p>This is the Slave Document</p>\n');
INSERT INTO `documents_elements` VALUES ('34','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('34','content:1.content','wysiwyg','<p>This page has a hardlink as child (see navigation on the left).&nbsp;</p>\n\n<p>This hardlink points to \"<a href=\"/basic-examples\">Basic Examples</a>\", so the whole content of /basic-examples is available in /advaned-examples/hardlink/basic-examples.&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>Want to know more about hardlinks?&nbsp;</p>\n\n<ul>\n	<li><a href=\"http://en.wikipedia.org/wiki/Hard_link\">http://en.wikipedia.org/wiki/Hard_link</a></li>\n	<li>see also:&nbsp;<a href=\"http://en.wikipedia.org/wiki/Symbolic_link\">http://en.wikipedia.org/wiki/Symbolic_link</a>&nbsp;</li>\n</ul>\n\n<p>&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('34','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('34','headline','input','Hard Link Example');
INSERT INTO `documents_elements` VALUES ('34','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('34','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('35','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:20:\"image-hotspot-marker\";}i:1;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:20:\"image-hotspot-marker\";}}');
INSERT INTO `documents_elements` VALUES ('35','content:1.image','image','a:9:{s:2:\"id\";i:53;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:4:{i:0;a:3:{s:3:\"top\";d:35.220125786163521;s:4:\"left\";d:82.098765432098759;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:27:\"Table Mountain Peak Station\";s:4:\"type\";s:9:\"textfield\";}}}i:1;a:3:{s:3:\"top\";d:67.924528301886795;s:4:\"left\";d:9.0534979423868318;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:16:\"Victoria Harbour\";s:4:\"type\";s:9:\"textfield\";}}}i:2;a:3:{s:3:\"top\";d:57.232704402515722;s:4:\"left\";d:45.267489711934154;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:12:\"District Six\";s:4:\"type\";s:9:\"textfield\";}}}i:3;a:3:{s:3:\"top\";d:45.911949685534594;s:4:\"left\";d:98.971193415637856;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:11:\"Lion\'s Head\";s:4:\"type\";s:9:\"textfield\";}}}}}');
INSERT INTO `documents_elements` VALUES ('35','content:2.image','image','a:9:{s:2:\"id\";i:51;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:3:{i:0;a:5:{s:3:\"top\";d:0.54794520547945003;s:4:\"left\";d:20.370370370370001;s:5:\"width\";d:22.016460905350002;s:6:\"height\";d:21.917808219177999;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:3:\"Ear\";s:4:\"type\";s:9:\"textfield\";}}}i:1;a:5:{s:3:\"top\";d:59.178082191781002;s:4:\"left\";d:8.8477366255144005;s:5:\"width\";d:33.127572016461002;s:6:\"height\";d:40.273972602740002;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:5:\"Claws\";s:4:\"type\";s:9:\"textfield\";}}}i:2;a:5:{s:3:\"top\";d:25.205479452054998;s:4:\"left\";d:11.934156378600999;s:5:\"width\";d:16.460905349794;s:6:\"height\";d:18.356164383562;s:4:\"data\";a:1:{i:0;a:3:{s:4:\"name\";s:5:\"title\";s:5:\"value\";s:3:\"Eye\";s:4:\"type\";s:9:\"textfield\";}}}}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('35','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('35','headline','input','Image with Hotspots & Markers');
INSERT INTO `documents_elements` VALUES ('35','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('36','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('36','content:1.content','wysiwyg','<p>The search is using the contents from&nbsp;pimcore.org.&nbsp;<strong>TIP</strong>: Search for \"web\".</p>\n');
INSERT INTO `documents_elements` VALUES ('36','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('36','headline','input','Search');
INSERT INTO `documents_elements` VALUES ('36','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('36','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('36','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('36','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('36','myDate','date','');
INSERT INTO `documents_elements` VALUES ('36','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('36','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('36','myInput','input','');
INSERT INTO `documents_elements` VALUES ('36','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('36','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('36','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('36','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('36','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('36','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('37','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('37','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('37','headline','input','Contact Form');
INSERT INTO `documents_elements` VALUES ('37','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('37','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('37','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('37','myDate','date','');
INSERT INTO `documents_elements` VALUES ('37','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('37','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('37','myInput','input','');
INSERT INTO `documents_elements` VALUES ('37','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('37','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('37','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('37','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('37','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('37','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('38','content','wysiwyg','<p><strong>Gender</strong>: %Text(gender);&nbsp;</p>\n\n<p><strong>Firstname</strong>: %Text(firstname);<br />\n<strong>Lastname</strong>: %Text(lastname);<br />\n<strong>E-Mail</strong>: %Text(email);&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p><strong>Message</strong>:<br />\n%Text(message);&nbsp;</p>\n\n<p>&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('38','headline','input','You\'ve got a new E-Mail!');
INSERT INTO `documents_elements` VALUES ('38','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('38','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('38','myDate','date','');
INSERT INTO `documents_elements` VALUES ('38','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('38','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('38','myInput','input','');
INSERT INTO `documents_elements` VALUES ('38','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('38','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('38','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('38','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('38','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('38','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('39','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('39','content:1.content','wysiwyg','<div id=\"idTextPanel\">\n<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt.</p>\n\n<p>&nbsp;</p>\n\n<p>Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus.</p>\n\n<p>&nbsp;</p>\n\n<p>Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n\n<div>&nbsp;</div>\n</div>\n');
INSERT INTO `documents_elements` VALUES ('39','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('39','headline','input','It seems that the page you were trying to find isn\'t around anymore. ');
INSERT INTO `documents_elements` VALUES ('39','headTitle','input','Oh no!');
INSERT INTO `documents_elements` VALUES ('39','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('41','authorcontent3','input','Albert Einstein');
INSERT INTO `documents_elements` VALUES ('41','carouselSlides','select','3');
INSERT INTO `documents_elements` VALUES ('41','cHeadline_0','input','Bereit beeindruckt zu werden? ');
INSERT INTO `documents_elements` VALUES ('41','cHeadline_1','input','Es wird dich umhauen!');
INSERT INTO `documents_elements` VALUES ('41','cHeadline_2','input','Oh ja, es ist wirklich so gut');
INSERT INTO `documents_elements` VALUES ('41','cImage_0','image','a:9:{s:2:\"id\";i:4;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','cImage_1','image','a:9:{s:2:\"id\";i:5;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','cImage_2','image','a:9:{s:2:\"id\";i:6;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','cLink_0','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('41','cLink_1','link','a:15:{s:4:\"text\";s:16:\"See it in Action\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('41','cLink_2','link','a:15:{s:4:\"text\";s:9:\"Checkmate\";s:4:\"path\";s:12:\"/experiments\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:6;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('41','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:10:\"featurette\";}}');
INSERT INTO `documents_elements` VALUES ('41','content:1.block','block','a:1:{i:0;s:1:\"1\";}');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.content','wysiwyg','<p>In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi.</p>\n');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.headline','input','Lorem ipsum.');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.image','image','a:9:{s:2:\"id\";i:55;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.position','select','');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.subline','input','Cum sociis.');
INSERT INTO `documents_elements` VALUES ('41','content:1.block:1.type','select','');
INSERT INTO `documents_elements` VALUES ('41','content:2.teaser_0','snippet','47');
INSERT INTO `documents_elements` VALUES ('41','content:2.teaser_1','snippet','48');
INSERT INTO `documents_elements` VALUES ('41','content:2.teaser_2','snippet','49');
INSERT INTO `documents_elements` VALUES ('41','content:2.type_0','select','');
INSERT INTO `documents_elements` VALUES ('41','content:2.type_1','select','');
INSERT INTO `documents_elements` VALUES ('41','content:2.type_2','select','');
INSERT INTO `documents_elements` VALUES ('41','contentcontent_blockcontent11_2','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>\n');
INSERT INTO `documents_elements` VALUES ('41','contentcontent_blockcontent11_3','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo.</p>\n');
INSERT INTO `documents_elements` VALUES ('41','cText_0','textarea','Teste unsere Beispiele und tauche ein in die nächste Generation von digitalem Inhaltsmanagement');
INSERT INTO `documents_elements` VALUES ('41','cText_1','textarea','Sieh\' selbst');
INSERT INTO `documents_elements` VALUES ('41','cText_2','textarea','Sieh\' selbst!');
INSERT INTO `documents_elements` VALUES ('41','headlinecontent_blockcontent11_2','input','Oh yeah, it\'s that good.');
INSERT INTO `documents_elements` VALUES ('41','headlinecontent_blockcontent11_3','input','And lastly, this one.');
INSERT INTO `documents_elements` VALUES ('41','imagecontent_blockcontent11_2','image','a:9:{s:2:\"id\";i:18;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','imagecontent_blockcontent11_3','image','a:9:{s:2:\"id\";i:19;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('41','imagePositioncontent_blockcontent11_1','select','');
INSERT INTO `documents_elements` VALUES ('41','imagePositioncontent_blockcontent11_2','select','left');
INSERT INTO `documents_elements` VALUES ('41','imagePositioncontent_blockcontent11_3','select','');
INSERT INTO `documents_elements` VALUES ('41','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('41','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('41','myDate','date','');
INSERT INTO `documents_elements` VALUES ('41','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('41','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('41','myInput','input','');
INSERT INTO `documents_elements` VALUES ('41','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('41','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('41','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('41','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('41','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('41','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('41','positioncontent_blockcontent11_2','select','left');
INSERT INTO `documents_elements` VALUES ('41','positioncontent_blockcontent11_3','select','');
INSERT INTO `documents_elements` VALUES ('41','quotecontent3','input','We can\'t solve problems by using the same kind of thinking we used when we created them.');
INSERT INTO `documents_elements` VALUES ('41','sublinecontent_blockcontent11_2','input','See for yourself.');
INSERT INTO `documents_elements` VALUES ('41','sublinecontent_blockcontent11_3','input','Checkmate.');
INSERT INTO `documents_elements` VALUES ('41','typecontent_blockcontent11_2','select','video');
INSERT INTO `documents_elements` VALUES ('41','typecontent_blockcontent11_3','select','');
INSERT INTO `documents_elements` VALUES ('41','videocontent_blockcontent11_2','video','a:5:{s:2:\"id\";i:27;s:4:\"type\";s:5:\"asset\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:6:\"poster\";N;}');
INSERT INTO `documents_elements` VALUES ('46','links','block','a:3:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";}');
INSERT INTO `documents_elements` VALUES ('46','links:1.link','link','a:12:{s:4:\"text\";s:11:\"pimcore.org\";s:4:\"path\";s:23:\"http://www.pimcore.org/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('46','links:2.link','link','a:11:{s:4:\"text\";s:13:\"Dokumentation\";s:4:\"path\";s:28:\"http://www.pimcore.org/wiki/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('46','links:3.link','link','a:12:{s:4:\"text\";s:11:\"Bug Tracker\";s:4:\"path\";s:30:\"http://www.pimcore.org/issues/\";s:6:\"target\";s:6:\"_blank\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('46','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('46','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('46','myDate','date','');
INSERT INTO `documents_elements` VALUES ('46','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('46','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('46','myInput','input','');
INSERT INTO `documents_elements` VALUES ('46','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('46','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('46','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('46','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('46','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('46','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('46','text','wysiwyg','<p>Designed and built with all the love in the world by&nbsp;<a href=\"http://twitter.com/mdo\" target=\"_blank\">@mdo</a>&nbsp;and&nbsp;<a href=\"http://twitter.com/fat\" target=\"_blank\">@fat</a>.</p>\n\n<p>Code licensed under&nbsp;<a href=\"http://www.apache.org/licenses/LICENSE-2.0\" target=\"_blank\">Apache License v2.0</a>,&nbsp;<a href=\"http://glyphicons.com/\">Glyphicons Free</a>&nbsp;licensed under&nbsp;<a href=\"http://creativecommons.org/licenses/by/3.0/\">CC BY 3.0</a>.</p>\n\n<p><strong>© Templates pimcore.org licensed under BSD License</strong></p>\n');
INSERT INTO `documents_elements` VALUES ('47','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('47','headline','input','Voll Responsive');
INSERT INTO `documents_elements` VALUES ('47','image','image','a:9:{s:2:\"id\";i:21;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('47','link','link','a:15:{s:4:\"text\";s:11:\"Lorem ipsum\";s:4:\"path\";s:18:\"/en/basic-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:3;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('47','text','wysiwyg','<p>Diese Demo basiert auf Bootstrap, dem wohl bekanntesten,&nbsp;beliebtesten und flexibelsten Fontend-Framework.</p>\n');
INSERT INTO `documents_elements` VALUES ('48','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('48','headline','input','Drag & Drop Inhaltserstellung');
INSERT INTO `documents_elements` VALUES ('48','image','image','a:9:{s:2:\"id\";i:20;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('48','link','link','a:15:{s:4:\"text\";s:12:\"Etiam rhoncu\";s:4:\"path\";s:21:\"/en/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('48','text','wysiwyg','<p>Inhalt wird einfach per drag &amp; drop mit Inhaltsblöcken erstellt, welche dann direkt in-line editiert werden können.</p>\n');
INSERT INTO `documents_elements` VALUES ('49','circle','checkbox','');
INSERT INTO `documents_elements` VALUES ('49','headline','input','HTML5 immer & überall');
INSERT INTO `documents_elements` VALUES ('49','image','image','a:9:{s:2:\"id\";i:18;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('49','link','link','a:15:{s:4:\"text\";s:14:\"Quisque rutrum\";s:4:\"path\";s:15:\"/en/experiments\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:6;s:12:\"internalType\";s:8:\"document\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('49','text','wysiwyg','<p>&nbsp;</p>\n\n<p>Bilder direkt per drag &amp; drop vom Desktop&nbsp;in den Baum in pimcore hochladen, automatische HTML5 Video Konvertierung&nbsp;und viel mehr ...</p>\n');
INSERT INTO `documents_elements` VALUES ('50','circle2content1','checkbox','');
INSERT INTO `documents_elements` VALUES ('50','content','areablock','a:3:{i:0;a:2:{s:3:\"key\";s:1:\"3\";s:4:\"type\";s:7:\"wysiwyg\";}i:1;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:19:\"standard-teaser-row\";}i:2;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:10:\"featurette\";}}');
INSERT INTO `documents_elements` VALUES ('50','content:1.teaser_0','snippet','47');
INSERT INTO `documents_elements` VALUES ('50','content:1.teaser_1','snippet','48');
INSERT INTO `documents_elements` VALUES ('50','content:1.teaser_2','snippet','49');
INSERT INTO `documents_elements` VALUES ('50','content:1.type_0','select','');
INSERT INTO `documents_elements` VALUES ('50','content:1.type_1','select','snippet');
INSERT INTO `documents_elements` VALUES ('50','content:1.type_2','select','snippet');
INSERT INTO `documents_elements` VALUES ('50','content:2.block','block','a:1:{i:0;s:1:\"1\";}');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.content','wysiwyg','<p>Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo.</p>\n');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.headline','input','Ullamcorper Scelerisque ');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.image','image','a:9:{s:2:\"id\";i:24;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.position','select','');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.subline','input','');
INSERT INTO `documents_elements` VALUES ('50','content:2.block:1.type','select','');
INSERT INTO `documents_elements` VALUES ('50','content:3.content','wysiwyg','<p>Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. <a href=\"/basic-examples\">Etiam rhoncus</a>.</p>\n\n<p>&nbsp;</p>\n\n<ul>\n	<li>Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.</li>\n	<li>Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem.</li>\n	<li>Maecenas nec odio et ante tincidunt tempus.</li>\n	<li><a href=\"/basic-examples\">Donec vitae sapien ut libero venenatis faucibus.</a></li>\n	<li>Nullam quis ante.</li>\n	<li>Etiam sit amet orci eget eros <a href=\"/advanced-examples\">faucibus </a>tincidunt.</li>\n</ul>\n\n<p>&nbsp;</p>\n\n<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform <a href=\"/experiments\">grammatica</a>, pronunciation e plu sommun paroles.</p>\n\n<p>&nbsp;</p>\n\n<ol>\n	<li>It va esser tam simplic quam Occidental in fact, it va esser Occidental.</li>\n	<li>A un Angleso it va semblar un simplificat Angles, quam un skeptic <a href=\"/introduction\">Cambridge </a>amico dit me que Occidental es.</li>\n	<li>Li Europan lingues es membres del sam familie.</li>\n	<li>Lor separat existentie es un myth.</li>\n	<li>Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</li>\n	<li>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules.</li>\n</ol>\n\n<p>&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('50','headDescription','input','Überblick über das Projekt und wie man mit einer einfachen Vorlage loslegen kann.');
INSERT INTO `documents_elements` VALUES ('50','headline','input','Einführung');
INSERT INTO `documents_elements` VALUES ('50','headline2content1','input','');
INSERT INTO `documents_elements` VALUES ('50','headTitle','input','Erste Schritte');
INSERT INTO `documents_elements` VALUES ('50','image2content1','image','a:9:{s:2:\"id\";N;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('50','imagecontent1','image','a:9:{s:2:\"id\";i:22;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('50','imagePositioncontent_blockcontent22_1','select','');
INSERT INTO `documents_elements` VALUES ('50','leadcontent3','wysiwyg','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.</p>\n');
INSERT INTO `documents_elements` VALUES ('50','link2content1','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('50','linkcontent1','link','a:14:{s:4:\"text\";s:12:\"Etiam rhoncu\";s:4:\"path\";s:18:\"/advanced-examples\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:8:\"internal\";b:1;s:10:\"internalId\";i:5;s:12:\"internalType\";s:8:\"document\";}');
INSERT INTO `documents_elements` VALUES ('50','text2content1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('50','textcontent1','wysiwyg','<p>Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna.</p>\n');
INSERT INTO `documents_elements` VALUES ('51','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('51','content:1.content','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('51','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('51','headline','input','Übersicht über einfache Beispiele');
INSERT INTO `documents_elements` VALUES ('51','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('51','leadcontent1','wysiwyg','<p>Diese Seite dient nur zur Demonstration einer mehrsprachigen Seite.&nbsp;</p>\n\n<p><a href=\"/en/basic-examples\" pimcore_id=\"3\" pimcore_type=\"document\">Um die Beispiele zu sehen verwende bitte die Englische Beispielseite.&nbsp;</a></p>\n');
INSERT INTO `documents_elements` VALUES ('52','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('52','content:1.content','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('52','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('52','headline','input','Übersicht über fortgeschrittene Beispiele');
INSERT INTO `documents_elements` VALUES ('52','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('52','leadcontent1','wysiwyg','<p>Diese Seite dient nur zur Demonstration einer mehrsprachigen Seite.&nbsp;</p>\n\n<p><a href=\"/en/advanced-examples\" pimcore_id=\"5\" pimcore_type=\"document\">Um die Beispiele zu sehen verwende bitte die Englische Beispielseite.&nbsp;</a></p>\n');
INSERT INTO `documents_elements` VALUES ('53','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}}');
INSERT INTO `documents_elements` VALUES ('53','content:1.content','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('53','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('53','headline','input','Neuigkeiten');
INSERT INTO `documents_elements` VALUES ('53','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('53','leadcontent1','wysiwyg','<p>Alle strukturierten Daten werden in \"Objects\" gespeichert.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('53','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('53','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('53','myDate','date','');
INSERT INTO `documents_elements` VALUES ('53','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('53','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('53','myInput','input','');
INSERT INTO `documents_elements` VALUES ('53','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('53','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('53','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('53','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('53','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('53','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('57','blogArticles','select','3');
INSERT INTO `documents_elements` VALUES ('57','teasers','block','a:1:{i:0;s:1:\"1\";}');
INSERT INTO `documents_elements` VALUES ('57','teasers:1.teaser','snippet','15');
INSERT INTO `documents_elements` VALUES ('58','teasers','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('58','teasers:1.teaser','snippet','47');
INSERT INTO `documents_elements` VALUES ('58','teasers:2.teaser','snippet','49');
INSERT INTO `documents_elements` VALUES ('59','blogArticles','select','2');
INSERT INTO `documents_elements` VALUES ('59','teasers','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('59','teasers:1.teaser','snippet','15');
INSERT INTO `documents_elements` VALUES ('59','teasers:2.teaser','snippet','16');
INSERT INTO `documents_elements` VALUES ('60','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:9:\"headlines\";}}');
INSERT INTO `documents_elements` VALUES ('60','content:2.headline','input','');
INSERT INTO `documents_elements` VALUES ('60','content:2.lead','wysiwyg','<p>A blog is also just a simple list of objects. You can easily modify the structure of an article in Settings -&gt; Object -&gt; Classes.</p>\n');
INSERT INTO `documents_elements` VALUES ('60','contentcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('60','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('60','headline','input','Blog');
INSERT INTO `documents_elements` VALUES ('60','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('60','leadcontent1','wysiwyg','<p>A blog is also just a simple list of objects.</p>\n\n<p>You can easily modify the structure of an article in Settings -&gt; Object -&gt; Classes.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('60','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('60','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('60','myDate','date',NULL);
INSERT INTO `documents_elements` VALUES ('60','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('60','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('60','myInput','input','');
INSERT INTO `documents_elements` VALUES ('60','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('60','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('60','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('60','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('60','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('60','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('61','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('61','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('61','headline','input','Auto-generated Sitemap');
INSERT INTO `documents_elements` VALUES ('61','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('61','multiselect','multiselect','a:0:{}');
INSERT INTO `documents_elements` VALUES ('61','myCheckbox','checkbox','');
INSERT INTO `documents_elements` VALUES ('61','myDate','date','');
INSERT INTO `documents_elements` VALUES ('61','myHref','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('61','myImageBlock','block','a:0:{}');
INSERT INTO `documents_elements` VALUES ('61','myInput','input','');
INSERT INTO `documents_elements` VALUES ('61','myLink','link','a:10:{s:4:\"type\";s:8:\"internal\";s:4:\"path\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:6:\"target\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('61','myMultihref','multihref','a:0:{}');
INSERT INTO `documents_elements` VALUES ('61','myNumber','numeric','');
INSERT INTO `documents_elements` VALUES ('61','mySelect','select','');
INSERT INTO `documents_elements` VALUES ('61','myTextarea','textarea','');
INSERT INTO `documents_elements` VALUES ('61','myWysiwyg','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('63','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('63','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('63','headline','input','Newsletter');
INSERT INTO `documents_elements` VALUES ('63','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('64','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('64','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('64','headline','input','');
INSERT INTO `documents_elements` VALUES ('64','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('65','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('65','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('65','headline','input','Newsletter Unsubscribe');
INSERT INTO `documents_elements` VALUES ('65','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('66','contactInfo','wysiwyg','<h5>Contact Info</h5>\n\n<p>Example Inc.<br />\nEvergreen Terrace 123<br />\nXX 89234 Springfield<br />\n<br />\n+8998 487563 34234<br />\n<a href=\"mailto:info@example.inc\">info@example.inc</a></p>\n');
INSERT INTO `documents_elements` VALUES ('66','content','wysiwyg','<p>Hi %Text(firstname);&nbsp;%Text(lastname);,&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>You have just subscribed our cool newsletter with the email address: %Text(email);.&nbsp;</p>\n\n<p>To finish the process please click the following link to confirm your email address.&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p><a href=\"http://demo.pimcore.org/en/advanced-examples/newsletter/confirm?token=%Text(token);\">CLICK HERE TO CONFIRM</a></p>\n\n<p>&nbsp;</p>\n\n<p>Thanks &amp; have a nice day!</p>\n');
INSERT INTO `documents_elements` VALUES ('66','footerLink1','link','a:12:{s:4:\"text\";s:5:\"Terms\";s:4:\"path\";s:1:\"#\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('66','footerLink2','link','a:12:{s:4:\"text\";s:7:\"Privacy\";s:4:\"path\";s:1:\"#\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('66','footerLink3','link','a:12:{s:4:\"text\";s:5:\"About\";s:4:\"path\";s:1:\"#\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";s:4:\"type\";s:8:\"internal\";}');
INSERT INTO `documents_elements` VALUES ('67','contactInfo','wysiwyg','<h5>Contact Info</h5>\n\n<p>Example Inc.<br />\nEvergreen Terrace 123<br />\nXX 89234 Springfield<br />\n<br />\n+8998 487563 34234<br />\n<a href=\"mailto:info@example.inc\">info@example.inc</a></p>\n');
INSERT INTO `documents_elements` VALUES ('67','content','wysiwyg','<p><span style=\"line-height: 1.3;\">Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</span></p>\n\n<p>Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.</p>\n\n<p>&nbsp;</p>\n\n<p><img pimcore_id=\"22\" pimcore_type=\"asset\" src=\"/website/var/tmp/image-thumbnails/22/thumb__auto_850904660de984af948beee3aee98a4f/img_0399.jpeg\" style=\"width:600px;\" /></p>\n\n<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante.&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>&nbsp;</p>\n\n<p>Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>\n\n<p>&nbsp;</p>\n\n<p>Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor.</p>\n');
INSERT INTO `documents_elements` VALUES ('67','footerLink1','link','a:11:{s:4:\"text\";s:5:\"Terms\";s:4:\"path\";s:1:\"#\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('67','footerLink2','link','a:11:{s:4:\"text\";s:7:\"Privacy\";s:4:\"path\";s:1:\"#\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('67','footerLink3','link','a:11:{s:4:\"text\";s:11:\"Unsubscribe\";s:4:\"path\";s:87:\"http://demo.pimcore.org/en/advanced-examples/newsletter/unsubscribe?token=%Text(token);\";s:6:\"target\";s:0:\"\";s:10:\"parameters\";s:0:\"\";s:6:\"anchor\";s:0:\"\";s:5:\"title\";s:0:\"\";s:9:\"accesskey\";s:0:\"\";s:3:\"rel\";s:0:\"\";s:8:\"tabindex\";s:0:\"\";s:5:\"class\";s:0:\"\";s:10:\"attributes\";s:0:\"\";}');
INSERT INTO `documents_elements` VALUES ('68','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('68','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('68','headline','input','Asset Thumbnail List');
INSERT INTO `documents_elements` VALUES ('68','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('68','parentFolder','href','a:3:{s:2:\"id\";N;s:4:\"type\";N;s:7:\"subtype\";N;}');
INSERT INTO `documents_elements` VALUES ('69','teasers','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('69','teasers:1.teaser','snippet','16');
INSERT INTO `documents_elements` VALUES ('69','teasers:2.teaser','snippet','17');
INSERT INTO `documents_elements` VALUES ('70','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:1;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('70','content:1.content','wysiwyg','<p>Please visit our&nbsp;<a href=\"http://pimcore.org/demo\">PIM, E-Commerce &amp; Asset Management demo</a> to see it in action.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('70','content:2.image','image','a:9:{s:2:\"id\";i:70;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('70','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('70','headline','input','Product Information Management');
INSERT INTO `documents_elements` VALUES ('70','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('70','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('70','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('71','content','areablock','a:2:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:7:\"wysiwyg\";}i:1;a:2:{s:3:\"key\";s:1:\"2\";s:4:\"type\";s:5:\"image\";}}');
INSERT INTO `documents_elements` VALUES ('71','content:1.content','wysiwyg','<p>Please visit our&nbsp;<a href=\"http://pimcore.org/demo\">PIM, E-Commerce &amp; Asset Management demo</a> to see it in action.&nbsp;</p>\n');
INSERT INTO `documents_elements` VALUES ('71','content:2.image','image','a:9:{s:2:\"id\";i:69;s:3:\"alt\";s:0:\"\";s:11:\"cropPercent\";N;s:9:\"cropWidth\";N;s:10:\"cropHeight\";N;s:7:\"cropTop\";N;s:8:\"cropLeft\";N;s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}}');
INSERT INTO `documents_elements` VALUES ('71','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('71','headline','input','E-Commerce');
INSERT INTO `documents_elements` VALUES ('71','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('71','leadcontent1','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('71','leadcontent2','wysiwyg','');
INSERT INTO `documents_elements` VALUES ('72','content','areablock','a:0:{}');
INSERT INTO `documents_elements` VALUES ('72','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('72','headline','input','');
INSERT INTO `documents_elements` VALUES ('72','headTitle','input','');
INSERT INTO `documents_elements` VALUES ('73','content','areablock','a:1:{i:0;a:2:{s:3:\"key\";s:1:\"1\";s:4:\"type\";s:5:\"embed\";}}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_1','block','a:3:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_1:1.socialContent','embed','a:1:{s:3:\"url\";s:61:\"https://twitter.com/DiscoverAustria/status/707491558229217280\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_1:2.socialContent','embed','a:1:{s:3:\"url\";s:27:\"https://vimeo.com/121649600\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_1:3.socialContent','embed','a:1:{s:3:\"url\";s:113:\"https://www.facebook.com/1007688325917154/photos/a.1046725715346748.1073741827.1007688325917154/1182670351752283/\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_2','block','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_2:1.socialContent','embed','a:1:{s:3:\"url\";s:43:\"https://www.youtube.com/watch?v=nPntDiARQYw\";}');
INSERT INTO `documents_elements` VALUES ('73','content:1.contents_2:2.socialContent','embed','a:1:{s:3:\"url\";s:40:\"https://www.instagram.com/p/BCne7kJIYLR/\";}');
INSERT INTO `documents_elements` VALUES ('73','headDescription','input','');
INSERT INTO `documents_elements` VALUES ('73','headline','input','Embedding Social Contents');
INSERT INTO `documents_elements` VALUES ('73','headTitle','input','');
INSERT INTO `documents_email` VALUES ('38','','default','default','Advanced/email.html.twig','pimcore@byom.de','pimcore@byom.de','','','Contact Form','0');
INSERT INTO `documents_email` VALUES ('66','','newsletter','standard-mail','','','','','','','0');
INSERT INTO `documents_hardlink` VALUES ('33','3','1','1');
INSERT INTO `documents_link` VALUES ('32',NULL,'0','http://www.pimcore.org/','direct');
INSERT INTO `documents_link` VALUES ('40','document','1','','internal');
INSERT INTO `documents_newsletter` VALUES ('67','','newsletter','standard-mail','','','Example Newsletter','newsletter','email','example-mailing','1','single','0');
INSERT INTO `documents_page` VALUES ('1','','Content','portal','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('3','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('4','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('5','','Advanced','index','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('6','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('7','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('9','','Advanced','object-form','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('18','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('19','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('20','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('21','','Content','thumbnails','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('22','','Content','website-translations','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('23','','Content','website-translations','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('24','','Content','default','','','','a:0:{}',NULL,'0','',NULL);
INSERT INTO `documents_page` VALUES ('25','','Content','editable-roundup','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('26','','Content','simple-form','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('27','','News','index','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('28','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('29','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('30','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('31','','Content','default','','','','a:0:{}','','30','',NULL);
INSERT INTO `documents_page` VALUES ('34','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('35','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('36','','Advanced','search','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('37','','Advanced','contact-form','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('39','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('41','','Content','portal','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('50','','Content','default','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('51','','Content','default','','Einfache Beispiele','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('52','','Content','default','','Beispiele für Fortgeschrittene','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('53','','News','index','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('60','','Blog','index','','Blog','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('61','','Advanced','sitemap','','Sitemap','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('63','','Newsletter','subscribe','','Newsletter','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('64','','Newsletter','confirm','','','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('65','','Newsletter','unsubscribe','','Unsubscribe','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('68','','Advanced','asset-thumbnail-list','','Asset Thumbnail List','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('70','','Content','default','','Product Information Management','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('71','','Content','default','','E-Commerce','','a:0:{}','','0','',NULL);
INSERT INTO `documents_page` VALUES ('72','','Category/Example','test','','','','a:0:{}',NULL,NULL,'',NULL);
INSERT INTO `documents_page` VALUES ('73',NULL,'Content','default',NULL,'Social Contents','','a:0:{}',NULL,NULL,'',NULL);
INSERT INTO `documents_snippet` VALUES ('12','','Default','default','Includes/footer.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('15','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('16','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('17','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('46','','Default','default','Includes/footer.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('47','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('48','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('49','','Default','default','Snippets/standard-teaser.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('57','','Default','default','Includes/sidebar.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('58','','Default','default','Includes/sidebar.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('59','','Default','default','Includes/sidebar.html.twig','0',NULL);
INSERT INTO `documents_snippet` VALUES ('69','','Default','default','Includes/sidebar.html.twig','0',NULL);
INSERT INTO `documents_translations` VALUES ('23','22','de');
INSERT INTO `documents_translations` VALUES ('41','40','de');
INSERT INTO `documents_translations` VALUES ('46','12','de');
INSERT INTO `documents_translations` VALUES ('47','15','de');
INSERT INTO `documents_translations` VALUES ('48','16','de');
INSERT INTO `documents_translations` VALUES ('49','17','de');
INSERT INTO `documents_translations` VALUES ('50','4','de');
INSERT INTO `documents_translations` VALUES ('51','3','de');
INSERT INTO `documents_translations` VALUES ('52','5','de');
INSERT INTO `documents_translations` VALUES ('53','27','de');
INSERT INTO `documents_translations` VALUES ('58','57','de');
INSERT INTO `glossary` VALUES ('1','en','0','1','occidental','7','','','0','0','0');
INSERT INTO `glossary` VALUES ('2','en','0','1','vocabular','20','','','0','0','0');
INSERT INTO `glossary` VALUES ('3','en','0','1','resultant','5','','','0','0','0');
INSERT INTO `glossary` VALUES ('4','en','0','1','familie','18','','','0','0','0');
INSERT INTO `glossary` VALUES ('5','en','0','1','omnicos','19','','','0','0','0');
INSERT INTO `glossary` VALUES ('6','en','0','1','coalesce','','coalesce','','0','0','0');
INSERT INTO `glossary` VALUES ('7','en','0','1','grammatica','','','grammatica','0','0','0');
INSERT INTO `notes` VALUES ('18','newsletter','47','object','1388412533','0','subscribe','');
INSERT INTO `notes` VALUES ('19','newsletter','47','object','1388412545','0','confirm','');
INSERT INTO `notes_data` VALUES ('18','ip','text','192.168.9.12');
INSERT INTO `notes_data` VALUES ('19','ip','text','192.168.9.12');
INSERT INTO `object_localized_data_2` VALUES ('3','de','Er hörte leise Schritte hinter sich','Das bedeutete nichts Gutes. Wer würde ihm schon folgen, spät in der Nacht und dazu noch in dieser engen Gasse mitten im übel beleumundeten Hafenviertel?','<p>Oder geh&ouml;rten die Schritte hinter ihm zu einem der unz&auml;hligen Gesetzesh&uuml;ter dieser Stadt, und die st&auml;hlerne Acht um seine Handgelenke w&uuml;rde gleich zuschnappen? Er konnte die Aufforderung stehen zu bleiben schon h&ouml;ren. Gehetzt sah er sich um. Pl&ouml;tzlich erblickte er den schmalen Durchgang. Blitzartig drehte er sich nach rechts und verschwand zwischen den beiden Geb&auml;uden. Beinahe w&auml;re er dabei &uuml;ber den umgest&uuml;rzten M&uuml;lleimer gefallen, der mitten im Weg lag.</p>\n\n<p>Er versuchte, sich in der Dunkelheit seinen Weg zu ertasten und erstarrte: Anscheinend gab es keinen anderen Ausweg aus diesem kleinen Hof als den Durchgang, durch den er gekommen war. Die Schritte wurden lauter und lauter, er sah eine dunkle Gestalt um die Ecke biegen. Fieberhaft irrten seine Augen durch die n&auml;chtliche Dunkelheit und suchten einen Ausweg. War jetzt wirklich alles vorbei.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('3','en','Lorem ipsum dolor sit amet','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.','<p>Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam&nbsp;<a href=\"/en/basic-examples/content-page\" pimcore_id=\"24\" pimcore_type=\"document\">ultricies&nbsp;</a>nisi vel augue.</p>\n\n<p>Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget&nbsp;<a href=\"/en/basic-examples/galleries\" pimcore_id=\"19\" pimcore_type=\"document\">condimentum&nbsp;rhoncus</a>, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('4','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','<p>Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</p>\n\n<p>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('4','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','<p>Nam eget dui. Etiam rhoncus.&nbsp;<a href=\"/en/basic-examples\" pimcore_id=\"3\" pimcore_type=\"document\">Maecenas&nbsp;tempus</a>, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. <a href=\"/en/basic-examples/news\" pimcore_id=\"27\" pimcore_type=\"document\">Donec vitae sapien</a> ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed&nbsp;fringilla&nbsp;mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('5','de','Zwei flinke Boxer jagen die quirlige Eva','Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zwölf Boxkämpfer jagen Viktor quer über den großen Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim.','<p>Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung. Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux bei Pforzheim. Polyfon zwitschernd a&szlig;en M&auml;xchens V&ouml;gel R&uuml;ben, Joghurt und Quark. &quot;Fix, Schwyz!&quot; qu&auml;kt J&uuml;rgen bl&ouml;d vom Pa&szlig;.</p>\n\n<p>Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung.Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('5','en','Nam eget dui','Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.','<p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('6','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','<p>Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</p>\n\n<p>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('6','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_data_2` VALUES ('7','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','<p>Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</p>\n\n<p>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('7','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_data_2` VALUES ('8','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','<p>Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</p>\n\n<p>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('8','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_data_2` VALUES ('9','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','<p>Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.</p>\n\n<p>Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n');
INSERT INTO `object_localized_data_2` VALUES ('9','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_data_5` VALUES ('35','de','Maecenas nec odio et ante tincidunt tempus','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue.</p>\n\n<p>Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus.</p>\n\n<p>Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc.</p>\n','Aenean Vestibulum Etiam Curabitur');
INSERT INTO `object_localized_data_5` VALUES ('35','en','Maecenas nec odio et ante tincidunt tempus','<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue.</p>\n\n<p>Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus.</p>\n\n<p>Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc.</p>\n','Aenean Vestibulum Etiam Curabitur');
INSERT INTO `object_localized_data_5` VALUES ('39','de','Lorem ipsum dolor sit amet','<p>Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst.</p>\n\n<p>Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante.</p>\n\n<p>In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci.</p>\n\n<p>Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla.</p>\n\n<p>Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio.</p>\n','Etiam Curabitur Fusce Quisque');
INSERT INTO `object_localized_data_5` VALUES ('39','en','Lorem ipsum dolor sit amet','<p>Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst.</p>\n\n<p>Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante.</p>\n\n<p>In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci.</p>\n\n<p>Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla.</p>\n\n<p>Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio.</p>\n','Etiam Curabitur Fusce Quisque');
INSERT INTO `object_localized_data_5` VALUES ('40','de','Cum sociis natoque penatibus et magnis','<p>Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem.</p>\n\n<p>Maecenas nec odio et ante tincidunt tempus. <strong>Donec vitae sapien</strong> ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui.</p>\n\n<p><img pimcore_id=\"21\" pimcore_type=\"asset\" src=\"/website/var/tmp/image-thumbnails/21/thumb__auto_850904660de984af948beee3aee98a4f/img_0037.jpeg\" style=\"width:600px;\" /></p>\n\n<p>Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus.</p>\n\n<hr />\n<p>Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor.</p>\n\n<p>Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>\n','Fusce Quisque Maecenas Donec');
INSERT INTO `object_localized_data_5` VALUES ('40','en','Cum sociis natoque penatibus et magnis','<p>Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem.</p>\n\n<p>Maecenas nec odio et ante tincidunt tempus. <strong>Donec vitae sapien</strong> ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui.</p>\n\n<p><img pimcore_id=\"21\" pimcore_type=\"asset\" src=\"/website/var/tmp/image-thumbnails/21/thumb__auto_850904660de984af948beee3aee98a4f/img_0037.jpeg\" style=\"width:600px;\" /></p>\n\n<p>Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus.</p>\n\n<hr />\n<p>Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor.</p>\n\n<p>Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>\n','Fusce Quisque Maecenas Donec');
INSERT INTO `object_localized_data_6` VALUES ('36','de','Curabitur ullamcorper');
INSERT INTO `object_localized_data_6` VALUES ('36','en','Curabitur ullamcorper');
INSERT INTO `object_localized_data_6` VALUES ('37','de','Nam eget dui');
INSERT INTO `object_localized_data_6` VALUES ('37','en','Nam eget dui');
INSERT INTO `object_localized_data_6` VALUES ('38','de','Etiam rhoncus');
INSERT INTO `object_localized_data_6` VALUES ('38','en','Etiam rhoncus');
INSERT INTO `object_localized_query_2_de` VALUES ('3','de','Er hörte leise Schritte hinter sich','Das bedeutete nichts Gutes. Wer würde ihm schon folgen, spät in der Nacht und dazu noch in dieser engen Gasse mitten im übel beleumundeten Hafenviertel?','Oder geh&ouml;rten die Schritte hinter ihm zu einem der unz&auml;hligen Gesetzesh&uuml;ter dieser Stadt, und die st&auml;hlerne Acht um seine Handgelenke w&uuml;rde gleich zuschnappen? Er konnte die Aufforderung stehen zu bleiben schon h&ouml;ren. Gehetzt sah er sich um. Pl&ouml;tzlich erblickte er den schmalen Durchgang. Blitzartig drehte er sich nach rechts und verschwand zwischen den beiden Geb&auml;uden. Beinahe w&auml;re er dabei &uuml;ber den umgest&uuml;rzten M&uuml;lleimer gefallen, der mitten im Weg lag. Er versuchte, sich in der Dunkelheit seinen Weg zu ertasten und erstarrte: Anscheinend gab es keinen anderen Ausweg aus diesem kleinen Hof als den Durchgang, durch den er gekommen war. Die Schritte wurden lauter und lauter, er sah eine dunkle Gestalt um die Ecke biegen. Fieberhaft irrten seine Augen durch die n&auml;chtliche Dunkelheit und suchten einen Ausweg. War jetzt wirklich alles vorbei. ');
INSERT INTO `object_localized_query_2_de` VALUES ('4','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. ');
INSERT INTO `object_localized_query_2_de` VALUES ('5','de','Zwei flinke Boxer jagen die quirlige Eva','Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zwölf Boxkämpfer jagen Viktor quer über den großen Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim.','Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung. Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux bei Pforzheim. Polyfon zwitschernd a&szlig;en M&auml;xchens V&ouml;gel R&uuml;ben, Joghurt und Quark. &quot;Fix, Schwyz!&quot; qu&auml;kt J&uuml;rgen bl&ouml;d vom Pa&szlig;. Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung.Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux ');
INSERT INTO `object_localized_query_2_de` VALUES ('6','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. ');
INSERT INTO `object_localized_query_2_de` VALUES ('7','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. ');
INSERT INTO `object_localized_query_2_de` VALUES ('8','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. ');
INSERT INTO `object_localized_query_2_de` VALUES ('9','de','Li Europan lingues es membres','Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular.','Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. ');
INSERT INTO `object_localized_query_2_en` VALUES ('3','en','Lorem ipsum dolor sit amet','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.','Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam&nbsp;<a href=\"/en/basic-examples/content-page\" pimcore_id=\"24\" pimcore_type=\"document\">ultricies&nbsp;</a>nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget&nbsp;<a href=\"/en/basic-examples/galleries\" pimcore_id=\"19\" pimcore_type=\"document\">condimentum&nbsp;rhoncus</a>, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. ');
INSERT INTO `object_localized_query_2_en` VALUES ('4','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','Nam eget dui. Etiam rhoncus.&nbsp;<a href=\"/en/basic-examples\" pimcore_id=\"3\" pimcore_type=\"document\">Maecenas&nbsp;tempus</a>, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. <a href=\"/en/basic-examples/news\" pimcore_id=\"27\" pimcore_type=\"document\">Donec vitae sapien</a> ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed&nbsp;fringilla&nbsp;mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, ');
INSERT INTO `object_localized_query_2_en` VALUES ('5','en','Nam eget dui','Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, ');
INSERT INTO `object_localized_query_2_en` VALUES ('6','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_query_2_en` VALUES ('7','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_query_2_en` VALUES ('8','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_query_2_en` VALUES ('9','en','In enim justo','Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.','');
INSERT INTO `object_localized_query_5_de` VALUES ('35','de','Maecenas nec odio et ante tincidunt tempus','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. ','Aenean Vestibulum Etiam Curabitur');
INSERT INTO `object_localized_query_5_de` VALUES ('39','de','Lorem ipsum dolor sit amet','Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst. Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante. In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla. Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio. ','Etiam Curabitur Fusce Quisque');
INSERT INTO `object_localized_query_5_de` VALUES ('40','de','Cum sociis natoque penatibus et magnis','Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. <img pimcore_id=\"21\" pimcore_type=\"asset\" src=\"/website/var/tmp/image-thumbnails/21/thumb__auto_850904660de984af948beee3aee98a4f/img_0037.jpeg\" style=\"width:600px;\" /> Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor. Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ','Fusce Quisque Maecenas Donec');
INSERT INTO `object_localized_query_5_en` VALUES ('35','en','Maecenas nec odio et ante tincidunt tempus','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. ','Aenean Vestibulum Etiam Curabitur');
INSERT INTO `object_localized_query_5_en` VALUES ('39','en','Lorem ipsum dolor sit amet','Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst. Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante. In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla. Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio. ','Etiam Curabitur Fusce Quisque');
INSERT INTO `object_localized_query_5_en` VALUES ('40','en','Cum sociis natoque penatibus et magnis','Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. <img pimcore_id=\"21\" pimcore_type=\"asset\" src=\"/website/var/tmp/image-thumbnails/21/thumb__auto_850904660de984af948beee3aee98a4f/img_0037.jpeg\" style=\"width:600px;\" /> Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor. Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ','Fusce Quisque Maecenas Donec');
INSERT INTO `object_localized_query_6_de` VALUES ('36','de','Curabitur ullamcorper');
INSERT INTO `object_localized_query_6_de` VALUES ('37','de','Nam eget dui');
INSERT INTO `object_localized_query_6_de` VALUES ('38','de','Etiam rhoncus');
INSERT INTO `object_localized_query_6_en` VALUES ('36','en','Curabitur ullamcorper');
INSERT INTO `object_localized_query_6_en` VALUES ('37','en','Nam eget dui');
INSERT INTO `object_localized_query_6_en` VALUES ('38','en','Etiam rhoncus');
INSERT INTO `object_query_2` VALUES ('3','2','news','1374147900','49','43','52');
INSERT INTO `object_query_2` VALUES ('4','2','news','1369761300','51','0','0');
INSERT INTO `object_query_2` VALUES ('5','2','news','1370037600','0','0','0');
INSERT INTO `object_query_2` VALUES ('6','2','news','1354558500','25','0','0');
INSERT INTO `object_query_2` VALUES ('7','2','news','1360606500','18','0','0');
INSERT INTO `object_query_2` VALUES ('8','2','news','1360001700','20','0','0');
INSERT INTO `object_query_2` VALUES ('9','2','news','1352830500','21','0','0');
INSERT INTO `object_query_3` VALUES ('29','3','inquiry','28','object','1368630902','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','1');
INSERT INTO `object_query_3` VALUES ('31','3','inquiry','30','object','1368630916','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','1');
INSERT INTO `object_query_4` VALUES ('28','4','persons','male','John','Doe','john@doe.com','0','0','1368630902');
INSERT INTO `object_query_4` VALUES ('30','4','persons','female','Jane','Doe','jane@doe.com','0','0','1368630916');
INSERT INTO `object_query_4` VALUES ('47','4','persons','male','Demo','User','pimcore@byom.de','1','1','1388412534');
INSERT INTO `object_query_5` VALUES ('35','5','blogArticle','1388649120',',37,38,','0','');
INSERT INTO `object_query_5` VALUES ('39','5','blogArticle','1389167640',',38,','23','a:3:{s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}s:4:\"crop\";a:5:{s:9:\"cropWidth\";d:99.599999999999994;s:10:\"cropHeight\";d:50.133333333333333;s:7:\"cropTop\";d:15.733333333333333;s:8:\"cropLeft\";d:1.8;s:11:\"cropPercent\";b:1;}}');
INSERT INTO `object_query_5` VALUES ('40','5','blogArticle','1388390100',',36,','20','a:3:{s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}s:4:\"crop\";a:5:{s:9:\"cropWidth\";d:98.799999999999997;s:10:\"cropHeight\";d:54.133333333333333;s:7:\"cropTop\";d:27.466666666666665;s:8:\"cropLeft\";i:2;s:11:\"cropPercent\";b:1;}}');
INSERT INTO `object_query_6` VALUES ('36','6','blogCategory');
INSERT INTO `object_query_6` VALUES ('37','6','blogCategory');
INSERT INTO `object_query_6` VALUES ('38','6','blogCategory');
INSERT INTO `object_query_7` VALUES ('49','7','user','john','$2y$10$79ZMRzu5KyQY0i6Tgf3iZuY.bS0S.yld7XRqgX.8oEeMFqy/T.TiK',',ROLE_USER,');
INSERT INTO `object_query_7` VALUES ('50','7','user','jane','$2y$10$BfgffXM.oUIIDdwtCDe.le0uMoku1LBX./4rIrVr.zpgNHWaSqr5q',',ROLE_ADMIN,');
INSERT INTO `object_relations_3` VALUES ('29','28','object','person','0','object','','0');
INSERT INTO `object_relations_3` VALUES ('31','30','object','person','0','object','','0');
INSERT INTO `object_relations_5` VALUES ('35','37','object','categories','1','object','','0');
INSERT INTO `object_relations_5` VALUES ('39','38','object','categories','1','object','','0');
INSERT INTO `object_relations_5` VALUES ('40','36','object','categories','1','object','','0');
INSERT INTO `object_relations_5` VALUES ('35','38','object','categories','2','object','','0');
INSERT INTO `object_store_2` VALUES ('3','1374147900','49','43','52');
INSERT INTO `object_store_2` VALUES ('4','1369761300','51','0','0');
INSERT INTO `object_store_2` VALUES ('5','1370037600','0','0','0');
INSERT INTO `object_store_2` VALUES ('6','1354558500','25','0','0');
INSERT INTO `object_store_2` VALUES ('7','1360606500','18','0','0');
INSERT INTO `object_store_2` VALUES ('8','1360001700','20','0','0');
INSERT INTO `object_store_2` VALUES ('9','1352830500','21','0','0');
INSERT INTO `object_store_3` VALUES ('29','1368630902','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','1');
INSERT INTO `object_store_3` VALUES ('31','1368630916','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.','1');
INSERT INTO `object_store_4` VALUES ('28','male','John','Doe','john@doe.com','0','0','1368630902');
INSERT INTO `object_store_4` VALUES ('30','female','Jane','Doe','jane@doe.com','0','0','1368630916');
INSERT INTO `object_store_4` VALUES ('47','male','Demo','User','pimcore@byom.de','1','1','1388412534');
INSERT INTO `object_store_5` VALUES ('35','1388649120','0','');
INSERT INTO `object_store_5` VALUES ('39','1389167640','23','a:3:{s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}s:4:\"crop\";a:5:{s:9:\"cropWidth\";d:99.599999999999994;s:10:\"cropHeight\";d:50.133333333333333;s:7:\"cropTop\";d:15.733333333333333;s:8:\"cropLeft\";d:1.8;s:11:\"cropPercent\";b:1;}}');
INSERT INTO `object_store_5` VALUES ('40','1388390100','20','a:3:{s:8:\"hotspots\";a:0:{}s:6:\"marker\";a:0:{}s:4:\"crop\";a:5:{s:9:\"cropWidth\";d:98.799999999999997;s:10:\"cropHeight\";d:54.133333333333333;s:7:\"cropTop\";d:27.466666666666665;s:8:\"cropLeft\";i:2;s:11:\"cropPercent\";b:1;}}');
INSERT INTO `object_store_6` VALUES ('36');
INSERT INTO `object_store_6` VALUES ('37');
INSERT INTO `object_store_6` VALUES ('38');
INSERT INTO `object_store_7` VALUES ('49','john','$2y$10$79ZMRzu5KyQY0i6Tgf3iZuY.bS0S.yld7XRqgX.8oEeMFqy/T.TiK','ROLE_USER');
INSERT INTO `object_store_7` VALUES ('50','jane','$2y$10$BfgffXM.oUIIDdwtCDe.le0uMoku1LBX./4rIrVr.zpgNHWaSqr5q','ROLE_ADMIN');
INSERT INTO `objects` VALUES ('1','0','folder','','/','999999','1','1368522989','1368522989','1','1','0','');
INSERT INTO `objects` VALUES ('2','1','folder','news','/','0','1','1368613451','1368613451','0','0','0','');
INSERT INTO `objects` VALUES ('3','2','object','lorem-ipsum','/news/','0','1','1368613483','1382958769','0','0','2','news');
INSERT INTO `objects` VALUES ('4','2','object','in-enim-justo','/news/','0','1','1368613645','1382958711','0','0','2','news');
INSERT INTO `objects` VALUES ('5','2','object','nam-eget-dui','/news/','0','1','1368613700','1382958801','0','0','2','news');
INSERT INTO `objects` VALUES ('6','2','object','in-enim-justo_2','/news/','0','1','1368615188','1382958710','0','0','2','news');
INSERT INTO `objects` VALUES ('7','2','object','in-enim-justo_3','/news/','0','1','1368615191','1382958709','0','0','2','news');
INSERT INTO `objects` VALUES ('8','2','object','in-enim-justo_4','/news/','0','1','1368615194','1382958708','0','0','2','news');
INSERT INTO `objects` VALUES ('9','2','object','in-enim-justo_5','/news/','0','1','1368615197','1382958706','0','0','2','news');
INSERT INTO `objects` VALUES ('10','1','folder','crm','/','0','1','1368620607','1368620607','0','0','0','');
INSERT INTO `objects` VALUES ('11','1','folder','inquiries','/','0','1','1368620624','1368620624','0','0','0','');
INSERT INTO `objects` VALUES ('28','42','object','john-doe.com','/crm/inquiries/','0','1','1368630902','1388409139','0','0','4','person');
INSERT INTO `objects` VALUES ('29','11','object','may-15-2013-5-15-02-pm~john-doe.com','/inquiries/','0','1','1368630902','1368630902','0','0','3','inquiry');
INSERT INTO `objects` VALUES ('30','42','object','jane-doe.com','/crm/inquiries/','0','1','1368630916','1388409137','0','0','4','person');
INSERT INTO `objects` VALUES ('31','11','object','may-15-2013-5-15-16-pm~jane-doe.com','/inquiries/','0','1','1368630916','1368630916','0','0','3','inquiry');
INSERT INTO `objects` VALUES ('32','1','folder','blog','/','0','1','1388389170','1388389170','7','7','0','');
INSERT INTO `objects` VALUES ('33','32','folder','categories','/blog/','0','1','1388389428','1388389428','7','7','0','');
INSERT INTO `objects` VALUES ('34','32','folder','articles','/blog/','0','1','1388389435','1388389435','7','7','0','');
INSERT INTO `objects` VALUES ('35','34','object','maecenas-nec-odio','/blog/articles/','0','1','1388389641','1388393754','7','7','5','blogArticle');
INSERT INTO `objects` VALUES ('36','33','object','curabitur-ullamcorper','/blog/categories/','0','1','1388389865','1388389870','7','7','6','blogCategory');
INSERT INTO `objects` VALUES ('37','33','object','nam-eget-dui','/blog/categories/','0','1','1388389881','1388393730','7','7','6','blogCategory');
INSERT INTO `objects` VALUES ('38','33','object','etiam-rhoncus','/blog/categories/','0','1','1388389892','1388389900','7','7','6','blogCategory');
INSERT INTO `objects` VALUES ('39','34','object','lorem-ipsum-dolor-sit-amet','/blog/articles/','0','1','1388390090','1388393711','7','7','5','blogArticle');
INSERT INTO `objects` VALUES ('40','34','object','cum-sociis-natoque-penatibus-et-magnis','/blog/articles/','0','1','1388390120','1388393706','7','7','5','blogArticle');
INSERT INTO `objects` VALUES ('41','10','folder','newsletter','/crm/','0','1','1388408967','1388408967','0','0','0','');
INSERT INTO `objects` VALUES ('42','10','folder','inquiries','/crm/','0','1','1388409135','1388409135','0','0','0','');
INSERT INTO `objects` VALUES ('47','41','object','pimcore-byom.de~7a3','/crm/newsletter/','0','1','1388412533','1388412544','0','0','4','person');
INSERT INTO `objects` VALUES ('48','1','folder','users','/',NULL,'1','1491821233','1491821233','3','3',NULL,NULL);
INSERT INTO `objects` VALUES ('49','48','object','john','/users/','0','1','1491821239','1491821246','3','3','7','user');
INSERT INTO `objects` VALUES ('50','48','object','jane','/users/','0','1','1491821254','1491821260','3','3','7','user');
INSERT INTO `properties` VALUES ('1','document','/','blog','document','60','1');
INSERT INTO `properties` VALUES ('1','document','/','language','text','en','1');
INSERT INTO `properties` VALUES ('1','document','/','leftNavStartNode','document','40','1');
INSERT INTO `properties` VALUES ('1','document','/','mainNavStartNode','document','40','1');
INSERT INTO `properties` VALUES ('1','document','/','navigation_name','text','Home','0');
INSERT INTO `properties` VALUES ('1','document','/','sidebar','document','57','1');
INSERT INTO `properties` VALUES ('3','document','/en/basic-examples','leftNavStartNode','document','3','1');
INSERT INTO `properties` VALUES ('3','document','/en/basic-examples','navigation_name','text','Basic Examples','0');
INSERT INTO `properties` VALUES ('4','document','/en/introduction','navigation_name','text','Introduction','0');
INSERT INTO `properties` VALUES ('4','document','/en/introduction','sidebar','document','59','1');
INSERT INTO `properties` VALUES ('5','document','/en/advanced-examples','leftNavStartNode','document','5','1');
INSERT INTO `properties` VALUES ('5','document','/en/advanced-examples','navigation_name','text','Advanced Examples','0');
INSERT INTO `properties` VALUES ('5','document','/en/advanced-examples','sidebar','document','69','1');
INSERT INTO `properties` VALUES ('6','document','/en/experiments','navigation_name','text','Experiments','0');
INSERT INTO `properties` VALUES ('7','document','/en/basic-examples/html5-video','navigation_name','text','HTML5 Video','0');
INSERT INTO `properties` VALUES ('9','document','/en/advanced-examples/creating-objects-using-forms','navigation_name','text','Creating Objects with a Form','0');
INSERT INTO `properties` VALUES ('18','document','/en/basic-examples/pdf-viewer','navigation_name','text','Document Viewer','0');
INSERT INTO `properties` VALUES ('19','document','/en/basic-examples/galleries','navigation_name','text','Galleries','0');
INSERT INTO `properties` VALUES ('20','document','/en/basic-examples/glossary','navigation_name','text','Glossary','0');
INSERT INTO `properties` VALUES ('21','document','/en/basic-examples/thumbnails','navigation_name','text','Thumbnails','0');
INSERT INTO `properties` VALUES ('22','document','/en/basic-examples/website-translations','navigation_name','text','Website Translations','0');
INSERT INTO `properties` VALUES ('23','document','/de/einfache-beispiele/website-uebersetzungen','language','text','de','1');
INSERT INTO `properties` VALUES ('23','document','/de/einfache-beispiele/website-uebersetzungen','navigation_name','text','Website Übersetzungen','0');
INSERT INTO `properties` VALUES ('24','document','/en/basic-examples/content-page','navigation_name','text','Content Page','0');
INSERT INTO `properties` VALUES ('25','document','/en/basic-examples/editable-roundup','navigation_name','text','Editable Round-Up','0');
INSERT INTO `properties` VALUES ('26','document','/en/basic-examples/form','navigation_name','text','Simple Form','0');
INSERT INTO `properties` VALUES ('27','document','/en/basic-examples/news','navigation_name','text','News','0');
INSERT INTO `properties` VALUES ('28','document','/en/basic-examples/properties','headerColor','select','blue','1');
INSERT INTO `properties` VALUES ('28','document','/en/basic-examples/properties','leftNavHide','bool','1','0');
INSERT INTO `properties` VALUES ('28','document','/en/basic-examples/properties','navigation_name','text','Properties','0');
INSERT INTO `properties` VALUES ('29','document','/en/basic-examples/tag-and-snippet-management','navigation_name','text','Tag & Snippet Management','0');
INSERT INTO `properties` VALUES ('30','document','/en/advanced-examples/content-inheritance','navigation_name','text','Content Inheritance','0');
INSERT INTO `properties` VALUES ('31','document','/en/advanced-examples/content-inheritance/content-inheritance','navigation_name','text','Slave Document','0');
INSERT INTO `properties` VALUES ('32','document','/en/basic-examples/pimcore.org','navigation_name','text','External Link','0');
INSERT INTO `properties` VALUES ('32','document','/en/basic-examples/pimcore.org','navigation_target','text','_blank','0');
INSERT INTO `properties` VALUES ('33','document','/en/advanced-examples/hard-link/basic-examples','leftNavStartNode','document','5','1');
INSERT INTO `properties` VALUES ('34','document','/en/advanced-examples/hard-link','navigation_name','text','Hard Link','0');
INSERT INTO `properties` VALUES ('35','document','/en/advanced-examples/image-with-hotspots-and-markers','navigation_name','text','Image with Hotspots','0');
INSERT INTO `properties` VALUES ('36','document','/en/advanced-examples/search','navigation_name','text','Search','0');
INSERT INTO `properties` VALUES ('36','asset','/documents/pimcore_t-mobile.pdf','document_page_count','text','39','0');
INSERT INTO `properties` VALUES ('37','document','/en/advanced-examples/contact-form','email','document','38','1');
INSERT INTO `properties` VALUES ('37','document','/en/advanced-examples/contact-form','navigation_name','text','Contact Form','0');
INSERT INTO `properties` VALUES ('40','document','/en','navigation_name','text','Home','0');
INSERT INTO `properties` VALUES ('41','document','/de','language','text','de','1');
INSERT INTO `properties` VALUES ('41','document','/de','leftNavStartNode','document','41','1');
INSERT INTO `properties` VALUES ('41','document','/de','mainNavStartNode','document','41','1');
INSERT INTO `properties` VALUES ('41','document','/de','navigation_name','text','Startseite','0');
INSERT INTO `properties` VALUES ('41','document','/de','sidebar','document','58','1');
INSERT INTO `properties` VALUES ('47','object','/crm/newsletter/pimcore-byom.de~7a3','token','text','YTozOntzOjQ6InNhbHQiO3M6MzI6IjNlMGRkYTk3MWU1YTY5MWViYmM0OGVkNGQ5NzA4MDFmIjtzOjU6ImVtYWlsIjtzOjE1OiJwaW1jb3JlQGJ5b20uZGUiO3M6MjoiaWQiO2k6NDc7fQ==','0');
INSERT INTO `properties` VALUES ('50','document','/de/einfuehrung','navigation_name','text','Einführung','0');
INSERT INTO `properties` VALUES ('51','document','/de/einfache-beispiele','navigation_name','text','Einfache Beispiele','0');
INSERT INTO `properties` VALUES ('52','document','/de/beispiele-fur-fortgeschrittene','navigation_name','text','Beispiele für Fortgeschrittene','0');
INSERT INTO `properties` VALUES ('53','document','/de/einfache-beispiele/neuigkeiten','navigation_name','text','Neuigkeiten','0');
INSERT INTO `properties` VALUES ('60','document','/en/advanced-examples/blog','navigation_name','text','Blog','0');
INSERT INTO `properties` VALUES ('61','document','/en/advanced-examples/sitemap','navigation_name','text','Sitemap','0');
INSERT INTO `properties` VALUES ('63','document','/en/advanced-examples/newsletter','navigation_name','text','Newsletter','0');
INSERT INTO `properties` VALUES ('64','document','/en/advanced-examples/newsletter/confirm','navigation_name','text','','0');
INSERT INTO `properties` VALUES ('65','document','/en/advanced-examples/newsletter/unsubscribe','navigation_name','text','Unsubscribe','0');
INSERT INTO `properties` VALUES ('68','document','/en/advanced-examples/asset-thumbnail-list','navigation_name','text','Asset Thumbnail List','0');
INSERT INTO `properties` VALUES ('70','document','/en/advanced-examples/product-information-management','navigation_name','text','Product Information Management','0');
INSERT INTO `properties` VALUES ('71','document','/en/advanced-examples/e-commerce','navigation_name','text','E-Commerce','0');
INSERT INTO `properties` VALUES ('72','document','/en/advanced-examples/sub-modules','navigation_exclude','text','','0');
INSERT INTO `properties` VALUES ('72','document','/en/advanced-examples/sub-modules','navigation_name','text','Sub-Modules','0');
INSERT INTO `properties` VALUES ('72','document','/en/advanced-examples/sub-modules','navigation_target','text','','0');
INSERT INTO `properties` VALUES ('72','document','/en/advanced-examples/sub-modules','navigation_title','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','language','text','en','1');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_accesskey','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_anchor','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_class','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_exclude','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_name','text','Social Contents','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_parameters','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_relation','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_tabindex','text','','0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_target','text',NULL,'0');
INSERT INTO `properties` VALUES ('73','document','/en/basic-examples/social-contents','navigation_title','text','','0');
INSERT INTO `search_backend_data` VALUES ('1','/','asset','folder','folder','1','1368522989','1368522989','1','1','ID: 1  \nPath: /  \n','');
INSERT INTO `search_backend_data` VALUES ('1','/','document','page','page','1','1368522989','1395151306','1','0','ID: 1  \nPath: /  \nAlbert Einstein Isla Colón, Bocas del Toro, Republic of Panama Bocas del Toro 3 Ready to be impressed? It\'ll blow your mind. Oh yeah, it\'s that good See it in Action See it in Action Checkmate In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. Check out our examples and dive into the next generation of digital data management. See for yourself. See for yourself pimcore is the only open-source multi-channel experience and engagement management platform available. With complete creative freedom, flexibility, and agility, pimcore is a dream come true for designers and developers. pimcore makes it easier to manage large international sites with key features like advanced content reuse, inheritance, and distribution. pimcore is based on UTF-8 standards and is compatible to any language including Right-to-Left (RTL). Good looking and completely custom galleries Lorem ipsum. Oh yeah, it\'s that good. And lastly, this one. About Us 100% Flexible 100% Editable International and Multi-site phone bullhorn screenshot left Are integrated within minutes Read More Read More Read More What is pimcore? and enjoy creative freedom 中國人嗎？沒問題。 About us Think different International and Multi-site left We can\'t solve problems by using the same kind of thinking we used when we created them. 1 3 3 Cum sociis. See for yourself. Checkmate. This demo is based on the Bootstrap framework which is the most popular, intuitive and powerful front-end framework available. HTML5, Javascript, CSS3, jQuery as well as concepts like responsive, mobile-apps or non-linear design-patterns. Content is created by simply dragging &amp; dropping blocks, that can be editited in-place and wysiwyg in a very intuitive and comfortable way. Fully Responsive 100% Buzzword Compatible Drag & Drop Interface video ','navigation_name:Home sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('1','/','object','folder','folder','1','1368522989','1368522989','1','1','ID: 1  \nPath: /  \n','');
INSERT INTO `search_backend_data` VALUES ('2','/news','object','folder','folder','1','1368613451','1368613451','0','0','ID: 2  \nPath: /news  \nnews','');
INSERT INTO `search_backend_data` VALUES ('3','/portal-sujets','asset','folder','folder','1','1368530371','1368632469','0','0','ID: 3  \nPath: /portal-sujets  \nportal-sujets','');
INSERT INTO `search_backend_data` VALUES ('3','/en/basic-examples','document','page','page','1','1368523212','1388738504','0','0','ID: 3  \nPath: /en/basic-examples  \n 1 1 1 1 1 1 Basic Examples HTML5 Video Glossary Simple Content News PDF Viewer Thumbnails Round-Up Properties Galleries Website Translations Simple Form Tag Manager See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action See it in Action Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. direct direct direct direct direct direct direct direct direct direct direct direct ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Basic Examples ');
INSERT INTO `search_backend_data` VALUES ('3','/news/lorem-ipsum','object','object','news','1','1368613483','1382958769','0','0','ID: 3  \nPath: /news/lorem-ipsum  \nEr hörte leise Schritte hinter sich Das bedeutete nichts Gutes. Wer würde ihm schon folgen, spät in der Nacht und dazu noch in dieser engen Gasse mitten im übel beleumundeten Hafenviertel? Oder geh&ouml;rten die Schritte hinter ihm zu einem der unz&auml;hligen Gesetzesh&uuml;ter dieser Stadt, und die st&auml;hlerne Acht um seine Handgelenke w&uuml;rde gleich zuschnappen? Er konnte die Aufforderung stehen zu bleiben schon h&ouml;ren. Gehetzt sah er sich um. Pl&ouml;tzlich erblickte er den schmalen Durchgang. Blitzartig drehte er sich nach rechts und verschwand zwischen den beiden Geb&auml;uden. Beinahe w&auml;re er dabei &uuml;ber den umgest&uuml;rzten M&uuml;lleimer gefallen, der mitten im Weg lag. Er versuchte, sich in der Dunkelheit seinen Weg zu ertasten und erstarrte: Anscheinend gab es keinen anderen Ausweg aus diesem kleinen Hof als den Durchgang, durch den er gekommen war. Die Schritte wurden lauter und lauter, er sah eine dunkle Gestalt um die Ecke biegen. Fieberhaft irrten seine Augen durch die n&auml;chtliche Dunkelheit und suchten einen Ausweg. War jetzt wirklich alles vorbei. Lorem ipsum dolor sit amet Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam&nbsp;ultricies&nbsp;nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget&nbsp;condimentum&nbsp;rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Jul 18, 2013 1:45:00 PM /examples/south-africa/img_2155.jpg /examples/south-africa/img_1414.jpg /examples/south-africa/img_1920.jpg ','');
INSERT INTO `search_backend_data` VALUES ('4','/portal-sujets/slide-01.jpg','asset','image','image','1','1368530684','1370432846','0','0','ID: 4  \nPath: /portal-sujets/slide-01.jpg  \nslide-01.jpg','');
INSERT INTO `search_backend_data` VALUES ('4','/en/introduction','document','page','page','1','1368523285','1395042868','0','0','ID: 4  \nPath: /en/introduction  \n Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. &nbsp; Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. &nbsp; Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. &nbsp; It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es. Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. &nbsp; Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Overview of the project and how to get started with a simple template. Introduction Maecenas tempus, tellus eget condimentum rhoncu Ullamcorper Scelerisque Getting started Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Etiam rhoncu Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. snippet snippet ','sidebar:/en/introduction/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en language:en navigation_name:Introduction ');
INSERT INTO `search_backend_data` VALUES ('4','/news/in-enim-justo','object','object','news','1','1368613645','1382958711','0','0','ID: 4  \nPath: /news/in-enim-justo  \nLi Europan lingues es membres Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. In enim justo Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Nam eget dui. Etiam rhoncus.&nbsp;Maecenas&nbsp;tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed&nbsp;fringilla&nbsp;mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, May 28, 2013 7:15:00 PM /examples/south-africa/img_1842.jpg ','');
INSERT INTO `search_backend_data` VALUES ('5','/portal-sujets/slide-02.jpg','asset','image','image','1','1368530764','1370432868','0','0','ID: 5  \nPath: /portal-sujets/slide-02.jpg  \nslide-02.jpg','');
INSERT INTO `search_backend_data` VALUES ('5','/en/advanced-examples','document','page','page','1','1368523389','1388738496','0','0','ID: 5  \nPath: /en/advanced-examples  \n The following list is generated automatically. See controller/action to see how it\'s done.&nbsp; Advanced Examples ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Advanced Examples ');
INSERT INTO `search_backend_data` VALUES ('5','/news/nam-eget-dui','object','object','news','1','1368613700','1382958801','0','0','ID: 5  \nPath: /news/nam-eget-dui  \nZwei flinke Boxer jagen die quirlige Eva Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zwölf Boxkämpfer jagen Viktor quer über den großen Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung. Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux bei Pforzheim. Polyfon zwitschernd a&szlig;en M&auml;xchens V&ouml;gel R&uuml;ben, Joghurt und Quark. &quot;Fix, Schwyz!&quot; qu&auml;kt J&uuml;rgen bl&ouml;d vom Pa&szlig;. Victor jagt zw&ouml;lf Boxk&auml;mpfer quer &uuml;ber den gro&szlig;en Sylter Deich. Falsches &Uuml;ben von Xylophonmusik qu&auml;lt jeden gr&ouml;&szlig;eren Zwerg. Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung.Zwei flinke Boxer jagen die quirlige Eva und ihren Mops durch Sylt. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern. Zw&ouml;lf Boxk&auml;mpfer jagen Viktor quer &uuml;ber den gro&szlig;en Sylter Deich. Vogel Quax zwickt Johnys Pferd Bim. Sylvia wagt quick den Jux Nam eget dui Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, Jun 1, 2013 12:00:00 AM ','');
INSERT INTO `search_backend_data` VALUES ('6','/portal-sujets/slide-03.jpg','asset','image','image','1','1368530764','1370432860','0','0','ID: 6  \nPath: /portal-sujets/slide-03.jpg  \nslide-03.jpg','');
INSERT INTO `search_backend_data` VALUES ('6','/en/experiments','document','page','page','1','1368523410','1395043974','0','0','ID: 6  \nPath: /en/experiments  \n Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. &nbsp; Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. &nbsp; Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, Experiments This space is reserved for your individual experiments & tests. ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en blog:/en/advanced-examples/blog language:en navigation_name:Experiments ');
INSERT INTO `search_backend_data` VALUES ('6','/news/in-enim-justo_2','object','object','news','1','1368615188','1382958710','0','0','ID: 6  \nPath: /news/in-enim-justo_2  \nLi Europan lingues es membres Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. In enim justo Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Dec 3, 2012 7:15:00 PM /examples/panama/img_0160.jpg ','');
INSERT INTO `search_backend_data` VALUES ('7','/examples','asset','folder','folder','1','1368531816','1368632468','0','0','ID: 7  \nPath: /examples  \nexamples','');
INSERT INTO `search_backend_data` VALUES ('7','/en/basic-examples/html5-video','document','page','page','1','1368525394','1395042970','0','0','ID: 7  \nPath: /en/basic-examples/html5-video  \n HTML5 Video is just as simple as that .... Just drop an video from your assets, the video will be automatically converted to the different HTML5 formats and to the correct size.&nbsp; Just drop an video from your assets, the video will be automatically converted to the different HTML5 formats and to the correct size. ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:HTML5 Video ');
INSERT INTO `search_backend_data` VALUES ('7','/news/in-enim-justo_3','object','object','news','1','1368615191','1382958709','0','0','ID: 7  \nPath: /news/in-enim-justo_3  \nLi Europan lingues es membres Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. In enim justo Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Feb 11, 2013 7:15:00 PM /examples/panama/img_0117.jpg ','');
INSERT INTO `search_backend_data` VALUES ('8','/news/in-enim-justo_4','object','object','news','1','1368615194','1382958708','0','0','ID: 8  \nPath: /news/in-enim-justo_4  \nLi Europan lingues es membres Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. In enim justo Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Feb 4, 2013 7:15:00 PM /examples/panama/img_0089.jpg ','');
INSERT INTO `search_backend_data` VALUES ('9','/en/advanced-examples/creating-objects-using-forms','document','page','page','1','1368525933','1382956042','0','0','ID: 9  \nPath: /en/advanced-examples/creating-objects-using-forms  \n &nbsp; In this example we dynamically create objects out of the data submitted via the form. The you can use the same approach to create objects using a commandline script, or wherever you need it. After submitting the form you\'ll find the data in \"Objects\" /crm and /inquiries.&nbsp; &nbsp; &nbsp; And here\'s the form:&nbsp; Please fill all fields and accept the terms of use. Creating Objects & Assets with a Form ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Creating Objects with a Form ');
INSERT INTO `search_backend_data` VALUES ('9','/news/in-enim-justo_5','object','object','news','1','1368615197','1382958706','0','0','ID: 9  \nPath: /news/in-enim-justo_5  \nLi Europan lingues es membres Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. In enim justo Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Nov 13, 2012 7:15:00 PM /examples/panama/img_0037.jpg ','');
INSERT INTO `search_backend_data` VALUES ('10','/en/shared','document','folder','folder','1','1368527956','1382956831','0','0','ID: 10  \nPath: /en/shared  \nshared','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('10','/crm','object','folder','folder','1','1368620607','1368620607','0','0','ID: 10  \nPath: /crm  \ncrm','');
INSERT INTO `search_backend_data` VALUES ('11','/en/shared/includes','document','folder','folder','1','1368527961','1382956831','0','0','ID: 11  \nPath: /en/shared/includes  \nincludes','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('11','/inquiries','object','folder','folder','1','1368620624','1368620624','0','0','ID: 11  \nPath: /inquiries  \ninquiries','');
INSERT INTO `search_backend_data` VALUES ('12','/en/shared/includes/footer','document','snippet','snippet','1','1368527967','1382956852','0','0','ID: 12  \nPath: /en/shared/includes/footer  \npimcore.org Documentation Bug Tracker Designed and built with all the love in the world by&nbsp;@mdo&nbsp;and&nbsp;@fat. Code licensed under&nbsp;Apache License v2.0,&nbsp;Glyphicons Free&nbsp;licensed under&nbsp;CC BY 3.0. © Templates pimcore.org licensed under BSD License ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('13','/en/shared/teasers','document','folder','folder','1','1368531657','1382956831','0','0','ID: 13  \nPath: /en/shared/teasers  \nteasers','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('14','/en/shared/teasers/standard','document','folder','folder','1','1368531665','1382956831','0','0','ID: 14  \nPath: /en/shared/teasers/standard  \nstandard','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('15','/en/shared/teasers/standard/basic-examples','document','snippet','snippet','1','1368531692','1382956831','0','0','ID: 15  \nPath: /en/shared/teasers/standard/basic-examples  \n Fully Responsive Lorem ipsum This demo is based on Bootstrap, the most popular, intuitive, and powerful front-end framework. ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('16','/en/shared/teasers/standard/advanced-examples','document','snippet','snippet','1','1368534298','1382956831','0','0','ID: 16  \nPath: /en/shared/teasers/standard/advanced-examples  \n Drag & Drop Interface Etiam rhoncu Content is created by simply dragging &amp; dropping blocks, that can&nbsp;be editited in-place and wysiwyg.&nbsp; ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('17','/examples/panama','asset','folder','folder','1','1368532826','1368632468','0','0','ID: 17  \nPath: /examples/panama  \npanama','');
INSERT INTO `search_backend_data` VALUES ('17','/en/shared/teasers/standard/experiments','document','snippet','snippet','1','1368534344','1382956831','0','0','ID: 17  \nPath: /en/shared/teasers/standard/experiments  \n HTML5 omnipresent Quisque rutrum Drag &amp; drop upload directly&nbsp;into the asset tree, automatic html5 video transcoding, and much more ... ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('18','/examples/panama/img_0117.jpg','asset','image','image','1','1368532831','1368632468','0','0','ID: 18  \nPath: /examples/panama/img_0117.jpg  \nimg_0117.jpg','');
INSERT INTO `search_backend_data` VALUES ('18','/en/basic-examples/pdf-viewer','document','page','page','1','1368548449','1395042961','0','0','ID: 18  \nPath: /en/basic-examples/pdf-viewer  \n Isn\'t that amazing? Just drop a PDF, doc(x), xls(x) or many other formats, et voilá ...&nbsp; Just drop a PDF, doc(x), xls(x) or many other formats, et voilá ... + &#x21e9; x var pimcore_pdf_pdfcontent1 = new pimcore.pdf({ id: \"pimcore-pdf-5436334873272\", data: {\"pages\":[{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-1\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-1\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-2\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-2\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-3\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-3\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-4\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-4\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-5\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-5\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-6\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-6\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-7\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-7\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-8\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-8\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-9\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-9\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-10\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-10\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-11\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-11\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-12\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-12\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-13\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-13\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-14\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-14\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-15\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-15\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-16\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-16\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-17\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-17\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-18\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-18\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-19\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-19\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-20\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-20\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-21\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-21\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-22\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-22\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-23\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-23\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-24\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-24\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-25\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-25\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-26\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-26\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-27\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-27\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-28\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-28\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-29\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-29\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-30\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-30\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-31\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-31\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-32\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-32\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-33\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-33\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-34\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-34\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-35\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-35\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-36\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-36\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-37\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-37\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-38\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-38\\/documentation.pjpeg\"},{\"thumbnail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_ca35914f842e48731761eda9e1b55fa1-39\\/documentation.pjpeg\",\"detail\":\"\\/website\\/var\\/tmp\\/image-thumbnails\\/0\\/36\\/thumb__document_auto_55c4d1de803e2f89c46b9a22287c3b50-39\\/documentation.pjpeg\"}],\"pdf\":\"\\/documents\\/pimcore_t-mobile.pdf\",\"fullscreen\":true} }); ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Document Viewer ');
INSERT INTO `search_backend_data` VALUES ('19','/examples/panama/img_0201.jpg','asset','image','image','1','1368532832','1368632468','0','0','ID: 19  \nPath: /examples/panama/img_0201.jpg  \nimg_0201.jpg','');
INSERT INTO `search_backend_data` VALUES ('19','/en/basic-examples/galleries','document','page','page','1','1368549805','1395043436','0','0','ID: 19  \nPath: /en/basic-examples/galleries  \nWhite beaches and the indian ocean National Nature Reserve Plettenberg Bay The Robberg Creating custom galleries is very simple Autogenerated Gallery (using Renderlet) Custom assembled Gallery Drag an asset folder on the following drop area, and the \"renderlet\" will create automatically a gallery out of the images in the folder. Drag an asset folder on the following drop area, and the \"renderlet\" will create automatically a gallery out of the images in the folder. 1 5 4 ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Galleries ');
INSERT INTO `search_backend_data` VALUES ('20','/examples/panama/img_0089.jpg','asset','image','image','1','1368532833','1368632468','0','0','ID: 20  \nPath: /examples/panama/img_0089.jpg  \nimg_0089.jpg','');
INSERT INTO `search_backend_data` VALUES ('20','/en/basic-examples/glossary','document','page','page','1','1368559903','1395043487','0','0','ID: 20  \nPath: /en/basic-examples/glossary  \n Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. &nbsp; Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es. &nbsp; Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. The Glossary ... ... makes it very simple to automatically link keywords, abbreviation and acronyms. This is not only perfect for SEO but also makes it super easy to navigate in the content.&nbsp; &nbsp; ... this is how it looks in the admin interface. ... makes it very simple to automatically link keywords, abbreviation and acronyms. This is not only perfect for SEO but also makes it super easy to navigate in the content. ... this is how it looks in the admin interface. ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Glossary ');
INSERT INTO `search_backend_data` VALUES ('21','/examples/panama/img_0037.jpg','asset','image','image','1','1368532834','1368632468','0','0','ID: 21  \nPath: /examples/panama/img_0037.jpg  \nimg_0037.jpg','');
INSERT INTO `search_backend_data` VALUES ('21','/en/basic-examples/thumbnails','document','page','page','1','1368602443','1395043532','0','0','ID: 21  \nPath: /en/basic-examples/thumbnails  \n Incredible Possibilities This is the original image This is how it looks in the admin interface ... ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Thumbnails ');
INSERT INTO `search_backend_data` VALUES ('22','/examples/panama/img_0399.jpg','asset','image','image','1','1368532836','1368632468','0','0','ID: 22  \nPath: /examples/panama/img_0399.jpg  \nimg_0399.jpg','');
INSERT INTO `search_backend_data` VALUES ('22','/en/basic-examples/website-translations','document','page','page','1','1368607207','1395043561','0','0','ID: 22  \nPath: /en/basic-examples/website-translations  \n &nbsp; Please visit this page to see the German translation of this page. &nbsp; Following some examples:&nbsp; &nbsp; Website Translations Common used terms across the website can be translated centrally, hassle-free and comfortable.&nbsp; Common used terms across the website can be translated centrally, hassle-free and comfortable. &nbsp; &nbsp; This is how it looks in the admin interface ...&nbsp; This is how it looks in the admin interface ... ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Website Translations ');
INSERT INTO `search_backend_data` VALUES ('23','/examples/panama/img_0411.jpg','asset','image','image','1','1368532837','1368632468','0','0','ID: 23  \nPath: /examples/panama/img_0411.jpg  \nimg_0411.jpg','');
INSERT INTO `search_backend_data` VALUES ('23','/de/einfache-beispiele/website-uebersetzungen','document','page','page','1','1368608357','1382958135','0','0','ID: 23  \nPath: /de/einfache-beispiele/website-uebersetzungen  \n Folgend ein paar Beispiele:&nbsp; Website Übersetzungen Häufig genutzte Begriffe auf der gesamten Website können komfortabel, zentral und einfach übersetzt werden. ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de navigation_name:Website Übersetzungen ');
INSERT INTO `search_backend_data` VALUES ('24','/examples/panama/img_0410.jpg','asset','image','image','1','1368532838','1368632468','0','0','ID: 24  \nPath: /examples/panama/img_0410.jpg  \nimg_0410.jpg','');
INSERT INTO `search_backend_data` VALUES ('24','/en/basic-examples/content-page','document','page','page','1','1368609059','1405923178','0','0','ID: 24  \nPath: /en/basic-examples/content-page  \n Albert Einstein Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: &nbsp; On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. &nbsp; A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. &nbsp; Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. &nbsp; Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. This is just a simple Content-Page ... Where some Content-Blocks are mixed together. Lorem ipsum dolor sit amet Cum sociis natoque penatibus et magnis dis parturient montes Donec pede justo, fringilla vel Maecenas tempus, tellus eget condimentum rhoncus Lorem ipsum. Etiam ultricies. thumbs-up qrcode trash African Animals Donec pede justo, fringilla vel, aliquet nec See in Action Read More Try it now left We can\'t solve problems by using the same kind of thinking we used when we created them. Dolor sit amet. Nam eget dui. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. &nbsp; Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. &nbsp; Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: &nbsp; On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental. &nbsp; A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. &nbsp; Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. At solmen va esser necessi far uniform grammatica. Curabitur ullamcorper ultricies nisi. Nam eget dui. On refusa continuar payar custosi traductores. Social Media Integration QR-Code Management Recycle Bin video ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Content Page ');
INSERT INTO `search_backend_data` VALUES ('25','/examples/panama/img_0160.jpg','asset','image','image','1','1368532839','1368632468','0','0','ID: 25  \nPath: /examples/panama/img_0160.jpg  \nimg_0160.jpg','');
INSERT INTO `search_backend_data` VALUES ('25','/en/basic-examples/editable-roundup','document','page','page','1','1368609569','1395043587','0','0','ID: 25  \nPath: /en/basic-examples/editable-roundup  \n This is an overview of all available \"editables\" (except area/areablock/block) Please view this page in the editmode (admin interface)! ... nothing to see here ;-)&nbsp; ... nothing to see here ;-) 1 May 16, 2013 2:00:00 AM /en/basic-examples/thumbnails Some Text My Link document: /en/basic-examples/glossarydocument: /en/basic-examples/thumbnailsdocument: /en/basic-examples/editable-roundupasset: /examples/south-africa/img_1842.jpgasset: /examples/south-africa/img_2133.jpgasset: /examples/south-africa/img_2240.jpg value2,value4 123 option2 Some Text Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. &nbsp; Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. &nbsp; Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, Value 1Value 2Value 3thisistest ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Editable Round-Up ');
INSERT INTO `search_backend_data` VALUES ('26','/videos','asset','folder','folder','1','1368542684','1368632471','0','0','ID: 26  \nPath: /videos  \nvideos','');
INSERT INTO `search_backend_data` VALUES ('26','/en/basic-examples/form','document','page','page','1','1368610663','1388733533','0','0','ID: 26  \nPath: /en/basic-examples/form  \n Just a simple form ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Simple Form ');
INSERT INTO `search_backend_data` VALUES ('27','/videos/home-trailer-english.mp4','asset','video','video','1','1368542794','1405922844','0','0','ID: 27  \nPath: /videos/home-trailer-english.mp4  \nhome-trailer-english.mp4','');
INSERT INTO `search_backend_data` VALUES ('27','/en/basic-examples/news','document','page','page','1','1368613137','1395043614','0','0','ID: 27  \nPath: /en/basic-examples/news  \n News Any kind of structured data is stored in \"Objects\".&nbsp; Any kind of structured data is stored in \"Objects\". ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:News ');
INSERT INTO `search_backend_data` VALUES ('28','/en/basic-examples/properties','document','page','page','1','1368615986','1382956040','0','0','ID: 28  \nPath: /en/basic-examples/properties  \n On this page we use \"Properties\" to hide the navigation on the left and to change the color of the header to blue.&nbsp; Properties are very useful to control the behavior or to store meta data of documents, assets and objects. And the best: they are inheritable.&nbsp; &nbsp; On the following screens you can see how this is done in this example. Properties ','blog:/en/advanced-examples/blog mainNavStartNode:/en sidebar:/en/sidebar language:en leftNavStartNode:/en/basic-examples navigation_name:Properties leftNavHide:1 headerColor:blue ');
INSERT INTO `search_backend_data` VALUES ('28','/crm/inquiries/john-doe.com','object','object','person','1','1368630902','1388409139','0','0','ID: 28  \nPath: /crm/inquiries/john-doe.com  \nmale John Doe john@doe.com May 15, 2013 5:15:02 PM ','');
INSERT INTO `search_backend_data` VALUES ('29','/documents','asset','folder','folder','1','1368548619','1368632467','0','0','ID: 29  \nPath: /documents  \ndocuments','');
INSERT INTO `search_backend_data` VALUES ('29','/en/basic-examples/tag-and-snippet-management','document','page','page','1','1368617118','1395043636','0','0','ID: 29  \nPath: /en/basic-examples/tag-and-snippet-management  \n This page demonstrates how to use the \"Tag &amp; Snippet Management\" to inject codes into the HTML source code. This functionality can be used to easily integrate tracking codes, conversion codes, social plugins and whatever that needs to go into the HTML. &nbsp; The functionality is similar to this products:&nbsp; http://www.google.com/tagmanager/&nbsp; http://www.searchdiscovery.com/satellite/&nbsp; http://www.tagcommander.com/en/ &nbsp; In our example we use it to integrate a facebook social plugin. Tag & Snippet Management ... gives all the freedom back to the marketing dept. ','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/basic-examples language:en navigation_name:Tag & Snippet Management ');
INSERT INTO `search_backend_data` VALUES ('29','/inquiries/may-15-2013-5-15-02-pm~john-doe.com','object','object','inquiry','1','1368630902','1368630902','0','0','ID: 29  \nPath: /inquiries/may-15-2013-5-15-02-pm~john-doe.com  \nMay 15, 2013 5:15:02 PM object:/crm/inquiries/john-doe.com Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. 1 ','');
INSERT INTO `search_backend_data` VALUES ('30','/en/advanced-examples/content-inheritance','document','page','page','1','1368623726','1395043816','0','0','ID: 30  \nPath: /en/advanced-examples/content-inheritance  \n Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet.&nbsp; Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, Content Inheritance First Headline Second Headline This is the Master Document This is the Master Document ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Content Inheritance ');
INSERT INTO `search_backend_data` VALUES ('30','/crm/inquiries/jane-doe.com','object','object','person','1','1368630916','1388409137','0','0','ID: 30  \nPath: /crm/inquiries/jane-doe.com  \nfemale Jane Doe jane@doe.com May 15, 2013 5:15:16 PM ','');
INSERT INTO `search_backend_data` VALUES ('31','/en/advanced-examples/content-inheritance/content-inheritance','document','page','page','1','1368623866','1395043901','0','0','ID: 31  \nPath: /en/advanced-examples/content-inheritance/content-inheritance  \n This is the Slave Document This is the Slave Document ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Slave Document ');
INSERT INTO `search_backend_data` VALUES ('31','/inquiries/may-15-2013-5-15-16-pm~jane-doe.com','object','object','inquiry','1','1368630916','1368630916','0','0','ID: 31  \nPath: /inquiries/may-15-2013-5-15-16-pm~jane-doe.com  \nMay 15, 2013 5:15:16 PM object:/crm/inquiries/jane-doe.com Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. 1 ','');
INSERT INTO `search_backend_data` VALUES ('32','/en/basic-examples/pimcore.org','document','link','link','1','1368626404','1382956040','0','0','ID: 32  \nPath: /en/basic-examples/pimcore.org  \n http://www.pimcore.org/','sidebar:/en/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en language:en leftNavStartNode:/en/basic-examples navigation_target:_blank navigation_name:External Link ');
INSERT INTO `search_backend_data` VALUES ('32','/blog','object','folder','folder','1','1388389170','1388389170','7','7','ID: 32  \nPath: /blog  \nblog','');
INSERT INTO `search_backend_data` VALUES ('33','/en/advanced-examples/hard-link/basic-examples','document','hardlink','hardlink','0','1368626461','1382956042','0','0','ID: 33  \nPath: /en/advanced-examples/hard-link/basic-examples  \n','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Basic Examples ');
INSERT INTO `search_backend_data` VALUES ('33','/blog/categories','object','folder','folder','1','1388389428','1388389428','7','7','ID: 33  \nPath: /blog/categories  \ncategories','');
INSERT INTO `search_backend_data` VALUES ('34','/screenshots','asset','folder','folder','1','1368560793','1368632470','0','0','ID: 34  \nPath: /screenshots  \nscreenshots','');
INSERT INTO `search_backend_data` VALUES ('34','/en/advanced-examples/hard-link','document','page','page','1','1368626655','1382956042','0','0','ID: 34  \nPath: /en/advanced-examples/hard-link  \n This page has a hardlink as child (see navigation on the left).&nbsp; This hardlink points to \"Basic Examples\", so the whole content of /basic-examples is available in /advaned-examples/hardlink/basic-examples.&nbsp; &nbsp; Want to know more about hardlinks?&nbsp; http://en.wikipedia.org/wiki/Hard_link see also:&nbsp;http://en.wikipedia.org/wiki/Symbolic_link&nbsp; &nbsp; Hard Link Example ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Hard Link ');
INSERT INTO `search_backend_data` VALUES ('34','/blog/articles','object','folder','folder','1','1388389435','1388389435','7','7','ID: 34  \nPath: /blog/articles  \narticles','');
INSERT INTO `search_backend_data` VALUES ('35','/screenshots/glossary.png','asset','image','image','1','1368560809','1368632470','0','0','ID: 35  \nPath: /screenshots/glossary.png  \nglossary.png','');
INSERT INTO `search_backend_data` VALUES ('35','/en/advanced-examples/image-with-hotspots-and-markers','document','page','page','1','1368626888','1382956042','0','0','ID: 35  \nPath: /en/advanced-examples/image-with-hotspots-and-markers  \n Image with Hotspots & Markers ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Image with Hotspots ');
INSERT INTO `search_backend_data` VALUES ('35','/blog/articles/maecenas-nec-odio','object','object','blogArticle','1','1388389641','1388393754','7','7','ID: 35  \nPath: /blog/articles/maecenas-nec-odio  \nMaecenas nec odio et ante tincidunt tempus Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Aenean Vestibulum Etiam Curabitur Maecenas nec odio et ante tincidunt tempus Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Aenean Vestibulum Etiam Curabitur Jan 2, 2014 8:52:00 AM /blog/categories/nam-eget-dui,/blog/categories/etiam-rhoncus ','');
INSERT INTO `search_backend_data` VALUES ('36','/documents/pimcore_t-mobile.pdf','asset','document','document','1','1368562442','1368632467','0','0','ID: 36  \nPath: /documents/pimcore_t-mobile.pdf  \npimcore_t-mobile.pdf Documentation 1. Pimcore Documentation . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1 Templates (Views) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1 Editables . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.1 Areablock (since 1.3.2) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.1.1 Create your own bricks . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.2 Area (since 1.4.3) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.3 Block . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.4 Checkbox . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.5 Date . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.6 Href (1 to 1 Relation) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.7 Image . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.8 Input . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.9 Link . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.10 Multihref (since 1.4.2) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.11 Multiselect . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.12 Numeric . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.13 Renderlet . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.14 Select . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.15 Snippet (embed) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.16 Table . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.17 Textarea . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.18 Video . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.1.19 WYSIWYG . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 1.1.2 Helpers (Available View Methods) . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . 3 5 6 7 9 13 14 17 17 18 19 23 25 26 27 27 28 30 31 32 33 33 36 38 Pimcore Documentation Table of Contents Installation and Upgrade Guide System Requirements Deployment using Capistrano Upgrade Notes Develop with pimcore Overview Documents Quick Start Guide Types Controllers Templates (Views) Editables Areablock (since 1.3.2) Create your own bricks Area (since 1.4.3) Block Checkbox Date Href (1 to 1 Relation) Image Input Link Multihref (since 1.4.2) Multiselect Numeric Renderlet Select Snippet (embed) Table Textarea Video WYSIWYG Helpers (Available View Methods) Document-Types Thumbnails Glossary Redirects Document Lists Localize your Documents (i18n) Translation of Document Editables Website Translations Navigation (since pimcore 1.4.0) Document Tree Copy documents and rewrite relations to new documents (since 1.4.2) Hardlinks for documents (since 1.4.2) Assets Asset Lists Custom Settings (Properties) Image Thumbnails Video Thumbnails (since 1.4.3) Data Objects Object Classes Data Fields Date, Date & Time, Time Geographic Fields - Point, Bounds, Polygon Href, Multihref, Objects - Relations, Dependencies and Lazy Loading Localized Fields (since 1.3.2) Non-Owner Objects Number Fields - Numeric, Slider Other Fields - Image, Image with Hotspots, Checkbox, Link Select Fields - Select, Multiselect, User, Country, Language Structured Data Fields Key Value Pairs (since 1.4.9) Structured Data Fields - Fieldcollections Structured Data Fields - Objectbricks Structured Data Fields - Structured Table Structured Data Fields - Table Text Input Fields - Input, Password,Textarea, WYSIWYG Layout Elements Object Lists External System Interaction Inheritance Custom Icons Locking fields Object Variants Object Preview (since 1.4.2) Object Tree Custom icons and style in object-tree (since 1.4.2) General Building URL\'s Cache Custom Cache Backends Output-Cache Custom Routes (Static Routes) Extending pimcore Class-Mappings - Overwrite pimcore models (since 1.3.2) Custom persistent models Hook into the startup-process (since 1.4.3) Google Custom Search Engine (Site Search) Integration (since 1.4.6) Magic Parameters Newsletter Properties Predefined Properties SQL Reports Static Helpers System Settings Email Tag & Snippet Management Versioning Website Settings Working with Sites (Multisite) Best Practices CLI Script for Object Import Eric Meyer reset.css Extending the Pimcore User with User - Object Relation High Traffic Server Setup (*nix based Environment) Reports & Marketing Google Analytics Setup Google Analytics Reporting with OAuth2 Service Accounts (since 1.4.6) API-Reference Web Services REST (since 1.4.9) SOAP (since pimcore 1.3.0) Localization Outputfilters Image base64 embed LESS (CSS Compiler) Install lessc on your server (Debian) Minify HTML, CSS & Javascript Mailing Framework (since build 1595) Pimcore_Mail Class Placeholders Object Text Administrator\'s Guide Backups Commandline Interface Backend Search Reindex Backups (since 1.3.0) Cache Warming Image Thumbnail Generator (since 1.4.6) MySQL Tools Install Plugins Setting up WebDav Translations User Permissions User\'s Guide Google Analytics Integration Keyboard Shortcuts Working with WebDAV BitKinex as WebDAV Client Cyberduck as WebDAV Client NetDrive as WebDAV Client Windows Explorer as WebDAV Client Extensions Extension Hub and Extension Manager Hooks Official Plugins Pimcore Demo Side - The Dev4Demo Project Plugin Developer\'s Guide Example Plugin Anatomy and Design Plugin Backend (PHP) UI Development and JS Hooks Useful Hints Develop for pimcore Releasing a new Version SVN Code-Repository and GitHub FAQ Archive Screencasts Install Example Data VMware Demo Image Commandline Updater CDN (Content Delivery Network) Google Summer of Code 2012 Ideas PhpUnit Tests Templates (Views) As mentioned already before, Pimcore uses Zend_View as its template engine, and the standard template language is PHP. The Pimcore implementation of Zend_View offers special methods to increase the usability: Method Description inc Use this function to directly include a document template Use this method to include a template cache In template caching translate i18n / translations glossary Glossary Additionally you can use the Zend_View helpers which are shipped with ZF. There are some really cool helpers which are really useful when used in combination with Pimcore. Some Examples Method Description action http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.action headMeta http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.headmeta headTitle http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.headtitle translate http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.translate You can use your own custom Zend_View helpers, or create some new one to make your life easier. There are some properties which are automatic available in the view: Name Type Description editmode boolean Is true if you are in editmode (admin), false if you are on the website controller Pimcore_Controller_Action_Frontend A reference to the controller document Document Reference to the current document object you can directly access the properties of the document in the view (eg. $this?document?getTitle();) Editables (Placeholders for content) Pimcore offers a basic set of placeholders which can be placed directly into the template. In editmode they appear as an editable widget, where you can put your content in. While in frontend-mode the content is directly embedded into the HTML. There is a standard scheme for how to call the editables. The first argument is always the name of the element (as string), the second argument is an array with multiple options (configurations) in it. Because most of the elements are based directly on Ext.form elements, you can also pass configurations directly to the Ext components (see API reference of Ext) Click here to get a detailed overview about the editables. Example Editables The editables are placeholders in the templates, which are input widgets in the admin (editmode) and output the content in frontend mode. Area (since 1.4.3) Areablock (since 1.3.2) Block Checkbox Date Href (1 to 1 Relation) Image Input Link Multihref (since 1.4.2) Multiselect Numeric Renderlet Select Snippet (embed) Table Textarea Video WYSIWYG General Most of the editables use ExtJS widgets, these editables can be also configured with options of the underlying ExtJS widget. For example: You can also use Zend_Json_Expr to add \"native\" Javascript to an editable: ','document_page_count:39 ');
INSERT INTO `search_backend_data` VALUES ('36','/en/advanced-examples/search','document','page','page','1','1368629524','1388733927','0','0','ID: 36  \nPath: /en/advanced-examples/search  \n The search is using the contents from&nbsp;pimcore.org.&nbsp;TIP: Search for \"web\". Search ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Search ');
INSERT INTO `search_backend_data` VALUES ('36','/blog/categories/curabitur-ullamcorper','object','object','blogCategory','1','1388389865','1388389870','7','7','ID: 36  \nPath: /blog/categories/curabitur-ullamcorper  \nCurabitur ullamcorper Curabitur ullamcorper ','');
INSERT INTO `search_backend_data` VALUES ('37','/examples/italy','asset','folder','folder','1','1368596763','1368632468','0','0','ID: 37  \nPath: /examples/italy  \nitaly','');
INSERT INTO `search_backend_data` VALUES ('37','/en/advanced-examples/contact-form','document','page','page','1','1368630444','1382956042','0','0','ID: 37  \nPath: /en/advanced-examples/contact-form  \n Contact Form ','blog:/en/advanced-examples/blog mainNavStartNode:/en sidebar:/en/advanced-examples/sidebar leftNavStartNode:/en/advanced-examples language:en navigation_name:Contact Form email:/en/advanced-examples/contact-form/email ');
INSERT INTO `search_backend_data` VALUES ('37','/blog/categories/nam-eget-dui','object','object','blogCategory','1','1388389881','1388393730','7','7','ID: 37  \nPath: /blog/categories/nam-eget-dui  \nNam eget dui Nam eget dui ','');
INSERT INTO `search_backend_data` VALUES ('38','/examples/italy/dsc04346.jpg','asset','image','image','1','1368596767','1368632468','0','0','ID: 38  \nPath: /examples/italy/dsc04346.jpg  \ndsc04346.jpg','');
INSERT INTO `search_backend_data` VALUES ('38','/en/advanced-examples/contact-form/email','document','email','email','1','1368631410','1382956042','0','0','ID: 38  \nPath: /en/advanced-examples/contact-form/email  \nGender: %Text(gender);&nbsp; Firstname: %Text(firstname); Lastname: %Text(lastname); E-Mail: %Text(email);&nbsp; &nbsp; Message: %Text(message);&nbsp; &nbsp; You\'ve got a new E-Mail! ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en email:/en/advanced-examples/contact-form/email ');
INSERT INTO `search_backend_data` VALUES ('38','/blog/categories/etiam-rhoncus','object','object','blogCategory','1','1388389892','1388389900','7','7','ID: 38  \nPath: /blog/categories/etiam-rhoncus  \nEtiam rhoncus Etiam rhoncus ','');
INSERT INTO `search_backend_data` VALUES ('39','/examples/italy/dsc04344.jpg','asset','image','image','1','1368596768','1368632468','0','0','ID: 39  \nPath: /examples/italy/dsc04344.jpg  \ndsc04344.jpg','');
INSERT INTO `search_backend_data` VALUES ('39','/error','document','page','page','1','1369854325','1369854422','0','0','ID: 39  \nPath: /error  \n Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. &nbsp; Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. &nbsp; Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, &nbsp; It seems that the page you were trying to find isn\'t around anymore. Oh no! ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('39','/blog/articles/lorem-ipsum-dolor-sit-amet','object','object','blogArticle','1','1388390090','1388393711','7','7','ID: 39  \nPath: /blog/articles/lorem-ipsum-dolor-sit-amet  \nLorem ipsum dolor sit amet Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst. Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante. In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla. Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio. Etiam Curabitur Fusce Quisque Lorem ipsum dolor sit amet Quisque id mi. Ut tincidunt tincidunt erat. Etiam feugiat lorem non metus. Vestibulum dapibus nunc ac augue. Curabitur vestibulum aliquam leo. Praesent egestas neque eu enim. In hac habitasse platea dictumst. Fusce a quam. Etiam ut purus mattis mauris sodales aliquam. Curabitur nisi. Quisque malesuada placerat nisl. Nam ipsum risus, rutrum vitae, vestibulum eu, molestie vel, lacus. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Mauris sollicitudin fermentum libero. Praesent nonummy mi in odio. Nunc interdum lacus sit amet orci. Vestibulum rutrum, mi nec elementum vehicula, eros quam gravida nisl, id fringilla neque ante vel mi. Morbi mollis tellus ac sapien. Phasellus volutpat, metus eget egestas mollis, lacus lacus blandit dui, id egestas quam mauris ut lacus. Fusce vel dui. Sed in libero ut nibh placerat accumsan. Proin faucibus arcu quis ante. In consectetuer turpis ut velit. Nulla sit amet est. Praesent metus tellus, elementum eu, semper a, adipiscing nec, purus. Cras risus ipsum, faucibus ut, ullamcorper id, varius ac, leo. Suspendisse feugiat. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Praesent nec nisl a purus blandit viverra. Praesent ac massa at ligula laoreet iaculis. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Mauris turpis nunc, blandit et, volutpat molestie, porta ut, ligula. Fusce pharetra convallis urna. Quisque ut nisi. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi. Suspendisse non nisl sit amet velit hendrerit rutrum. Ut leo. Ut a nisl id ante tempus hendrerit. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Suspendisse eu ligula. Nulla facilisi. Donec id justo. Praesent porttitor, nulla vitae posuere iaculis, arcu nisl dignissim dolor, a pretium mi sem ut ipsum. Curabitur suscipit suscipit tellus. Praesent vestibulum dapibus nibh. Etiam iaculis nunc ac metus. Ut id nisl quis enim dignissim sagittis. Etiam sollicitudin, ipsum eu pulvinar rutrum, tellus ipsum laoreet sapien, quis venenatis ante odio sit amet eros. Proin magna. Duis vel nibh at velit scelerisque suscipit. Curabitur turpis. Vestibulum suscipit nulla quis orci. Fusce ac felis sit amet ligula pharetra condimentum. Maecenas egestas arcu quis ligula mattis placerat. Duis lobortis massa imperdiet quam. Suspendisse potenti. Pellentesque commodo eros a enim. Vestibulum turpis sem, aliquet eget, lobortis pellentesque, rutrum eu, nisl. Sed libero. Aliquam erat volutpat. Etiam vitae tortor. Morbi vestibulum volutpat enim. Aliquam eu nunc. Nunc sed turpis. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Nulla porta dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Pellentesque dapibus hendrerit tortor. Praesent egestas tristique nibh. Sed a libero. Cras varius. Donec vitae orci sed dolor rutrum auctor. Fusce egestas elit eget lorem. Suspendisse nisl elit, rhoncus eget, elementum ac, condimentum eget, diam. Nam at tortor in tellus interdum sagittis. Aliquam lobortis. Donec orci lectus, aliquam ut, faucibus non, euismod id, nulla. Curabitur blandit mollis lacus. Nam adipiscing. Vestibulum eu odio. Etiam Curabitur Fusce Quisque Jan 8, 2014 8:54:00 AM /blog/categories/etiam-rhoncus TzoyNDoiT2JqZWN0X0RhdGFfSG90c3BvdGltYWdlIjo0OntzOjU6ImltYWdlIjtPOjExOiJBc3NldF9JbWFnZSI6MTU6e3M6NDoidHlwZSI7czo1OiJpbWFnZSI7czoyOiJpZCI7aToyMztzOjg6InBhcmVudElkIjtpOjE3O3M6ODoiZmlsZW5hbWUiO3M6MTI6ImltZ18wNDExLmpwZyI7czo0OiJwYXRoIjtzOjE3OiIvZXhhbXBsZXMvcGFuYW1hLyI7czo4OiJtaW1ldHlwZSI7czoxMDoiaW1hZ2UvanBlZyI7czoxMjoiY3JlYXRpb25EYXRlIjtpOjEzNjg1MzI4Mzc7czoxNjoibW9kaWZpY2F0aW9uRGF0ZSI7aToxMzY4NjMyNDY4O3M6OToidXNlck93bmVyIjtpOjA7czoxNjoidXNlck1vZGlmaWNhdGlvbiI7aTowO3M6ODoibWV0YWRhdGEiO2E6MDp7fXM6NjoibG9ja2VkIjtOO3M6MTQ6ImN1c3RvbVNldHRpbmdzIjthOjM6e3M6MTA6ImltYWdlV2lkdGgiO2k6MjAwMDtzOjExOiJpbWFnZUhlaWdodCI7aToxNTAwO3M6MjU6ImltYWdlRGltZW5zaW9uc0NhbGN1bGF0ZWQiO2I6MTt9czoxNToiACoAX2RhdGFDaGFuZ2VkIjtiOjA7czoyNDoiX19fX3BpbWNvcmVfY2FjaGVfaXRlbV9fIjtzOjE2OiJwaW1jb3JlX2Fzc2V0XzIzIjt9czo4OiJob3RzcG90cyI7YTowOnt9czo2OiJtYXJrZXIiO2E6MDp7fXM6NDoiY3JvcCI7YTo1OntzOjk6ImNyb3BXaWR0aCI7ZDo5OS41OTk5OTk5OTk5OTk5OTQ7czoxMDoiY3JvcEhlaWdodCI7ZDo1MC4xMzMzMzMzMzMzMzMzMzM7czo3OiJjcm9wVG9wIjtkOjE1LjczMzMzMzMzMzMzMzMzMztzOjg6ImNyb3BMZWZ0IjtkOjEuODtzOjExOiJjcm9wUGVyY2VudCI7YjoxO319 ','');
INSERT INTO `search_backend_data` VALUES ('40','/examples/italy/dsc04462.jpg','asset','image','image','1','1368596769','1368632468','0','0','ID: 40  \nPath: /examples/italy/dsc04462.jpg  \ndsc04462.jpg','');
INSERT INTO `search_backend_data` VALUES ('40','/en','document','link','link','1','1382956013','1382956551','0','0','ID: 40  \nPath: /en  \n /','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en blog:/en/advanced-examples/blog language:en navigation_name:Home ');
INSERT INTO `search_backend_data` VALUES ('40','/blog/articles/cum-sociis-natoque-penatibus-et-magnis','object','object','blogArticle','1','1388390120','1388393706','7','7','ID: 40  \nPath: /blog/articles/cum-sociis-natoque-penatibus-et-magnis  \nCum sociis natoque penatibus et magnis Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor. Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce Quisque Maecenas Donec Cum sociis natoque penatibus et magnis Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Donec posuere vulputate arcu. Phasellus accumsan cursus velit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed aliquam, nisi quis porttitor congue, elit erat euismod orci, ac placerat dolor lectus quis orci. Phasellus consectetuer vestibulum elit. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Vestibulum fringilla pede sit amet augue. In turpis. Pellentesque posuere. Praesent turpis. Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis lacus. Donec elit libero, sodales nec, volutpat a, suscipit non, turpis. Nullam sagittis. Suspendisse pulvinar, augue ac venenatis condimentum, sem libero volutpat nibh, nec pellentesque velit pede quis nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce id purus. Ut varius tincidunt libero. Phasellus dolor. Maecenas vestibulum mollis diam. Pellentesque ut neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. In ac felis quis tortor malesuada pretium. Pellentesque auctor neque nec urna. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Aenean viverra rhoncus pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce Quisque Maecenas Donec Dec 30, 2013 8:55:00 AM /blog/categories/curabitur-ullamcorper TzoyNDoiT2JqZWN0X0RhdGFfSG90c3BvdGltYWdlIjo0OntzOjU6ImltYWdlIjtPOjExOiJBc3NldF9JbWFnZSI6MTU6e3M6NDoidHlwZSI7czo1OiJpbWFnZSI7czoyOiJpZCI7aToyMDtzOjg6InBhcmVudElkIjtpOjE3O3M6ODoiZmlsZW5hbWUiO3M6MTI6ImltZ18wMDg5LmpwZyI7czo0OiJwYXRoIjtzOjE3OiIvZXhhbXBsZXMvcGFuYW1hLyI7czo4OiJtaW1ldHlwZSI7czoxMDoiaW1hZ2UvanBlZyI7czoxMjoiY3JlYXRpb25EYXRlIjtpOjEzNjg1MzI4MzM7czoxNjoibW9kaWZpY2F0aW9uRGF0ZSI7aToxMzY4NjMyNDY4O3M6OToidXNlck93bmVyIjtpOjA7czoxNjoidXNlck1vZGlmaWNhdGlvbiI7aTowO3M6ODoibWV0YWRhdGEiO2E6MDp7fXM6NjoibG9ja2VkIjtOO3M6MTQ6ImN1c3RvbVNldHRpbmdzIjthOjM6e3M6MTA6ImltYWdlV2lkdGgiO2k6MjAwMDtzOjExOiJpbWFnZUhlaWdodCI7aToxNTAwO3M6MjU6ImltYWdlRGltZW5zaW9uc0NhbGN1bGF0ZWQiO2I6MTt9czoxNToiACoAX2RhdGFDaGFuZ2VkIjtiOjA7czoyNDoiX19fX3BpbWNvcmVfY2FjaGVfaXRlbV9fIjtzOjE2OiJwaW1jb3JlX2Fzc2V0XzIwIjt9czo4OiJob3RzcG90cyI7YTowOnt9czo2OiJtYXJrZXIiO2E6MDp7fXM6NDoiY3JvcCI7YTo1OntzOjk6ImNyb3BXaWR0aCI7ZDo5OC43OTk5OTk5OTk5OTk5OTc7czoxMDoiY3JvcEhlaWdodCI7ZDo1NC4xMzMzMzMzMzMzMzMzMzM7czo3OiJjcm9wVG9wIjtkOjI3LjQ2NjY2NjY2NjY2NjY2NTtzOjg6ImNyb3BMZWZ0IjtpOjI7czoxMToiY3JvcFBlcmNlbnQiO2I6MTt9fQ== ','');
INSERT INTO `search_backend_data` VALUES ('41','/examples/italy/dsc04399.jpg','asset','image','image','1','1368596770','1368632468','0','0','ID: 41  \nPath: /examples/italy/dsc04399.jpg  \ndsc04399.jpg','');
INSERT INTO `search_backend_data` VALUES ('41','/de','document','page','page','1','1382956716','1382962917','0','0','ID: 41  \nPath: /de  \nAlbert Einstein 3 Bereit beeindruckt zu werden? Es wird dich umhauen! Oh ja, es ist wirklich so gut See it in Action See it in Action Checkmate In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. Teste unsere Beispiele und tauche ein in die nächste Generation von digitalem Inhaltsmanagement Sieh\' selbst Sieh\' selbst! Lorem ipsum. Oh yeah, it\'s that good. And lastly, this one. left left We can\'t solve problems by using the same kind of thinking we used when we created them. Cum sociis. See for yourself. Checkmate. video ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de navigation_name:Startseite ');
INSERT INTO `search_backend_data` VALUES ('41','/crm/newsletter','object','folder','folder','1','1388408967','1388408967','0','0','ID: 41  \nPath: /crm/newsletter  \nnewsletter','');
INSERT INTO `search_backend_data` VALUES ('42','/examples/south-africa','asset','folder','folder','1','1368596785','1368632468','0','0','ID: 42  \nPath: /examples/south-africa  \nsouth-africa','');
INSERT INTO `search_backend_data` VALUES ('42','/de/shared','document','folder','folder','1','1382956884','1382956887','0','0','ID: 42  \nPath: /de/shared  \nshared','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('42','/crm/inquiries','object','folder','folder','1','1388409135','1388409135','0','0','ID: 42  \nPath: /crm/inquiries  \ninquiries','');
INSERT INTO `search_backend_data` VALUES ('43','/examples/south-africa/img_1414.jpg','asset','image','image','1','1368596789','1368632468','0','0','ID: 43  \nPath: /examples/south-africa/img_1414.jpg  \nimg_1414.jpg','');
INSERT INTO `search_backend_data` VALUES ('43','/de/shared/includes','document','folder','folder','1','1382956885','1382956888','0','0','ID: 43  \nPath: /de/shared/includes  \nincludes','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('44','/examples/south-africa/img_2133.jpg','asset','image','image','1','1368596791','1368632468','0','0','ID: 44  \nPath: /examples/south-africa/img_2133.jpg  \nimg_2133.jpg','');
INSERT INTO `search_backend_data` VALUES ('44','/de/shared/teasers','document','folder','folder','1','1382956885','1382956888','0','0','ID: 44  \nPath: /de/shared/teasers  \nteasers','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('45','/examples/south-africa/img_2240.jpg','asset','image','image','1','1368596793','1368632468','0','0','ID: 45  \nPath: /examples/south-africa/img_2240.jpg  \nimg_2240.jpg','');
INSERT INTO `search_backend_data` VALUES ('45','/de/shared/teasers/standard','document','folder','folder','1','1382956885','1382956888','0','0','ID: 45  \nPath: /de/shared/teasers/standard  \nstandard','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('46','/examples/south-africa/img_1752.jpg','asset','image','image','1','1368596795','1368632468','0','0','ID: 46  \nPath: /examples/south-africa/img_1752.jpg  \nimg_1752.jpg','');
INSERT INTO `search_backend_data` VALUES ('46','/de/shared/includes/footer','document','snippet','snippet','1','1382956886','1382956919','0','0','ID: 46  \nPath: /de/shared/includes/footer  \npimcore.org Dokumentation Bug Tracker Designed and built with all the love in the world by&nbsp;@mdo&nbsp;and&nbsp;@fat. Code licensed under&nbsp;Apache License v2.0,&nbsp;Glyphicons Free&nbsp;licensed under&nbsp;CC BY 3.0. © Templates pimcore.org licensed under BSD License ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('47','/examples/south-africa/img_1739.jpg','asset','image','image','1','1368596798','1368632468','0','0','ID: 47  \nPath: /examples/south-africa/img_1739.jpg  \nimg_1739.jpg','');
INSERT INTO `search_backend_data` VALUES ('47','/de/shared/teasers/standard/basic-examples','document','snippet','snippet','1','1382956886','1382957000','0','0','ID: 47  \nPath: /de/shared/teasers/standard/basic-examples  \n Voll Responsive Lorem ipsum Diese Demo basiert auf Bootstrap, dem wohl bekanntesten,&nbsp;beliebtesten und flexibelsten Fontend-Framework. ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('47','/crm/newsletter/pimcore-byom.de~7a3','object','object','person','1','1388412533','1388412544','0','0','ID: 47  \nPath: /crm/newsletter/pimcore-byom.de~7a3  \nmale Demo User pimcore@byom.de 1 1 Dec 30, 2013 3:08:54 PM ','token:YTozOntzOjQ6InNhbHQiO3M6MzI6IjNlMGRkYTk3MWU1YTY5MWViYmM0OGVkNGQ5NzA4MDFmIjtzOjU6ImVtYWlsIjtzOjE1OiJwaW1jb3JlQGJ5b20uZGUiO3M6MjoiaWQiO2k6NDc7fQ== ');
INSERT INTO `search_backend_data` VALUES ('48','/examples/south-africa/img_0391.jpg','asset','image','image','1','1368596800','1368632468','0','0','ID: 48  \nPath: /examples/south-africa/img_0391.jpg  \nimg_0391.jpg','');
INSERT INTO `search_backend_data` VALUES ('48','/de/shared/teasers/standard/advanced-examples','document','snippet','snippet','1','1382956886','1382957114','0','0','ID: 48  \nPath: /de/shared/teasers/standard/advanced-examples  \n Drag & Drop Inhaltserstellung Etiam rhoncu Inhalt wird einfach per drag &amp; drop mit Inhaltsblöcken erstellt, welche dann direkt in-line editiert werden können. ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('48','/users','object','folder','folder','1','1491821233','1491821233','3','3','ID: 48  \nPath: /users  \nusers','');
INSERT INTO `search_backend_data` VALUES ('49','/examples/south-africa/img_2155.jpg','asset','image','image','1','1368596801','1368632468','0','0','ID: 49  \nPath: /examples/south-africa/img_2155.jpg  \nimg_2155.jpg','');
INSERT INTO `search_backend_data` VALUES ('49','/de/shared/teasers/standard/experiments','document','snippet','snippet','1','1382956887','1382957197','0','0','ID: 49  \nPath: /de/shared/teasers/standard/experiments  \n HTML5 immer & überall Quisque rutrum &nbsp; Bilder direkt per drag &amp; drop vom Desktop&nbsp;in den Baum in pimcore hochladen, automatische HTML5 Video Konvertierung&nbsp;und viel mehr ... ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('49','/users/john','object','object','user','1','1491821239','1491821246','3','3','ID: 49  \nPath: /users/john  \njohn ROLE_USER ','');
INSERT INTO `search_backend_data` VALUES ('50','/examples/south-africa/img_1544.jpg','asset','image','image','1','1368596804','1368632468','0','0','ID: 50  \nPath: /examples/south-africa/img_1544.jpg  \nimg_1544.jpg','');
INSERT INTO `search_backend_data` VALUES ('50','/de/einfuehrung','document','page','page','1','1382957658','1382957760','0','0','ID: 50  \nPath: /de/einfuehrung  \n Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. &nbsp; Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. &nbsp; Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. &nbsp; It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es. Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. &nbsp; Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Praesent commodo cursus magna, vel scelerisque nisl consectetur. Fusce dapibus, tellus ac cursus commodo. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Überblick über das Projekt und wie man mit einer einfachen Vorlage loslegen kann. Einführung Ullamcorper Scelerisque Erste Schritte Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Etiam rhoncu Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. snippet snippet ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de navigation_name:Einführung ');
INSERT INTO `search_backend_data` VALUES ('50','/users/jane','object','object','user','1','1491821254','1491821260','3','3','ID: 50  \nPath: /users/jane  \njane ROLE_ADMIN ','');
INSERT INTO `search_backend_data` VALUES ('51','/examples/south-africa/img_1842.jpg','asset','image','image','1','1368596806','1368632468','0','0','ID: 51  \nPath: /examples/south-africa/img_1842.jpg  \nimg_1842.jpg','');
INSERT INTO `search_backend_data` VALUES ('51','/de/einfache-beispiele','document','page','page','1','1382957793','1382957910','0','0','ID: 51  \nPath: /de/einfache-beispiele  \n Übersicht über einfache Beispiele Diese Seite dient nur zur Demonstration einer mehrsprachigen Seite.&nbsp; Um die Beispiele zu sehen verwende bitte die Englische Beispielseite.&nbsp; Einfache Beispiele ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de navigation_name:Einfache Beispiele ');
INSERT INTO `search_backend_data` VALUES ('52','/examples/south-africa/img_1920.jpg','asset','image','image','1','1368596808','1368632468','0','0','ID: 52  \nPath: /examples/south-africa/img_1920.jpg  \nimg_1920.jpg','');
INSERT INTO `search_backend_data` VALUES ('52','/de/beispiele-fur-fortgeschrittene','document','page','page','1','1382957961','1382957999','0','0','ID: 52  \nPath: /de/beispiele-fur-fortgeschrittene  \n Übersicht über fortgeschrittene Beispiele Diese Seite dient nur zur Demonstration einer mehrsprachigen Seite.&nbsp; Um die Beispiele zu sehen verwende bitte die Englische Beispielseite.&nbsp; Beispiele für Fortgeschrittene ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de navigation_name:Beispiele für Fortgeschrittene ');
INSERT INTO `search_backend_data` VALUES ('53','/examples/south-africa/img_0322.jpg','asset','image','image','1','1368596810','1368632468','0','0','ID: 53  \nPath: /examples/south-africa/img_0322.jpg  \nimg_0322.jpg','');
INSERT INTO `search_backend_data` VALUES ('53','/de/einfache-beispiele/neuigkeiten','document','page','page','1','1382958188','1382958240','0','0','ID: 53  \nPath: /de/einfache-beispiele/neuigkeiten  \n Neuigkeiten Alle strukturierten Daten werden in \"Objects\" gespeichert.&nbsp; ','blog:/en/advanced-examples/blog sidebar:/de/sidebar mainNavStartNode:/de language:de leftNavStartNode:/de navigation_name:Neuigkeiten ');
INSERT INTO `search_backend_data` VALUES ('54','/examples/singapore','asset','folder','folder','1','1368596871','1368632468','0','0','ID: 54  \nPath: /examples/singapore  \nsingapore','');
INSERT INTO `search_backend_data` VALUES ('55','/examples/singapore/dsc03778.jpg','asset','image','image','1','1368597116','1368632468','0','0','ID: 55  \nPath: /examples/singapore/dsc03778.jpg  \ndsc03778.jpg','');
INSERT INTO `search_backend_data` VALUES ('56','/examples/singapore/dsc03807.jpg','asset','image','image','1','1368597117','1368632468','0','0','ID: 56  \nPath: /examples/singapore/dsc03807.jpg  \ndsc03807.jpg','');
INSERT INTO `search_backend_data` VALUES ('57','/examples/singapore/dsc03835.jpg','asset','image','image','1','1368597119','1368632468','0','0','ID: 57  \nPath: /examples/singapore/dsc03835.jpg  \ndsc03835.jpg','');
INSERT INTO `search_backend_data` VALUES ('57','/en/sidebar','document','snippet','snippet','1','1382962826','1388735598','0','0','ID: 57  \nPath: /en/sidebar  \n3 ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('58','/de/sidebar','document','snippet','snippet','1','1382962891','1382962906','0','0','ID: 58  \nPath: /de/sidebar  \n ','blog:/en/advanced-examples/blog mainNavStartNode:/de sidebar:/de/sidebar leftNavStartNode:/de language:de ');
INSERT INTO `search_backend_data` VALUES ('59','/screenshots/thumbnail-configuration.png','asset','image','image','1','1368606782','1368632470','0','0','ID: 59  \nPath: /screenshots/thumbnail-configuration.png  \nthumbnail-configuration.png','');
INSERT INTO `search_backend_data` VALUES ('59','/en/introduction/sidebar','document','snippet','snippet','1','1382962940','1388738272','0','0','ID: 59  \nPath: /en/introduction/sidebar  \n2 ','sidebar:/en/introduction/sidebar mainNavStartNode:/en leftNavStartNode:/en blog:/en/advanced-examples/blog language:en ');
INSERT INTO `search_backend_data` VALUES ('60','/screenshots/website-translations.png','asset','image','image','1','1368608949','1368632470','0','0','ID: 60  \nPath: /screenshots/website-translations.png  \nwebsite-translations.png','');
INSERT INTO `search_backend_data` VALUES ('60','/en/advanced-examples/blog','document','page','page','1','1388391128','1395043669','7','0','ID: 60  \nPath: /en/advanced-examples/blog  \n Blog A blog is also just a simple list of objects. You can easily modify the structure of an article in Settings -&gt; Object -&gt; Classes.&nbsp; A blog is also just a simple list of objects. You can easily modify the structure of an article in Settings -&gt; Object -&gt; Classes. Blog ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Blog ');
INSERT INTO `search_backend_data` VALUES ('61','/screenshots/properties-1.png','asset','image','image','1','1368616805','1368632470','0','0','ID: 61  \nPath: /screenshots/properties-1.png  \nproperties-1.png','');
INSERT INTO `search_backend_data` VALUES ('61','/en/advanced-examples/sitemap','document','page','page','1','1388406334','1388406406','0','0','ID: 61  \nPath: /en/advanced-examples/sitemap  \n Auto-generated Sitemap Sitemap ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Sitemap ');
INSERT INTO `search_backend_data` VALUES ('62','/screenshots/properties-2.png','asset','image','image','1','1368616805','1368632470','0','0','ID: 62  \nPath: /screenshots/properties-2.png  \nproperties-2.png','');
INSERT INTO `search_backend_data` VALUES ('62','/newsletters','document','folder','folder','1','1388409377','1388409377','0','0','ID: 62  \nPath: /newsletters  \nnewsletters','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('63','/screenshots/properties-3.png','asset','image','image','1','1368616847','1368632470','0','0','ID: 63  \nPath: /screenshots/properties-3.png  \nproperties-3.png','');
INSERT INTO `search_backend_data` VALUES ('63','/en/advanced-examples/newsletter','document','page','page','1','1388409438','1388409571','0','0','ID: 63  \nPath: /en/advanced-examples/newsletter  \n Newsletter Newsletter ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Newsletter ');
INSERT INTO `search_backend_data` VALUES ('64','/screenshots/tag-snippet-management.png','asset','image','image','1','1368617634','1368632470','0','0','ID: 64  \nPath: /screenshots/tag-snippet-management.png  \ntag-snippet-management.png','');
INSERT INTO `search_backend_data` VALUES ('64','/en/advanced-examples/newsletter/confirm','document','page','page','1','1388409594','1388409641','0','0','ID: 64  \nPath: /en/advanced-examples/newsletter/confirm  \n ','blog:/en/advanced-examples/blog mainNavStartNode:/en sidebar:/en/advanced-examples/sidebar leftNavStartNode:/en/advanced-examples language:en navigation_name: ');
INSERT INTO `search_backend_data` VALUES ('65','/screenshots/objects-forms.png','asset','image','image','1','1368623266','1368632470','0','0','ID: 65  \nPath: /screenshots/objects-forms.png  \nobjects-forms.png','');
INSERT INTO `search_backend_data` VALUES ('65','/en/advanced-examples/newsletter/unsubscribe','document','page','page','1','1388409614','1388412346','0','0','ID: 65  \nPath: /en/advanced-examples/newsletter/unsubscribe  \n Newsletter Unsubscribe Unsubscribe ','blog:/en/advanced-examples/blog mainNavStartNode:/en sidebar:/en/advanced-examples/sidebar leftNavStartNode:/en/advanced-examples language:en navigation_name:Unsubscribe ');
INSERT INTO `search_backend_data` VALUES ('66','/documents/example-excel.xlsx','asset','document','document','1','1378992590','1378992590','0','0','ID: 66  \nPath: /documents/example-excel.xlsx  \nexample-excel.xlsx Firmenname Zwölf Monate GEWINN- UND VERLUSTPROJEKTION JÄN 12 UMSATZ (VERKAUF) TREND FEB 12 GESCHÄFTSJAHR BEGINNT: MÄR 12 APR 12Y MAI 12 JUN 12 JUL 12 AUG 12 SEP 12 OKT 12 NOV 12 DEZ 12 JÄHRLICH IND % J% F% M% A% M% J% J% A% S% O% N% JAN 2012 D% JAHR % TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TRENDTRENDTRENDRENDRENDRENDRENDRENDRENDRENDRENDRENDRENDREND TREND Umsatz 1 186.00 € 108.00 € 92.00 € 122.00 € 190.00 € 71.00 € 21.00 € 37.00 € 24.00 € 178.00 € 92.00 € 97.00 € Err:508 12% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 2 15.00 € 16.00 € 198.00 € 44.00 € 25.00 € 68.00 € 43.00 € 119.00 € 37.00 € 118.00 € 29.00 € 171.00 € Err:508 18% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 3 166.00 € 185.00 € 89.00 € 170.00 € 131.00 € 70.00 € 50.00 € 149.00 € 179.00 € 104.00 € 119.00 € 187.00 € Err:508 19% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 4 21.00 € 113.00 € 83.00 € 17.00 € 130.00 € 26.00 € 167.00 € 102.00 € 82.00 € 33.00 € 88.00 € 193.00 € Err:508 11% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 5 70.00 € 160.00 € 125.00 € 84.00 € 191.00 € 97.00 € 52.00 € 45.00 € 173.00 € 136.00 € 144.00 € 167.00 € Err:508 20% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 6 61.00 € 99.00 € 70.00 € 162.00 € 28.00 € 163.00 € 101.00 € 103.00 € 78.00 € 33.00 € 162.00 € 159.00 € Err:508 10% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Umsatz 7 105.00 € 55.00 € 163.00 € 12.00 € 117.00 € 83.00 € 163.00 € 120.00 € 171.00 € 79.00 € 105.00 € 69.00 € Err:508 10% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 GESAMTUMSATZ UMSATZKOSTEN Err:508 TREND Err:508 TREND TREND Err:508 TREND Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 TREND TREND TREND TREND TREND TREND TREND TREND Err:508 TREND Err:508 TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND Kosten 1 61.00 € 78.00 € 65.00 € 29.00 € 125.00 € 49.00 € 14.00 € 26.00 € 14.00 € 129.00 € 60.00 € 65.00 € Err:508 12% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 2 7.00 € 5.00 € 69.00 € 32.00 € 11.00 € 30.00 € 27.00 € 32.00 € 10.00 € 41.00 € 13.00 € 105.00 € Err:508 18% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 3 99.00 € 95.00 € 51.00 € 90.00 € 21.00 € 34.00 € 30.00 € 24.00 € 109.00 € 16.00 € 21.00 € 52.00 € Err:508 19% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 4 13.00 € 28.00 € 15.00 € 8.00 € 84.00 € 12.00 € 54.00 € 72.00 € 49.00 € 24.00 € 60.00 € 39.00 € Err:508 11% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 5 34.00 € 78.00 € 43.00 € 30.00 € 77.00 € 54.00 € 26.00 € 13.00 € 56.00 € 30.00 € 40.00 € 63.00 € Err:508 20% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 6 33.00 € 61.00 € 42.00 € 43.00 € 19.00 € 94.00 € 46.00 € 15.00 € 55.00 € 15.00 € 37.00 € 89.00 € Err:508 10% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Kosten 7 18.00 € 11.00 € 30.00 € 9.00 € 62.00 € 39.00 € 102.00 € 44.00 € 121.00 € 19.00 € 33.00 € 40.00 € Err:508 10% Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 7% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 SUMME UMSATZKOSTEN Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Bruttogewinn Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND AUSGABEN TREND Gehaltsaufwendungen 10.00 € 18.00 € 13.00 € 8.00 € 22.00 € 18.00 € 8.00 € 17.00 € 20.00 € 8.00 € 4.00 € Personalaufwand 23.00 € 11.00 € 7.00 € 14.00 € 12.00 € 19.00 € 19.00 € 4.00 € 7.00 € 13.00 € Fremdleistungen 23.00 € 20.00 € 3.00 € 16.00 € 10.00 € 5.00 € 20.00 € 7.00 € 4.00 € 22.00 € Energie (Büro und Betrieb) 19.00 € 4.00 € 7.00 € 14.00 € 22.00 € 10.00 € 22.00 € 5.00 € 4.00 € Reparaturen und Wartung 11.00 € 11.00 € 17.00 € 12.00 € 2.00 € 14.00 € 12.00 € 10.00 € Werbung 2.00 € 16.00 € 6.00 € 13.00 € 11.00 € 22.00 € 21.00 € Kfz, Lieferungen und Reisen 8.00 € 17.00 € 11.00 € 11.00 € 21.00 € 9.00 € Buchhaltung und Rechtsabteilung 5.00 € 13.00 € 6.00 € 15.00 € 19.00 € TRENDTREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND TREND 12.00 € Err:508 12% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 25.00 € 5.00 € Err:508 9% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 13.00 € 14.00 € Err:508 2% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 12.00 € 18.00 € 24.00 € Err:508 8% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 18.00 € 11.00 € 23.00 € 11.00 € Err:508 3% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 3.00 € 12.00 € 7.00 € 17.00 € 20.00 € Err:508 15% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 20.00 € 3.00 € 14.00 € 22.00 € 16.00 € 12.00 € Err:508 12% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 10.00 € 12.00 € 9.00 € 15.00 € 16.00 € 4.00 € 9.00 € Err:508 9% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Miete 8.00 € 4.00 € 23.00 € 25.00 € 10.00 € 24.00 € 22.00 € 5.00 € 12.00 € 24.00 € 24.00 € 12.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Telefon 25.00 € 2.00 € 12.00 € 25.00 € 10.00 € 24.00 € 3.00 € 20.00 € 3.00 € 9.00 € 20.00 € 18.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Nebenkosten 16.00 € 19.00 € 9.00 € 16.00 € 13.00 € 2.00 € 4.00 € 24.00 € 16.00 € 22.00 € 7.00 € 18.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Versicherung 12.00 € 9.00 € 16.00 € 19.00 € 25.00 € 17.00 € 20.00 € 14.00 € 5.00 € 14.00 € 5.00 € 2.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Steuern (Grundsteuer usw.) 16.00 € 13.00 € 10.00 € 7.00 € 13.00 € 3.00 € 13.00 € 17.00 € 9.00 € 4.00 € 22.00 € 18.00 € Err:508 14% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Zinsen 3.00 € 2.00 € 19.00 € 21.00 € 13.00 € 9.00 € 7.00 € 13.00 € 3.00 € 6.00 € 10.00 € 13.00 € Err:508 6% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Abschreibung 8.00 € 7.00 € 6.00 € 7.00 € 6.00 € 15.00 € 23.00 € 21.00 € 16.00 € 19.00 € 7.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Sonstige Ausgaben (angeben) 14.00 € 4.00 € 24.00 € 6.00 € 20.00 € ### 14.00 € 21.00 € 20.00 € 22.00 € 3.00 € 14.00 € 6.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Sonstige Ausgaben (angeben) 14.00 € 7.00 € 24.00 € 10.00 € 7.00 € 24.00 € 2.00 € 11.00 € 21.00 € 19.00 € 19.00 € 20.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Sonstige Ausgaben (angeben) 11.00 € 8.00 € 25.00 € 11.00 € 9.00 € 24.00 € 13.00 € 14.00 € 19.00 € 24.00 € 15.00 € 7.00 € Err:508 1% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Sonstiges (nicht angegeben) 8.00 € 20.00 € 11.00 € 11.00 € 20.00 € 12.00 € 16.00 € 5.00 € 7.00 € 21.00 € 3.00 € Err:508 2% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 7% Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 ### GESAMTAUSGABEN Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Err:508 Reingewinn Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 Err:509 ','');
INSERT INTO `search_backend_data` VALUES ('66','/en/advanced-examples/newsletter/confirmation-email','document','email','email','1','1388409670','1388412587','0','0','ID: 66  \nPath: /en/advanced-examples/newsletter/confirmation-email  \nContact Info Example Inc. Evergreen Terrace 123 XX 89234 Springfield +8998 487563 34234 info@example.inc Hi %Text(firstname);&nbsp;%Text(lastname);,&nbsp; &nbsp; You have just subscribed our cool newsletter with the email address: %Text(email);.&nbsp; To finish the process please click the following link to confirm your email address.&nbsp; &nbsp; CLICK HERE TO CONFIRM &nbsp; Thanks &amp; have a nice day! Terms Privacy About ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Newsletter ');
INSERT INTO `search_backend_data` VALUES ('67','/documents/example.docx','asset','document','document','1','1378992591','1378992591','0','0','ID: 67  \nPath: /documents/example.docx  \nexample.docx ﻿Example Document Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. One Two Three Four 1,23134.032 123.123 Some Value Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam semper libero, sit amet adipiscing sem neque sed ipsum. Consectetuer adipiscing elit Aenean commodo ligula eget dolor Aenean massa. 1. Cum sociis natoque penatibus et magnis dis parturient montes 2. Nascetur ridiculus mus. Donec quam felis, ultricies nec 3. Pellentesque eu, pretium quis, sem 4. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, ','');
INSERT INTO `search_backend_data` VALUES ('67','/newsletters/example-mailing','document','newsletter','newsletter','1','1388412605','1388412917','0','0','ID: 67  \nPath: /newsletters/example-mailing  \nContact Info Example Inc. Evergreen Terrace 123 XX 89234 Springfield +8998 487563 34234 info@example.inc Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. &nbsp; &nbsp; &nbsp; Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante.&nbsp; &nbsp; &nbsp; &nbsp; Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. &nbsp; Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada. Praesent congue erat at massa. Sed cursus turpis vitae tortor. Terms Privacy Unsubscribe ','sidebar:/en/sidebar mainNavStartNode:/en leftNavStartNode:/en language:en blog:/en/advanced-examples/blog ');
INSERT INTO `search_backend_data` VALUES ('68','/documents/example.pptx','asset','document','document','1','1378992592','1378992592','0','0','ID: 68  \nPath: /documents/example.pptx  \nexample.pptx Example Just a simple example Image Example ','');
INSERT INTO `search_backend_data` VALUES ('68','/en/advanced-examples/asset-thumbnail-list','document','page','page','1','1388414727','1388414883','0','0','ID: 68  \nPath: /en/advanced-examples/asset-thumbnail-list  \n Asset Thumbnail List Asset Thumbnail List ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Asset Thumbnail List ');
INSERT INTO `search_backend_data` VALUES ('69','/screenshots/e-commerce1.png','asset','image','image','1','1388740480','1388740490','0','0','ID: 69  \nPath: /screenshots/e-commerce1.png  \ne-commerce1.png','');
INSERT INTO `search_backend_data` VALUES ('69','/en/advanced-examples/sidebar','document','snippet','snippet','1','1388734403','1388738477','0','0','ID: 69  \nPath: /en/advanced-examples/sidebar  \n ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en ');
INSERT INTO `search_backend_data` VALUES ('70','/screenshots/pim1.png','asset','image','image','1','1388740572','1388740580','0','0','ID: 70  \nPath: /screenshots/pim1.png  \npim1.png','');
INSERT INTO `search_backend_data` VALUES ('70','/en/advanced-examples/product-information-management','document','page','page','1','1388740191','1388740585','0','0','ID: 70  \nPath: /en/advanced-examples/product-information-management  \n Please visit our&nbsp;PIM, E-Commerce &amp; Asset Management demo to see it in action.&nbsp; Product Information Management Product Information Management ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:Product Information Management ');
INSERT INTO `search_backend_data` VALUES ('71','/en/advanced-examples/e-commerce','document','page','page','1','1388740265','1388740613','0','0','ID: 71  \nPath: /en/advanced-examples/e-commerce  \n Please visit our&nbsp;PIM, E-Commerce &amp; Asset Management demo to see it in action.&nbsp; E-Commerce E-Commerce ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_name:E-Commerce ');
INSERT INTO `search_backend_data` VALUES ('72','/en/advanced-examples/sub-modules','document','page','page','1','1419933647','1419933980','32','32','ID: 72  \nPath: /en/advanced-examples/sub-modules  \n ','sidebar:/en/advanced-examples/sidebar blog:/en/advanced-examples/blog mainNavStartNode:/en leftNavStartNode:/en/advanced-examples language:en navigation_title: navigation_target: navigation_name:Sub-Modules navigation_exclude: ');
INSERT INTO `search_backend_data` VALUES ('73','/en/basic-examples/social-contents','document','page','page','1','1459501213','1459501429','39','39','ID: 73  \nPath: /en/basic-examples/social-contents  \n Embedding Social Contents Summer in Salzburg pic.twitter.com/XjulNLrnxZ&mdash; Discover Austria (@DiscoverAustria) March 9, 2016 (function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = \"//connect.facebook.net/de_DE/sdk.js#xfbml=1&version=v2.3\"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));Over the rainbow in SalzburgPosted by Discover Austria on&nbsp;Dienstag, 29. März 2016 SALZBURG | by @fatboy199 A photo posted by Discover Austria (@discoveraustria) on Mar 6, 2016 at 7:39am PST Social Contents ','blog:/en/advanced-examples/blog leftNavStartNode:/en/basic-examples mainNavStartNode:/en sidebar:/en/sidebar language:en navigation_parameters: navigation_target: navigation_tabindex: navigation_relation: navigation_anchor: navigation_name:Social Contents navigation_exclude: navigation_class: navigation_accesskey: navigation_title: ');
INSERT INTO `tags` VALUES ('12','0','/','imagetype');
INSERT INTO `tags` VALUES ('13','0','/','format');
INSERT INTO `tags` VALUES ('14','0','/','country');
INSERT INTO `tags` VALUES ('15','13','/13/','portrait');
INSERT INTO `tags` VALUES ('16','13','/13/','landscape');
INSERT INTO `tags` VALUES ('17','12','/12/','jpg');
INSERT INTO `tags` VALUES ('18','12','/12/','png');
INSERT INTO `tags` VALUES ('19','14','/14/','italy');
INSERT INTO `tags` VALUES ('20','14','/14/','panama');
INSERT INTO `tags` VALUES ('21','14','/14/','singapore');
INSERT INTO `tags` VALUES ('22','14','/14/','south-africa');
INSERT INTO `tags` VALUES ('23','12','/12/','screenshot');
INSERT INTO `tags` VALUES ('25','12','/12/','svg');
INSERT INTO `translations_admin` VALUES ('Admin','ca','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','cs','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','de','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','en','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','es','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','fa','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','fr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','it','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','ja','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','nl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','pl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','pt','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','pt_BR','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','ru','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','sk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','sv','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','tr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','uk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','zh_Hans','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Admin','zh_Hant','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','ca','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','cs','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','de','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','en','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','es','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','fa','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','fr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','it','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','ja','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','nl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','pl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','pt','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','pt_BR','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','ru','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','sk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','sv','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','tr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','uk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','zh_Hans','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master','zh_Hant','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','ca','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','cs','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','de','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','en','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','es','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','fa','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','fr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','it','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','ja','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','nl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','pl','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','pt','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','pt_BR','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','ru','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','sk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','sv','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','tr','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','uk','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','zh_Hans','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('Master (Admin Mode)','zh_Hant','','1491821250','1491821250');
INSERT INTO `translations_admin` VALUES ('blockquote','de','','1368611528','1368611528');
INSERT INTO `translations_admin` VALUES ('blockquote','en','','1368611528','1368611528');
INSERT INTO `translations_admin` VALUES ('blog','en','','1388389180','1388389180');
INSERT INTO `translations_admin` VALUES ('blogArticle','ca','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','cs','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','de','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','en','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','es','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','fa','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','fr','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','it','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','ja','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','nl','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','pl','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','pt','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','pt_BR','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','ru','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','sk','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','sv','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','tr','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','uk','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','zh_Hans','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogArticle','zh_Hant','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','ca','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','cs','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','de','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','en','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','es','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','fa','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','fr','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','it','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','ja','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','nl','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','pl','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','pt','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','pt_BR','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','ru','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','sk','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','sv','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','tr','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','uk','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','zh_Hans','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogCategory','zh_Hant','','1491819866','1491819866');
INSERT INTO `translations_admin` VALUES ('blogarticle','en','Blog Article','1388389510','1388396937');
INSERT INTO `translations_admin` VALUES ('blogcategories','en','','1388389420','1388389420');
INSERT INTO `translations_admin` VALUES ('blogcategory','en','Blog Category','1388389840','1388396950');
INSERT INTO `translations_admin` VALUES ('categories','en','','1388389661','1388389661');
INSERT INTO `translations_admin` VALUES ('content-page','en','','1368523214','1368523214');
INSERT INTO `translations_admin` VALUES ('contents','en','','1382958363','1382958363');
INSERT INTO `translations_admin` VALUES ('date','en','','1368613497','1368613497');
INSERT INTO `translations_admin` VALUES ('dateregister','en','','1368621929','1368621929');
INSERT INTO `translations_admin` VALUES ('email','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('embed','de','','1459501213','1459501213');
INSERT INTO `translations_admin` VALUES ('embed','en','','1459501213','1459501213');
INSERT INTO `translations_admin` VALUES ('featurette ','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('featurette ','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('female','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('firstname','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('gallery (carousel)','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('gallery (carousel)','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('gallery (folder)','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('gallery (folder)','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('gallery (single)','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('gallery (single)','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('gender','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('header color','en','','1368616347','1368616347');
INSERT INTO `translations_admin` VALUES ('headlines','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('headlines','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('hide left navigation','en','','1368616017','1368616017');
INSERT INTO `translations_admin` VALUES ('horiz. line','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('horiz. line','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('icon teaser','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('icon teaser','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('image','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('image','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('image hotspot','de','','1368627186','1368627186');
INSERT INTO `translations_admin` VALUES ('image hotspot','en','','1368627186','1368627186');
INSERT INTO `translations_admin` VALUES ('image hotspot & marker','de','','1368627476','1368627476');
INSERT INTO `translations_admin` VALUES ('image hotspot & marker','en','','1368627476','1368627476');
INSERT INTO `translations_admin` VALUES ('inquiry','en','Inquiry','1368620428','1388396996');
INSERT INTO `translations_admin` VALUES ('lastname','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('left navigation start node','en','','1368612685','1368612685');
INSERT INTO `translations_admin` VALUES ('male','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('message','en','','1368622768','1368622768');
INSERT INTO `translations_admin` VALUES ('name','en','','1388389870','1388389870');
INSERT INTO `translations_admin` VALUES ('news','en','News','1368613317','1388396966');
INSERT INTO `translations_admin` VALUES ('newsletter active','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('newsletter confirmed','en','','1368621928','1368621928');
INSERT INTO `translations_admin` VALUES ('pdf','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('pdf','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('person','en','Person','1368621928','1388397002');
INSERT INTO `translations_admin` VALUES ('persons','en','','1368620458','1368620458');
INSERT INTO `translations_admin` VALUES ('poster image','en','','1388389661','1388389661');
INSERT INTO `translations_admin` VALUES ('short text','en','','1368613497','1368613497');
INSERT INTO `translations_admin` VALUES ('sidebar','en','','1382962847','1382962847');
INSERT INTO `translations_admin` VALUES ('slider (tabs/text)','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('slider (tabs/text)','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('standard teaser','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('standard teaser','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('standard-mail','en','','1388409372','1388409372');
INSERT INTO `translations_admin` VALUES ('standard-teaser','en','','1368531641','1368531641');
INSERT INTO `translations_admin` VALUES ('tags','en','','1388389660','1388389660');
INSERT INTO `translations_admin` VALUES ('terms of use','en','','1368622768','1368622768');
INSERT INTO `translations_admin` VALUES ('text','en','','1368613497','1368613497');
INSERT INTO `translations_admin` VALUES ('text accordion','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('text accordion','en','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('title','en','','1368613497','1368613497');
INSERT INTO `translations_admin` VALUES ('unittest','en','','1368561373','1368561373');
INSERT INTO `translations_admin` VALUES ('user','ca','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','cs','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','de','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','en','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','es','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','fa','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','fr','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','it','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','ja','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','nl','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','pl','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','pt','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','pt_BR','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','ru','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','sk','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','sv','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','tr','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','uk','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','zh_Hans','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('user','zh_Hant','','1491821111','1491821111');
INSERT INTO `translations_admin` VALUES ('video','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('video','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('wysiwyg','de','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('wysiwyg','en','','1368608412','1368608412');
INSERT INTO `translations_admin` VALUES ('wysiwyg w. images','de','',NULL,NULL);
INSERT INTO `translations_admin` VALUES ('wysiwyg w. images','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('\'%value%\' is not a valid email address in the basic format local-part@hostname','de','','1368631595','1368631595');
INSERT INTO `translations_website` VALUES ('\'%value%\' is not a valid email address in the basic format local-part@hostname','en','','1368631595','1368631595');
INSERT INTO `translations_website` VALUES ('advanced examples','de','','0','0');
INSERT INTO `translations_website` VALUES ('advanced examples','en','','0','0');
INSERT INTO `translations_website` VALUES ('aktuelles','de','','0','0');
INSERT INTO `translations_website` VALUES ('aktuelles','en','','0','0');
INSERT INTO `translations_website` VALUES ('all categories','de','','0','0');
INSERT INTO `translations_website` VALUES ('all categories','en','','0','0');
INSERT INTO `translations_website` VALUES ('all dates','de','','0','0');
INSERT INTO `translations_website` VALUES ('all dates','en','','0','0');
INSERT INTO `translations_website` VALUES ('archive','de','','0','0');
INSERT INTO `translations_website` VALUES ('archive','en','','0','0');
INSERT INTO `translations_website` VALUES ('asset thumbnail list','de','','0','0');
INSERT INTO `translations_website` VALUES ('asset thumbnail list','en','','0','0');
INSERT INTO `translations_website` VALUES ('back to top','de','','0','0');
INSERT INTO `translations_website` VALUES ('back to top','en','','0','0');
INSERT INTO `translations_website` VALUES ('basic examples','de','','0','0');
INSERT INTO `translations_website` VALUES ('basic examples','en','','0','0');
INSERT INTO `translations_website` VALUES ('beispiele für fortgeschrittene','de','','0','0');
INSERT INTO `translations_website` VALUES ('beispiele für fortgeschrittene','en','','0','0');
INSERT INTO `translations_website` VALUES ('blog','de','','0','0');
INSERT INTO `translations_website` VALUES ('blog','en','','0','0');
INSERT INTO `translations_website` VALUES ('categories','de','','0','0');
INSERT INTO `translations_website` VALUES ('categories','en','','0','0');
INSERT INTO `translations_website` VALUES ('check me out','de','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('check me out','en','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('combined 1','en','','1368606496','1368606496');
INSERT INTO `translations_website` VALUES ('combined 2','en','','1368606637','1368606637');
INSERT INTO `translations_website` VALUES ('combined 3','en','','1368606637','1368606637');
INSERT INTO `translations_website` VALUES ('contact form','de','','0','0');
INSERT INTO `translations_website` VALUES ('contact form','en','','0','0');
INSERT INTO `translations_website` VALUES ('contain','en','','1368603255','1368603255');
INSERT INTO `translations_website` VALUES ('contain &amp; overlay','en','','1368605819','1368605819');
INSERT INTO `translations_website` VALUES ('content inheritance','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('content inheritance','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('content page','de','','0','0');
INSERT INTO `translations_website` VALUES ('content page','en','','0','0');
INSERT INTO `translations_website` VALUES ('cover','en','','1368602697','1368602697');
INSERT INTO `translations_website` VALUES ('creating objects with a form','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('creating objects with a form','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('deutsche übersetzung','de','','0','0');
INSERT INTO `translations_website` VALUES ('deutsche übersetzung','en','','0','0');
INSERT INTO `translations_website` VALUES ('dimensions','en','','1368604632','1368604632');
INSERT INTO `translations_website` VALUES ('document viewer','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('document viewer','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('download','de','Herunterladen','1368608523','1368608523');
INSERT INTO `translations_website` VALUES ('download','en','','1368608523','1368608523');
INSERT INTO `translations_website` VALUES ('download compiled','de','Herunterladen (kompiliert)','1368608505','1368608505');
INSERT INTO `translations_website` VALUES ('download compiled','en','','1368608505','1368608505');
INSERT INTO `translations_website` VALUES ('download now (%s)','de','','1368619727','1368619727');
INSERT INTO `translations_website` VALUES ('download now (%s)','en','','1368619727','1368619727');
INSERT INTO `translations_website` VALUES ('download source','de','Herunterladen (Quellen)','1368608508','1368608508');
INSERT INTO `translations_website` VALUES ('download source','en','','1368608508','1368608508');
INSERT INTO `translations_website` VALUES ('e-commerce','de','','0','0');
INSERT INTO `translations_website` VALUES ('e-commerce','en','','0','0');
INSERT INTO `translations_website` VALUES ('e-mail','de','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('e-mail','en','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('editable round-up','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('editable round-up','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('einfache beispiele','de','','0','0');
INSERT INTO `translations_website` VALUES ('einfache beispiele','en','','0','0');
INSERT INTO `translations_website` VALUES ('einführung','de','','0','0');
INSERT INTO `translations_website` VALUES ('einführung','en','','0','0');
INSERT INTO `translations_website` VALUES ('experiments','de','','0','0');
INSERT INTO `translations_website` VALUES ('experiments','en','','0','0');
INSERT INTO `translations_website` VALUES ('fastest way to get started: get the compiled and minified versions of our css, js, and images. no docs or original source files.','de','Der schnellste Weg um loszulegen: Lade die kompilierten und reduzierten Versionen unserer CSS, JS und Grafiken. Keine Dokumentation oder Quelldateien.','1368608611','1368608611');
INSERT INTO `translations_website` VALUES ('fastest way to get started: get the compiled and minified versions of our css, js, and images. no docs or original source files.','en','','1368608611','1368608611');
INSERT INTO `translations_website` VALUES ('female','de','','0','0');
INSERT INTO `translations_website` VALUES ('female','en','','0','0');
INSERT INTO `translations_website` VALUES ('firstname','de','','1368610819','1368610819');
INSERT INTO `translations_website` VALUES ('firstname','en','','1368610819','1368610819');
INSERT INTO `translations_website` VALUES ('frame','en','','1368603255','1368603255');
INSERT INTO `translations_website` VALUES ('galleries','de','','0','0');
INSERT INTO `translations_website` VALUES ('galleries','en','','0','0');
INSERT INTO `translations_website` VALUES ('gender','de','','1368622092','1368622092');
INSERT INTO `translations_website` VALUES ('gender','en','','1368622092','1368622092');
INSERT INTO `translations_website` VALUES ('get the original files for all css and javascript, along with a local copy of the docs by downloading the latest version directly from github.','de','Lade die originalen  CSS und Javascript Dateien zusammen mit einer lokalen Kopie der Dokumentation von github.com','1368608698','1368608698');
INSERT INTO `translations_website` VALUES ('get the original files for all css and javascript, along with a local copy of the docs by downloading the latest version directly from github.','en','','1368608698','1368608698');
INSERT INTO `translations_website` VALUES ('glossary','de','','0','0');
INSERT INTO `translations_website` VALUES ('glossary','en','','0','0');
INSERT INTO `translations_website` VALUES ('grayscale','en','','1368606077','1368606077');
INSERT INTO `translations_website` VALUES ('hard link','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('hard link','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('home','de','Startseite','0','1382961053');
INSERT INTO `translations_website` VALUES ('home','en','Home','0','1382961053');
INSERT INTO `translations_website` VALUES ('html5 video','de','','0','0');
INSERT INTO `translations_website` VALUES ('html5 video','en','','0','0');
INSERT INTO `translations_website` VALUES ('i accept the terms of use','de','','1368620808','1368620808');
INSERT INTO `translations_website` VALUES ('i accept the terms of use','en','','1368620808','1368620808');
INSERT INTO `translations_website` VALUES ('image with hotspots','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('image with hotspots','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('introduction','de','','0','0');
INSERT INTO `translations_website` VALUES ('introduction','en','','0','0');
INSERT INTO `translations_website` VALUES ('keyword','de','','0','0');
INSERT INTO `translations_website` VALUES ('keyword','en','','0','0');
INSERT INTO `translations_website` VALUES ('language','de','','1459501213','1459501213');
INSERT INTO `translations_website` VALUES ('language','en','','1459501213','1459501213');
INSERT INTO `translations_website` VALUES ('lastname','de','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('lastname','en','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('male','de','','0','0');
INSERT INTO `translations_website` VALUES ('male','en','','0','0');
INSERT INTO `translations_website` VALUES ('mask','en','','1368606259','1368606259');
INSERT INTO `translations_website` VALUES ('message','de','','1368620708','1368620708');
INSERT INTO `translations_website` VALUES ('message','en','','1368620708','1368620708');
INSERT INTO `translations_website` VALUES ('neuigkeiten','de','','0','0');
INSERT INTO `translations_website` VALUES ('neuigkeiten','en','','0','0');
INSERT INTO `translations_website` VALUES ('news','de','','0','0');
INSERT INTO `translations_website` VALUES ('news','en','','0','0');
INSERT INTO `translations_website` VALUES ('newsletter','de','','1368620340','1368620340');
INSERT INTO `translations_website` VALUES ('newsletter','en','','1368620340','1368620340');
INSERT INTO `translations_website` VALUES ('original dimensions of the image','en','','1368604779','1368604779');
INSERT INTO `translations_website` VALUES ('overlay','en','','1368605562','1368605562');
INSERT INTO `translations_website` VALUES ('product information management','de','','0','0');
INSERT INTO `translations_website` VALUES ('product information management','en','','0','0');
INSERT INTO `translations_website` VALUES ('product information managment','de','','0','0');
INSERT INTO `translations_website` VALUES ('product information managment','en','','0','0');
INSERT INTO `translations_website` VALUES ('properties','de','','0','0');
INSERT INTO `translations_website` VALUES ('properties','en','','0','0');
INSERT INTO `translations_website` VALUES ('recently in the blog','de','','0','0');
INSERT INTO `translations_website` VALUES ('recently in the blog','en','','0','0');
INSERT INTO `translations_website` VALUES ('resize','en','','1368603801','1368603801');
INSERT INTO `translations_website` VALUES ('rotate','en','','1368603255','1368603255');
INSERT INTO `translations_website` VALUES ('rounded corners','en','','1368605936','1368605936');
INSERT INTO `translations_website` VALUES ('scale by height','en','','1368603959','1368603959');
INSERT INTO `translations_website` VALUES ('scale by width','en','','1368603959','1368603959');
INSERT INTO `translations_website` VALUES ('search','de','','1368629830','1368629830');
INSERT INTO `translations_website` VALUES ('search','en','','1368629830','1368629830');
INSERT INTO `translations_website` VALUES ('sepia','en','','1368606075','1368606075');
INSERT INTO `translations_website` VALUES ('simple form','de','','0','0');
INSERT INTO `translations_website` VALUES ('simple form','en','','0','0');
INSERT INTO `translations_website` VALUES ('sitemap','de','','0','0');
INSERT INTO `translations_website` VALUES ('sitemap','en','','0','0');
INSERT INTO `translations_website` VALUES ('slave document','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('slave document','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('sorry, something went wrong, please check the data in the form and try again!','de','','0','0');
INSERT INTO `translations_website` VALUES ('sorry, something went wrong, please check the data in the form and try again!','en','','0','0');
INSERT INTO `translations_website` VALUES ('submit','de','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('submit','en','','1368610820','1368610820');
INSERT INTO `translations_website` VALUES ('success, please check your mailbox!','de','','0','0');
INSERT INTO `translations_website` VALUES ('success, please check your mailbox!','en','','0','0');
INSERT INTO `translations_website` VALUES ('tag & snippet management','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('tag & snippet management','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('thank you very much','de','','1368611300','1368611300');
INSERT INTO `translations_website` VALUES ('thank you very much','en','','1368611300','1368611300');
INSERT INTO `translations_website` VALUES ('thanks for confirming your address!','de','','0','0');
INSERT INTO `translations_website` VALUES ('thanks for confirming your address!','en','','0','0');
INSERT INTO `translations_website` VALUES ('thumbnails','de','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('thumbnails','en','',NULL,NULL);
INSERT INTO `translations_website` VALUES ('total %s','de','','1368619656','1368619656');
INSERT INTO `translations_website` VALUES ('total %s','en','','1368619656','1368619656');
INSERT INTO `translations_website` VALUES ('total: %s','de','','1368619663','1368619663');
INSERT INTO `translations_website` VALUES ('total: %s','en','','1368619663','1368619663');
INSERT INTO `translations_website` VALUES ('unsubscribe','de','','0','0');
INSERT INTO `translations_website` VALUES ('unsubscribe','en','','0','0');
INSERT INTO `translations_website` VALUES ('website translations','de','','0','0');
INSERT INTO `translations_website` VALUES ('website translations','en','','0','0');
INSERT INTO `translations_website` VALUES ('website übersetzungen','de','','0','0');
INSERT INTO `translations_website` VALUES ('website übersetzungen','en','','0','0');
INSERT INTO `tree_locks` VALUES ('12','document','self');
INSERT INTO `tree_locks` VALUES ('46','document','self');
INSERT INTO `users` VALUES ('0','0','user','system','',NULL,NULL,NULL,'','','1','1','','','0','0','0','0','','',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` VALUES ('3','0','user','admin','$2y$10$C/NMm0Tu.vyqWecorItgVOepQSRjT2VCzFqB3hX6m5KunYx2BgtIe',NULL,NULL,NULL,'en',NULL,'1','1','','','0','1','1','1','','',NULL,NULL,'','','');
INSERT INTO `users_permission_definitions` VALUES ('application_logging');
INSERT INTO `users_permission_definitions` VALUES ('assets');
INSERT INTO `users_permission_definitions` VALUES ('backup');
INSERT INTO `users_permission_definitions` VALUES ('bounce_mail_inbox');
INSERT INTO `users_permission_definitions` VALUES ('classes');
INSERT INTO `users_permission_definitions` VALUES ('clear_cache');
INSERT INTO `users_permission_definitions` VALUES ('clear_temp_files');
INSERT INTO `users_permission_definitions` VALUES ('dashboards');
INSERT INTO `users_permission_definitions` VALUES ('documents');
INSERT INTO `users_permission_definitions` VALUES ('document_types');
INSERT INTO `users_permission_definitions` VALUES ('emails');
INSERT INTO `users_permission_definitions` VALUES ('glossary');
INSERT INTO `users_permission_definitions` VALUES ('http_errors');
INSERT INTO `users_permission_definitions` VALUES ('newsletter');
INSERT INTO `users_permission_definitions` VALUES ('notes_events');
INSERT INTO `users_permission_definitions` VALUES ('objects');
INSERT INTO `users_permission_definitions` VALUES ('plugins');
INSERT INTO `users_permission_definitions` VALUES ('predefined_properties');
INSERT INTO `users_permission_definitions` VALUES ('qr_codes');
INSERT INTO `users_permission_definitions` VALUES ('recyclebin');
INSERT INTO `users_permission_definitions` VALUES ('redirects');
INSERT INTO `users_permission_definitions` VALUES ('reports');
INSERT INTO `users_permission_definitions` VALUES ('robots.txt');
INSERT INTO `users_permission_definitions` VALUES ('routes');
INSERT INTO `users_permission_definitions` VALUES ('seemode');
INSERT INTO `users_permission_definitions` VALUES ('seo_document_editor');
INSERT INTO `users_permission_definitions` VALUES ('system_settings');
INSERT INTO `users_permission_definitions` VALUES ('tag_snippet_management');
INSERT INTO `users_permission_definitions` VALUES ('targeting');
INSERT INTO `users_permission_definitions` VALUES ('thumbnails');
INSERT INTO `users_permission_definitions` VALUES ('translations');
INSERT INTO `users_permission_definitions` VALUES ('users');
INSERT INTO `users_permission_definitions` VALUES ('website_settings');


DROP VIEW IF EXISTS `object_2`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_2` AS select `object_query_2`.`oo_id` AS `oo_id`,`object_query_2`.`oo_classId` AS `oo_classId`,`object_query_2`.`oo_className` AS `oo_className`,`object_query_2`.`date` AS `date`,`object_query_2`.`image_1` AS `image_1`,`object_query_2`.`image_2` AS `image_2`,`object_query_2`.`image_3` AS `image_3`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_2` join `objects` on((`objects`.`o_id` = `object_query_2`.`oo_id`)));

DROP VIEW IF EXISTS `object_3`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_3` AS select `object_query_3`.`oo_id` AS `oo_id`,`object_query_3`.`oo_classId` AS `oo_classId`,`object_query_3`.`oo_className` AS `oo_className`,`object_query_3`.`person__id` AS `person__id`,`object_query_3`.`person__type` AS `person__type`,`object_query_3`.`date` AS `date`,`object_query_3`.`message` AS `message`,`object_query_3`.`terms` AS `terms`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_3` join `objects` on((`objects`.`o_id` = `object_query_3`.`oo_id`)));

DROP VIEW IF EXISTS `object_4`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_4` AS select `object_query_4`.`oo_id` AS `oo_id`,`object_query_4`.`oo_classId` AS `oo_classId`,`object_query_4`.`oo_className` AS `oo_className`,`object_query_4`.`gender` AS `gender`,`object_query_4`.`firstname` AS `firstname`,`object_query_4`.`lastname` AS `lastname`,`object_query_4`.`email` AS `email`,`object_query_4`.`newsletterActive` AS `newsletterActive`,`object_query_4`.`newsletterConfirmed` AS `newsletterConfirmed`,`object_query_4`.`dateRegister` AS `dateRegister`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_4` join `objects` on((`objects`.`o_id` = `object_query_4`.`oo_id`)));

DROP VIEW IF EXISTS `object_5`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_5` AS select `object_query_5`.`oo_id` AS `oo_id`,`object_query_5`.`oo_classId` AS `oo_classId`,`object_query_5`.`oo_className` AS `oo_className`,`object_query_5`.`date` AS `date`,`object_query_5`.`categories` AS `categories`,`object_query_5`.`posterImage__image` AS `posterImage__image`,`object_query_5`.`posterImage__hotspots` AS `posterImage__hotspots`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_5` join `objects` on((`objects`.`o_id` = `object_query_5`.`oo_id`)));

DROP VIEW IF EXISTS `object_6`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_6` AS select `object_query_6`.`oo_id` AS `oo_id`,`object_query_6`.`oo_classId` AS `oo_classId`,`object_query_6`.`oo_className` AS `oo_className`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_6` join `objects` on((`objects`.`o_id` = `object_query_6`.`oo_id`)));

DROP VIEW IF EXISTS `object_7`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_7` AS select `object_query_7`.`oo_id` AS `oo_id`,`object_query_7`.`oo_classId` AS `oo_classId`,`object_query_7`.`oo_className` AS `oo_className`,`object_query_7`.`username` AS `username`,`object_query_7`.`password` AS `password`,`object_query_7`.`roles` AS `roles`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className` from (`object_query_7` join `objects` on((`objects`.`o_id` = `object_query_7`.`oo_id`)));

DROP VIEW IF EXISTS `object_localized_2_de`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_2_de` AS select `object_query_2`.`oo_id` AS `oo_id`,`object_query_2`.`oo_classId` AS `oo_classId`,`object_query_2`.`oo_className` AS `oo_className`,`object_query_2`.`date` AS `date`,`object_query_2`.`image_1` AS `image_1`,`object_query_2`.`image_2` AS `image_2`,`object_query_2`.`image_3` AS `image_3`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_2_de`.`ooo_id` AS `ooo_id`,`object_localized_query_2_de`.`language` AS `language`,`object_localized_query_2_de`.`title` AS `title`,`object_localized_query_2_de`.`shortText` AS `shortText`,`object_localized_query_2_de`.`text` AS `text` from ((`object_query_2` join `objects` on((`objects`.`o_id` = `object_query_2`.`oo_id`))) left join `object_localized_query_2_de` on((`object_query_2`.`oo_id` = `object_localized_query_2_de`.`ooo_id`)));

DROP VIEW IF EXISTS `object_localized_2_en`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_2_en` AS select `object_query_2`.`oo_id` AS `oo_id`,`object_query_2`.`oo_classId` AS `oo_classId`,`object_query_2`.`oo_className` AS `oo_className`,`object_query_2`.`date` AS `date`,`object_query_2`.`image_1` AS `image_1`,`object_query_2`.`image_2` AS `image_2`,`object_query_2`.`image_3` AS `image_3`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_2_en`.`ooo_id` AS `ooo_id`,`object_localized_query_2_en`.`language` AS `language`,`object_localized_query_2_en`.`title` AS `title`,`object_localized_query_2_en`.`shortText` AS `shortText`,`object_localized_query_2_en`.`text` AS `text` from ((`object_query_2` join `objects` on((`objects`.`o_id` = `object_query_2`.`oo_id`))) left join `object_localized_query_2_en` on((`object_query_2`.`oo_id` = `object_localized_query_2_en`.`ooo_id`)));

DROP VIEW IF EXISTS `object_localized_5_de`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_5_de` AS select `object_query_5`.`oo_id` AS `oo_id`,`object_query_5`.`oo_classId` AS `oo_classId`,`object_query_5`.`oo_className` AS `oo_className`,`object_query_5`.`date` AS `date`,`object_query_5`.`categories` AS `categories`,`object_query_5`.`posterImage__image` AS `posterImage__image`,`object_query_5`.`posterImage__hotspots` AS `posterImage__hotspots`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_5_de`.`ooo_id` AS `ooo_id`,`object_localized_query_5_de`.`language` AS `language`,`object_localized_query_5_de`.`title` AS `title`,`object_localized_query_5_de`.`text` AS `text`,`object_localized_query_5_de`.`tags` AS `tags` from ((`object_query_5` join `objects` on((`objects`.`o_id` = `object_query_5`.`oo_id`))) left join `object_localized_query_5_de` on((`object_query_5`.`oo_id` = `object_localized_query_5_de`.`ooo_id`)));

DROP VIEW IF EXISTS `object_localized_5_en`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_5_en` AS select `object_query_5`.`oo_id` AS `oo_id`,`object_query_5`.`oo_classId` AS `oo_classId`,`object_query_5`.`oo_className` AS `oo_className`,`object_query_5`.`date` AS `date`,`object_query_5`.`categories` AS `categories`,`object_query_5`.`posterImage__image` AS `posterImage__image`,`object_query_5`.`posterImage__hotspots` AS `posterImage__hotspots`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_5_en`.`ooo_id` AS `ooo_id`,`object_localized_query_5_en`.`language` AS `language`,`object_localized_query_5_en`.`title` AS `title`,`object_localized_query_5_en`.`text` AS `text`,`object_localized_query_5_en`.`tags` AS `tags` from ((`object_query_5` join `objects` on((`objects`.`o_id` = `object_query_5`.`oo_id`))) left join `object_localized_query_5_en` on((`object_query_5`.`oo_id` = `object_localized_query_5_en`.`ooo_id`)));

DROP VIEW IF EXISTS `object_localized_6_de`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_6_de` AS select `object_query_6`.`oo_id` AS `oo_id`,`object_query_6`.`oo_classId` AS `oo_classId`,`object_query_6`.`oo_className` AS `oo_className`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_6_de`.`ooo_id` AS `ooo_id`,`object_localized_query_6_de`.`language` AS `language`,`object_localized_query_6_de`.`name` AS `name` from ((`object_query_6` join `objects` on((`objects`.`o_id` = `object_query_6`.`oo_id`))) left join `object_localized_query_6_de` on((`object_query_6`.`oo_id` = `object_localized_query_6_de`.`ooo_id`)));

DROP VIEW IF EXISTS `object_localized_6_en`;
CREATE ALGORITHM=UNDEFINED  VIEW `object_localized_6_en` AS select `object_query_6`.`oo_id` AS `oo_id`,`object_query_6`.`oo_classId` AS `oo_classId`,`object_query_6`.`oo_className` AS `oo_className`,`objects`.`o_id` AS `o_id`,`objects`.`o_parentId` AS `o_parentId`,`objects`.`o_type` AS `o_type`,`objects`.`o_key` AS `o_key`,`objects`.`o_path` AS `o_path`,`objects`.`o_index` AS `o_index`,`objects`.`o_published` AS `o_published`,`objects`.`o_creationDate` AS `o_creationDate`,`objects`.`o_modificationDate` AS `o_modificationDate`,`objects`.`o_userOwner` AS `o_userOwner`,`objects`.`o_userModification` AS `o_userModification`,`objects`.`o_classId` AS `o_classId`,`objects`.`o_className` AS `o_className`,`object_localized_query_6_en`.`ooo_id` AS `ooo_id`,`object_localized_query_6_en`.`language` AS `language`,`object_localized_query_6_en`.`name` AS `name` from ((`object_query_6` join `objects` on((`objects`.`o_id` = `object_query_6`.`oo_id`))) left join `object_localized_query_6_en` on((`object_query_6`.`oo_id` = `object_localized_query_6_en`.`ooo_id`)));
