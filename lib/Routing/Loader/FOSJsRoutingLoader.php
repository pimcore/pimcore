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

namespace Pimcore\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollection;

class FOSJsRoutingLoader extends Loader
{
    /**
     * @inheritDoc
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        $file = '@FOSJsRoutingBundle/Resources/config/routing/routing.xml';

        if (version_compare(Kernel::VERSION, '4.0') >= 0) {
            $file = '@FOSJsRoutingBundle/Resources/config/routing/routing-sf4.xml';
        }

        $routes = $this->import($file);
        $collection->addCollection($routes);

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function supports($resource, $type = null)
    {
        return 'fos_js_routing' === $type;
    }
}
