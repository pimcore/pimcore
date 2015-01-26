<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

chdir(__DIR__);

include_once("startup.php");

use Pimcore\Model\Asset;

try {
    $opts = new \Zend_Console_Getopt(array(
        'verbose|v' => 'show detailed information (for debug, ...)',
        'help|h' => 'display this help',
        "parent|p=i" => "only create thumbnails of images in this folder (ID)",
        "thumbnails|t=s" => "only create specified thumbnails (comma separated eg.: thumb1,thumb2)",
        "system|s" => "create system thumbnails (used for tree-preview, ...)",
        "force|f" => "recreate thumbnails, regardless if they exist already"
    ));
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    $opts->parse();
} catch (\Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage();
}


// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

if($opts->getOption("verbose")) {
    $writer = new \Zend_Log_Writer_Stream('php://output');
    $logger = new \Zend_Log($writer);
    \Logger::addLogger($logger);

    // set all priorities
    \Logger::setVerbosePriorities();
}

// get all thumbnails
$dir = Asset\Image\Thumbnail\Config::getWorkingDir();
$thumbnails = array();
$files = scandir($dir);
foreach ($files as $file) {
    if(strpos($file, ".xml")) {
        $thumbnails[] = str_replace(".xml", "", $file);
    }
}

$allowedThumbs = array();
if($opts->getOption("thumbnails")) {
    $allowedThumbs = explode(",", $opts->getOption("thumbnails"));
}


// get only images
$conditions = array("type = 'image'");

if($opts->getOption("parent")) {
    $parent = Asset::getById($opts->getOption("parent"));
    if($parent instanceof Asset\Folder) {
        $conditions[] = "path LIKE '" . $parent->getFullPath() . "/%'";
    } else {
        echo $opts->getOption("parent") . " is not a valid asset folder ID!\n";
        exit;
    }
}

$list = new Asset\Listing();
$list->setCondition(implode(" AND ", $conditions));
$total = $list->getTotalCount();
$perLoop = 10;

for($i=0; $i<(ceil($total/$perLoop)); $i++) {
    $list->setLimit($perLoop);
    $list->setOffset($i*$perLoop);

    $images = $list->load();
    foreach ($images as $image) {
        foreach ($thumbnails as $thumbnail) {
            if((empty($allowedThumbs) && !$opts->getOption("system")) || in_array($thumbnail, $allowedThumbs)) {
                if($opts->getOption("force")) {
                    $image->clearThumbnail($thumbnail);
                }

                echo "generating thumbnail for image: " . $image->getFullpath() . " | " . $image->getId() . " | Thumbnail: " . $thumbnail . " : " . formatBytes(memory_get_usage()) . " \n";
                echo "generated thumbnail: " . $image->getThumbnail($thumbnail) . "\n";
            }
        }

        if($opts->getOption("system")) {

            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
            if($opts->getOption("force")) {
                $image->clearThumbnail($thumbnail->getName());
            }

            echo "generating thumbnail for image: " . $image->getFullpath() . " | " . $image->getId() . " | Thumbnail: System Preview (tree) : " . formatBytes(memory_get_usage()) . " \n";
            echo "generated thumbnail: " . $image->getThumbnail($thumbnail) . "\n";
        }
    }
    \Pimcore::collectGarbage();
}


