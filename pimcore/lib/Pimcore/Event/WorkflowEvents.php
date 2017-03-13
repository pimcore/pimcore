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

namespace Pimcore\Event;

final class WorkflowEvents
{
    /**
     * Fired BEFORE any action happens in the workflow. use this to hook into actions globally and define
     * your own logic. i.e. validation or checks on other system vars
     *
     * Arguments:
     *  - actionName | string | name of the action
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const PRE_ACTION = 'pimcore.workflowmanagement.preAction';

    /**
     * 	Fired AFTER any action happens in the workflow. Use this to hook into actions globally and
     * define your own logic. i.e. trigger an email or maintenance job.
     *
     * Arguments:
     *  - actionName | string | name of the action
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const POST_ACTION = 'pimcore.workflowmanagement.postAction';

    /**
     * Fired when returning the available actions to a user in the admin panel. use this to further customise what
     * actions are available to a user. i.e. stop them logging time after 5pm ;)
     *
     * Arguments:
     *  - actions | array | name of allowed actions
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const PRE_RETURN_AVAILABLE_ACTIONS = "pimcore.workflowmanagement.preReturnAvailableActions";

    /**
     * Arguments:
     *  - actionConfig | array
     *  - data | array
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const ACTION_BEFORE = "pimcore.workflowmanagement.action.before";

    /**
     * Arguments:
     *  - actionConfig | array
     *  - data | array
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const ACTION_SUCCESS = "pimcore.workflowmanagement.action.success";

    /**
     * Arguments:
     *  - actionConfig | array
     *  - data | array
     *  - exception | \Exception
     *
     * @Event("Pimcore\Event\Model\WorkflowEvent")
     * @var string
     */
    const ACTION_FAILURE = "pimcore.workflowmanagement.action.failure";
}
