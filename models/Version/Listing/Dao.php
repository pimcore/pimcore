<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Version\Listing;

use Exception;
use Pimcore;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Version\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    public function getCondition(): string
    {
        $condition = parent::getCondition();
        if ($this->model->isLoadAutoSave() == false) {
            if (trim($condition)) {
                $condition .= ' AND autoSave = 0';
            } else {
                $condition = ' WHERE autoSave = 0';
            }
        }

        return $condition;
    }

    /**
     * Loads a list of versions for the specicified parameters, returns an array of Version elements
     *
     * @return Model\Version[]
     */
    public function load(): array
    {
        $versionsData = $this->db->fetchAllAssociative(
            'SELECT * FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );
        $versions = [];
        $modelFactory = Pimcore::getContainer()->get('pimcore.model.factory');

        foreach ($versionsData as $versionData) {
            $versionData['public'] = (bool)$versionData['public'];
            $versionData['serialized'] = (bool)$versionData['serialized'];
            $versionData['autoSave'] = (bool)$versionData['autoSave'];

            /** @var Model\Version $version */
            $version = $modelFactory->build(Model\Version::class);
            $version->getDao()->assignVariablesToModel($versionData);

            $versions[] = $version;
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $versionIds = $this->db->fetchFirstColumn('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $versionIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM versions ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
