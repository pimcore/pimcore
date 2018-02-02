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

namespace Pimcore\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface RouteReferenceInterface
{
    /**
     * Get route name
     *
     * @return string
     */
    public function getRoute();

    /**
     * Get parameters to use when generating the route
     *
     * @return array
     */
    public function getParameters();

    /**
     * Get route type - directly passed to URL generator
     *
     * @see UrlGeneratorInterface
     *
     * @return int
     */
    public function getType();
}
