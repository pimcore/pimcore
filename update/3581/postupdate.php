<?php

// get db connection
$db = Pimcore_Resource::get();

$db->query("ALTER TABLE `application_logs`CHANGE COLUMN `priority` `priority-legacy` INT(10) NULL DEFAULT NULL AFTER `message`;");
$db->query("ALTER TABLE application_logs ADD COLUMN `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL AFTER `message`;");

$zendLoggerPsr3Mapping = [
    \Zend_Log::DEBUG => "debug",
    \Zend_Log::INFO => "info",
    \Zend_Log::NOTICE => "notice",
    \Zend_Log::WARN => "warning",
    \Zend_Log::ERR => "error",
    \Zend_Log::CRIT => "critical",
    \Zend_Log::ALERT => "alert",
    \Zend_Log::EMERG => "emergency"
];

foreach ($zendLoggerPsr3Mapping as $zend => $psr) {
    $db->update("application_logs", ["priority" => $psr], "`priority-legacy` = '" . $zend . "'");
}

$db->query("ALTER TABLE application_logs DROP COLUMN `priority-legacy`;");

