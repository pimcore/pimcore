<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Pimcore\Logger;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
trait MaintenanceTaskHandlerTrait
{
    /**
     * @var bool
     */
    protected bool $excluded;


    public function isExcluded(): bool
    {
        return $this->excluded;
    }

    public function setExcluded(bool $exclude): void
    {
        $this->excluded = $exclude;
    }
}
