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
class Geopolygon implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $resultItems = [];
            foreach ($value as $p) {
                $resultItems[] = [$p['latitude'], $p['longitude']];
            }

            $result = ['value' => json_encode($resultItems)];

            return $result;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value['value'] ?? null) {
            $value = json_decode($value['value'], true);
            $result = [];

            if (is_array($value)) {
                foreach ($value as $point) {
                    $result[] = [
                        'latitude' => $point[0],
                        'longitude' => $point[1],
                    ];
                }
            }

            return $result;
        }

        return null;
    }
}
