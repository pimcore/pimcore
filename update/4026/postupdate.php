<?php

// Update thumbnail configs (doNotScaleUp is changed for forceResize)
$list = new Asset\Image\Thumbnail\Config\Listing();
$thumbnailConfigs = $list->load();

foreach ($thumbnailConfigs as $thumbnailConfig) {
    $toUpdate = false;

    $items = $thumbnailConfig->getItems();
    foreach ($items as &$item) {
        if (isset($item["method"]) && $item["method"] === "cover") {
            if (isset($item["arguments"])) {
                $toUpdate = true;
                foreach ($item["arguments"] as $key => $value) {
                    if ($key === "doNotScaleUp") {
                        $item["arguments"]["forceResize"] = !$value;
                        unset($item["arguments"][$key]);
                    }
                }
            }
        }
    }
    unset($item); // good practice to unset pointer at the end of the loop

    // We update only if necessary
    if ($toUpdate) {
        $thumbnailConfig->setItems($items);
        $thumbnailConfig->save();
    }
}
