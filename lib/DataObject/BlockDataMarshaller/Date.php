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

use Carbon\Carbon;
use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Date implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $result = new Carbon();
            $result->setTimestamp($value);

            return $result;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return null;
    }
}
