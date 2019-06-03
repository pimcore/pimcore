<?php


$prefixSearch = realpath(__DIR__ . '/../../../public');
$prefixReplace = '/bundles/pimcoreadmin';
$iconDir = realpath($prefixSearch . '/img');
$icons = rscandir($iconDir . '/flat-color-icons/');
$twemoji = rscandir($iconDir . '/twemoji/');

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pimcore :: Icon list</title>
    <style type="text/css">

        body {
            font-family: Arial;
            font-size: 12px;
        }

        .icons {
            width:1200px;
            margin: 0 auto;
        }

        .icon {
            text-align: center;
            width:100px;
            height:75px;
            margin: 0 10px 20px 0;
            float: left;
            font-size: 10px;
            word-wrap: break-word;
        }

        .info {
            text-align: center;
            margin-bottom: 30px;
            clear: both;
            font-size: 22px;
            padding-top: 50px;
        }
    </style>
</head>
<body>

<div class="info">
    <a target="_blank">Pimcore Icons</a>
</div>

<div id="icons" class="icons">
    <?php foreach ($icons as $icon) {
        ?>
        <div class="icon">
            <img style="width:50px;" src="<?= str_replace($prefixSearch, $prefixReplace, $icon) ?>" title="<?= basename($icon) ?>">
            <div class="label"><?= basename($icon) ?></div>
        </div>
        <?php
    } ?>
</div>


<div class="info">
    <a href="https://github.com/twitter/twemoji" target="_blank">Source (Twemoji)</a>
</div>

<div id="twenoji" class="icons">
    <?php foreach ($twemoji as $icon) {
        ?>
        <div class="icon">
            <img style="width:50px;" src="<?= str_replace($prefixSearch, $prefixReplace, $icon) ?>" title="<?= basename($icon) ?>">
            <div class="label"><?= basename($icon) ?></div>
        </div>
        <?php
    } ?>
</div>

<div class="info">
    Flags
</div>

<?php

$iconPath = '/bundles/pimcoreadmin/img/flags/';

$locales = \Pimcore\Tool::getSupportedLocales();
$languageOptions = [];
foreach ($locales as $short => $translation) {
    if (!empty($short)) {
        $languageOptions[] = [
            'language' => $short,
            'display' => $translation . " ($short)"
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
            <td><img style="width:16px" src="<?= str_replace(PIMCORE_WEB_ROOT, '', \Pimcore\Tool::getLanguageFlagFile($lang['language'])) ?>"></td>
            <td><?= $lang['language'] ?></td>
            <td><?= $lang['display'] ?></td>
        </tr>
        <?php
    } ?>
</table>


</body>
</html>

