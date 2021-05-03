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
class Geopoint implements MarshallerInterface
{
    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
    {
        if (is_array($value)) {
            return [
                'value' => $value['latitude'],
                'value2' => $value['longitude'],
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
    {
        if (is_array($value)) {
            return [
                'latitude' => $value['value'],
                'longitude' => $value['value2'],
            ];
        }

        return null;
    }
}
