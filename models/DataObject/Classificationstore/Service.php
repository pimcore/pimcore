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

namespace Pimcore\Model\DataObject\Classificationstore;

use Exception;
use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;

/**
 * @internal
 */
class Service
{
    /**
     * @var array Used for storing definitions
     */
    protected static array $definitionsCache = [];

    /**
     * Clears the cache for the definitions
     */
    public static function clearDefinitionsCache(): void
    {
        self::$definitionsCache = [];
    }

    /**
     *
     * @return EncryptedField|Data|null
     *
     * @throws Exception
     */
    public static function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): DataObject\ClassDefinition\Data\EncryptedField|DataObject\ClassDefinition\Data|null
    {
        if ($keyConfig instanceof KeyConfig) {
            $cacheId = $keyConfig->getId();
        } elseif ($keyConfig instanceof KeyGroupRelation) {
            $cacheId = $keyConfig->getKeyId();
        } else {
            throw new Exception('$keyConfig should be KeyConfig or KeyGroupRelation');
        }

        if (array_key_exists($cacheId, self::$definitionsCache)) {
            return self::$definitionsCache[$cacheId];
        }

        $definition = $keyConfig->getDefinition();
        $definition = json_decode($definition, true);
        $type = $keyConfig->getType();
        $fd = self::getFieldDefinitionFromJson($definition, $type);
        self::$definitionsCache[$cacheId] = $fd;

        return $fd;
    }

    public static function getFieldDefinitionFromJson(array $definition, string $type): DataObject\ClassDefinition\Data\EncryptedField|DataObject\ClassDefinition\Data|null
    {
        if (!$definition) {
            return null;
        }

        if (!$type) {
            $type = 'input';
        }

        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');

        /** @var DataObject\ClassDefinition\Data $dataDefinition */
        $dataDefinition = $loader->build($type);

        $dataDefinition->setValues($definition);
        $className = get_class($dataDefinition);

        $dataDefinition = $className::__set_state((array) $dataDefinition);

        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            $delegateDefinition = self::getFieldDefinitionFromJson((array) $delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }
}
