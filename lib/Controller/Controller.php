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

namespace Pimcore\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;

abstract class Controller extends BaseController
{
    /**
     * @param ContainerInterface $container
     *
     * @return ContainerInterface
     */
    public function setContainer(ContainerInterface $container)
    {
        return parent::setContainer($container);
    }

    /**
     * Gets a container configuration parameter by its name.
     *
     * @param string $name
     *
     * @return mixed
     *
     */
    protected function getParameter(string $name)
    {
        if ($this->container instanceof \Symfony\Component\DependencyInjection\ContainerInterface) {
            return $this->container->getParameter($name);
        } elseif (method_exists(get_parent_class($this), 'getParameter')) {
            return parent::getParameter($name);
        }
    }
}
