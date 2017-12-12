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

namespace Pimcore\DataObject\Import;

use DeepCopy\DeepCopy;
use Pimcore\DataObject\Import\ColumnConfig\ConfigElementInterface;
use Pimcore\DataObject\Import\ColumnConfig\Operator\Factory\OperatorFactoryInterface;
use Pimcore\DataObject\Import\ColumnConfig\Operator\OperatorInterface;
use Pimcore\DataObject\Import\ColumnConfig\Value\Factory\ValueFactoryInterface;
use Pimcore\DataObject\Import\ColumnConfig\Value\ValueInterface;
use Pimcore\DataObject\Import\Resolver\ResolverInterface;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\GridConfig;
use Pimcore\Model\ImportConfig;
use Pimcore\Tool;
use Psr\Container\ContainerInterface;

class Service
{
    const FORBIDDEN_KEYS = ['id', 'fullpath', 'filename', 'published', 'creationDate', 'modificationDate'];

    /**
     * @var Db\Connection
     */
    private $db;

    /**
     * @var ContainerInterface
     */
    private $resolvers;

    /**
     * @var ContainerInterface
     */
    private $operatorFactories;

    /**
     * @var ContainerInterface
     */
    private $valueFactories;

    public function __construct(
        Db\Connection $db,
        ContainerInterface $resolvers,
        ContainerInterface $operatorFactories,
        ContainerInterface $valueFactories
    )
    {
        $this->db                = $db;
        $this->resolvers         = $resolvers;
        $this->operatorFactories = $operatorFactories;
        $this->valueFactories    = $valueFactories;
    }

    public function getResolver(string $name): ResolverInterface
    {
        if (!$this->resolvers->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no resolver registered for "%s"', $name));
        }

        /** @var ResolverInterface $resolver */
        $resolver = $this->resolvers->get($name);

        return $resolver;
    }

    /**
     * @param \stdClass[] $jsonConfigs
     * @param mixed|null $context
     *
     * @return array
     */
    public function buildInputDataConfig(array $jsonConfigs, $context = null): array
    {
        $config = $this->doBuildConfig($jsonConfigs, [], $context);

        return $config;
    }

    /**
     * @param \stdClass[] $jsonConfigs
     * @param $config
     * @param mixed|null $context
     *
     * @return ConfigElementInterface[]
     */
    private function doBuildConfig(array $jsonConfigs, array $config, $context = null): array
    {
        if (empty($jsonConfigs)) {
            return $config;
        }

        foreach ($jsonConfigs as $configElement) {
            if ('Ignore' === $configElement->class) {
                continue;
            }

            if ('value' === $configElement->type) {
                $config[] = $this->buildValue($configElement->class, $configElement, $context);
            } elseif ('operator' === $configElement->type) {
                if (!empty($configElement->childs)) {
                    $configElement->childs = $this->doBuildConfig($configElement->childs, [], $context);
                }

                $config[] = $this->buildOperator($configElement->class, $configElement, $context);
            }
        }

        return $config;
    }

    private function buildOperator(string $name, \stdClass $configElement, $context = null): OperatorInterface
    {
        if (!$this->operatorFactories->has($name)) {
            throw new \InvalidArgumentException(sprintf('Operator "%s" is not supported', $name));
        }

        /** @var OperatorFactoryInterface $factory */
        $factory = $this->operatorFactories->get($name);

        return $factory->build($configElement, $context);
    }

    private function buildValue(string $name, \stdClass $configElement, $context = null): ValueInterface
    {
        if (!$this->valueFactories->has($name)) {
            throw new \InvalidArgumentException(sprintf('Value "%s" is not supported', $name));
        }

        /** @var ValueFactoryInterface $factory */
        $factory = $this->valueFactories->get($name);

        return $factory->build($configElement, $context);
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
        $configListingConditionParts = [];
        $configListingConditionParts[] = 'sharedWithUserId = ' . $userId;
        $configListingConditionParts[] = 'classId = ' . $classId;
        $configListing = [];

        $userIds = [$userId];
        // collect all roles
        $userIds = array_merge($userIds, $user->getRoles());
        $userIds = implode(',', $userIds);

        $query = 'select distinct c.id from importconfigs c, importconfig_shares s where '
            . ' c.id = s.importConfigId and s.sharedWithUserId IN (' . $userIds . ') and c.classId = ' . $classId
                . ' UNION distinct select c2.id from importconfigs c2 where shareGlobally = 1 and c2.classId = ' . $classId;

        $ids = $this->db->fetchCol($query);

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

    /**
     * @param $user
     * @param $classId
     *
     * @return ImportConfig\Listing
     */
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
        $class = ClassDefinition::getById($exportConfigData['classId']);

        $importConfigData->selectedGridColumns = [];
        if (is_array($exportConfigData['columns'])) {
            foreach ($exportConfigData['columns'] as $exportColumn) {
                $importColumn = $this->getImportColumn($class, $exportColumn);
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

    /**
     * @param $class ClassDefinition
     * @param $exportColumn
     *
     * @return array|\stdClass
     */
    public function getImportColumn($class, $exportColumn)
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

            $keyParts = explode('~', $fieldConfig['key']);

            if (isset($fieldConfig['key']) && count($keyParts) > 1) {
                // object brick

                $bricktype = $keyParts[0];
                $fieldname = \Pimcore\Model\DataObject\Service::getFieldForBrickType($class, $bricktype);
                $importColumn->attributes->class = 'ObjectBrickSetter';
                $importColumn->attributes->brickType = $bricktype;
                $importColumn->attributes->attr = $fieldname;
//                $importColumn->attributes->label = $fieldname;

                $bricksetter = new \stdClass();
                $bricksetter->type = 'value';
                $bricksetter->label = $fieldConfig['label'];
                $bricksetter->class = 'DefaultValue';
                $bricksetter->attribute = $fieldConfig['key'];
                $bricksetter->dataType = $fieldConfig['type'];
                $bricksetter->childs = [];
                $importColumn->attributes->childs[] = $bricksetter;
            } elseif ($fieldConfig['attributes']['type'] == 'operator' && $fieldConfig['attributes']['class'] == 'LFExpander') {
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

                            $newChild->dataType = $child['dataType'];
                            $newChild->label = $child['label'];
                            $newChild->class = 'DefaultValue';

                            $lfImportColumn->attributes->childs = [$newChild];
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
}
