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
class StructuredTable implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $table = new \Pimcore\Model\DataObject\Data\StructuredTable();
            $table->setData($value);

            return $table;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value instanceof \Pimcore\Model\DataObject\Data\StructuredTable) {
            return $value->getData();
        }

        return null;
    }
}
