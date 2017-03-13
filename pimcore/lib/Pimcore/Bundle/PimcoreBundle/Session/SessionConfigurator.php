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

namespace Pimcore\Bundle\PimcoreBundle\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Handles a collection of session configurators.
 */
class SessionConfigurator implements SessionConfiguratorInterface
{
    /**
     * @var SessionConfiguratorInterface[]
     */
    protected $configurators = [];

    /**
     * @param SessionConfiguratorInterface $configurator
     */
    public function addConfigurator(SessionConfiguratorInterface $configurator)
    {
        $this->configurators[] = $configurator;
    }

    /**
     * @param SessionConfiguratorInterface[] $configurators
     */
    public function setConfigurators(array $configurators)
    {
        $this->configurators = [];

        foreach ($configurators as $configurator) {
            $this->addConfigurator($configurator);
        }
    }

    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        foreach ($this->configurators as $configurator) {
            $configurator->configure($session);
        }
    }
}
