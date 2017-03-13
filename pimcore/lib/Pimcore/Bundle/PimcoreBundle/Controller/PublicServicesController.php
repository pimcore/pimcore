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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as FrameworkController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Model\Tool;

class PublicServicesController extends FrameworkController {

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function thumbnailAction(Request $request) {


        $assetId = $request->get("assetId");
        $thumbnailName = $request->get("thumbnailName");
        $filename = $request->get("filename");

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
                    $deferredConfigId = "thumb_" . $assetId . "__" . md5($request->getPathInfo());
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

                    preg_match("@([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@", $filename, $matches);

                    if (array_key_exists(2, $matches)) {
                        $highResFactor = (float) str_replace(["@", "x"], "", $matches[2]);
                        $thumbnailConfig->setHighResolution($highResFactor);
                    }

                    // check if a media query thumbnail was requested
                    if (preg_match("#~\-~([\d]+w)#", $matches[1], $mediaQueryResult)) {
                        $thumbnailConfig->selectMedia($mediaQueryResult[1]);
                    }

                    $thumbnailFile = $asset->getThumbnail($thumbnailConfig)->getFileSystemPath();
                }

                if ($thumbnailFile && file_exists($thumbnailFile)) {

                    // set appropriate caching headers
                    // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
                    $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess

                    return new BinaryFileResponse($thumbnailFile, 200, [
                        "Cache-Control" => "public, max-age=" . $lifetime,
                        "Expires" => date("D, d M Y H:i:s T", time()+$lifetime)
                    ]);
                }
            } catch (\Exception $e) {
                // nothing to do
                Logger::error("Thumbnail with name '" . $thumbnailName . "' doesn't exist");
            }
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function commonFilesAction(Request $request) {
        return new Response("HTTP/1.1 404 Not Found\nFiltered by common files filter", 404);
    }


    /**
     * @param Request $request
     */
    public function hybridauthAction(Request $request) {
        \Pimcore\Tool\HybridAuth::process();
    }

    /**
     * @param Request $request
     */
    public function qrcodeAction(Request $request) {
        $code = Tool\Qrcode\Config::getByName($request->get("key"));
        if ($code) {
            $url = $code->getUrl();
            if ($code->getGoogleAnalytics()) {
                $glue = "?";
                if (strpos($url, "?")) {
                    $glue = "&";
                }

                $url .= $glue;
                $url .= "utm_source=Mobile&utm_medium=QR-Code&utm_campaign=" . $code->getName();
            }

            return $this->redirect($url);
        } else {
            Logger::error("called an QR code but '" . $request->get("key") . " is not a code in the system.");
        }
    }
}
