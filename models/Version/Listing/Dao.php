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

namespace Pimcore\Model\Version\Listing;

use Exception;
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
        $versions = [];
        $data = $this->loadIdList();

        foreach ($data as $id) {
            $versions[] = Model\Version::getById($id);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $versionIds = $this->db->fetchFirstColumn('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return array_map('intval', $versionIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM versions ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
