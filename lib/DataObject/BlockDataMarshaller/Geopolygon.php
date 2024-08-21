<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
