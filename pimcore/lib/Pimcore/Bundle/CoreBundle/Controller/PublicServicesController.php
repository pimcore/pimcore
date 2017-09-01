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

namespace Pimcore\Bundle\CoreBundle\Controller;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Site;
use Pimcore\Model\Tool;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as FrameworkController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicServicesController extends FrameworkController
{
    /**
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function thumbnailAction(Request $request)
    {
        $assetId = $request->get('assetId');
        $thumbnailName = $request->get('thumbnailName');
        $filename = $request->get('filename');

        if ($asset = Asset::getById($assetId)) {
            try {
                $page = 1; // default
                $thumbnailFile = null;
                $thumbnailConfig = null;

                //get page in case of an asset document (PDF, ...)
                if (preg_match("|~\-~page\-(\d+)\.|", $filename, $matchesThumbs)) {
                    $page = (int)$matchesThumbs[1];
                }

                // just check if the thumbnail exists -> throws exception otherwise
                $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($thumbnailName);

                if (!$thumbnailConfig) {
                    // check if there's an item in the TmpStore
                    $deferredConfigId = 'thumb_' . $assetId . '__' . md5(urldecode($request->getPathInfo()));
                    if ($thumbnailConfigItem = TmpStore::get($deferredConfigId)) {
                        $thumbnailConfig = $thumbnailConfigItem->getData();
                        TmpStore::delete($deferredConfigId);

                        if (!$thumbnailConfig instanceof Asset\Image\Thumbnail\Config) {
                            throw new \Exception("Deferred thumbnail config file doesn't contain a valid \\Asset\\Image\\Thumbnail\\Config object");
                        }
                    }
                }

                if (!$thumbnailConfig) {
                    throw $this->createNotFoundException("Thumbnail '" . $thumbnailName . "' file doesn't exist");
                }

                if ($asset instanceof Asset\Document) {
                    $thumbnailConfig->setName(preg_replace("/\-[\d]+/", '', $thumbnailConfig->getName()));
                    $thumbnailConfig->setName(str_replace('document_', '', $thumbnailConfig->getName()));

                    $thumbnailFile = $asset->getImageThumbnail($thumbnailConfig, $page)->getFileSystemPath();
                } elseif ($asset instanceof Asset\Image) {
                    //check if high res image is called

                    preg_match("@([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@", $filename, $matches);

                    if (array_key_exists(2, $matches) && $matches[2]) {
                        $highResFactor = (float) str_replace(['@', 'x'], '', $matches[2]);
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
                        'Cache-Control' => 'public, max-age=' . $lifetime,
                        'Expires' => date('D, d M Y H:i:s T', time() + $lifetime)
                    ]);
                }
            } catch (\Exception $e) {
                $message = "Thumbnail with name '" . $thumbnailName . "' doesn't exist";

                Logger::error($message);
                throw $this->createNotFoundException($message, $e);
            }
        }
    }

    /**
     * @param $request
     * @return Response
     */
    public function robotsTxtAction(Request $request) {

        // check for site
        $site = null;
        try {
            $domain = \Pimcore\Tool::getHostname();
            $site = Site::getByDomain($domain);
        } catch (\Exception $e) {
        }

        $siteSuffix = "-default";
        if ($site instanceof Site) {
            $siteSuffix = "-" . $site->getId();
        }

        // send correct headers
        header("Content-Type: text/plain; charset=utf8"); while (@ob_end_flush()) ;

        // check for configured robots.txt in pimcore
        $content = '';
        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots" . $siteSuffix . ".txt";
        if (is_file($robotsPath)) {
            $content = file_get_contents($robotsPath);
        }

        if(empty($content)){
            // default behavior, allow robots to index everything
            $content = "User-agent: *\nDisallow:";
        }

        return new Response($content, 200);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function commonFilesAction(Request $request)
    {
        return new Response("HTTP/1.1 404 Not Found\nFiltered by common files filter", 404);
    }

    /**
     * @param Request $request
     */
    public function hybridauthAction(Request $request)
    {
        \Pimcore\Tool\HybridAuth::process();
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function qrcodeAction(Request $request)
    {
        $code = Tool\Qrcode\Config::getByName($request->get('key'));
        if ($code) {
            $url = $code->getUrl();
            if ($code->getGoogleAnalytics()) {
                $glue = '?';
                if (strpos($url, '?')) {
                    $glue = '&';
                }

                $url .= $glue;
                $url .= 'utm_source=Mobile&utm_medium=QR-Code&utm_campaign=' . $code->getName();
            }

            return $this->redirect($url);
        } else {
            Logger::error("called an QR code but '" . $request->get('key') . ' is not a code in the system.');
        }
    }
}
