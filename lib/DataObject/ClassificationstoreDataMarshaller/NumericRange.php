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
use function is_array;
use function is_numeric;

/**
 * @internal
 */
class NumericRange implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            return [
                'value' => $value['minimum'] ?? null,
                'value2' => $value['maximum'] ?? null,
            ];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value) && ($value['value'] !== null || $value['value2'] !== null)) {
            return [
                'minimum' => $this->toNumber($value['value'] ?? null),
                'maximum' => $this->toNumber($value['value2'] ?? null),
            ];
        }

        return null;
    }

    /**
     * The store columns are LONGTEXT, so a number comes back as a string. NumericRange only
     * accepts int|float|null and its file declares strict types, so it has to be cast back.
     */
    private function toNumber(mixed $value): int|float|null
    {
        if (!is_numeric($value)) {
            return null;
        }

        return $value + 0;
    }
}
