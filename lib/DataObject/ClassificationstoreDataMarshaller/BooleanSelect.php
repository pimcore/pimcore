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

/**
 * @internal
 */
class BooleanSelect implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value === true) {
            return ['value' => \Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect::YES_VALUE];
        } elseif ($value === false) {
            return ['value' => \Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect::NO_VALUE];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            if ($value['value'] == \Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect::YES_VALUE) {
                return true;
            } elseif ($value['value'] == \Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect::NO_VALUE) {
                return false;
            }
        }

        return null;
    }
}
