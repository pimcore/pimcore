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
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Traits;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\Request;

trait ApplySchedulerDataTrait
{
    /**
     * @param Request $request
     * @param ElementInterface $element
     */
    protected function applySchedulerDataToElement(Request $request, ElementInterface $element)
    {
        /** @var AdminController $this */

        // scheduled tasks
        if ($request->get('scheduler')) {
            $tasks = [];
            $tasksData = $this->decodeJson($request->get('scheduler'));

            if (!empty($tasksData)) {
                foreach ($tasksData as $taskData) {
                    $taskData['date'] = strtotime($taskData['date'] . ' ' . ($taskData['time'] ?? ''));
                    $taskData['userId'] = $this->getAdminUser()->getId();

                    $task = new Task($taskData);
                    $tasks[] = $task;
                }
            }

            if ($element->isAllowed('settings') && method_exists($element, 'setScheduledTasks')) {
                $element->setScheduledTasks($tasks);
            }
        }
    }
}
