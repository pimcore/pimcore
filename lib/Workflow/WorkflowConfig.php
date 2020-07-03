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

namespace Pimcore\Workflow;

class WorkflowConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $workflowConfigArray;

    public function __construct(string $name, array $workflowConfigArray)
    {
        $this->name = $name;
        $this->workflowConfigArray = $workflowConfigArray;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->workflowConfigArray['label'] ?? $this->name;
    }

    public function getPriority(): int
    {
        return $this->workflowConfigArray['priority'];
    }

    public function getType(): string
    {
        return $this->workflowConfigArray['type'];
    }

    /**
     * @return array
     */
    public function getWorkflowConfigArray(): array
    {
        return $this->workflowConfigArray;
    }
}
