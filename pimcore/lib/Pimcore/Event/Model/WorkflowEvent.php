<?php

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\WorkflowManagement\Workflow\Manager;
use Symfony\Component\EventDispatcher\Event;

class WorkflowEvent extends Event {

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
    function __construct(Manager $workflowManager, array $arguments = [])
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