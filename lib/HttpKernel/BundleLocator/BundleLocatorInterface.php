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
     * @deprecated will be removed in 13.0. Use "getBundle()->getPath()" instead.
     *
     * @throws NotFoundException
     */
    public function getBundlePath(object|string $class): string;
}
