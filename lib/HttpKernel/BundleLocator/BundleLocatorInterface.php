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

namespace Pimcore\HttpKernel\BundleLocator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface BundleLocatorInterface
{
    /**
     * Loads bundle for a class name. Returns the AppBundle for AppBundle\Controller\FooController
     *
     * @param string|object $class
     *
     * @return BundleInterface
     *
     * @throws NotFoundException
     */
    public function getBundle($class): BundleInterface;

    /**
     * Resolves bundle directory from a class name.
     *
     * AppBundle\Controller\FooController returns src/AppBundle
     *
     * @param string|object $class
     *
     * @return string
     *
     * @throws NotFoundException
     */
    public function getBundlePath($class): string;
}
