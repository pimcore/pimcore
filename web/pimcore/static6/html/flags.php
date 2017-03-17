<?php

include("../../../../pimcore/config/startup.php");

$iconPath = '/pimcore/static6/img/flags/';

$locales = \Pimcore\Tool::getSupportedLocales();
$languageOptions = [];
foreach ($locales as $short => $translation) {
    if (!empty($short)) {
        $languageOptions[] = [
            "language" => $short,
            "display" => $translation . " ($short)"
        ];
    }
}


?>


<table>
    <tr>
        <th>Flag</th>
        <th>Code</th>
        <th>Name</th>
    </tr>
    <?php foreach ($languageOptions as $lang) {
    ?>
        <tr>
            <td><img style="width:16px" src="<?= str_replace(PIMCORE_WEB_ROOT, "", \Pimcore\Tool::getLanguageFlagFile($lang["language"])) ?>"></td>
            <td><?= $lang["language"] ?></td>
            <td><?= $lang["display"] ?></td>
        </tr>
    <?php

} ?>
</table>
