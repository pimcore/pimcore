<?php

    include("../../../../pimcore/config/startup_cli.php");

    $iconDir = realpath(__DIR__ . '/../img');
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
        <a href="https://raw.githack.com/icons8/flat-color-icons/master/index.html" target="_blank">Source (Icon8)</a>
    </div>

    <div id="icon8" class="icons">
        <?php foreach ($icons as $icon) { ?>
            <div class="icon">
                <img style="width:50px;" src="<?= str_replace(PIMCORE_WEB_ROOT, "", $icon) ?>" title="<?= basename($icon) ?>">
                <div class="label"><?= basename($icon) ?></div>
            </div>
        <?php } ?>
    </div>


    <div class="info">
        <a href="https://github.com/twitter/twemoji" target="_blank">Source (Twemoji)</a>
    </div>

    <div id="icon8" class="icons">
        <?php foreach ($twemoji as $icon) { ?>
            <div class="icon">
                <img style="width:50px;" src="<?= str_replace(PIMCORE_WEB_ROOT, "", $icon) ?>" title="<?= basename($icon) ?>">
                <div class="label"><?= basename($icon) ?></div>
            </div>
        <?php } ?>
    </div>

</body>
</html>

