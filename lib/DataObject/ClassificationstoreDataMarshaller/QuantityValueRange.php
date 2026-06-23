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
