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

    public function execute() {

        $conf = Pimcore_Config::getSystemConfig();
        if (!empty($conf->general->logrecipient)) {
            logger::debug(get_class($this).": detected log recipient:".$conf->general->logrecipient);
            $user = User::getById($conf->general->logrecipient);
            logger::debug(get_class($this).": detected log recipient:".$user->getEmail());
            if ($user instanceof User && $user->isAdmin()) {
                $email = $user->getEmail();
                logger::debug(get_class($this).": user is valid");
                if (!empty($email)) {
                     if(is_dir(PIMCORE_LOG_MAIL_TEMP)){
                         logger::debug(get_class($this).": detected mail log dir");
                         logger::debug(get_class($this).": opening dir ".PIMCORE_LOG_MAIL_TEMP);
                        if ($handle = opendir(PIMCORE_LOG_MAIL_TEMP)) {
                            logger::debug(get_class($this).": reading dir ".PIMCORE_LOG_MAIL_TEMP);
                            while (false !== ($file = readdir($handle))) {
                                logger::debug(get_class($this).": detected file ".$file);
                                if(is_file(PIMCORE_LOG_MAIL_TEMP."/".$file) and is_writable(PIMCORE_LOG_MAIL_TEMP."/".$file)){
                                    $now = time();
                                   $threshold = 1 * 60 * 15;
                                   $fileModified = filemtime(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                    logger::debug(get_class($this).": file is writeable and was last modified: ".$fileModified);
                                    if($fileModified!==FALSE and $fileModified<($now-$threshold)){
                                        $mail = Pimcore_Tool::getMail(array($email),"pimcore log notification - ".$file);
                                        $mail->setBodyText(file_get_contents(PIMCORE_LOG_MAIL_TEMP."/".$file));
                                        $mail->send();
                                        @unlink(PIMCORE_LOG_MAIL_TEMP."/".$file);
                                        logger::debug(get_class($this).": sent mail and deleted temp log file ".$file);
                                    } else if ($fileModified>($now-$threshold)){
                                        logger::debug(get_class($this).": leaving temp log file alone because file [ $file ] was written to within the last 15 minutes");
                                    }
                                }

                            }
                        }
                     }
                } else {
                    logger::err(get_class($this).": Cannot send mail to configured log user [".$user->getUsername()."] because email is empty");
                }
            } else {
                logger::err(get_class($this).": Cannot send mail to configured log user. User is either null or not an admin");
            }
        } else {
            logger::debug(get_class($this).": No log recipient configured");
        }


    }

}