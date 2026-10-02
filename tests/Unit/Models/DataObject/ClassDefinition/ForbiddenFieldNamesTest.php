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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;

/**
 * Members of the element base classes must not be usable as data object field names: the
 * generated class would declare a property, getter and setter of the same name and break on
 * load. This covers the members AbstractElement uses to carry a pending workflow marking.
 */
class ForbiddenFieldNamesTest extends TestCase
{
    #[DataProvider('forbiddenNameProvider')]
    public function testElementMemberNamesAreForbidden(string $name): void
    {
        $field = new Input();
        $field->setName($name);

        $this->assertTrue($field->isForbiddenName(), sprintf('"%s" collides with a member of the element base classes.', $name));
    }

    public static function forbiddenNameProvider(): array
    {
        return [
            'pending workflow marking (getter/setter)' => ['pendingWorkflowMarking'],
            'pending workflow markings (property/getter)' => ['pendingWorkflowMarkings'],
            'case-insensitive' => ['PendingWorkflowMarkings'],
            'existing entry, for reference' => ['dependencies'],
        ];
    }

    public function testOrdinaryNamesAreAllowed(): void
    {
        $field = new Input();
        $field->setName('workflowStatus');

        $this->assertFalse($field->isForbiddenName());
    }
}
