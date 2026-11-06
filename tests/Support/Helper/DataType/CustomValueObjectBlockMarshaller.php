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

use Pimcore\Marshaller\MarshallerInterface;

/**
 * Block marshaller for the customValueObject test field type, registered under the key
 * `block_customValueObject` in the test container. Like the core Consent / ExternalImage block
 * marshallers it rebuilds a value object before the block data is serialized, so
 * Block::getDataFromResource() must allow that class on unserialize() - which is what
 * `pimcore.objects.block.unserialize_allowed_classes` is for.
 */
final class CustomValueObjectBlockMarshaller implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value) && isset($value['text'])) {
            return new CustomValueObject((string) $value['text']);
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value instanceof CustomValueObject) {
            return ['text' => $value->getText()];
        }

        return null;
    }
}
