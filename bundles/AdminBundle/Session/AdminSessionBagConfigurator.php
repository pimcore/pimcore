<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Session;

use Pimcore\Session\Attribute\LockableAttributeBag;
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminSessionBagConfigurator implements SessionConfiguratorInterface
{
    /**
     * Attribute bag configuration
     *
     * @var array
     */
    private $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        foreach ($this->config as $name => $config) {
            $bag = new LockableAttributeBag($config['storage_key']);
            $bag->setName($name);

            $session->registerBag($bag);
        }
    }
}
