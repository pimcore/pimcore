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
