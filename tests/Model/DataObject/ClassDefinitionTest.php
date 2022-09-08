<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tests\Test\ModelTestCase;

/**
 * Class ObjectTest
 *
 * @package Pimcore\Tests\Model\DataObject
 *
 * @group model.dataobject.object
 */
class ClassDefinitionTest extends ModelTestCase
{
    /**
     * Verifies that the class definition gets renamed properly
     */
    public function testRename()
    {
        $class = ClassDefinition::getByName('unittest');
        $class->rename('unittest_renamed');

        $renamedClass = ClassDefinition::getByName('unittest_renamed');
        $renamedClass->rename('unittest');
    }
}
