<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Event\Model\DataObject\ClassificationStore;

use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Symfony\Contracts\EventDispatcher\Event;

class GroupConfigEvent extends Event
{
    protected GroupConfig $groupConfig;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(GroupConfig $groupConfig)
    {
        $this->groupConfig = $groupConfig;
    }

    public function getGroupConfig(): GroupConfig
    {
        return $this->groupConfig;
    }

    public function setGroupConfig(GroupConfig $groupConfig): void
    {
        $this->groupConfig = $groupConfig;
    }
}
