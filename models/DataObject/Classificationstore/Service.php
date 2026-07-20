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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore;
use Pimcore\Model\DataObject;

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

    public static function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): ?DataObject\ClassDefinition\Data
    {
        if ($keyConfig instanceof KeyConfig) {
            $cacheId = $keyConfig->getId();
        } else {
            $cacheId = $keyConfig->getKeyId();
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

    public static function getFieldDefinitionFromJson(array $definition, string $type): ?DataObject\ClassDefinition\Data
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

        // Classification store key names are labels referenced by keyId — they are never
        // emitted into generated PHP classes or DDL, so they are exempt from the identifier
        // validation in Data::setName() (existing stores contain names like "Voltage Type").
        $name = $definition['name'] ?? null;
        unset($definition['name']);

        $dataDefinition->setValues($definition);
        $className = get_class($dataDefinition);

        $state = (array) $dataDefinition;
        unset($state['name']);
        $dataDefinition = $className::__set_state($state);

        if (is_string($name)) {
            $dataDefinition->name = $name;
        }

        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            $delegateDefinition = self::getFieldDefinitionFromJson((array) $delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }
}
