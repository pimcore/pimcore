<?php

$files = rscandir(PIMCORE_APP_ROOT);
foreach($files as $file) {
    if(is_file($file)) {
        $content = file_get_contents($file);
        $newContent = str_replace(["@PimcoreBundle", "\\PimcoreLegacyBundle\\"], ["@PimcoreCoreBundle", "\\LegacyBundle\\"], $content);
        if($newContent != $content) {
            file_put_contents($file, $newContent);
        }
    }
}
