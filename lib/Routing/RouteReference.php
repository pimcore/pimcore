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

class RouteReference implements RouteReferenceInterface
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var int
     */
    protected $type;

    /**
     * @param string $route
     * @param array $parameters
     * @param int $type
     */
    public function __construct($route, array $parameters = [], $type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $this->route      = $route;
        $this->parameters = $parameters;
        $this->type       = $type;
    }

    /**
     * @inheritDoc
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->type;
    }
}
