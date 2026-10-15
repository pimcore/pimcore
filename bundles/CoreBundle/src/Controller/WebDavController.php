<?php

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

/**
 * @internal
 */
class WebDavController extends Controller
{
    public function webdavAction(): void
    {
        try {
            $server = Asset\WebDAV\Service::createServer($this->generateUrl('pimcore_webdav', ['path' => '/']));
            $server->start();
        } catch (Exception $e) {
            Logger::error((string)$e);
        }

        exit;
    }
}
