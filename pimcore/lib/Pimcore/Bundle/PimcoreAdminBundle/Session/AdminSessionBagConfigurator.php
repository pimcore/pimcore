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

namespace Pimcore\Bundle\PimcoreAdminBundle\Session;

use Pimcore\Session\Attribute\LockableAttributeBag;
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminSessionBagConfigurator implements SessionConfiguratorInterface
{
    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        $this->registerBag($session, 'pimcore_admin');
        $this->registerBag($session, 'pimcore_documents');
        $this->registerBag($session, 'pimcore_objects');
        $this->registerBag($session, 'pimcore_copy');
        $this->registerBag($session, 'pimcore_backup');
    }

    /**
     * Create and register an attribute bag
     *
     * @param SessionInterface $session
     * @param string $name
     */
    protected function registerBag(SessionInterface $session, $name)
    {
        $bag = new LockableAttributeBag('_' . $name);
        $bag->setName($name);

        $session->registerBag($bag);
    }
}
