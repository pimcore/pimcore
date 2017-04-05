<!DOCTYPE html>
<html>
<head>

    <?php
        $this->get("translate")->setDomain("admin");
    ?>

<style type="text/css">
    body {
        margin: 0;
        padding: 10px;
        font-family: Arial;
        font-size: 12px;
    }

    h2 {
        border-bottom: 1px solid #000;
    }

    .sub {
        font-style: italic;
        border:0;
    }

    table {
        border-left: 1px solid #000;
        border-top: 1px solid #000;
        border-collapse: collapse;
    }

    td, th {
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 2px;
    }

    th {
        text-align: left;
    }
</style>


</head>

<body>

<h2><?= $this->data["code"]; ?> | <?= $this->data["uri"]; ?></h2>

<?php foreach ($this->data as $key => $value) { ?>
    <?php if(in_array($key, array("parametersGet", "parametersPost", "serverVars", "cookies"))) { ?>

        <?php if (!empty($value)) { ?>
        <h2 class="sub"><?= $this->translate($key); ?></h2>

        <table>
            <?php foreach ($value as $k => $v) { ?>
                <tr>
                    <th valign="top"><?= $k; ?></th>
                    <td valign="top"><?= $v; ?></td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
    <?php } ?>
<?php } ?>


</body>
</html>
