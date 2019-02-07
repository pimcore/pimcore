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

namespace Pimcore\Event\Workflow;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Workflow\GlobalAction;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Workflow\Workflow;

class GlobalActionEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @var mixed
     */
    protected $subject;

    /**
     * @var GlobalAction
     */
    protected $globalAction;

    /**
     * DocumentEvent constructor.
     *
     * @param Workflow $workflow
     * @param mixed $subject
     * @param GlobalAction $globalAction
     * @param array $arguments
     */
    public function __construct(Workflow $workflow, $subject, GlobalAction $globalAction, array $arguments = [])
    {
        $this->workflow = $workflow;
        $this->subject = $subject;
        $this->globalAction = $globalAction;
        $this->arguments = $arguments;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return GlobalAction
     */
    public function getGlobalAction()
    {
        return $this->globalAction;
    }
}
