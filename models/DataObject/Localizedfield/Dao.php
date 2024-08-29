<?php

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

namespace Pimcore\Model\DataObject\Localizedfield;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Exception;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Tool;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Localizedfield $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use DataObject\ClassDefinition\Helper\Dao;
    use DataObject\Traits\CompositeIndexTrait;

    protected array $tableDefinitions = [];

    protected DataObject\Concrete\Dao\InheritanceHelper $inheritanceHelper;

    public function getTableName(): string
    {
        $context = $this->model->getContext();
        if ($context) {
            $containerType = $context['containerType'] ?? null;
            if ($containerType === 'fieldcollection') {
                $containerKey = $context['containerKey'];

                return 'object_collection_'.$containerKey.'_localized_'.$this->model->getClass()->getId();
            } elseif ($containerType === 'objectbrick') {
                $containerKey = $context['containerKey'];

                return 'object_brick_localized_'.$containerKey.'_'.$this->model->getClass()->getId();
            }
        }

        return 'object_localized_data_'.$this->model->getClass()->getId();
    }

    public function getQueryTableName(): string
    {
        $context = $this->model->getContext();
        if ($context) {
            $containerType = $context['containerType'] ?? null;
            if ($containerType == 'objectbrick') {
                $containerKey = $context['containerKey'];

                return 'object_brick_localized_query_'.$containerKey.'_'.$this->model->getClass()->getId();
            }
        }

        return 'object_localized_query_'.$this->model->getClass()->getId();
    }

    /**
     *
     * @throws Exception
     */
    public function save(array $params = []): void
    {
        $context = $this->model->getContext();

        // if inside a field collection a delete is not necessary as the fieldcollection deletes all entries anyway
        // see Pimcore\Model\DataObject\Fieldcollection\Dao::delete

        $forceUpdate = false;
        if ((isset($params['newParent']) && $params['newParent']) || DataObject::isDirtyDetectionDisabled() || $this->model->hasDirtyLanguages(
        ) || $context['containerType'] == 'fieldcollection') {
            $forceUpdate = $this->delete(false, true);
        }

        $object = $this->model->getObject();
        $validLanguages = Tool::getValidLanguages();

        if (isset($context['containerType']) && $context['containerType'] === 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $container = DataObject\Fieldcollection\Definition::getByKey($containerKey);
        } elseif (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
            $containerKey = $context['containerKey'];
            $container = DataObject\Objectbrick\Definition::getByKey($containerKey);
        } else {
            $container = $this->model->getClass();
        }

        if (!isset($params['owner'])) {
            throw new Exception('need owner from container implementation');
        }

        $this->model->_setOwner($params['owner']);
        $this->model->_setOwnerFieldname('localizedfields');
        $this->model->_setOwnerLanguage(null);

        /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedfields */
        $localizedfields = $container->getFieldDefinition('localizedfields');
        $fieldDefinitions = $localizedfields->getFieldDefinitions(
            ['suppressEnrichment' => true]
        );

        /**
         * We temporary enable the runtime cache so we don't have to calculate the tree for each language
         * which is a great performance gain if you have a lot of languages
         */
        DataObject\Concrete\Dao\InheritanceHelper::setUseRuntimeCache(true);

        $ignoreLocalizedQueryFallback = \Pimcore\Config::getSystemConfiguration('objects')['ignore_localized_query_fallback'];
        if (!$ignoreLocalizedQueryFallback) {
            $this->model->markLanguageAsDirtyByFallback();
        }

        $flag = DataObject\Localizedfield::getGetFallbackValues();

        if (!$ignoreLocalizedQueryFallback) {
            DataObject\Localizedfield::setGetFallbackValues(true);
        }

        foreach ($validLanguages as $language) {
            if (empty($params['newParent'])
                && !empty($params['isUpdate'])
                && !$this->model->isLanguageDirty($language)
                && !$forceUpdate
            ) {
                continue;
            }

            $inheritedValues = DataObject::doGetInheritedValues();
            DataObject::setGetInheritedValues(false);

            $insertData = [
                'ooo_id' => $this->model->getObject()->getId(),
                'language' => $language,
            ];

            if ($container instanceof DataObject\Objectbrick\Definition || $container instanceof DataObject\Fieldcollection\Definition) {
                $insertData['fieldname'] = $context['fieldname'];
                $insertData['index'] = $context['index'] ?? 0;
            }

            foreach ($fieldDefinitions as $fieldName => $fd) {
                if ($fd instanceof CustomResourcePersistingInterface) {
                    // for fieldtypes which have their own save algorithm eg. relational data types, ...
                    $context = $this->model->getContext() ? $this->model->getContext() : [];
                    if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                        $context['subContainerType'] = 'localizedfield';
                    }

                    $isUpdate = isset($params['isUpdate']) && $params['isUpdate'];
                    $childParams = $this->getFieldDefinitionParams($fieldName, $language, ['isUpdate' => $isUpdate, 'context' => $context]);

                    if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
                        $saveLocalizedRelations = $forceUpdate || ($params['saveRelationalData']['saveLocalizedRelations'] ?? false);
                        if (($saveLocalizedRelations && $container instanceof DataObject\Fieldcollection\Definition)
                            || (((!$container instanceof DataObject\Fieldcollection\Definition || $container instanceof DataObject\Objectbrick\Definition)
                                    && $this->model->isLanguageDirty($language))
                                || $saveLocalizedRelations)) {
                            if ($saveLocalizedRelations) {
                                $childParams['forceSave'] = true;
                            }
                            $fd->save($this->model, $childParams);
                        }
                    } else {
                        $fd->save($this->model, $childParams);
                    }
                }
                if ($fd instanceof ResourcePersistenceAwareInterface
                    && $fd instanceof DataObject\ClassDefinition\Data) {
                    if (is_array($fd->getColumnType())) {
                        $fieldDefinitionParams = $this->getFieldDefinitionParams($fieldName, $language, ['isUpdate' => ($params['isUpdate'] ?? false)]);
                        $insertDataArray = $fd->getDataForResource(
                            $this->model->getLocalizedValue($fieldName, $language, true),
                            $object,
                            $fieldDefinitionParams
                        );
                        $insertData = array_merge($insertData, $insertDataArray);
                        $this->model->setLocalizedValue($fieldName, $fd->getDataFromResource($insertDataArray, $object, $fieldDefinitionParams), $language, false);
                    } else {
                        $fieldDefinitionParams = $this->getFieldDefinitionParams($fieldName, $language, ['isUpdate' => ($params['isUpdate'] ?? false)]);
                        $insertData[$fd->getName()] = $fd->getDataForResource(
                            $this->model->getLocalizedValue($fieldName, $language, true),
                            $object,
                            $fieldDefinitionParams
                        );
                        $this->model->setLocalizedValue($fieldName, $fd->getDataFromResource($insertData[$fd->getName()], $object, $fieldDefinitionParams), $language, false);
                    }
                }
            }

            $storeTable = $this->getTableName();
            $queryTable = $this->getQueryTableName().'_'.$language;

            try {
                if ((isset($params['newParent']) && $params['newParent']) || !isset($params['isUpdate']) || !$params['isUpdate'] || $this->model->isLanguageDirty(
                    $language
                )) {
                    Helper::upsert($this->db, $storeTable, $insertData, $this->getPrimaryKey($storeTable));
                }
            } catch (TableNotFoundException $e) {
                // if the table doesn't exist -> create it! deferred creation for object bricks ...
                try {
                    $this->db->rollBack();
                } catch (Exception $er) {
                    // PDO adapter throws exceptions if rollback fails
                    Logger::info((string) $er);
                }

                $this->createUpdateTable();

                // throw exception which gets caught in AbstractObject::save() -> retry saving
                throw new LanguageTableDoesNotExistException('missing table created, start next run ... ;-)');
            }

            if ($container instanceof DataObject\ClassDefinition || $container instanceof DataObject\Objectbrick\Definition) {
                // query table
                $data = [];
                $data['ooo_id'] = $this->model->getObject()->getId();
                $data['language'] = $language;

                $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper(
                    $object->getClassId(),
                    'ooo_id',
                    $storeTable,
                    $queryTable
                );
                $this->inheritanceHelper->resetFieldsToCheck();
                $sql = 'SELECT * FROM '.$queryTable.' WHERE ooo_id = '.$object->getId(
                )." AND language = '".$language."'";

                $oldData = [];

                try {
                    $oldData = $this->db->fetchAssociative($sql);
                } catch (TableNotFoundException $e) {
                    // if the table doesn't exist -> create it!

                    // the following is to ensure consistent data and atomic transactions, while having the flexibility
                    // to add new languages on the fly without saving all classes having localized fields

                    // first we need to roll back all modifications, because otherwise they would be implicitly committed
                    // by the following DDL
                    try {
                        $this->db->rollBack();
                    } catch (Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::info((string) $er);
                    }

                    // this creates the missing table
                    $this->createUpdateTable();

                    // at this point we throw an exception so that the transaction gets repeated in DataObject::save()
                    throw new LanguageTableDoesNotExistException('missing table created, start next run ... ;-)');
                }

                // get fields which shouldn't be updated
                $untouchable = [];

                // @TODO: currently we do not support lazyloading in localized fields

                $inheritanceEnabled = $object->getClass()->getAllowInherit();
                $parentData = null;
                if ($inheritanceEnabled) {
                    // get the next suitable parent for inheritance
                    $parentForInheritance = $object->getNextParentForInheritance();
                    if ($parentForInheritance) {
                        // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                        // we cannot DataObject\AbstractObject::setGetInheritedValues(true); and then $this->model->getLocalizedValue($key, $language)
                        // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                        // so the data of the parent cannot be changed while this transaction is on progress
                        $parentData = $this->db->fetchAssociative(
                            'SELECT * FROM '.$queryTable.' WHERE ooo_id = ? AND language = ? FOR UPDATE',
                            [$parentForInheritance->getId(), $language]
                        );
                    }
                }

                foreach ($fieldDefinitions as $fd) {
                    if ($fd instanceof QueryResourcePersistenceAwareInterface
                        &&  $fd instanceof DataObject\ClassDefinition\Data) {
                        $key = $fd->getName();

                        // exclude untouchables if value is not an array - this means data has not been loaded
                        if (!in_array($key, $untouchable)) {
                            $localizedValue = $this->model->getLocalizedValue($key, $language, $ignoreLocalizedQueryFallback);
                            $insertData = $fd->getDataForQueryResource(
                                $localizedValue,
                                $object,
                                $this->getFieldDefinitionParams($key, $language)
                            );
                            $isEmpty = $fd->isEmpty($localizedValue);

                            if (is_array($insertData)) {
                                $columnNames = array_keys($insertData);
                                $data = array_merge($data, $insertData);
                            } else {
                                $columnNames = [$key];
                                $data[$key] = $insertData;
                            }

                            // if the current value is empty and we have data from the parent, we just use it
                            if ($isEmpty && $parentData) {
                                foreach ($columnNames as $columnName) {
                                    if (array_key_exists($columnName, $parentData)) {
                                        $data[$columnName] = $parentData[$columnName];
                                        if (is_array($insertData)) {
                                            $insertData[$columnName] = $parentData[$columnName];
                                        } else {
                                            $insertData = $parentData[$columnName];
                                        }
                                    }
                                }
                            }

                            if ($inheritanceEnabled && $fd->getFieldType() != 'calculatedValue') {
                                //get changed fields for inheritance
                                if ($fd->isRelationType()) {
                                    if (is_array($insertData)) {
                                        $doInsert = false;
                                        foreach ($insertData as $insertDataKey => $insertDataValue) {
                                            $oldDataValue = $oldData[$insertDataKey] ?? null;
                                            $parentDataValue = $parentData[$insertDataKey] ?? null;
                                            if ($isEmpty && $oldDataValue == $parentDataValue) {
                                                // do nothing, ... value is still empty and parent data is equal to current data in query table
                                            } elseif ($oldDataValue != $insertDataValue) {
                                                $doInsert = true;

                                                break;
                                            }
                                        }

                                        if ($doInsert) {
                                            $this->inheritanceHelper->addRelationToCheck(
                                                $key,
                                                $fd,
                                                array_keys($insertData)
                                            );
                                        }
                                    } else {
                                        $oldDataValue = $oldData[$key] ?? null;
                                        $parentDataValue = $parentData[$key] ?? null;
                                        if ($isEmpty && $oldDataValue == $parentDataValue) {
                                            // do nothing, ... value is still empty and parent data is equal to current data in query table
                                        } elseif ($oldDataValue != $insertData) {
                                            $this->inheritanceHelper->addRelationToCheck($key, $fd);
                                        }
                                    }
                                } else {
                                    if (is_array($insertData)) {
                                        foreach ($insertData as $insertDataKey => $insertDataValue) {
                                            $oldDataValue = $oldData[$insertDataKey] ?? null;
                                            $parentDataValue = $parentData[$insertDataKey] ?? null;
                                            if ($isEmpty && $oldDataValue == $parentDataValue) {
                                                // do nothing, ... value is still empty and parent data is equal to current data in query table
                                            } elseif ($oldDataValue != $insertDataValue) {
                                                $this->inheritanceHelper->addFieldToCheck($insertDataKey, $fd);
                                            }
                                        }
                                    } else {
                                        $oldDataValue = $oldData[$key] ?? null;
                                        $parentDataValue = $parentData[$key] ?? null;
                                        if ($isEmpty && $oldDataValue == $parentDataValue) {
                                            // do nothing, ... value is still empty and parent data is equal to current data in query table
                                        } elseif ($oldDataValue != $insertData) {
                                            // data changed, do check and update
                                            $this->inheritanceHelper->addFieldToCheck($key, $fd);
                                        }
                                    }
                                }
                            }
                        } else {
                            Logger::debug(
                                'Excluding untouchable query value for object [ '.$this->model->getObjectId() ." ]  key [ $key ] because it has not been loaded"
                            );
                        }
                    }
                }

                $queryTable = $this->getQueryTableName().'_'.$language;
                Helper::upsert($this->db, $queryTable, $data, $this->getPrimaryKey($queryTable));
                if ($inheritanceEnabled) {
                    $context = isset($params['context']) ? $params['context'] : [];
                    if ($context['containerType'] === 'objectbrick') {
                        $inheritanceRelationContext = [
                            'ownertype' => 'localizedfield',
                            'ownername' => '/objectbrick~' . $context['fieldname'] . '/' . $context['containerKey'] . '/localizedfield~localizedfield',
                        ];
                    } else {
                        $inheritanceRelationContext = [
                            'ownertype' => 'localizedfield',
                            'ownername' => 'localizedfield',
                        ];
                    }
                    $this->inheritanceHelper->doUpdate($object->getId(), true, [
                        'language' => $language,
                        'inheritanceRelationContext' => $inheritanceRelationContext,
                    ]);
                }
                $this->inheritanceHelper->resetFieldsToCheck();
            }

            DataObject::setGetInheritedValues($inheritedValues);
        } // foreach language

        if (!$ignoreLocalizedQueryFallback) {
            DataObject\Localizedfield::setGetFallbackValues($flag);
        }
        DataObject\Concrete\Dao\InheritanceHelper::setUseRuntimeCache(false);
        DataObject\Concrete\Dao\InheritanceHelper::clearRuntimeCache();
    }

    /**
     *
     * @return bool force update
     */
    public function delete(bool $deleteQuery = true, bool $isUpdate = true): bool
    {
        if ($isUpdate && !DataObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields()) {
            return false;
        }
        $object = $this->model->getObject();
        $context = $this->model->getContext();
        $container = null;

        try {
            if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                $containerKey = $context['containerKey'];
                if ($context['containerType'] === 'fieldcollection') {
                    $container = DataObject\Fieldcollection\Definition::getByKey($containerKey);
                } else {
                    $container = DataObject\Objectbrick\Definition::getByKey($containerKey);
                }
            } else {
                $container = $object->getClass();
            }
            if ($deleteQuery) {
                $id = $object->getId();
                $tablename = $this->getTableName();
                if (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
                    $this->db->delete($tablename, ['ooo_id' => $id, 'fieldname' => $context['fieldname']]);
                } else {
                    $this->db->delete($tablename, ['ooo_id' => $id]);
                }

                if (!$container instanceof DataObject\Fieldcollection\Definition || $container instanceof DataObject\Objectbrick\Definition) {
                    $validLanguages = Tool::getValidLanguages();
                    foreach ($validLanguages as $language) {
                        $queryTable = $this->getQueryTableName().'_'.$language;
                        $this->db->delete($queryTable, ['ooo_id' => $id]);
                    }
                }
            }

            /** @var DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
            $fieldDefinition = $container->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);
            $childDefinitions = $fieldDefinition->getFieldDefinitions(['suppressEnrichment' => true]);

            foreach ($childDefinitions as $fd) {
                if ($fd instanceof CustomResourcePersistingInterface) {
                    $params = [
                        'context' => $this->model->getContext() ? $this->model->getContext() : [],
                        'isUpdate' => $isUpdate,
                    ];
                    if (isset($params['context']['containerType']) && ($params['context']['containerType'] === 'fieldcollection' || $params['context']['containerType'] === 'objectbrick')) {
                        $params['context']['subContainerType'] = 'localizedfield';
                    }

                    $fd->delete($object, $params);
                }
            }
        } catch (Exception $e) {
            Logger::error((string) $e);

            if ($isUpdate && $e instanceof TableNotFoundException) {
                try {
                    $this->db->rollBack();
                } catch (Exception $er) {
                    // PDO adapter throws exceptions if rollback fails
                    Logger::info((string) $er);
                }

                $this->createUpdateTable();

                // throw exception which gets caught in AbstractObject::save() -> retry saving
                throw new LanguageTableDoesNotExistException('missing table created, start next run ... ;-)');
            }
        }

        // remove relations
        $ignoreLocalizedQueryFallback = \Pimcore\Config::getSystemConfiguration('objects')['ignore_localized_query_fallback'];
        if (!$ignoreLocalizedQueryFallback) {
            $this->model->markLanguageAsDirtyByFallback();
        }

        if (!DataObject::isDirtyDetectionDisabled()) {
            if (!$this->model->hasDirtyFields()) {
                return false;
            }
        }

        $db = Db::get();
        $dirtyLanguageCondition = null;

        if ($this->model->allLanguagesAreDirty() ||
            ($container instanceof DataObject\Fieldcollection\Definition)
        ) {
            $dirtyLanguageCondition = '';
        } elseif ($this->model->hasDirtyLanguages()) {
            $languageList = [];
            if (is_array($this->model->getDirtyLanguages())) {
                foreach ($this->model->getDirtyLanguages() as $language => $flag) {
                    if ($flag) {
                        $languageList[] = $db->quote($language);
                    }
                }
            }

            $dirtyLanguageCondition = ' AND position IN('.implode(',', $languageList).')';
        }

        if ($container instanceof DataObject\Fieldcollection\Definition) {
            $objectId = $object->getId();
            $index = $context['index'] ?? $context['containerKey'] ?? null;
            $containerName = $context['fieldname'];
            if (!$context['containerType']) {
                throw new Exception('no container type set');
            }

            $sql = Helper::quoteInto($this->db, 'src_id = ?', $objectId)." AND ownertype = 'localizedfield' AND "
                .Helper::quoteInto($this->db,
                    'ownername LIKE ?',
                    '/'.$context['containerType'].'~'.$containerName.'/'.$index.'/%'
                ).$dirtyLanguageCondition;

            if ($deleteQuery || $context['containerType'] === 'fieldcollection') {
                // Fieldcollection don't support delta updates, so we delete the relations and insert them later again
                $this->db->executeStatement('DELETE FROM object_relations_'.$object->getClassId().' WHERE '.$sql);
            }

            return true;
        }

        if ($deleteQuery || $context['containerType'] === 'fieldcollection') {
            // Fieldcollection don't support delta updates, so we delete the relations and insert them later again
            $sql = 'ownertype = "localizedfield" AND ownername = "localizedfield" and src_id = '.$this->model->getObject(
            )->getId().$dirtyLanguageCondition;
            $this->db->executeStatement(
                'DELETE FROM object_relations_'.$this->model->getObject()->getClassId().' WHERE '.$sql
            );
        }

        return false;
    }

    public function load(DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): void
    {
        $validLanguages = Tool::getValidLanguages();
        foreach ($validLanguages as &$language) {
            $language = $this->db->quote($language);
        }

        $context = $this->model->getContext();
        if (isset($context['containerType']) && $context['containerType'] === 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $index = $context['index'];
            $fieldname = $context['fieldname'];

            $container = DataObject\Fieldcollection\Definition::getByKey($containerKey);

            $data = $this->db->fetchAllAssociative(
                'SELECT * FROM '.$this->getTableName()
                .' WHERE ooo_id = ? AND language IN ('.implode(
                    ',',
                    $validLanguages
                ).') AND `fieldname` = ? AND `index` = ?',
                [
                    $this->model->getObject()->getId(),
                    $fieldname,
                    $index,
                ]
            );
        } elseif (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
            $containerKey = $context['containerKey'];
            $container = DataObject\Objectbrick\Definition::getByKey($containerKey);
            $fieldname = $context['fieldname'];

            $data = $this->db->fetchAllAssociative(
                'SELECT * FROM '.$this->getTableName()
                .' WHERE ooo_id = ? AND language IN ('.implode(',', $validLanguages).') AND `fieldname` = ?',
                [
                    $this->model->getObject()->getId(),
                    $fieldname,
                ]
            );
        } else {
            $object->__objectAwareFields['localizedfields'] = true;
            $container = $this->model->getClass();
            $data = $this->db->fetchAllAssociative(
                'SELECT * FROM '.$this->getTableName().' WHERE ooo_id = ? AND language IN ('.implode(
                    ',',
                    $validLanguages
                ).')',
                [$this->model->getObject()->getId()]
            );
        }

        if (!isset($params['owner'])) {
            throw new Exception('need owner from container implementation');
        }

        $this->model->_setOwner($params['owner']);
        $this->model->_setOwnerFieldname('localizedfields');
        $this->model->_setOwnerLanguage(null);

        foreach ($data as $row) {
            /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedfields */
            $localizedfields = $container->getFieldDefinition('localizedfields');
            foreach ($localizedfields->getFieldDefinitions(
                ['object' => $object, 'suppressEnrichment' => true]
            ) as $key => $fd) {
                if ($fd instanceof CustomResourcePersistingInterface) {
                    // datafield has it's own loader
                    $params['language'] = $row['language'];
                    $params['object'] = $object;
                    if (!isset($params['context'])) {
                        $params['context'] = [];
                    }
                    $params['context']['object'] = $object;

                    if ($fd instanceof LazyLoadingSupportInterface
                        && $fd instanceof DataObject\ClassDefinition\Data
                        && $fd->getLazyLoading()) {
                        $lazyKey = $fd->getName() . DataObject\LazyLoadedFieldsInterface::LAZY_KEY_SEPARATOR . $row['language'];
                    } else {
                        $value = $fd->load($this->model, $params);
                        if ($value === 0 || !empty($value)) {
                            $this->model->setLocalizedValue($key, $value, $row['language'], false);
                        }
                    }
                }
                if ($fd instanceof ResourcePersistenceAwareInterface) {
                    if (is_array($fd->getColumnType())) {
                        $multidata = [];
                        foreach ($fd->getColumnType() as $fkey => $fvalue) {
                            $multidata[$key.'__'.$fkey] = $row[$key.'__'.$fkey];
                        }
                        $value = $fd->getDataFromResource($multidata, null, $this->getFieldDefinitionParams($key, $row['language']));
                        $this->model->setLocalizedValue($key, $value, $row['language'], false);
                    } else {
                        $value = $fd->getDataFromResource($row[$key], null, $this->getFieldDefinitionParams($key, $row['language']));
                        $this->model->setLocalizedValue($key, $value, $row['language'], false);
                    }
                }
            }
        }
    }

    public function createLocalizedViews(): void
    {
        // init
        $languages = Tool::getValidLanguages();
        $defaultTable = 'object_query_'.$this->model->getClass()->getId();

        $db = $this->db;

        /**
         * macro for creating ifnull statement
         *
         * @param string $field
         * @param array $languages
         *
         * @return string
         */
        $getFallbackValue = function (string $field, array $languages) use (&$getFallbackValue, $db) {
            // init
            $lang = array_shift($languages);

            // get fallback for current language
            $fallback = count($languages) > 0
                ? $getFallbackValue($field, $languages)
                : 'null';

            // create query
            $sql = sprintf(
                'IF(`%s`.`%s` IS NULL OR STRCMP(`%s`.`%s`, "") = 0, %s, `%s`.`%s`)',
                $lang,
                $field,
                $lang,
                $field,
                $fallback,
                $lang,
                $field
            );

            return $fallback !== 'null'
                ? $sql
                : $db->quoteIdentifier($lang).'.'.$db->quoteIdentifier($field);
        };

        foreach ($languages as $language) {
            try {
                $tablename = $this->getQueryTableName().'_'.$language;

                // get available columns
                $viewColumns = array_merge(
                    $this->db->fetchAllAssociative('SHOW COLUMNS FROM `'.$defaultTable.'`'),
                    $this->db->fetchAllAssociative('SHOW COLUMNS FROM `objects`')
                );
                $localizedColumns = $this->db->fetchAllAssociative('SHOW COLUMNS FROM `'.$tablename.'`');

                // get view fields
                $viewFields = [];
                foreach ($viewColumns as $row) {
                    $viewFields[] = $this->db->quoteIdentifier($row['Field']);
                }

                // create fallback select
                $localizedFields = [];
                $fallbackLanguages = array_unique(Tool::getFallbackLanguagesFor($language));
                array_unshift($fallbackLanguages, $language);
                foreach ($localizedColumns as $row) {
                    if ($row['Field'] == 'language' || $row['Field'] == 'ooo_id') {
                        $localizedFields[] = $db->quoteIdentifier($language).'.'.$db->quoteIdentifier($row['Field']);
                    } else {
                        $localizedFields[] = $getFallbackValue($row['Field'], $fallbackLanguages).sprintf(
                            ' as "%s"',
                            $row['Field']
                        );
                    }
                }

                // create view select fields
                $selectViewFields = implode(',', array_merge($viewFields, $localizedFields));

                // create view
                $viewQuery = <<<QUERY
CREATE OR REPLACE VIEW `object_localized_{$this->model->getClass()->getId()}_{$language}` AS

SELECT {$selectViewFields}
FROM `{$defaultTable}`
    JOIN `objects`
        ON (`objects`.`id` = `{$defaultTable}`.`oo_id`)
QUERY;

                // join fallback languages
                foreach ($fallbackLanguages as $lang) {
                    $viewQuery .= <<<QUERY
LEFT JOIN {$this->getQueryTableName()}_{$lang} as `{$lang}`
    ON( 1
        AND {$defaultTable}.oo_id = {$lang}.ooo_id
    )
QUERY;
                }

                // execute
                $this->db->executeQuery($viewQuery);
            } catch (Exception $e) {
                Logger::error((string) $e);
            }
        }
    }

    /**
     *
     * @throws Exception
     */
    public function createUpdateTable(array $params = []): void
    {
        $table = $this->getTableName();

        $context = $this->model->getContext();
        if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
            $this->db->executeQuery(
                'CREATE TABLE IF NOT EXISTS `'.$table."` (
              `ooo_id` int(11) UNSIGNED NOT NULL default '0',
              `index` INT(11) NOT NULL DEFAULT '0',
              `fieldname` VARCHAR(190) NOT NULL DEFAULT '',
              `language` varchar(10) NOT NULL DEFAULT '',
              PRIMARY KEY (`ooo_id`, `language`, `index`, `fieldname`),
              INDEX `index` (`index`),
              INDEX `fieldname` (`fieldname`),
              INDEX `language` (`language`),
              CONSTRAINT `".self::getForeignKeyName($table, 'ooo_id').'` FOREIGN KEY (`ooo_id`) REFERENCES objects (`id`) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4;'
            );
        } else {
            $this->db->executeQuery(
                'CREATE TABLE IF NOT EXISTS `'.$table."` (
              `ooo_id` int(11) UNSIGNED NOT NULL default '0',
              `language` varchar(10) NOT NULL DEFAULT '',
              PRIMARY KEY (`ooo_id`,`language`),
              INDEX `language` (`language`),
              CONSTRAINT `".self::getForeignKeyName($table, 'ooo_id').'` FOREIGN KEY (`ooo_id`) REFERENCES objects (`id`) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4;'
            );
        }

        $this->handleEncryption($this->model->getClass(), [$table]);

        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;

        DataObject\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, ([$table]));

        if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
            $protectedColumns = ['ooo_id', 'language', 'index', 'fieldname'];
            $containerKey = $context['containerKey'];
            if ($context['containerType'] === 'fieldcollection') {
                $container = DataObject\Fieldcollection\Definition::getByKey($containerKey);
            } else {
                $container = DataObject\Objectbrick\Definition::getByKey($containerKey);
            }
        } else {
            $protectedColumns = ['ooo_id', 'language'];
            $container = $this->model->getClass();
        }

        /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedFieldDefinition */
        $localizedFieldDefinition = $container->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);
        if ($localizedFieldDefinition instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            foreach ($localizedFieldDefinition->getFieldDefinitions(['suppressEnrichment' => true]) as $value) {
                if ($value instanceof ResourcePersistenceAwareInterface
                    && $value instanceof DataObject\ClassDefinition\Data) {
                    if ($value->getColumnType()) {
                        $key = $value->getName();

                        if (is_array($value->getColumnType())) {
                            // if a datafield requires more than one column
                            foreach ($value->getColumnType() as $fkey => $fvalue) {
                                $this->addModifyColumn($table, $key . '__' . $fkey, $fvalue, '', 'NULL');
                                $protectedColumns[] = $key . '__' . $fkey;
                            }
                        } else {
                            $this->addModifyColumn($table, $key, $value->getColumnType(), '', 'NULL');
                            $protectedColumns[] = $key;
                        }

                        $this->addIndexToField($value, $table, 'getColumnType', true, true);
                    }
                }
            }
        }

        $this->removeIndices($table, $columnsToRemove, $protectedColumns);
        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColumns);

        $validLanguages = Tool::getValidLanguages();

        if ($container instanceof DataObject\ClassDefinition || $container instanceof DataObject\Objectbrick\Definition) {
            foreach ($validLanguages as &$language) {
                $queryTable = $this->getQueryTableName();
                $queryTable .= '_'.$language;

                $this->db->executeQuery(
                    'CREATE TABLE IF NOT EXISTS `'.$queryTable."` (
                      `ooo_id` int(11) UNSIGNED NOT NULL default '0',
                      `language` varchar(10) NOT NULL DEFAULT '',
                      PRIMARY KEY (`ooo_id`,`language`),
                      INDEX `language` (`language`),
                      CONSTRAINT `".self::getForeignKeyName($queryTable, 'ooo_id').'` FOREIGN KEY (`ooo_id`) REFERENCES objects (`id`) ON DELETE CASCADE
                    ) DEFAULT CHARSET=utf8mb4;'
                );

                $this->handleEncryption($this->model->getClass(), [$queryTable]);

                // create object table if not exists
                $protectedColumns = ['ooo_id', 'language'];

                $existingColumns = $this->getValidTableColumns($queryTable, false); // no caching of table definition
                $columnsToRemove = $existingColumns;

                DataObject\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, [$queryTable]);

                $fieldDefinitions = [];
                if ($container instanceof DataObject\Objectbrick\Definition) {
                    $containerKey = $context['containerKey'];
                    $container = DataObject\Objectbrick\Definition::getByKey($containerKey);

                    /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedfields */
                    $localizedfields = $container->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);
                    $fieldDefinitions = $localizedfields->getFieldDefinitions(['suppressEnrichment' => true]);
                } else {
                    /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedfields */
                    $localizedfields = $this->model->getClass()->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);

                    if ($localizedfields instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        $fieldDefinitions = $localizedfields->getFieldDefinitions(['suppressEnrichment' => true]);
                    }
                }

                // add non existing columns in the table
                foreach ($fieldDefinitions as $value) {
                    if ($value instanceof DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface) {
                        $key = $value->getName();

                        // if a datafield requires more than one column in the query table
                        if (is_array($value->getQueryColumnType())) {
                            foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                                $this->addModifyColumn($queryTable, $key.'__'.$fkey, $fvalue, '', 'NULL');
                                $protectedColumns[] = $key.'__'.$fkey;
                            }
                        } elseif ($value->getQueryColumnType()) {
                            $this->addModifyColumn($queryTable, $key, $value->getQueryColumnType(), '', 'NULL');
                            $protectedColumns[] = $key;
                        }

                        // add indices
                        $this->addIndexToField($value, $queryTable, 'getQueryColumnType');
                    }
                }

                // remove unused columns in the table
                $this->removeUnusedColumns($queryTable, $columnsToRemove, $protectedColumns);

                if ($container instanceof DataObject\ClassDefinition) {
                    $this->updateCompositeIndices($queryTable, 'localized_query', $this->model->getClass()->getCompositeIndices());
                }
            }
        }

        if ($container instanceof DataObject\ClassDefinition) {
            $this->updateCompositeIndices($table, 'localized_store', $this->model->getClass()->getCompositeIndices());
            $this->createLocalizedViews();
        }

        $this->tableDefinitions = [];
    }

    public function getFieldDefinitionParams(string $fieldname, string $language, array $extraParams = []): array
    {
        return array_merge(
            [
                'owner' => $this->model,
                'fieldname' => $fieldname,
                'language' => $language,
            ],
            $extraParams
        );
    }
}
