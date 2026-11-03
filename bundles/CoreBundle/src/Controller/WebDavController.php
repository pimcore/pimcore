<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Controller;

use Exception;
use Pimcore\Controller\Controller;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Sabre\DAV\Exception\NotFound;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
class WebDavController extends Controller
{
    public function __construct(
        #[Autowire('%pimcore.assets.webdav.browser_plugin%')]
        private readonly bool $browserPluginEnabled,
    ) {
    }

    public function webdavAction(): void
    {
        try {
            $server = Asset\WebDAV\Service::createServer(
                $this->generateUrl('pimcore_webdav', ['path' => '/']),
                $this->browserPluginEnabled
            );
        } catch (NotFound $e) {
            Logger::error('WebDAV: home directory asset (ID 1) not found');

            // let Symfony emit a proper 404 instead of exiting with an empty 200 response
            throw new NotFoundHttpException('WebDAV root not found', $e);
        }

        try {
            $server->start();
        } catch (Exception $e) {
            Logger::error((string)$e);
        }

        exit;
    }
}
