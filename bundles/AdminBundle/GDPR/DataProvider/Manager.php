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
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\DependencyInjection\CollectionServiceLocator;

class Manager
{
    /**
     * @var CollectionServiceLocator
     */
    private $services;

    /**
     * @var array
     */
    private $sortedServices;

    public function __construct(CollectionServiceLocator $services)
    {
        $this->services = $services;
    }

    /**
     * Returns registered services in sorted order
     *
     * @return DataProviderInterface[]
     */
    public function getServices(): array
    {
        if (null !== $this->sortedServices) {
            return $this->sortedServices;
        }

        $this->sortedServices = $this->services->all();

        usort($this->sortedServices, function (DataProviderInterface $left, DataProviderInterface $right) {
            return $left->getSortPriority() > $right->getSortPriority();
        });

        return $this->sortedServices;
    }
}
