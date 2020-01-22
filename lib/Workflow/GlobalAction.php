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

use Pimcore\Workflow\Notes\NotesAwareInterface;
use Pimcore\Workflow\Notes\NotesAwareTrait;
use Symfony\Component\Workflow\Workflow;

class GlobalAction implements NotesAwareInterface
{
    use NotesAwareTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ExpressionService
     */
    private $expressionService;

    /** @var string */
    private $workflowName;

    public function __construct(string $name, array $options, ExpressionService $expressionService, string $workflowName)
    {
        $this->name = $name;
        $this->options = $options;
        $this->expressionService = $expressionService;
        $this->workflowName = $workflowName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
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
    public function getObjectLayout()
    {
        return $this->options['objectLayout'] ?: false;
    }

    /**
     * @return array
     */
    public function getTos(): array
    {
        return $this->options['to'] ?? [];
    }

    /**
     * @return null|string
     */
    public function getGuard(): ?string
    {
        return $this->options['guard'] ?? null;
    }

    /**
     * @return bool
     */
    public function isGuardValid(Workflow $workflow, $subject): bool
    {
        if (empty($this->getGuard())) {
            return true;
        }

        return $this->expressionService->evaluateExpression($workflow, $subject, $this->getGuard());
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }
}
