<?php

include("../../cli/startup.php");

$iconPath = '/pimcore/static/img/flags/';

$locales = \Pimcore\Tool::getSupportedLocales();
$languageOptions = array();
foreach ($locales as $short => $translation) {
    if(!empty($short)) {
        $languageOptions[] = array(
            "language" => $short,
            "display" => $translation . " ($short)"
        );
    }
}

function getIconPath($language) {

    $iconBasePath = PIMCORE_PATH . '/static/img/flags';

    $code = strtolower($language);
    $code = str_replace("_","-", $code);
    $countryCode = null;
    $fallbackLanguageCode = null;

    $parts = explode("-", $code);
    if(count($parts) > 1) {
        $countryCode = array_pop($parts);
        $fallbackLanguageCode = $parts[0];
    }

    $languagePath = $iconBasePath . "/languages/" . $code . ".png";
    $countryPath = $iconBasePath . "/countries/" . $countryCode . ".png";
    $fallbackLanguagePath = $iconBasePath . "/languages/" . $fallbackLanguageCode . ".png";

    $iconPath = $iconBasePath . "/countries/_unknown.png";
    if(file_exists($languagePath)) {
        $iconPath = $languagePath;
    } else if($countryCode && file_exists($countryPath)) {
        $iconPath = $countryPath;
    } else if ($fallbackLanguageCode && file_exists($fallbackLanguagePath)) {
        $iconPath = $fallbackLanguagePath;
    }

    $iconPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $iconPath);

    return $iconPath;
}


?>


<table>
    <tr>
        <th>Flag</th>
        <th>Code</th>
        <th>Name</th>
    </tr>
    <?php foreach($languageOptions as $lang) { ?>
        <tr>
            <td><img src="<?= getIconPath($lang["language"]) ?>"></td>
            <td><?= $lang["language"] ?></td>
            <td><?= $lang["display"] ?></td>
        </tr>
    <?php } ?>
</table>