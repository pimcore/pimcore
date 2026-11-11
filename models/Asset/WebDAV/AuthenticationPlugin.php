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

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\Tool\Admin;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Rejects every anonymous WebDAV request before Sabre dispatches the method. Not all methods
 * resolve a node through Tree::getNodeForPath() - UNLOCK, for one, only talks to the lock backend -
 * so the check in the tree alone is not a sufficient authentication boundary.
 *
 * @internal
 */
final class AuthenticationPlugin extends ServerPlugin
{
    public function initialize(Server $server): void
    {
        // runs ahead of the default priority (100) used by Sabre's core and lock plugins
        $server->on('beforeMethod:*', [$this, 'beforeMethod'], 10);
    }

    /**
     * Server::start() turns the exception into the response itself, so the HTTP Basic challenge has
     * to be added here - otherwise clients never resend the request with credentials.
     *
     * @throws NotAuthenticated
     */
    public function beforeMethod(RequestInterface $request, ResponseInterface $response): void
    {
        if (Admin::getCurrentUser() === null) {
            $response->addHeader('WWW-Authenticate', 'Basic realm="Pimcore WebDAV", charset="UTF-8"');

            throw new NotAuthenticated('No authenticated user available');
        }
    }

    public function getPluginName(): string
    {
        return 'pimcore-authentication';
    }
}
