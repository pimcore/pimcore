<?php

$list = new Asset_Image_Thumbnail_List();
$list->load();

$thumbnails = array();
foreach ($list->getThumbnails() as $thumbnail) {

    $pipe = new Asset_Image_Thumbnail_Config();
    $pipe->setName($thumbnail->getName());
    $pipe->setDescription($thumbnail->getDescription());
    $pipe->setFormat($thumbnail->getFormat());
    $pipe->setQuality($thumbnail->getQuality());

    if ($thumbnail->getCover()) {
        $pipe->addItem("cover", array(
            "width" => $thumbnail->getWidth(),
            "height" => $thumbnail->getHeight(),
            "positioning" => "center"
        ));
    }
    else if ($thumbnail->getContain()) {
        $pipe->addItem("contain", array(
            "width" => $thumbnail->getWidth(),
            "height" => $thumbnail->getHeight()
        ));
    }
    else if ($thumbnail->getAspectratio()) {

        if ($thumbnail->getHeight() > 0 && $thumbnail->getWidth() > 0) {
            $pipe->addItem("contain", array(
                "width" => $thumbnail->getWidth(),
                "height" => $thumbnail->getHeight()
            ));
        }
        else if ($thumbnail->getHeight() > 0) {
            $pipe->addItem("scaleByHeight", array(
                "height" => $thumbnail->getHeight()
            ));
        }
        else {
            $pipe->addItem("scaleByWidth", array(
                "width" => $thumbnail->getWidth()
            ));
        }
    }
    else {
        $pipe->addItem("resize", array(
            "width" => $thumbnail->getWidth(),
            "height" => $thumbnail->getHeight()
        ));
    }

    $pipe->save();
}

// backup thumbnail table
$db = Pimcore_Resource::get();
$tableData = $db->fetchAll("SELECT * FROM thumbnails");
$dumpData = "";
foreach ($tableData as $row) {

    $cells = array();
    foreach ($row as $cell) {
        $cells[] = $db->quote($cell);
    }

    $dumpData .= "INSERT INTO `thumbnails` VALUES (" . implode(",", $cells) . ");";
    $dumpData .= "\n";
}

if(!is_dir(PIMCORE_BACKUP_DIRECTORY)) {
    mkdir(PIMCORE_BACKUP_DIRECTORY);
}
file_put_contents(PIMCORE_BACKUP_DIRECTORY . "/update-1218-recovery.sql",$dumpData);

// drop thumbnail table
$db->exec("DROP TABLE `thumbnails`");

