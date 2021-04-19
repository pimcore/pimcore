<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use Carbon\Carbon;
use Pimcore\Marshaller\MarshallerInterface;

class Date implements MarshallerInterface
{
    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
    {
        if ($value !== null) {
            $result = new Carbon();
            $result->setTimestamp($value);

            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
    {
        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return null;
    }
}
