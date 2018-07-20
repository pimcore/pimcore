<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\Storage\Cookie;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CookieSaveHandlerInterface
{
    /**
     * Loads data from cookie
     *
     * @param Request $request
     * @param string $scope
     * @param string $name
     *
     * @return array
     */
    public function load(Request $request, string $scope, string $name): array;

    /**
     * Saves data to cookie
     *
     * @param Response $response
     * @param string $scope
     * @param string $name
     * @param int|string|\DateTimeInterface $expire
     * @param array|null $data
     */
    public function save(Response $response, string $scope, string $name, $expire, $data);
}
