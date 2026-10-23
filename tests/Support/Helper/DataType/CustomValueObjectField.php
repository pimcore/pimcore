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

namespace Pimcore\Tests\Support\Helper\DataType;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * A custom field type whose value is a CustomValueObject, registered as `customValueObject` in the
 * test container's class definition data map. Its normalized form is a plain array; the block
 * marshaller CustomValueObjectBlockMarshaller turns that back into the value object for the block
 * resource blob.
 */
final class CustomValueObjectField extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    NormalizerInterface
{
    public function getFieldType(): string
    {
        return 'customValueObject';
    }

    public function getColumnType(): string
    {
        return 'varchar(255)';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getDataForResource(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $data instanceof CustomValueObject ? $data->getText() : null;
    }

    public function getDataFromResource(mixed $data, ?Concrete $object = null, array $params = []): ?CustomValueObject
    {
        return is_string($data) ? new CustomValueObject($data) : null;
    }

    public function getDataForQueryResource(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataFromEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?CustomValueObject
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        return $value instanceof CustomValueObject ? ['text' => $value->getText()] : null;
    }

    public function denormalize(mixed $value, array $params = []): ?CustomValueObject
    {
        return is_array($value) && isset($value['text']) ? new CustomValueObject((string) $value['text']) : null;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . CustomValueObject::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . CustomValueObject::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . CustomValueObject::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . CustomValueObject::class . '|null';
    }

    public function isEmpty(mixed $data): bool
    {
        return !$data instanceof CustomValueObject || $data->getText() === '';
    }
}
