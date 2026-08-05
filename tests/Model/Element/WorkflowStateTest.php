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

namespace Pimcore\Tests\Model\Element;

use Pimcore\Model\Element\WorkflowState;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class WorkflowStateTest
 *
 * @package Pimcore\Tests\Model\Element
 *
 * @group model.element.workflowstate
 */
class WorkflowStateTest extends ModelTestCase
{
    /**
     * Deleting one WorkflowState row must not delete other rows that belong
     * to the same element under a different workflow.
     */
    public function testDeleteIsScopedToWorkflow(): void
    {
        $object = TestHelper::createEmptyObject();

        $first = new WorkflowState();
        $first->setCid($object->getId());
        $first->setCtype('object');
        $first->setWorkflow('workflow_a');
        $first->setPlace('place_a');
        $first->save();

        $second = new WorkflowState();
        $second->setCid($object->getId());
        $second->setCtype('object');
        $second->setWorkflow('workflow_b');
        $second->setPlace('place_b');
        $second->save();

        $loaded = WorkflowState::getByPrimary($object->getId(), 'object', 'workflow_a');
        $this->assertNotNull($loaded);
        $loaded->delete();

        $this->assertNull(
            WorkflowState::getByPrimary($object->getId(), 'object', 'workflow_a'),
            'The targeted row should be deleted.'
        );

        $survivor = WorkflowState::getByPrimary($object->getId(), 'object', 'workflow_b');
        $this->assertNotNull($survivor, 'The other workflow\'s row must not be deleted.');
        $this->assertSame('place_b', $survivor->getPlace());
    }
}
