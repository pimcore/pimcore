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

namespace Pimcore\Event;

final class WorkflowEvents
{
    /**
     * Fired BEFORE a global action happens in the workflow. use this to hook into actions globally and define
     * your own logic. i.e. validation or checks on other system vars
     *
     * @Event("Pimcore\Event\Workflow\GlobalActionEvent")
     *
     * @var string
     */
    const PRE_GLOBAL_ACTION = 'pimcore.workflow.preGlobalAction';

    /**
     * 	Fired AFTER a global action happens in the workflow. Use this to hook into actions globally and
     * define your own logic. i.e. trigger an email or maintenance job.
     *
     * @Event("Pimcore\Event\Workflow\GlobalActionEvent")
     *
     * @var string
     */
    const POST_GLOBAL_ACTION = 'pimcore.workflow.postGlobalAction';
}
