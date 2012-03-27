<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

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

<h2><?php echo $this->data["code"]; ?> | <?php echo $this->data["path"]; ?></h2>

<?php foreach ($this->data as $key => $value) { ?>
    <?php if(in_array($key, array("parametersGet", "parametersPost", "serverVars", "cookies"))) { ?>

        <?php if (!empty($value)) { ?>
        <h2 class="sub"><?php echo $this->translate($key); ?></h2>

        <table>
            <?php foreach ($value as $k => $v) { ?>
                <tr>
                    <th valign="top"><?php echo $k; ?></th>
                    <td valign="top"><?php echo $v; ?></td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
    <?php } ?>
<?php } ?>


</body>
</html>