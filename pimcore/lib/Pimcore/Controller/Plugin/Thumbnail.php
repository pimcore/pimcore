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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Plugin_Thumbnail extends Zend_Controller_Plugin_Abstract {

    /**
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        if(preg_match("@^/website/var/tmp/thumb_([0-9]+)__([a-zA-Z0-9_\-]+)@",$request->getPathInfo(),$matches)) {
            $assetId = $matches[1];
            $thumbnailName = $matches[2];

            if($asset = Asset::getById($assetId)) {
                try {
                    // just check if the thumbnail exists -> throws exception otherwise
                    $thumbnailConfig = Asset_Image_Thumbnail_Config::getByName($thumbnailName);
                    $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $asset->getThumbnail($thumbnailName);
                    $imageContent = file_get_contents($thumbnailFile);

                    $fileExtension = Pimcore_File::getFileExtension($thumbnailFile);
                    if(in_array($fileExtension, array("gif","jpeg","jpeg","png","pjpeg"))) {
                        header("Content-Type: image/".$fileExtension, true);
                    } else {
                        header("Content-Type: " . $asset->getMimetype(), true);
                    }

                    header("Content-Length: " . filesize($thumbnailFile), true);
                    echo $imageContent;
                    exit;

                } catch (Exception $e) {
                    // nothing to do
                    Logger::error("Thumbnail with name '" . $thumbnailName . "' doesn't exist");
                }
            }
        }
    }
}
