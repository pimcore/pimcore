<?php

//directory for additional languages
$langDir = PIMCORE_WEBSITE_PATH . "/var/config/texts";
if (!is_dir($langDir)) {
    mkdir($langDir, 0755, true);
}

$success = is_dir($langDir);
if ($success) {
    $language = "de";
    $src = "http://www.pimcore.org/?controller=translation&action=download&language=" . $language;
    $data = @file_get_contents($src);

    if (!empty($language) and !empty($data)) {
        try {
            $languageFile = $langDir . "/" . $language . ".csv";
            $fh = fopen($languageFile, 'w');
            fwrite($fh, $data);
            fclose($fh);

        } catch (Exception $e) {
            logger::log("could not download language file", Zend_Log::WARN);
            logger::log($e);
            $success = false;
        }
    }
}


?>
<b>Release Notes (545):</b>
<br/>
- Added system languages download<br/>
- Removed german from core languages, moved additional system languages to website/var/config/texts

