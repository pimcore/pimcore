<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Helper;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick;

class GridHelperService
{

    /**
     * @param string $filterJson
     * @param ClassDefinition $class
     * @param string $requestedLanguage
     * @return array
     */
    public function getFeatureFilters(string $filterJson, ClassDefinition $class, string $requestedLanguage): array
    {
        $joins = [];
        $conditions = [];

        if ($filterJson) {
            $filters = json_decode($filterJson, true);
            foreach ($filters as $filter) {
                $operator = '=';

                $filterField = $filter['property'];
                $filterOperator = $filter['operator'];

                if ($filter['type'] == 'string') {
                    $operator = 'LIKE';
                } elseif ($filter['type'] == 'numeric') {
                    if ($filterOperator == 'lt') {
                        $operator = '<';
                    } elseif ($filterOperator == 'gt') {
                        $operator = '>';
                    } elseif ($filterOperator == 'eq') {
                        $operator = '=';
                    }
                } elseif ($filter['type'] == 'date') {
                    if ($filterOperator == 'lt') {
                        $operator = '<';
                    } elseif ($filterOperator == 'gt') {
                        $operator = '>';
                    } elseif ($filterOperator == 'eq') {
                        $operator = '=';
                    }
                    $filter['value'] = strtotime($filter['value']);
                } elseif ($filter['type'] == 'list') {
                    $operator = '=';
                } elseif ($filter['type'] == 'boolean') {
                    $operator = '=';
                    $filter['value'] = (int)$filter['value'];
                }

                $keyParts = explode('~', $filterField);

                if (substr($filterField, 0, 1) != '~') {
                    continue;
                }

                $type = $keyParts[1];
                if ($type != 'classificationstore') {
                    continue;
                }

                $fieldName = $keyParts[2];
                $groupKeyId = explode('-', $keyParts[3]);

                /** @var $csFieldDefinition Model\DataObject\ClassDefinition\Data\Classificationstore */
                $csFieldDefinition = $class->getFieldDefinition($fieldName);

                $language = $requestedLanguage;
                if (!$csFieldDefinition->isLocalized()) {
                    $language = 'default';
                }

                $groupId = $groupKeyId[0];
                $keyid = $groupKeyId[1];

                $keyConfig = Model\DataObject\Classificationstore\KeyConfig::getById($keyid);
                $type = $keyConfig->getType();
                $definition = json_decode($keyConfig->getDefinition());
                $field = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                if ($field instanceof Model\DataObject\ClassDefinition\Data) {
                    $mappedKey = 'cskey_' . $fieldName . '_' . $groupId . '_' . $keyid;
                    $joins[] = ['fieldname' => $fieldName, 'groupId' => $groupId, 'keyId' => $keyid, 'language' => $language];
                    $condition = $field->getFilterConditionExt(
                        $filter['value'],
                        $operator,
                        [
                            'name' => $mappedKey]
                    );

                    $conditions[$mappedKey] = $condition;
                }
            }
        }

        $result = [
            'joins' => $joins,
            'conditions' => $conditions
        ];

        return $result;
    }

