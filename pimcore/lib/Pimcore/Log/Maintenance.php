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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Log_Maintenance {

    /**
     *
     */
    public function mail () {

        $conf = Pimcore_Config::getSystemConfig();
        if (!empty($conf->general->logrecipient)) {
            Logger::debug(get_class($this).": detected log recipient:".$conf->general->logrecipient);
            $user = User::getById($conf->general->logrecipient);
            Logger::debug(get_class($this).": detected log recipient:".$user->getEmail());
            if ($user instanceof User && $user->isAdmin()) {
                $email = $user->getEmail();
                Logger::debug(get_class($this).": user is valid");
                if (!empty($email)) {
                     if(is_dir(PIMCORE_LOG_MAIL_TEMP)){
                         Logger::debug(get_class($this).": detected mail log dir");
                         Logger::debug(get_class($this).": opening dir ".PIMCORE_LOG_MAIL_TEMP);
                        if ($handle = opendir(PIMCORE_LOG_MAIL_TEMP)) {
                            Logger::debug(get_class($this).": reading dir ".PIMCORE_LOG_MAIL_TEMP);
                            while (false !== ($file = readdir($handle))) {
                                Logger::debug(get_class($this).": detected file ".$file);
                                if(is_file(PIMCORE_LOG_MAIL_TEMP."/".$file) and is_writable(PIMCORE_LOG_MAIL_TEMP."/".$file)){
                                    $now = time();
                                   $threshold = 1 * 60 * 15;
                                   $fileModified = filemtime(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                    Logger::debug(get_class($this).": file is writeable and was last modified: ".$fileModified);
                                    if($fileModified!==FALSE and $fileModified<($now-$threshold)){
                                        $mail = Pimcore_Tool::getMail(array($email),"pimcore log notification - ".$file);
                                        $mail->setIgnoreDebugMode(true);
                                        $mail->setBodyText(file_get_contents(PIMCORE_LOG_MAIL_TEMP."/".$file));
                                        $mail->send();
                                        @unlink(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                        Logger::debug(get_class($this).": sent mail and deleted temp log file ".$file);
                                    } else if ($fileModified>($now-$threshold)){
                                        Logger::debug(get_class($this).": leaving temp log file alone because file [ $file ] was written to within the last 15 minutes");
                                    }
                                }

                            }
                        }
                     }
                } else {
                    Logger::err(get_class($this).": Cannot send mail to configured log user [".$user->getName()."] because email is empty");
                }
            } else {
                Logger::err(get_class($this).": Cannot send mail to configured log user. User is either null or not an admin");
            }
        } else {
            Logger::debug(get_class($this).": No log recipient configured");
        }


    }

    /**
     *
     */
    public function httpErrorLogCleanup () {

        // keep the history for max. 7 days (=> exactly 144h), according to the privacy policy (EU/German Law)
        // it's allowed to store the IP for 7 days for security reasons (DoS, ...)
        $limit = time() - (6 * 86400);

        $db = Pimcore_Resource::get();
        $db->delete("http_error_log", "date < " . $limit);
    }

}