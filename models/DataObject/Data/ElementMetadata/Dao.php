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

namespace Pimcore\Model\DataObject\Data\ElementMetadata;

use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Data\ElementMetadata $model
 */
class Dao extends DataObject\Data\ObjectMetadata\Dao
{
    /**
     * @param DataObject\Concrete $source
     * @param int $destinationId
     * @param string $fieldname
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     * @param string $destinationType
     *
     * @return DataObject\Data\ElementMetadata|null
     */
    public function load(DataObject\Concrete $source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index, $destinationType = 'object')
    {
        if ($destinationType == 'object') {
            $typeQuery = " AND (type = 'object' or type = '')";
        } else {
            $typeQuery = ' AND type = ' . $this->db->quote($destinationType);
        }

        $dataRaw = $this->db->fetchAll('SELECT * FROM ' .
            $this->getTablename($source) . ' WHERE ' . $this->getTablename($source) .'.o_id = ? AND dest_id = ? AND fieldname = ? AND ownertype = ? AND ownername = ? and position = ? and `index` = ? ' . $typeQuery, [$source->getId(), $destinationId, $fieldname, $ownertype, $ownername, $position, $index]);
        if (!empty($dataRaw)) {
            $this->model->setElementTypeAndId($destinationType, $destinationId);
            $this->model->setFieldname($fieldname);
            $columns = $this->model->getColumns();
            foreach ($dataRaw as $row) {
                if (in_array($row['column'], $columns)) {
                    $setter = 'set' . ucfirst($row['column']);
                    $this->model->$setter($row['data']);
                }
            }

            return $this->model;
        } else {
            return null;
        }
    }
}
