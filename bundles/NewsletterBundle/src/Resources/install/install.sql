CREATE TABLE IF NOT EXISTS `documents_newsletter` (
    `id` int(11) unsigned NOT NULL DEFAULT '0',
    `controller` varchar(500) DEFAULT NULL,
    `template` varchar(255) DEFAULT NULL,
    `from` varchar(255) DEFAULT NULL,
    `subject` varchar(255) DEFAULT NULL,
    `trackingParameterSource` varchar(255) DEFAULT NULL,
    `trackingParameterMedium` varchar(255) DEFAULT NULL,
    `trackingParameterName` varchar(255) DEFAULT NULL,
    `enableTrackingParameters` tinyint(1) unsigned DEFAULT NULL,
    `sendingMode` varchar(20) DEFAULT NULL,
    `plaintext` LONGTEXT NULL DEFAULT NULL,
    `missingRequiredEditable` tinyint(1) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_documents_newsletter_documents` FOREIGN KEY (`id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;