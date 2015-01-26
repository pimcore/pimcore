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
use Pimcore\Model\Version;

try {
    $opts = new \Zend_Console_Getopt(array(
        'verbose|v' => 'show detailed information (for debug, ...)',
        'help|h' => 'display this help',
        "parent|p=i" => "only create thumbnails of videos in this folder (ID)",
        "thumbnails|t=s" => "only create specified thumbnails (comma separated eg.: thumb1,thumb2)",
        "system|s" => "create system thumbnails (used for tree-preview, ...)"
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

// disable versioning
Version::disable();

// get all thumbnails
$dir = Asset\Video\Thumbnail\Config::getWorkingDir();
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
$conditions = array("type = 'video'");

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

    $videos = $list->load();
    foreach ($videos as $video) {
        foreach ($thumbnails as $thumbnail) {
            if((empty($allowedThumbs) && !$opts->getOption("system")) || in_array($thumbnail, $allowedThumbs)) {
                echo "generating thumbnail for video: " . $video->getFullpath() . " | " . $video->getId() . " | Thumbnail: " . $thumbnail . " : " . formatBytes(memory_get_usage()) . " \n";
                $video->getThumbnail($thumbnail);
                waitTillFinished($video->getId(), $thumbnail);
            }
        }

        if($opts->getOption("system")) {
            echo "generating thumbnail for video: " . $video->getFullpath() . " | " . $video->getId() . " | Thumbnail: System Preview : " . formatBytes(memory_get_usage()) . " \n";
            $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
            $video->getThumbnail($thumbnail);
            waitTillFinished($video->getId(), $thumbnail);
        }
    }
}


function waitTillFinished($videoId, $thumbnail) {

    $finished = false;


    // initial delay
    $video = Asset::getById($videoId);
    $thumb = $video->getThumbnail($thumbnail);
    if ($thumb["status"] != "finished") {
        sleep(20);
    }

    while (!$finished) {
        \Pimcore::collectGarbage();

        $video = Asset::getById($videoId);
        $thumb = $video->getThumbnail($thumbnail);
        if ($thumb["status"] == "finished") {
            $finished = true;
            \Logger::debug("video [" . $video->getId() . "] FINISHED");
        } else if ($thumb["status"] == "inprogress") {
            $progress = Asset\Video\Thumbnail\Processor::getProgress($thumb["processId"]);
            \Logger::debug("video [" . $video->getId() . "] in progress: " . number_format($progress,0) . "%");

            sleep(5);
        } else {
            // error
            \Logger::debug("video [" . $video->getId() . "] has status: '" . $thumb["status"] . "' -> skipping");
            break;
        }
    }
}
