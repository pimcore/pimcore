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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject;

class Service
{
    /**
     * @var array Used for storing definitions
     */
    protected static $definitionsCache = [];

    /**
     * Clears the cache for the definitions
     */
    public static function clearDefinitionsCache()
    {
        self::$definitionsCache = [];
    }

    /**
     * @param KeyConfig|KeyGroupRelation $keyConfig
     *
     * @return DataObject\ClassDefinition\Data
     */
    public static function getFieldDefinitionFromKeyConfig($keyConfig)
    {
        if ($keyConfig instanceof KeyConfig) {
            $cacheId = $keyConfig->getId();
        } elseif ($keyConfig instanceof KeyGroupRelation) {
            $cacheId = $keyConfig->getKeyId();
        } else {
            throw new \Exception('$keyConfig should be KeyConfig or KeyGroupRelation');
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

    /**
     * @param array $definition
     * @param string $type
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    public static function getFieldDefinitionFromJson($definition, $type)
    {
        if (!$definition) {
            return null;
        }

        if (!$type) {
            $type = 'input';
        }

        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');

        /** @var DataObject\ClassDefinition\Data $dataDefinition */
        $dataDefinition = $loader->build($type);

        $dataDefinition->setValues($definition);
        $className = get_class($dataDefinition);

        if (method_exists($className, '__set_state')) {
            $dataDefinition = $className::__set_state($dataDefinition);
        }

        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            $delegateDefinition = self::getFieldDefinitionFromJson($delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }
}
