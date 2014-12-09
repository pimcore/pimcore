<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

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
    $thumbnail = $this->asset->getImageThumbnailSavePath() . "/asset-version-preview-" . $this->asset->getId() . "-" . time() . ".png";
    $convert = \Pimcore\Image::getInstance();
    $convert->load($this->asset->getTemporaryFile(true));
    $convert->contain(500,500);
    $convert->save($thumbnail, "png");
    $thumbnail = str_replace(PIMCORE_DOCUMENT_ROOT, "", $thumbnail);
?>

<table id="wrapper" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <img src="<?php echo $thumbnail ?>"/>
        </td>
    </tr>
</table>


</body>
</html>