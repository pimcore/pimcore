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

namespace Pimcore\Event\System;

use Pimcore\Model\Schedule\Manager\Procedural;
use Symfony\Component\EventDispatcher\Event;

class MaintenanceEvent extends Event
{

    /**
     * @var Procedural
     */
    protected $manager;

    /**
     * MaintenanceEvent constructor.
     * @param Procedural $manager
     */
    public function __construct(Procedural $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return Procedural
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Procedural $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }
}
