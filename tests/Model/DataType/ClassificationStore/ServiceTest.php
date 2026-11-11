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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;

class ServiceTest extends ModelTestCase
{
    /**
     * Classification store key names are labels referenced by keyId — unlike class field
     * names they never reach generated PHP classes or DDL, so names that are not valid
     * identifiers (e.g. "Voltage Type") must keep resolving to a field definition.
     */
    public function testFieldDefinitionAcceptsKeyNamesThatAreNotValidIdentifiers(): void
    {
        $definition = Service::getFieldDefinitionFromJson(
            ['fieldtype' => 'input', 'name' => 'Voltage Type'],
            'input'
        );

        $this->assertInstanceOf(Input::class, $definition);
        $this->assertSame('Voltage Type', $definition->getName());
    }
}
