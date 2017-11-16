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

use Pimcore\Db;
use Pimcore\Model\DataObject\ImportResolver\Code;
use Pimcore\Model\DataObject\ImportResolver\Id;
use Pimcore\Model\GridConfig;
use Pimcore\Model\ImportConfig;

class Service
{
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
            .  ' c.id = s.importConfigId and s.sharedWithUserId IN (' . $userIds . ') and c.classId = ' . $classId;
        $ids = $db->fetchCol($query);
        if ($ids) {
            $ids = implode(',', $ids);
            $configListing = new ImportConfig\Listing();
            $configListing->setOrderKey('name');
            $configListing->setOrder('ASC');
            $configListing->setCondition('id in (' . $ids .')');
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
//
//        $result = [];
//        if ($configListing) {
//            /** @var $item ImportConfig */
//            foreach ($configListing as $item) {
//                $result[] = [
//                    'id' => $item->getId(),
//                    'name' => $item->getName()
//                ];
//            }
//        }
//
//        return $result;
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
                $importColumn = new \stdClass();

                $importColumn->isOperator = true;
                $importColumn->attributes = new \stdClass();

                $importColumn->attributes->class = 'Ignore';

                $fieldConfig = $exportColumn['fieldConfig'];
                if ($fieldConfig['isOperator']
                    || (
                        isset($fieldConfig['key'])
                            && ($fieldConfig['key'] == 'fullpath' || strpos($fieldConfig['key'], '~') !== false))) {
                    $importColumn->attributes->type = 'operator';
                    $importColumn->attributes->label = $fieldConfig['attributes']['label'];
                } else {
                    $importColumn->attributes->type = 'value';
                    $importColumn->attributes->label = $fieldConfig['label'];
                    $importColumn->attributes->class = 'DefaultValue';
                    $importColumn->attributes->attribute = $fieldConfig['key'];
                    $importColumn->attributes->dataType = $fieldConfig['type'];
                }

                $importColumn->attributes->childs = [];

                $importConfigData->selectedGridColumns[] = $importColumn;
            }
        }

        return $importConfigData;
    }

    /**
     * @param $config
     * @return Id
     * @throws \Exception
     */
    public function getResolverImplementation($config)
    {
        switch ($config->resolverSettings->strategy) {
            case 'id':
                return new Id($config);
            case 'code':
                return new Code($config);
        }
        throw new \Exception("unknown/unsupported resolver implementation");
    }
}
