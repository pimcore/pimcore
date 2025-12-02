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
class Table implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (!is_null($value)) {
            return ['value' => Serialize::serialize($value)];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            return Serialize::unserialize($value['value']);
        }

        return null;
    }
}
