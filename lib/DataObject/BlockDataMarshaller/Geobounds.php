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

namespace Pimcore\DataObject\BlockDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Geobounds implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            return [
                'value' => json_encode(['latitude' => $value['northEast']['latitude'], 'longitude' => $value['northEast']['longitude']]),
                'value2' => json_encode(['latitude' => $value['southWest']['latitude'], 'longitude' => $value['southWest']['longitude']]),
            ];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $northEast = json_decode($value['value'], true);
            $southWest = json_decode($value['value2'], true);

            return [
                'northEast' => $northEast,
                'southWest' => $southWest,
            ];
        }

        return null;
    }
}
