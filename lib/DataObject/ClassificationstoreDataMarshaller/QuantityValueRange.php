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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Tool\Serialize;

/**
 * @internal
 */
class QuantityValueRange implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $minMaxValue = [
                'minimum' => $value['minimum'] ?? null,
                'maximum' => $value['maximum'] ?? null,
            ];

            return [
                'value' => Serialize::serialize($minMaxValue),
                'value2' => $value['unitId'] ?? null,
            ];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value) && ($value['value'] !== null || $value['value2'] !== null)) {
            $minMaxValue = Serialize::unserialize($value['value'] ?? null);

            return [
                'minimum' => $minMaxValue['minimum'] ?? null,
                'maximum' => $minMaxValue['maximum'] ?? null,
                'unitId' => $value['value2'],
            ];
        }

        return null;
    }
}
