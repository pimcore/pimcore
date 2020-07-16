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
    <link rel="stylesheet" type="text/css" href="/bundles/pimcoreadmin/css/object_versions.css"/>

</head>

<body>

<?php
    $thumbnail = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/image-version-preview-" . uniqid() . ".png";
    $convert = \Pimcore\Image::getInstance();
    $tempFile = $this->asset->getTemporaryFile();
    $convert->load($tempFile);
    $convert->contain(500,500);
    $convert->save($thumbnail, "png");

    $dataUri = "data:image/png;base64," . base64_encode(file_get_contents($thumbnail));
    unlink($thumbnail);
    unlink($tempFile);

use Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data; ?>

<table id="wrapper" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <img src="<?= $dataUri ?>"/>
              <table class="preview" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr class="odd">
                                <th>Name</th>
                                <th>Value</th>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td><?php echo $this->asset->getFileName(); ?></td>
                            </tr>
                            <tr>
                                <td>Creation Date</td>
                                <td><?php echo date('m/d/Y H:i:s', $this->asset->getCreationDate()); ?></td>
                            </tr>
                            <tr>
                                <td>Modification Date</td>
                                <td><?php echo date('m/d/Y H:i:s', $this->asset->getModificationDate()); ?></td>
                            </tr>
                            <tr>
                                <td>File Size</td>
                                <td><?php echo $this->asset->getFileSize(true); ?> </td>
                            </tr>
                            <tr>
                                <td>Mime Type</td>
                                <td><?php echo $this->asset->getMimetype(); ?></td>
                            </tr>
                            <tr>
                                <td>Dimensions</td>
                                <td><?php
                                    if (is_array($this->asset->getDimensions())) {
                                        echo $this->asset->getDimensions()["width"] . " X " . $this->asset->getDimensions()["height"];
                                    }
                                    ?></td>
                            </tr>
                            <?php
                            if ($this->asset->getHasMetadata()) {
                                ?>
                                <?php
                                $metaData = $this->asset->getMetadata();

                                $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');


                                if (is_array($metaData) && count($metaData) > 0) {
                                    foreach ($metaData as $data) {
                                        $preview = $data["data"];
                                        try {
                                            /** @var Data $instance */
                                            $instance = $loader->build($data['type']);
                                            $preview = $instance->getVersionPreview($preview, $data);
                                        } catch (\Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException $e) {

                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $data['name']; ?>
                                                (<?php echo $data['type']; ?>)
                                            </td>
                                            <td><?php echo $preview; ?>
                                            </td>
                                            <?php ?>
                                        </tr>

                                        <?php
                                    }
                                }
                                ?>
                            <?php }
                            ?>
                        </tbody>
                    </table>

        </td>
    </tr>
</table>


</body>
</html>
