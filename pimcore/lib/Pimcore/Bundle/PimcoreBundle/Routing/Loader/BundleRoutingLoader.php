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

namespace Pimcore\Bundle\PimcoreBundle\Routing\Loader;

use Pimcore\Config\BundleConfigLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class BundleRoutingLoader extends Loader
{
    /**
     * @var BundleConfigLocator
     */
    private $locator;

    /**
     * @param BundleConfigLocator $locator
     */
    public function __construct(BundleConfigLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @inheritDoc
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        $files      = $this->locator->locate('routing');

        if (empty($files)) {
            return $collection;
        }

        foreach ($files as $file) {
            $routes = $this->import($file);
            $collection->addCollection($routes);
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function supports($resource, $type = null)
    {
        return 'pimcore_bundle' === $type;
    }
}
