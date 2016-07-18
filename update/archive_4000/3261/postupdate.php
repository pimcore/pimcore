<?php

// rebuild thumbnails that have "cover" transformation in it

$dir = Asset_Image_Thumbnail_Config::getWorkingDir();
$files = scandir($dir);
foreach ($files as $file) {
    $found = false;
    if(strpos($file, ".xml")) {
        $name = str_replace(".xml", "", $file);

        $thumbnail = Asset_Image_Thumbnail_Config::getByName($name);
        foreach($thumbnail->items as &$item) {
            if($item["method"] == "cover") {
                $item["arguments"]["doNotScaleUp"] = "1";
                $found = true;
            }
        }

        if($found) {
            unset($thumbnail->filenameSuffix);
            $thumbnail->save();
        }
    }
}
