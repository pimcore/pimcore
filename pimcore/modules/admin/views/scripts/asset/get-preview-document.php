<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css">

        html {
            height: 100%;
            overflow: hidden;
        }

        #flashcontent {
            height: 100%;
        }

        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

    </style>

</head>

<body>

<?php
    // add the PDF check here, otherwise the preview layer in admin is shown without content
    if(Pimcore_Document::isAvailable() && Pimcore_Document::isFileTypeSupported($this->asset->getFilename())) { ?>
    <?php
        $pdf = new Document_Tag_Pdf();
        $pdf->setId($this->asset->getId());
        $pdf->setOptions(array("fullscreen" => false));
        echo $pdf->frontend();
    ?>
<?php } else { ?>
    <iframe src="https://docs.google.com/viewer?embedded=true&url=<?php echo urlencode($this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . $this->asset->getFullPath() . "?dc_=" . time()); ?>" frameborder="0" width="100%" height="100%"></iframe>
<?php } ?>

</body>
</html>