    /**
     *
     * @param string $filterJson
     * @param ClassDefinition $class
     *
     * @return string
     */
    public function getFilterCondition($filterJson, ClassDefinition $class): string
    {
        $systemFields = Model\DataObject\Service::getSystemFields();

        // create filter condition
        $conditionPartsFilters = [];

        if ($filterJson) {
            $db = \Pimcore\Db::get();
            $filters = json_decode($filterJson, true);
            foreach ($filters as $filter) {
                $operator = '=';

                $filterField = $filter['property'];
                $filterOperator = $filter['operator'];

                if ($filter['type'] == 'string') {
                    $operator = 'LIKE';
                } elseif ($filter['type'] == 'numeric') {
                    if ($filterOperator == 'lt') {
                        $operator = '<';
                    } elseif ($filterOperator == 'gt') {
                        $operator = '>';
                    } elseif ($filterOperator == 'eq') {
                        $operator = '=';
                    }
                } elseif ($filter['type'] == 'date') {
                    if ($filterOperator == 'lt') {
                        $operator = '<';
                    } elseif ($filterOperator == 'gt') {
                        $operator = '>';
                    } elseif ($filterOperator == 'eq') {
                        $operator = '=';
                    }
                    $filter['value'] = strtotime($filter['value']);
                } elseif ($filter['type'] == 'list') {
                    $operator = '=';
                } elseif ($filter['type'] == 'boolean') {
                    $operator = '=';
                    $filter['value'] = (int)$filter['value'];
                }

                $field = $class->getFieldDefinition($filterField);
                $brickField = null;
                $brickType = null;
                if (!$field) {

                    // if the definition doesn't exist check for a localized field
                    $localized = $class->getFieldDefinition('localizedfields');
                    if ($localized instanceof ClassDefinition\Data\Localizedfields) {
                        $field = $localized->getFieldDefinition($filterField);
                    }

                    //if the definition doesn't exist check for object brick
                    $keyParts = explode('~', $filterField);

                    if (substr($filterField, 0, 1) == '~') {
                        // not needed for now
//                            $type = $keyParts[1];
//                            $field = $keyParts[2];
//                            $keyid = $keyParts[3];
                    } elseif (count($keyParts) > 1) {
                        $brickType = $keyParts[0];
                        $brickKey = $keyParts[1];

                        $key = Model\DataObject\Service::getFieldForBrickType($class, $brickType);
                        $field = $class->getFieldDefinition($key);

                        if (strpos($brickType, '?') !== false) {
                            $brickDescriptor = substr($brickType, 1);
                            $brickDescriptor = json_decode($brickDescriptor, true);
                            $brickType = $brickDescriptor['containerKey'];
                        }

                        $brickClass = Objectbrick\Definition::getByKey($brickType);

                        if ($brickDescriptor) {
                            $brickField = $brickClass->getFieldDefinition('localizedfields')->getFieldDefinition($brickDescriptor['brickfield']);
                        } else {
                            $brickField = $brickClass->getFieldDefinition($brickKey);
                        }
                    }
                }
                if ($field instanceof ClassDefinition\Data\Objectbricks || $brickDescriptor) {
                    // custom field
                    $db = \Pimcore\Db::get();
                    $brickPrefix = '';

                    $ommitPrefix = false;

                    if ($brickField instanceof Model\DataObject\ClassDefinition\Data\Checkbox
                        || (($brickField instanceof Model\DataObject\ClassDefinition\Data\Date || $brickField instanceof Model\DataObject\ClassDefinition\Data\Datetime) && $brickField->getColumnType() == 'datetime')
                    ) {
                        $ommitPrefix = true;
                    }

                    if (!$ommitPrefix) {
                        if ($brickDescriptor) {
                            $brickPrefix = $db->quoteIdentifier($brickType . '_localized') . '.';
                        } else {
                            $brickPrefix = $db->quoteIdentifier($brickType) . '.';
                        }
                    }
                    if (is_array($filter['value'])) {
                        $fieldConditions = [];
                        foreach ($filter['value'] as $filterValue) {
                            $fieldConditions[] = $brickPrefix . $brickField->getFilterCondition($filterValue, $operator,
                                    ['brickType' => $brickType]
                                );
                        }
                        $conditionPartsFilters[] = '(' . implode(' OR ', $fieldConditions) . ')';
                    } else {
                        $conditionPartsFilters[] = $brickPrefix . $brickField->getFilterCondition($filter['value'], $operator,
                                ['brickType' => $brickType]);
                    }
                } elseif ($field instanceof ClassDefinition\Data) {
                    // custom field
                    if (is_array($filter['value'])) {
                        $fieldConditions = [];
                        foreach ($filter['value'] as $filterValue) {
                            $fieldConditions[] = $field->getFilterCondition($filterValue, $operator);
                        }

                        if (!empty($fieldConditions)) {
                            $conditionPartsFilters[] = '(' . implode(' OR ', $fieldConditions) . ')';
                        }
                    } else {
                        $conditionPartsFilters[] = $field->getFilterCondition($filter['value'], $operator);
                    }
                } elseif (in_array('o_' . $filterField, $systemFields)) {
                    // system field
                    if ($filterField == 'fullpath') {
                        $conditionPartsFilters[] = 'concat(o_path, o_key) ' . $operator . ' ' . $db->quote('%' . $filter['value'] . '%');
                    } elseif ($filterField == 'key') {
                        $conditionPartsFilters[] = 'o_key ' . $operator . ' ' . $db->quote('%' . $filter['value'] . '%');
                    } elseif ($filterField == 'id') {
                        $conditionPartsFilters[] = 'oo_id ' . $operator . ' ' . $db->quote($filter['value']);
                    } else {
                        if ($filter['type'] == 'date' && $operator == '=') {
                            //if the equal operator is chosen with the date type, condition has to be changed
                            $maxTime = $filter['value'] + (86400 - 1); //specifies the top point of the range used in the condition
                            $conditionPartsFilters[] = '`o_' . $filterField . '` BETWEEN ' . $db->quote($filter['value']) . ' AND ' . $db->quote($maxTime);
                        } else {
                            $conditionPartsFilters[] = '`o_' . $filterField . '` ' . $operator . ' ' . $db->quote($filter['value']);
                        }
                    }
                }
            }
        }

        $conditionFilters = '1 = 1';
        if (count($conditionPartsFilters) > 0) {
            $conditionFilters = '(' . implode(' AND ', $conditionPartsFilters) . ')';
        }
        Logger::log('DataObjectController filter condition:' . $conditionFilters);

        return $conditionFilters;
    }


