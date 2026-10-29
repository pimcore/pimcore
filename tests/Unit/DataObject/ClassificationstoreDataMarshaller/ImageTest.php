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

namespace Pimcore\Tests\Unit\DataObject\ClassificationstoreDataMarshaller;

use Pimcore;
use Pimcore\DataObject\ClassificationstoreDataMarshaller\Image as ImageMarshaller;
use Pimcore\Element\MarshallerService;
use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image as ImageFieldDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The locator key of a marshaller comes from the `key` attribute of its tag, not from its service
 * id, so only the booted container tells whether the field type reaches it.
 *
 * @group unit.model.datatype.classificationstore
 */
class ImageTest extends TestCase
{
    private const FORMAT = 'classificationstore';

    public function testTheMarshallerIsRegisteredUnderTheImageFieldType(): void
    {
        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);
        $fieldType = (new ImageFieldDefinition())->getFieldType();

        $this->assertTrue($marshallerService->supportsFielddefinition(self::FORMAT, $fieldType));
        $this->assertInstanceOf(
            MarshallerInterface::class,
            $marshallerService->buildFieldefinitionMarshaller(self::FORMAT, $fieldType)
        );
    }

    public function testAStoredValueThatIsNoAssetIdUnmarshalsToNull(): void
    {
        $marshaller = new ImageMarshaller();

        $this->assertNull($marshaller->unmarshal(['value' => '', 'value2' => null]));
        $this->assertNull($marshaller->unmarshal(['value' => 'not-an-id', 'value2' => null]));
    }
}
