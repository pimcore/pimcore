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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter;

use Pimcore\Model\DataObject\Classificationstore;

class DefaultClassificationStore implements InterpreterInterface
{
    /**
     * @param Classificationstore|null $value
     * @param array|null $config
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function interpret(mixed $value, ?array $config = null): ?array
    {
        if (!$value instanceof Classificationstore) {
            return null;
        }

        $data = [
            'values' => [],
            'keys' => [],
        ];

        foreach ($this->getAllKeysFromStore($value) as $groupId => $groupItem) {
            foreach ($groupItem as $keyId => $item) {
                if (!isset($data['values'][$keyId])) {
                    $data['values'][$keyId] = [];
                }
                $data['values'][$keyId][] = (string) $value->getLocalizedKeyValue($groupId, $keyId, 'en');
                $data['keys'][$keyId] = $keyId;
            }
        }

        $data['keys'] = array_values($data['keys']);

        return $data;
    }

    /**
     * Get all keys from objects store - including inherited information
     *
     * @param Classificationstore $store
     *
     * @return array
     */
    public function getAllKeysFromStore(Classificationstore $store): array
    {
        if ($store->getClass()->getAllowInherit()) {
            $items = [];

            //TODO eventually cache that information
            /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore $fieldDefinition */
            $fieldDefinition = $store->getClass()->getFieldDefinition($store->getFieldname());
            $activeGroupIds = $fieldDefinition->recursiveGetActiveGroupsIds($store->getObject());

            foreach ($activeGroupIds as $groupId => $enabled) {
                if (!$enabled) {
                    continue;
                }

                $relation = new Classificationstore\KeyGroupRelation\Listing();
                $relation->setCondition('groupId = ' . $relation->quote($groupId));
                $relation = $relation->load();
                foreach ($relation as $key) {
                    $keyId = $key->getKeyId();
                    $items[$groupId][$keyId] = $keyId;
                }
            }

            return $items;
        }

        return $store->getItems();
    }
}
