CREATE TABLE IF NOT EXISTS `documents_printpage` (
    `id` int(11) unsigned NOT NULL DEFAULT '0',
    `controller` varchar(500) DEFAULT NULL,
    `template` varchar(255) DEFAULT NULL,
    `lastGenerated` int(11) DEFAULT NULL,
    `lastGenerateMessage` text,
    `contentMainDocumentId` int(11) DEFAULT NULL,
    `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_documents_printpage_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;
