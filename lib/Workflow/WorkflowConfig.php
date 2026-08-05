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

namespace Pimcore\Workflow;

class WorkflowConfig
{
    private string $name;

    private array $workflowConfigArray;

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

    public function getWorkflowConfigArray(): array
    {
        return $this->workflowConfigArray;
    }
}
