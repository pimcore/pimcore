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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\WorkflowManagement\Workflow\Manager;
use Symfony\Component\EventDispatcher\Event;

class WorkflowEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Manager
     */
    protected $workflowManager;

    /**
     * DocumentEvent constructor.
     * @param Manager $workflowManager
     * @param array $arguments
     */
    public function __construct(Manager $workflowManager, array $arguments = [])
    {
        $this->workflowManager = $workflowManager;
        $this->arguments = $arguments;
    }

    /**
     * @return Manager
     */
    public function getWorkflowManager()
    {
        return $this->workflowManager;
    }

    /**
     * @param Manager $workflowManager
     */
    public function setWorkflowManager($workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }
}
