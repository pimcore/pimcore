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

namespace Pimcore\WorkflowManagement\Workflow\Manager;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Object\Concrete as ConcreteObject;
use Pimcore\Model\User;
use Pimcore\WorkflowManagement\Workflow;

class Factory
{
    /**
     * @static
     *
     * @param AbstractElement|Asset|Document|ConcreteObject $element
     * @param User $user
     *
     * @return \Pimcore\WorkflowManagement\Workflow\Manager
     */
    public static function getManager(AbstractElement $element, User $user = null)
    {
        $manager = new Workflow\Manager($element, $user);

        return $manager;
    }
}
