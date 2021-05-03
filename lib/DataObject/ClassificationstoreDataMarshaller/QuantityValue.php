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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class QuantityValue implements MarshallerInterface
{
    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
    {
        if (is_array($value)) {
            return [
                'value' => $value['value'],
                'value2' => $value['unitId'],
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
    {
        if (is_array($value) && ($value['value'] !== null || $value['value2'] !== null)) {
            return [
                'value' => $value['value'],
                'unitId' => $value['value2'],

            ];
        }

        return null;
    }
}