    /**
     * @param array $fieldsParameter
     * @return array
     */
    protected function extractBricks(array $fields): array
    {
        $bricks = [];
        if ($fields) {
            foreach ($fields as $f) {
                $fieldName = $f;
                $parts = explode('~', $f);
                if (substr($f, 0, 1) == '~') {
                    // key value, ignore for now
                } elseif (count($parts) > 1) {
                    $brickType = $parts[0];

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $brickType = $brickDescriptor['containerKey'];
                    }

                    $bricks[$brickType] = $brickType;
                }
                $newFields[] = $fieldName;
            }
        }

        return $bricks;
    }

    /**
     * Adds all the query stuff that is needed for displaying, filtering and exporting the feature grid data.
     *
     * @param DataObject\Listing\Concrete $list
     * @param array $featureJoins
     * @param ClassDefinition $class
     * @param array $featureFilters
     */
    public function addGridFeatureJoins(DataObject\Listing\Concrete $list, array $featureJoins, ClassDefinition $class, array $featureFilters)
    {
        if ($featureJoins) {
            $me = $list;
            $list->onCreateQuery(function (Db\ZendCompatibility\QueryBuilder $select) use ($list, $featureJoins, $class, $featureFilters, $me) {
                $db = \Pimcore\Db::get();

                $alreadyJoined = [];

                foreach ($featureJoins as $featureJoin) {
                    $fieldname = $featureJoin['fieldname'];
                    $mappedKey = 'cskey_' . $fieldname . '_' . $featureJoin['groupId'] . '_' . $featureJoin['keyId'];
                    if (isset($alreadyJoined[$mappedKey]) && $alreadyJoined[$mappedKey]) {
                        continue;
                    }
                    $alreadyJoined[$mappedKey] = 1;

                    $table = $me->getDao()->getTableName();
                    $select->joinLeft(
                        [$mappedKey => 'object_classificationstore_data_' . $class->getId()],
                        '('
                        . $mappedKey . '.o_id = ' . $table . '.o_id'
                        . ' and ' . $mappedKey . '.fieldname = ' . $db->quote($fieldname)
                        . ' and ' . $mappedKey . '.groupId=' . $featureJoin['groupId']
                        . ' and ' . $mappedKey . '.keyId=' . $featureJoin['keyId']
                        . ' and ' . $mappedKey . '.language = ' . $db->quote($featureJoin['language'])
                        . ')',
                        [
                            $mappedKey => 'value'
                        ]
                    );
                }

                $havings = $featureFilters['conditions'];
                if ($havings) {
                    $havings = implode(' AND ', $havings);
                    $select->having($havings);
                }
            });
        }
    }



    public function prepareListingForGrid(array $requestParams, string $requestedLanguage, $adminUser): DataObject\Listing\Concrete {

        $folder = Model\DataObject::getById($requestParams['folderId']);
        $class = ClassDefinition::getById($requestParams['classId']);
        $className = $class->getName();

        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';
        /**
         * @var $list DataObject\Listing\Concrete
         */
        $list = new $listClass();



        $colMappings = [
            'key' => 'o_key',
            'filename' => 'o_key',
            'fullpath' => ['o_path', 'o_key'],
            'id' => 'oo_id',
            'published' => 'o_published',
            'modificationDate' => 'o_modificationDate',
            'creationDate' => 'o_creationDate'
        ];

        $start = 0;
        $limit = 20;
        $orderKey = 'oo_id';
        $order = 'ASC';

        $fields = [];
        $bricks = [];
        if ($requestParams['fields']) {
            $fields = $requestParams['fields'];
            $bricks = $this->extractBricks($fields);
        }

        if ($requestParams['limit']) {
            $limit = $requestParams['limit'];
        }
        if ($requestParams['start']) {
            $start = $requestParams['start'];
        }

        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($requestParams);
        $doNotQuote = false;

        if ($sortingSettings['order']) {
            $order = $sortingSettings['order'];
        }
        if ($sortingSettings['orderKey'] !== null && strlen($sortingSettings['orderKey']) > 0) {
            $orderKey = $sortingSettings['orderKey'];
            if (!(substr($orderKey, 0, 1) == '~')) {
                if (array_key_exists($orderKey, $colMappings)) {
                    $orderKey = $colMappings[$orderKey];
                } elseif ($class->getFieldDefinition($orderKey) instanceof  ClassDefinition\Data\QuantityValue) {
                    $orderKey = 'concat(' . $orderKey . '__unit, ' . $orderKey . '__value)';
                    $doNotQuote = true;
                } elseif ($class->getFieldDefinition($orderKey) instanceof  ClassDefinition\Data\RgbaColor) {
                    $orderKey = 'concat(' . $orderKey . '__rgb, ' . $orderKey . '__a)';
                    $doNotQuote = true;
                } elseif (strpos($orderKey, '~') !== false) {
                    $orderKeyParts = explode('~', $orderKey);

                    if (strpos($orderKey, '?') !== false) {
                        $brickDescriptor = substr($orderKeyParts[0], 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $db = Db::get();
                        $orderKey = $db->quoteIdentifier($brickDescriptor['containerKey'] . '_localized') . '.' . $db->quoteIdentifier($brickDescriptor['brickfield']);
                        $doNotQuote = true;
                    } else {
                        if (count($orderKeyParts) == 2) {
                            $orderKey = $orderKeyParts[1];
                        }
                    }
                }
            }
        }


        $conditionFilters = [];
        if ($requestParams['only_direct_children'] == 'true') {
            $conditionFilters[] = 'o_parentId = ' . $folder->getId();
        } else {
            $quotedPath = $list->quote($folder->getRealFullPath());
            $quotedWildcardPath = $list->quote(str_replace('//', '/', $folder->getRealFullPath() . '/') . '%');
            $conditionFilters[] = '(o_path = ' . $quotedPath . ' OR o_path LIKE ' . $quotedWildcardPath . ')';
        }

        if (!$adminUser->isAdmin()) {
            $userIds = $adminUser->getRoles();
            $userIds[] = $adminUser->getId();
            $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
        }

        $featureJoins = [];
        $featureFilters = [];

        // create filter condition
        if ($requestParams['filter']) {
            $conditionFilters[] = $this->getFilterCondition($requestParams['filter'], $class);
            $featureFilters = $this->getFeatureFilters($requestParams['filter'], $class, $requestedLanguage);
            if ($featureFilters) {
                $featureJoins = array_merge($featureJoins, $featureFilters['joins']);
            }
        }

        if ($requestParams['condition'] && $adminUser->isAdmin()) {
            $conditionFilters[] = '(' . $requestParams['condition'] . ')';
        }

        if ($requestParams['query']) {
            $query = $this->filterQueryParam($requestParams['query']);
            if (!empty($query)) {
                $conditionFilters[] = 'oo_id IN (SELECT id FROM search_backend_data WHERE MATCH (`data`,`properties`) AGAINST (' . $list->quote($query) . ' IN BOOLEAN MODE))';
            }
        }

        if (!empty($bricks)) {
            foreach ($bricks as $b) {
                $brickType = $b;
                if (is_array($brickType)) {
                    $brickType = $brickType['containerKey'];
                }
                $list->addObjectbrick($brickType);
            }
        }

        $list->setCondition(implode(' AND ', $conditionFilters));
        $list->setLimit($limit);
        $list->setOffset($start);

        if (isset($sortingSettings['isFeature']) && $sortingSettings['isFeature']) {
            $orderKey = 'cskey_' . $sortingSettings['fieldname'] . '_' . $sortingSettings['groupId'] . '_' . $sortingSettings['keyId'];
            $list->setOrderKey($orderKey);
            $list->setGroupBy('o_id');

            $parts = explode('_', $orderKey);

            $fieldname = $parts[1];
            /** @var $csFieldDefinition DataObject\ClassDefinition\Data\Classificationstore */
            $csFieldDefinition = $class->getFieldDefinition($fieldname);
            $sortingSettings['language'] = $csFieldDefinition->isLocalized() ? $requestedLanguage : 'default';
            $featureJoins[] = $sortingSettings;
        } else {
            $list->setOrderKey($orderKey, !$doNotQuote);
        }
        $list->setOrder($order);

        //parameters specified in the objects grid
        if($requestParams['ids']) {
            $quotedIds = [];
            foreach ($requestParams['ids'] as $id) {
                $quotedIds[] = $list->quote($id);
            }
            if (!empty($quotedIds)) {
                //add a condition if id numbers are specified
                $list->addConditionParam("oo_id IN (" . implode(',', $quotedIds) . ')');
            }

        }

        if ($class->getShowVariants()) {
            $list->setObjectTypes([DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);
        }

        $this->addGridFeatureJoins($list, $featureJoins, $class, $featureFilters);
        $list->setLocale($requestedLanguage);

        return $list;
    }

    /**
     * @param string $query
     *
     * @return string
     */
    protected function filterQueryParam(string $query)
    {
        if ($query == '*') {
            $query = '';
        }

        $query = str_replace('%', '*', $query);
        $query = str_replace('@', '#', $query);
        $query = preg_replace("@([^ ])\-@", '$1 ', $query);

        $query = str_replace(['<', '>', '(', ')', '~'], ' ', $query);

        // it is not allowed to have * behind another *
        $query = preg_replace('#[*]+#', '*', $query);

        // no boolean operators at the end of the query
        $query = rtrim($query, '+- ');

        return $query;
    }
}
