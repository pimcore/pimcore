<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Log;

use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Model\User;

class Maintenance {

    /**
     *
     */
    public function mail () {

        $conf = Config::getSystemConfig();
        if (!empty($conf->general->logrecipient)) {
            \Logger::debug(get_class($this).": detected log recipient:".$conf->general->logrecipient);
            $user = User::getById($conf->general->logrecipient);
            \Logger::debug(get_class($this).": detected log recipient:".$user->getEmail());
            if ($user instanceof User && $user->isAdmin()) {
                $email = $user->getEmail();
                \Logger::debug(get_class($this).": user is valid");
                if (!empty($email)) {
                     if(is_dir(PIMCORE_LOG_MAIL_TEMP)){
                         \Logger::debug(get_class($this).": detected mail log dir");
                         \Logger::debug(get_class($this).": opening dir ".PIMCORE_LOG_MAIL_TEMP);
                        if ($handle = opendir(PIMCORE_LOG_MAIL_TEMP)) {
                            \Logger::debug(get_class($this).": reading dir ".PIMCORE_LOG_MAIL_TEMP);
                            while (false !== ($file = readdir($handle))) {
                                \Logger::debug(get_class($this).": detected file ".$file);
                                if(is_file(PIMCORE_LOG_MAIL_TEMP."/".$file) and is_writable(PIMCORE_LOG_MAIL_TEMP."/".$file)){
                                    $now = time();
                                   $threshold = 1 * 60 * 15;
                                   $fileModified = filemtime(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                    \Logger::debug(get_class($this).": file is writeable and was last modified: ".$fileModified);
                                    if($fileModified!==FALSE and $fileModified<($now-$threshold)){
                                        $mail = Tool::getMail(array($email),"pimcore log notification - ".$file);
                                        $mail->setIgnoreDebugMode(true);
                                        $mail->setBodyText(file_get_contents(PIMCORE_LOG_MAIL_TEMP."/".$file));
                                        $mail->send();
                                        @unlink(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                        \Logger::debug(get_class($this).": sent mail and deleted temp log file ".$file);
                                    } else if ($fileModified>($now-$threshold)){
                                        \Logger::debug(get_class($this).": leaving temp log file alone because file [ $file ] was written to within the last 15 minutes");
                                    }
                                }

                            }
                        }
                     }
                } else {
                    \Logger::err(get_class($this).": Cannot send mail to configured log user [".$user->getName()."] because email is empty");
                }
            } else {
                \Logger::err(get_class($this).": Cannot send mail to configured log user. User is either null or not an admin");
            }
        } else {
            \Logger::debug(get_class($this).": No log recipient configured");
        }
    }

    /**
     *
     */
    public function httpErrorLogCleanup () {

        // keep the history for max. 7 days (=> exactly 144h), according to the privacy policy (EU/German Law)
        // it's allowed to store the IP for 7 days for security reasons (DoS, ...)
        $limit = time() - (6 * 86400);

        $db = \Pimcore\Resource::get();
        $db->delete("http_error_log", "date < " . $limit);
    }

    /**
     *
     */
    public function usageStatistics() {

        if(Config::getSystemConfig()->general->disableusagestatistics) {
            return;
        }

        $logFile = PIMCORE_LOG_DIRECTORY . "/usagelog.log";
        if(is_file($logFile) && filesize($logFile) > 200000) {
            $data = gzencode(file_get_contents($logFile));
            $response = Tool::getHttpData("https://www.pimcore.org/usage-statistics/", array(), array("data" => $data));
            if(strpos($response, "true") !== false) {
                @unlink($logFile);
                \Logger::debug("Usage statistics are transmitted and logfile was cleaned");
            } else {
                \Logger::debug("Unable to send usage statistics");
            }
        }
    }

    /**
     *
     */
    public function cleanupLogFiles () {
        $files = glob(PIMCORE_LOG_DIRECTORY . "/*.log-archive-*");
        if(is_array($files)) {
            foreach ($files as $file) {
                if(filemtime($file) < (time()-(86400*30))) { // we keep the logs for 30 days
                    unlink($file);
                } else if (!preg_match("/\.gz$/", $file)) {
                    gzcompressfile($file);
                    unlink($file);
                }
            }
        }
    }

    public function checkErrorLogsDb(){
        $conf = Config::getSystemConfig();
        $config = $conf->applicationlog;

        if($config->mail_notification->send_log_summary){

            $receivers = preg_split("/,|;/",$config->mail_notification->mail_receiver);

            array_walk($receivers, function (&$value){
                $value = trim($value);
            });

            $logLevel = (int)$config->mail_notification->filter_priority;
            $db = \Pimcore\Resource::get()->getResource();


            $query = "SELECT * FROM ". \Pimcore\Log\Helper::ERROR_LOG_TABLE_NAME . " WHERE maintenanceChecked IS NULL AND priority <= $logLevel order by id desc";

            $rows = $db->fetchAll($query);
            $limit = 100;
            $rowsProcessed = 0;

            $rowCount = count($rows);
            if($rowCount){
                while ($rowsProcessed < $rowCount) {
                    $entries = array();

                    if ($rowCount <= $limit) {
                        $entries = $rows;
                    } else {
                        for ($i = $rowsProcessed; $i < $rowCount && count($entries) < $limit; $i++) {
                            $entries[] = $rows[$i];
                        }
                    }

                    $rowsProcessed += count($entries);

                    $html = var_export($entries, true);
                    $html = "<pre>$html</pre>";
                    $mail = new \Pimcore_Mail();
                    $mail->setBodyHtml($html);
                    $mail->addTo($receivers);
                    $mail->setSubject('Error Log ' . \Pimcore_Tool::getHostUrl());
                    $mail->send();
                }
            }

            $db->query("UPDATE " . \Pimcore\Log\Helper::ERROR_LOG_TABLE_NAME . " set maintenanceChecked = 1");
        }
    }


    public function archiveLogEntries() {
        $conf = Config::getSystemConfig();
        $config = $conf->applicationlog;

        $db = \Pimcore\Resource::get();

        $tablename =  \Pimcore\Log\Helper::ERROR_LOG_ARCHIVE_TABLE_NAME . "_" . \Zend_Date::now()->get(\Zend_Date::MONTH_NAME) . '_' .\Zend_Date::now()->get(\Zend_Date::YEAR);

        if($config->archive_alternative_database) {
            $tablename = $config->archive_alternative_database . '.' . $tablename;
        }

        $archive_treshold = intval($config->archive_treshold) ? : 30;

        $db->query("CREATE TABLE IF NOT EXISTS " . $tablename . " (
                       id BIGINT(20) NOT NULL,
                       `timestamp` DATETIME NOT NULL,
                       message VARCHAR(1024),
                       priority INT(10),
                       fileobject VARCHAR(1024),
                       info VARCHAR(1024),
                       component VARCHAR(255),
                       source VARCHAR(255) NULL DEFAULT NULL,
                       relatedobject BIGINT(20),
                       relatedobjecttype ENUM('object', 'document', 'asset'),
                       maintenanceChecked TINYINT(4)
                    ) ENGINE = ARCHIVE ROW_FORMAT = DEFAULT;");


        $timestamp = time();

        $db->query("INSERT INTO " . $tablename . " SELECT * FROM " .  \Pimcore\Log\Helper::ERROR_LOG_TABLE_NAME . " WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(" . $timestamp . "), INTERVAL " . $archive_treshold . " DAY);");
        $db->query("DELETE FROM " .  \Pimcore\Log\Helper::ERROR_LOG_TABLE_NAME . " WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(" . $timestamp . "), INTERVAL " . $archive_treshold . " DAY);");


    }
}
