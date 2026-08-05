<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
