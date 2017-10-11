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

namespace Pimcore\Locator;

use Psr\Container\ContainerInterface;

final class ServiceLocatorFacade implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $serviceContainer;

    /**
     * @param ContainerInterface $serviceContainer
     */
    public function __construct(ContainerInterface $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->serviceContainer->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->serviceContainer->has($id);
    }
}
