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

namespace Pimcore\Model\DataObject\ImportColumnConfig;

use DeepCopy\DeepCopy;
use Pimcore\Db;
use Pimcore\Model\DataObject\ImportResolver\Code;
use Pimcore\Model\DataObject\ImportResolver\Filename;
use Pimcore\Model\DataObject\ImportResolver\Fullpath;
use Pimcore\Model\DataObject\ImportResolver\GetBy;
use Pimcore\Model\DataObject\ImportResolver\Id;
use Pimcore\Model\GridConfig;
use Pimcore\Model\ImportConfig;
use Pimcore\Tool;

class Service
{
    const FORBIDDEN_KEYS = ['id', 'fullpath', 'filename', 'published', 'creationDate', 'modificationDate'];

    /**
     * @param $outputDataConfig
     *
     * @return ConfigElementInterface[]
     */
    public function buildInputDataConfig($outputDataConfig, $context = null)
    {
        $config = [];
        $config = $this->doBuildConfig($outputDataConfig, $config, $context);

        return $config;
    }

    /**
     * @param $jsonConfig
     * @param $config
     * @param null $context
     *
     * @return array
     */
    private function doBuildConfig($jsonConfig, $config, $context = null)
    {
        if (!empty($jsonConfig)) {
            foreach ($jsonConfig as $configElement) {
                if ($configElement->type == 'value') {
                    $name = 'Pimcore\\Model\\DataObject\\ImportColumnConfig\\Value\\' . ucfirst($configElement->class);

                    if (class_exists($name)) {
                        $config[] = new $name($configElement, $context);
                    }
                } elseif ($configElement->type == 'operator') {
                    $name = 'Pimcore\\Model\\DataObject\\ImportColumnConfig\\Operator\\' . ucfirst($configElement->class);

                    if (!empty($configElement->childs)) {
                        $configElement->childs = $this->doBuildConfig($configElement->childs, [], $context);
                    }

                    if (class_exists($name)) {
                        $operatorInstance = new $name($configElement, $context);
//                        if ($operatorInstance instanceof PHPCode) {
//                            $operatorInstance = $operatorInstance->getRealInstance();
//                        }
                        if ($operatorInstance) {
                            $config[] = $operatorInstance;
                        }
                    }
                }
            }
        }

        return $config;
    }

    /**
     * @param $user
     * @param $classId
     *
     * @return array|ImportConfig\Listing
     */
    public function getSharedImportConfigs($user, $classId)
    {
        $userId = $user->getId();
        $db = Db::get();
        $configListingConditionParts = [];
        $configListingConditionParts[] = 'sharedWithUserId = ' . $userId;
        $configListingConditionParts[] = 'classId = ' . $classId;
        $configListing = [];

        $userIds = [$userId];
        // collect all roles
        $userIds = array_merge($userIds, $user->getRoles());
        $userIds = implode(',', $userIds);

        $query = 'select distinct c.id from importconfigs c, importconfig_shares s where '
            . ' c.id = s.importConfigId and s.sharedWithUserId IN (' . $userIds . ') and c.classId = ' . $classId;
        $ids = $db->fetchCol($query);
        if ($ids) {
            $ids = implode(',', $ids);
            $configListing = new ImportConfig\Listing();
            $configListing->setOrderKey('name');
            $configListing->setOrder('ASC');
            $configListing->setCondition('id in (' . $ids . ')');
            $configListing = $configListing->load();
        }

        return $configListing;
    }

    public function getMyOwnImportConfigs($user, $classId)
    {
        $userId = $user->getId();
        $configListingConditionParts = [];
        $configListingConditionParts[] = 'ownerId = ' . $userId;
        $configListingConditionParts[] = 'classId = ' . $classId;
        $configCondition = implode(' AND ', $configListingConditionParts);
        $configListing = new ImportConfig\Listing();
        $configListing->setOrderKey('name');
        $configListing->setOrder('ASC');
        $configListing->setCondition($configCondition);
        $configListing = $configListing->load();

        return $configListing;
    }

    /**
     * @param $gridConfig GridConfig
     */
    public function createFromExportConfig($gridConfig)
    {
        $importConfigData = new \stdClass();
        $exportConfigData = json_decode($gridConfig->getConfig(), true);

        $importConfigData->classId = $exportConfigData->classId;

        $importConfigData->selectedGridColumns = [];
        if (is_array($exportConfigData['columns'])) {
            foreach ($exportConfigData['columns'] as $exportColumn) {
                $importColumn = $this->getImportColumn($exportColumn);
                if (is_array($importColumn)) {
                    foreach ($importColumn as $item) {
                        $importConfigData->selectedGridColumns[] = $item;
                    }
                } else {
                    $importConfigData->selectedGridColumns[] = $importColumn;
                }
            }
        }

        return $importConfigData;
    }

    public function getImportColumn($exportColumn)
    {
        $importColumn = new \stdClass();

        $importColumn->isOperator = true;
        $importColumn->attributes = new \stdClass();

        $importColumn->attributes->class = 'Ignore';

        $fieldConfig = $exportColumn['fieldConfig'];
        if ($fieldConfig['isOperator'] || (isset($fieldConfig['key'])
                && (in_array($fieldConfig['key'], self::FORBIDDEN_KEYS) || strpos($fieldConfig['key'], '~') !== false))) {
            $importColumn->attributes->type = 'operator';
            $importColumn->attributes->label = $fieldConfig['attributes']['label'];
            $importColumn->attributes->childs = [];

            if ($fieldConfig['attributes']['type'] == 'operator' && $fieldConfig['attributes']['class'] == 'LFExpander') {
                $childs = $fieldConfig['attributes']['childs'];
                if (count($childs) == 1) {
                    $importColumns = [];
                    $child = $childs[0];
                    if (!$child['isOperator']) {
                        if ($fieldConfig['attributes']['locales']) {
                            $validLanguages = $fieldConfig['attributes']['locales'];
                        } else {
                            $validLanguages = Tool::getValidLanguages();
                        }
                        foreach ($validLanguages as $validLanguage) {
                            $copier = new DeepCopy();
                            $lfImportColumn = $copier->copy($importColumn);
                            $lfImportColumn->attributes->class = 'LocaleSwitcher';
                            $lfImportColumn->attributes->locale = $validLanguage;

                            $newChild = new \stdClass();
                            $newChild->attribute = $child['attribute'];
//                            $newChild->type = "value";

                            $newChild->dataType = $child['dataType'];;
                            $newChild->label = $child['label'];
                            $newChild->class = 'DefaultValue';

                            $importColumn->attributes->childs = [$newChild];
                            $importColumns[] = $lfImportColumn;
                        }

                        return $importColumns;
                    }
                }
            }
        } else {
            $importColumn->attributes->type = 'value';
            $importColumn->attributes->label = $fieldConfig['label'];
            $importColumn->attributes->class = 'DefaultValue';
            $importColumn->attributes->attribute = $fieldConfig['key'];
            $importColumn->attributes->dataType = $fieldConfig['type'];
            $importColumn->attributes->childs = [];
        }

        return $importColumn;
    }

    /**
     * @param $config
     *
     * @return Id
     *
     * @throws \Exception
     */
    public function getResolverImplementation($config)
    {
        switch ($config->resolverSettings->strategy) {
        case 'id':
            return new Id($config);
        case 'filename':
            return new Filename($config);
        case 'fullpath':
            return new Fullpath($config);
        case 'code':
            return new Code($config);
        case 'getBy':
            return new GetBy($config);
    }
        throw new \Exception('unknown/unsupported resolver implementation');
    }
}
