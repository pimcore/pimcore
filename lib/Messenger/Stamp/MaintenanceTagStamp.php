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

namespace Pimcore\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class MaintenanceTagStamp implements StampInterface
{
    public function __construct(private string $tag)
    {
    }

    /**
     * Get the context related to a message.
     */
    public function getTag(): string
    {
        return $this->tag;
    }
}

