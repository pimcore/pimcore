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
use PDO;
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
        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset\WebDAV\Folder($homeDir);
            $objectTree = new Asset\WebDAV\Tree($publicDir);
            $server = new \Sabre\DAV\Server($objectTree);
            $server->setBaseUri($this->generateUrl('pimcore_webdav', ['path' => '/']));

            // lock plugin
            /** @var PDO $pdo */
            $pdo = \Pimcore\Db::get()->getNativeConnection();
            $lockBackend = new \Sabre\DAV\Locks\Backend\PDO($pdo);
            $lockBackend->tableName = 'webdav_locks';

            $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            // browser plugin
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

            $server->start();
        } catch (Exception $e) {
            Logger::error((string)$e);
        }

        exit;
    }
}
