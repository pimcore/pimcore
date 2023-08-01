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
