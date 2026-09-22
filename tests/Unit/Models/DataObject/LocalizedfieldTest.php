<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for the "Call to a member function getClass() on null" fatal error that
 * occurred when a Localizedfield carrying a block context has no object back-reference set
 * (e.g. while building cache tags for a Block containing nested localizedfields).
 *
 * @see https://github.com/pimcore/service-operations/issues/837
 *
 * @group model.datatype.localizedfield
 */
class LocalizedfieldTest extends TestCase
{
    private function buildBlockContextField(): Localizedfield
    {
        $field = new Localizedfield();
        $field->setContext([
            'containerType' => 'block',
            'containerKey' => 'testblock',
            'fieldname' => 'testblock',
        ]);

        return $field;
    }

    /**
     * getFieldDefinition() must not dereference a missing object in the block branch.
     */
    public function testGetFieldDefinitionReturnsNullWhenObjectMissingInBlockContext(): void
    {
        $field = $this->buildBlockContextField();

        self::assertNull($field->getObject());
        self::assertNull($field->getFieldDefinition('someField', $field->getContext()));
    }

    /**
     * getInternalData() traverses getFieldDefinitions() (the frame that crashed at
     * Localizedfield.php:348); with no object it must return the raw items instead of fataling.
     */
    public function testGetInternalDataDoesNotFatalWhenObjectMissingInBlockContext(): void
    {
        $field = $this->buildBlockContextField();

        self::assertNull($field->getObject());
        self::assertSame([], $field->getInternalData());
    }
}
