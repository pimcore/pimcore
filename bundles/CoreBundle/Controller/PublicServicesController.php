<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Controller;

use Pimcore\Config;
use Pimcore\Controller\Controller;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Site;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener;

/**
 * @internal
 */
class PublicServicesController extends Controller
{
    /**
     * @param Request $request
     *
     * @return BinaryFileResponse|StreamedResponse
     */
    public function thumbnailAction(Request $request)
    {
        $assetId = $request->get('assetId');
        $thumbnailName = $request->get('thumbnailName');
        $filename = $request->get('filename');
        $requestedFileExtension = strtolower(File::getFileExtension($filename));
        $asset = Asset::getById($assetId);

        $prefix = preg_replace('@^cache-buster\-[\d]+\/@', '', $request->get('prefix'));

        if ($asset && $asset->getPath() === ('/' . $prefix)) {
            // we need to check the path as well, this is important in the case you have restricted the public access to
            // assets via rewrite rules
            try {
                $imageThumbnail = null;
                $thumbnailStream = null;

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
                    $thumbnailStream = $imageThumbnail->getStream();
                } elseif ($asset instanceof Asset\Document) {
                    $page = 1;
                    if (preg_match("|~\-~page\-(\d+)\.|", $filename, $matchesThumbs)) {
                        $page = (int)$matchesThumbs[1];
                    }

                    $thumbnailConfig->setName(preg_replace("/\-[\d]+/", '', $thumbnailConfig->getName()));
                    $thumbnailConfig->setName(str_replace('document_', '', $thumbnailConfig->getName()));

                    $imageThumbnail = $asset->getImageThumbnail($thumbnailConfig, $page);
                    $thumbnailStream = $imageThumbnail->getStream();
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
                    $thumbnailStream = $imageThumbnail->getStream();
                }

                if ($imageThumbnail && $thumbnailStream) {
                    $pathReference = $imageThumbnail->getPathReference();
                    $actualFileExtension = File::getFileExtension($pathReference['src']);

                    if ($actualFileExtension !== $requestedFileExtension) {
                        // create a copy/symlink to the file with the original file extension
                        // this can be e.g. the case when the thumbnail is called as foo.png but the thumbnail config
                        // is set to auto-optimized format so the resulting thumbnail can be jpeg
                        $requestedFile = preg_replace('/\.' . $actualFileExtension . '$/', '.' . $requestedFileExtension, $pathReference['src']);
                        Storage::get('thumbnail')->writeStream($requestedFile, $thumbnailStream);
                    }

                    // set appropriate caching headers
                    // see also: https://github.com/pimcore/pimcore/blob/1931860f0aea27de57e79313b2eb212dcf69ef13/.htaccess#L86-L86
                    $lifetime = 86400 * 7; // 1 week lifetime, same as direct delivery in .htaccess

                    $headers = [
                        'Cache-Control' => 'public, max-age=' . $lifetime,
                        'Expires' => date('D, d M Y H:i:s T', time() + $lifetime),
                        'Content-Type' => $imageThumbnail->getMimeType(),
                        'Content-Length' => fstat($thumbnailStream)['size'],
                    ];

                    $headers[AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER] = true;

                    return new StreamedResponse(function () use ($thumbnailStream) {
                        fpassthru($thumbnailStream);
                    }, 200, $headers);
                }

                throw new \Exception('Unable to generate thumbnail, see logs for details.');
            } catch (\Exception $e) {
                $message = "Thumbnail with name '" . $thumbnailName . "' doesn't exist";
                Logger::error($message);

                return new BinaryFileResponse(PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg', 200);
            }
        }

        throw $this->createNotFoundException('Asset not found');
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
        if (!empty($customAdminPathIdentifier) && $request->cookies->get('pimcore_custom_admin') != $customAdminPathIdentifier) {
            $redirect->headers->setCookie(new Cookie('pimcore_custom_admin', $customAdminPathIdentifier, strtotime('+1 year'), '/', null, false, true));
        }

        return $redirect;
    }
}
