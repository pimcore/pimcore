<?php
//moving email logs from "var" to "system" folder
if(!is_dir(PIMCORE_LOG_MAIL_PERMANENT)){
    mkdir(PIMCORE_LOG_MAIL_PERMANENT, 0755, true);
}

if(is_dir(PIMCORE_LOG_MAIL_TEMP)){
    $cmd = "mv " . PIMCORE_LOG_MAIL_TEMP . DIRECTORY_SEPARATOR . "email-* " . PIMCORE_LOG_MAIL_PERMANENT . DIRECTORY_SEPARATOR;
    system($cmd);
}