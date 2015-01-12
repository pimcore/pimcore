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

namespace Pimcore\Controller\Plugin;

use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;

class Thumbnail extends \Zend_Controller_Plugin_Abstract {

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        if(preg_match("@^/website/var/tmp/image-thumbnails(.*)?/([0-9]+)/thumb__([a-zA-Z0-9_\-]+)([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@",$request->getPathInfo(),$matches)) {
            $assetId = $matches[2];
            $thumbnailName = $matches[3];
            $format = $matches[6];

            if($asset = Asset::getById($assetId)) {
                try {

                    $thumbnailConfig = null;
                    $deferredConfigId = "thumb_" . $assetId . "__" . md5($request->getPathInfo());
                    if($thumbnailConfigItem = TmpStore::get($deferredConfigId)) {
                        $thumbnailConfig = $thumbnailConfigItem->getData();
                        TmpStore::delete($deferredConfigId);

                        if(!$thumbnailConfig instanceof Asset\Image\Thumbnail\Config) {
                            throw new \Exception("Deferred thumbnail config file doesn't contain a valid \\Asset\\Image\\Thumbnail\\Config object");
                        }
                    } else {
                        // just check if the thumbnail exists -> throws exception otherwise
                        $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($thumbnailName);
                    }

                    if($asset instanceof Asset\Document) {
                        $page = 1;

                        $tmpPage = array_pop(explode("-", $thumbnailName));
                        if(is_numeric($tmpPage)) {
                            $page = $tmpPage;
                        }

                        $thumbnailConfig->setName(preg_replace("/\-[\d]+/","",$thumbnailConfig->getName()));
                        $thumbnailConfig->setName(str_replace("document_","",$thumbnailConfig->getName()));

                        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $asset->getImageThumbnail($thumbnailConfig, $page);
                    } else if ($asset instanceof Asset\Image) {
                        //check if high res image is called
                        if(array_key_exists(5, $matches)) {
                            $highResFactor = (float) str_replace(array("@","x"),"", $matches[5]);
                            $thumbnailConfig->setHighResolution($highResFactor);
                        }

                        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $asset->getThumbnail($thumbnailConfig);
                    }

                    $imageContent = file_get_contents($thumbnailFile);
                    $fileExtension = \Pimcore\File::getFileExtension($thumbnailFile);
                    if(in_array($fileExtension, array("gif","jpeg","jpeg","png","pjpeg"))) {
                        header("Content-Type: image/".$fileExtension, true);
                    } else {
                        header("Content-Type: " . $asset->getMimetype(), true);
                    }

                    header("Content-Length: " . filesize($thumbnailFile), true);
                    echo $imageContent;
                    exit;

                } catch (\Exception $e) {
                    // nothing to do
                    \Logger::error("Thumbnail with name '" . $thumbnailName . "' doesn't exist");
                }
            }
        }
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!Asset\Image\Thumbnail::isPictureElementInUse()) {
            return;
        }

        if(!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }


        // analytics
        $body = $this->getResponse()->getBody();

        // search for the end <head> tag, and insert the google analytics code before
        // this method is much faster than using simple_html_dom and uses less memory
        $code = '<script type="text/javascript" src="/pimcore/static/js/frontend/picturePolyfill.min.js" defer></script>';
        $headEndPosition = stripos($body, "</head>");
        if($headEndPosition !== false) {
            $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
        }

        $this->getResponse()->setBody($body);
    }
}
