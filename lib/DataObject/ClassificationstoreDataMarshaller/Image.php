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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Image implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value) && isset($value['id'])) {
            return ['value' => $value['id']];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        // the value column is LONGTEXT, so an id reads back as a string, and anything else as not an id
        if (is_array($value) && (int) ($value['value'] ?? 0) > 0) {
            return [
                'type' => 'asset',
                'id' => (int) $value['value'],
            ];
        }

        return null;
    }
}
