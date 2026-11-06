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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * A custom field type that declares `mixed` as its normalized return type and actually returns
 * whatever shape the value has - an array for a {@see CustomValueObject}, the value itself for
 * anything else. Registered as `mixedNormalizingObject` in the test container's class definition
 * data map. No classificationstore marshaller is registered for it.
 *
 * It exists because no shipped field type has this combination, and it is the combination that
 * separates what a field type DECLARES from what it RETURNS: a declaration of `mixed` is read as
 * scalar-compatible by
 * {@see \Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport::isHeldInValueColumns()} -
 * correctly, because a key definition has nothing but the declaration to go on - so a key with this
 * type as an `encryptedField` delegate is accepted on either engine and only the value itself says
 * that the two value columns cannot hold it.
 */
final class MixedNormalizingField extends Data implements NormalizerInterface
{
    public function getFieldType(): string
    {
        return 'mixedNormalizingObject';
    }

    public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $data instanceof CustomValueObject ? $data->getText() : null;
    }

    public function getDataFromEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?CustomValueObject
    {
        return is_string($data) ? new CustomValueObject($data) : null;
    }

    /**
     * A {@see CustomValueObject} normalizes to its array form; anything else normalizes to itself.
     *
     * The pass-through is the point, and it is why this type is `mixed` rather than a declared
     * shape: it is the one type whose values are sometimes a scalar and sometimes not, which is what
     * a key holding it has to tell apart. One classification store key stores a string today and an
     * array tomorrow, and on the document engine only the structure is wrapped in a JSON envelope -
     * so the plain string `{"_csJson":[1,2]}` and the array `[1,2]` reach the encryption as exactly
     * the same bytes, and nothing inside the ciphertext can say which of the two is stored. No
     * shipped type has this combination, which is why it is built here.
     */
    public function normalize(mixed $value, array $params = []): mixed
    {
        return $value instanceof CustomValueObject ? ['text' => $value->getText()] : $value;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        if (is_array($value) && isset($value['text'])) {
            return new CustomValueObject((string) $value['text']);
        }

        return $value;
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
