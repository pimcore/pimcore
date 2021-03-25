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

namespace Pimcore\DataObject\FielddefinitionMarshaller\Block;

use Pimcore\DataObject\MarshallerInterface;
use Pimcore\Model\DataObject\Data\GeoCoordinates;

class Geopolygon implements MarshallerInterface
{
    /** @inheritDoc */
    public function marshal($value, $params = [])
    {
        if (is_array($value)) {
            $resultItems = [];
            foreach ($value as $p) {
                $resultItems[] = [$p['latitude'], $p['longitude']];
            }

            $result = ["value" => json_encode($resultItems)];
            return $result;
        }

        return null;
    }

    /** @inheritDoc */
    public function unmarshal($value, $params = [])
    {
        if ($value["value"] ?? null) {
            $value = json_decode($value["value"], true);
            $result = [];
            if (is_array($value)) {
                foreach ($value as $point) {
                    $result[] = new GeoCoordinates($point[0], $point[1]);
                }
            }
            return $result;
        }
        return null;
    }


}
