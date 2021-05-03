<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Geopolygon implements MarshallerInterface
{
    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
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
