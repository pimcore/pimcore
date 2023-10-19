<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\HttpKernel\BundleLocator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface BundleLocatorInterface
{
    /**
     * Loads bundle for a class name. Returns the AppBundle for AppBundle\Controller\FooController
     *
     *
     *
     * @throws NotFoundException
     */
    public function getBundle(object|string $class): BundleInterface;

    /**
     * Resolves bundle directory from a class name.
     *
     * AppBundle\Controller\FooController returns src/AppBundle
     *
     *
     *
     * @throws NotFoundException
     */
    public function getBundlePath(object|string $class): string;
}
