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

namespace Pimcore\Workflow;

use Pimcore\Workflow\Notes\CustomHtmlServiceInterface;
use Pimcore\Workflow\Notes\NotesAwareInterface;
use Pimcore\Workflow\Notes\NotesAwareTrait;
use Symfony\Component\Workflow\WorkflowInterface;

class GlobalAction implements NotesAwareInterface
{
    use NotesAwareTrait;

    private string $name;

    /**
     * @var array
     */
    private $options;

    private ExpressionService $expressionService;

    private string $workflowName;

    public function __construct(string $name, array $options, ExpressionService $expressionService, string $workflowName, CustomHtmlServiceInterface $customHtmlService = null)
    {
        $this->name = $name;
        $this->options = $options;
        $this->expressionService = $expressionService;
        $this->workflowName = $workflowName;
        if ($customHtmlService instanceof CustomHtmlServiceInterface) {
            $this->setCustomHtmlService($customHtmlService);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?: $this->getName();
    }

    public function getIconClass(): string
    {
        return $this->options['iconClass'] ?? 'pimcore_icon_workflow_action';
    }

    /**
     * @return string|int|false
     */
    public function getObjectLayout(): bool|int|string
    {
        return $this->options['objectLayout'] ?: false;
    }

    public function getTos(): array
    {
        return $this->options['to'] ?? [];
    }

    public function getGuard(): ?string
    {
        return $this->options['guard'] ?? null;
    }

    public function isGuardValid(WorkflowInterface $workflow, object $subject): bool
    {
        if (empty($this->getGuard())) {
            return true;
        }

        return (bool)$this->expressionService->evaluateExpression($workflow, $subject, $this->getGuard());
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getSaveSubject(): bool
    {
        return $this->options['saveSubject'] ?? true;
    }
}
