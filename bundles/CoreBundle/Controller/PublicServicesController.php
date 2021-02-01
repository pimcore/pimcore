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

use Pimcore\Config;
use Pimcore\Controller\Controller;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Site;
use Pimcore\Model\Tool;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener;

class PublicServicesController extends Controller
{
    /**
     * @param Request $request
     * @param SessionListener $sessionListener
     *
     * @return BinaryFileResponse
     */
    public function thumbnailAction(Request $request, SessionListener $sessionListener)
    {
        $errorImage = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
        $assetId = $request->get('assetId');
        $thumbnailName = $request->get('thumbnailName');
        $filename = $request->get('filename');
        $requestedFileExtension = strtolower(File::getFileExtension($filename));
        $asset = Asset::getById($assetId);

        $prefix = preg_replace('@^cache-buster\-[\d]+\/@', '', $request->get('prefix'));

        if ($asset && $asset->getPath() == ('/' . $prefix)) {
            // we need to check the path as well, this is important in the case you have restricted the public access to
            // assets via rewrite rules
            try {
                $imageThumbnail = null;
                $thumbnailFile = null;
                $thumbnailConfig = null;

                // just check if the thumbnail exists -> throws exception otherwise
                $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($thumbnailName);

                if (!$thumbnailConfig) {
                    // check if there's an item in the TmpStore
                    // remove an eventually existing cache-buster prefix first (eg. when using with a CDN)
                    $pathInfo = preg_replace('@^/cache-buster\-[\d]+@', '', $request->getPathInfo());
                    $deferredConfigId = 'thumb_' . $assetId . '__' . md5(urldecode($pathInfo));
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

                if (strcasecmp($thumbnailConfig->getFormat(), 'SOURCE') === 0) {
                    $formatOverride = $requestedFileExtension;
                    if (in_array($requestedFileExtension, ['jpg', 'jpeg'])) {
                        $formatOverride = 'pjpeg';
                    }
                    $thumbnailConfig->setFormat($formatOverride);
                }

                if ($asset instanceof Asset\Video) {
                    $time = 1;
                    if (preg_match("|~\-~time\-(\d+)\.|", $filename, $matchesThumbs)) {
                        $time = (int)$matchesThumbs[1];
                    }

                    $imageThumbnail = $asset->getImageThumbnail($thumbnailConfig, $time);
                    $thumbnailFile = $imageThumbnail->getFileSystemPath();
                } elseif ($asset instanceof Asset\Document) {
                    $page = 1;
                    if (preg_match("|~\-~page\-(\d+)\.|", $filename, $matchesThumbs)) {
                        $page = (int)$matchesThumbs[1];
                    }

                    $thumbnailConfig->setName(preg_replace("/\-[\d]+/", '', $thumbnailConfig->getName()));
                    $thumbnailConfig->setName(str_replace('document_', '', $thumbnailConfig->getName()));

                    $imageThumbnail = $asset->getImageThumbnail($thumbnailConfig, $page);
                    $thumbnailFile = $imageThumbnail->getFileSystemPath();
                } elseif ($asset instanceof Asset\Image) {
                    //check if high res image is called

                    preg_match("@([^\@]+)(\@[0-9.]+x)?\.([a-zA-Z]{2,5})@", $filename, $matches);

                    if (array_key_exists(2, $matches) && $matches[2]) {
                        $highResFactor = (float) str_replace(['@', 'x'], '', $matches[2]);
                        $thumbnailConfig->setHighResolution($highResFactor);
                    }

                    // check if a media query thumbnail was requested
                    if (preg_match("#~\-~media\-\-(.*)\-\-query#", $matches[1], $mediaQueryResult)) {
                        $thumbnailConfig->selectMedia($mediaQueryResult[1]);
                    }

                    $imageThumbnail = $asset->getThumbnail($thumbnailConfig);
                    $thumbnailFile = $imageThumbnail->getFileSystemPath();
                }

                if ($imageThumbnail && $thumbnailFile && file_exists($thumbnailFile)) {
                    $actualFileExtension = File::getFileExtension($thumbnailFile);

                    if ($actualFileExtension !== $requestedFileExtension && $thumbnailFile != $errorImage) {
                        // create a copy/symlink to the file with the original file extension
                        // this can be e.g. the case when the thumbnail is called as foo.png but the thumbnail config
                        // is set to auto-optimized format so the resulting thumbnail can be jpeg
                        $requestedFile = preg_replace('/\.' . $actualFileExtension . '$/', '.' . $requestedFileExtension, $thumbnailFile);
                        $linked = is_link($requestedFile) || symlink($thumbnailFile, $requestedFile);
                        if (false === $linked) {
                            // create a hard copy
                            copy($thumbnailFile, $requestedFile);
                        }
                    }

                    // set appropriate caching headers
                    // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
                    $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess

                    $headers = [
                        'Cache-Control' => 'public, max-age=' . $lifetime,
                        'Expires' => date('D, d M Y H:i:s T', time() + $lifetime),
                        'Content-Type' => $imageThumbnail->getMimeType(),
                    ];

                    // in certain cases where an event listener starts a session (e.g. when there's a firewall
                    // configured for the entire site /*) the session event listener shouldn't modify the
                    // cache control headers of this response
                    if (defined('Symfony\Component\HttpKernel\EventListener\AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER')) {
                        // this method of bypassing the session listener was introduced in Symfony 4, so we need
                        // to check for the constant first
                        $headers[AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER] = true;
                    } else {
                        // @TODO to be removed in Pimcore 7
                        // Symfony 3.4 doesn't support bypassing the session listener, so we just remove it
                        \Pimcore::getEventDispatcher()->removeSubscriber($sessionListener);
                    }

                    return new BinaryFileResponse($thumbnailFile, 200, $headers);
                }
            } catch (\Exception $e) {
                $message = "Thumbnail with name '" . $thumbnailName . "' doesn't exist";
                Logger::error($message);
                throw $this->createNotFoundException($message, $e);
            }
        } else {
            throw $this->createNotFoundException('Asset not found');
        }

        throw $this->createNotFoundException('Unable to create image thumbnail');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function robotsTxtAction(Request $request)
    {
        // check for site
        $domain = \Pimcore\Tool::getHostname();
        $site = Site::getByDomain($domain);

        $config = Config::getRobotsConfig()->toArray();

        $siteId = 'default';
        if ($site instanceof Site) {
            $siteId = $site->getId();
        }

        // send correct headers
        header('Content-Type: text/plain; charset=utf8');
        while (@ob_end_flush()) ;

        // check for configured robots.txt in pimcore
        $content = '';
        if (array_key_exists($siteId, $config)) {
            $content = $config[$siteId];
        }

        if (empty($content)) {
            // default behavior, allow robots to index everything
            $content = "User-agent: *\nDisallow:";
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
        ]);
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
     *
     * @deprecated
     */
    public function hybridauthAction(Request $request)
    {
        \Pimcore\Tool\HybridAuth::process();
    }

    /**
     * @deprecated
     *
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

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function customAdminEntryPointAction(Request $request)
    {
        $params = $request->query->all();
        if (isset($params['token'])) {
            $url = $this->generateUrl('pimcore_admin_login_check', $params);
        } else {
            $url = $this->generateUrl('pimcore_admin_login', $params);
        }

        $redirect = new RedirectResponse($url);

        $customAdminPathIdentifier = $this->getParameter('pimcore_admin.custom_admin_path_identifier');
        if (isset($customAdminPathIdentifier) && $request->cookies->get('pimcore_custom_admin') != $customAdminPathIdentifier) {
            $redirect->headers->setCookie(new Cookie('pimcore_custom_admin', $customAdminPathIdentifier, strtotime('+1 year'), '/', null, false, true));
        }

        return $redirect;
    }
}
