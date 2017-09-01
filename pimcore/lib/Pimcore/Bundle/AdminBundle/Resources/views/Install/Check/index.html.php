<?php

use Pimcore\Tool\Requirements\Check;

?><?php if(!$this->headless) { ?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow" />
</head>
<body>
<?php } ?>

    <style type="text/css">
        body {
            font-family: Arial, Tahoma, Verdana;
            font-size: 12px;
        }

        h2 {
            font-size: 16px;
            margin: 0;
            padding: 0 0 5px 0;
        }

        table {
            border-collapse: collapse;
        }

        a {
            color: #0066cc;
        }

        .legend {
            display: inline-block;
        }

        div.legend {
            padding-left: 20px;
        }

        span.legend {
            line-height: 30px;
            position: relative;
            padding: 0 30px 0 40px;
        }

        .legend img {
            position: absolute;
            top: 0;
            left: 0;
            width:30px;
        }

        table img {
            width:20px;
        }
    </style>

    <table cellpadding="20">
        <tr>
            <td valign="top">
                <h2>PHP</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksPHP as $check) { ?>
                        <tr>
                            <td><a href="<?= $check["link"]; ?>" target="_blank"><?= $check["name"]; ?></a></td>
                            <td><img src="/pimcore/static6/img/flat-color-icons/<?php
                                if($check["state"] == Check::STATE_OK) {
                                    echo "ok";
                                } else if ($check["state"] == Check::STATE_WARNING) {
                                    echo "warning";
                                } else {
                                    echo "high_priority";
                                }
                            ?>.svg" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>MySQL</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksMySQL as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/flat-color-icons/<?php
                                if($check["state"] == Check::STATE_OK) {
                                    echo "ok";
                                } else if ($check["state"] == Check::STATE_WARNING) {
                                    echo "warning";
                                } else {
                                    echo "high_priority";
                                }
                            ?>.svg" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>Filesystem</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksFS as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/flat-color-icons/<?php
                                if($check["state"] == Check::STATE_OK) {
                                    echo "ok";
                                } else if ($check["state"] == Check::STATE_WARNING) {
                                    echo "warning";
                                } else {
                                    echo "high_priority";
                                }
                            ?>.svg" /></td>
                        </tr>
                    <?php } ?>
                </table>

                <br />
                <br />

                <h2>CLI Tools &amp; Applications</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksApps as $check) { ?>
                        <tr>
                            <td><?= $check["name"]; ?></td>
                            <td><img src="/pimcore/static6/img/flat-color-icons/<?php
                                if($check["state"] == Check::STATE_OK) {
                                    echo "ok";
                                } else if ($check["state"] == Check::STATE_WARNING) {
                                    echo "warning";
                                } else {
                                    echo "high_priority";
                                }
                            ?>.svg" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>
    </table>


    <div class="legend">
        <p>
            <b>Explanation:</b>
        </p>
        <p>
            <span class="legend"><img src="/pimcore/static6/img/flat-color-icons/ok.svg" /> Everything ok</span>
            <span class="legend"><img src="/pimcore/static6/img/flat-color-icons/warning.svg" /> Recommended but not required</span>
            <span class="legend"><img src="/pimcore/static6/img/flat-color-icons/high_priority.svg" /> Required</span>
        </p>
    </div>

<?php if(!$this->headless) { ?>

</body>
</html>

<?php } ?>
