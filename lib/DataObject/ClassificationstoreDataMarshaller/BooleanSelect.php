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
