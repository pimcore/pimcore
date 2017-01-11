<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Plugin;

use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Logger;

class Thumbnail extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        if (preg_match("@/image-thumbnails(.*)?/([0-9]+)/thumb__([a-zA-Z0-9_\-]+)([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@", rawurldecode($request->getPathInfo()), $matches)) {
            $assetId = $matches[2];
            $thumbnailName = $matches[3];

            if ($asset = Asset::getById($assetId)) {
                try {
                    $page = 1; // default
                    $thumbnailFile = null;
                    $thumbnailConfig = null;

                    //get thumbnail for e.g. pdf page thumb__document_pdfPage-5
                    if (preg_match("|document_(.*)\-(\d+)$|", $thumbnailName, $matchesThumbs)) {
                        $thumbnailName = $matchesThumbs[1];
                        $page = (int)$matchesThumbs[2];
                    }

                    // just check if the thumbnail exists -> throws exception otherwise
                    $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($thumbnailName);

                    if (!$thumbnailConfig) {
                        // check if there's an item in the TmpStore
                        $deferredConfigId = "thumb_" . $assetId . "__" . md5($matches[0]);
                        if ($thumbnailConfigItem = TmpStore::get($deferredConfigId)) {
                            $thumbnailConfig = $thumbnailConfigItem->getData();
                            TmpStore::delete($deferredConfigId);

                            if (!$thumbnailConfig instanceof Asset\Image\Thumbnail\Config) {
                                throw new \Exception("Deferred thumbnail config file doesn't contain a valid \\Asset\\Image\\Thumbnail\\Config object");
                            }
                        }
                    }

                    if (!$thumbnailConfig) {
                        throw new \Exception("Thumbnail '" . $thumbnailName . "' file doesn't exists");
                    }

                    if ($asset instanceof Asset\Document) {
                        $thumbnailConfig->setName(preg_replace("/\-[\d]+/", "", $thumbnailConfig->getName()));
                        $thumbnailConfig->setName(str_replace("document_", "", $thumbnailConfig->getName()));

                        $thumbnailFile = $asset->getImageThumbnail($thumbnailConfig, $page)->getFileSystemPath();
                    } elseif ($asset instanceof Asset\Image) {
                        //check if high res image is called
                        if (array_key_exists(5, $matches)) {
                            $highResFactor = (float) str_replace(["@", "x"], "", $matches[5]);
                            $thumbnailConfig->setHighResolution($highResFactor);
                        }

                        // check if a media query thumbnail was requested
                        if (preg_match("#~\-~([\d]+w)#", $matches[4], $mediaQueryResult)) {
                            $thumbnailConfig->selectMedia($mediaQueryResult[1]);
                        }

                        $thumbnailFile = $asset->getThumbnail($thumbnailConfig)->getFileSystemPath();
                    }

                    if ($thumbnailFile && file_exists($thumbnailFile)) {

                        // set appropriate caching headers
                        // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
                        $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess
                        header("Cache-Control: public, max-age=" . $lifetime);
                        header("Expires: ". date("D, d M Y H:i:s T", time()+$lifetime));

                        $fileExtension = \Pimcore\File::getFileExtension($thumbnailFile);
                        if (in_array($fileExtension, ["gif", "jpeg", "jpeg", "png", "pjpeg"])) {
                            header("Content-Type: image/".$fileExtension, true);
                        } else {
                            header("Content-Type: " . $asset->getMimetype(), true);
                        }

                        header("Content-Length: " . filesize($thumbnailFile), true); while (@ob_end_flush()) ;
                        flush();

                        readfile($thumbnailFile);
                        exit;
                    }
                } catch (\Exception $e) {
                    // nothing to do
                    Logger::error("Thumbnail with name '" . $thumbnailName . "' doesn't exist");
                }
            }
        }
    }

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        if (!Asset\Image\Thumbnail::isPictureElementInUse()) {
            return;
        }

        if (!Asset\Image\Thumbnail::getEmbedPicturePolyfill()) {
            return;
        }

        if (!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }


        // analytics
        $body = $this->getResponse()->getBody();

        // search for the end <head> tag, and insert the google analytics code before
        // this method is much faster than using simple_html_dom and uses less memory
        $code = '<script type="text/javascript" src="/pimcore/static/js/frontend/picturePolyfill.min.js" defer></script>';
        $headEndPosition = stripos($body, "</head>");
        if ($headEndPosition !== false) {
            $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
        }

        $this->getResponse()->setBody($body);
    }
}
