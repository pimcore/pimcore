<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style type="text/css">

        html, body, #wrapper {
            height: 100%;
            margin: 0;
            padding: 0;
            border: none;
            text-align: center;
        }

        #wrapper {
            margin: 0 auto;
            text-align: left;
            vertical-align: middle;
            width: 400px;
        }


    </style>

</head>

<body>

<?php
    $thumbnail = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/image-version-preview-" . uniqid() . ".png";
    $convert = \Pimcore\Image::getInstance();
    $convert->load($this->asset->getTemporaryFile());
    $convert->contain(500,500);
    $convert->save($thumbnail, "png");

    $dataUri = "data:image/png;base64," . base64_encode(file_get_contents($thumbnail));
    unlink($thumbnail);
?>

<table id="wrapper" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <img src="<?= $dataUri ?>"/>
        </td>
    </tr>
</table>


</body>
</html>
