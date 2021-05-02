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
class StructuredTable implements MarshallerInterface
{
    /**
     * @inheritDoc
     */
    public function marshal($value, $params = [])
    {
        if (is_array($value)) {
            $table = new \Pimcore\Model\DataObject\Data\StructuredTable();
            $table->setData($value);

            return $table;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function unmarshal($value, $params = [])
    {
        if ($value instanceof \Pimcore\Model\DataObject\Data\StructuredTable) {
            return $value->getData();
        }

        return null;
    }
}
