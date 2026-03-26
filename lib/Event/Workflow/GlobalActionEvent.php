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

namespace Pimcore\Event\Workflow;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Workflow\GlobalAction;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GlobalActionEvent extends Event
{
    use ArgumentsAwareTrait;

    protected WorkflowInterface $workflow;

    protected mixed $subject = null;

    protected GlobalAction $globalAction;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(
        WorkflowInterface $workflow,
        mixed $subject,
        GlobalAction $globalAction,
        array $arguments = [])
    {
        $this->workflow = $workflow;
        $this->subject = $subject;
        $this->globalAction = $globalAction;
        $this->arguments = $arguments;
    }

    public function getWorkflow(): WorkflowInterface
    {
        return $this->workflow;
    }

    public function getSubject(): mixed
    {
        return $this->subject;
    }

    public function getGlobalAction(): GlobalAction
    {
        return $this->globalAction;
    }
}
