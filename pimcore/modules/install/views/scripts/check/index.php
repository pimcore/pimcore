<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

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
    </style>

</head>
<body>

    <table cellpadding="20">
        <tr>
            <td valign="top">
                <h2>PHP</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksPHP as $check) { ?>
                        <tr>
                            <td><a href="<?php echo $check["link"]; ?>" target="_blank"><?php echo $check["name"]; ?></a></td>
                            <td><img src="/pimcore/static/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>MySQL</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksMySQL as $check) { ?>
                        <tr>
                            <td><?php echo $check["name"]; ?></td>
                            <td><img src="/pimcore/static/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td valign="top">
                <h2>Filesystem</h2>
                <table border="1" cellpadding="3" cellspacing="0">
                    <?php foreach ($this->checksFS as $check) { ?>
                        <tr>
                            <td><?php echo $check["name"]; ?></td>
                            <td><img src="/pimcore/static/img/icon/<?php
                                if($check["state"] == "ok") {
                                    echo "accept";
                                } else if ($check["state"] == "warning") {
                                    echo "error";
                                } else {
                                    echo "delete";
                                }
                            ?>.png" /></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>