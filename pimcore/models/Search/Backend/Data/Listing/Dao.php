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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Search\Backend\Data\Listing;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Search;

/**
 * @property \Pimcore\Model\Search\Backend\Data\Listing $model
 */
class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of entries for the specicifies parameters, returns an array of Search\Backend\Data
     *
     * @return array
     */
    public function load()
    {
        $entries = [];
        $data = $this->db->fetchAll('SELECT * FROM search_backend_data' .  $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $entryData) {
            if ($entryData['maintype'] == 'document') {
                $element = Document::getById($entryData['id']);
            } elseif ($entryData['maintype'] == 'asset') {
                $element = Asset::getById($entryData['id']);
            } elseif ($entryData['maintype'] == 'object') {
                $element = Object::getById($entryData['id']);
            } else {
                Logger::err('unknown maintype ');
            }
            if ($element) {
                $entry = new Search\Backend\Data();
                $entry->setId(new Search\Backend\Data\Id($element));
                $entry->setFullPath($entryData['fullpath']);
                $entry->setType($entryData['type']);
                $entry->setSubtype($entryData['subtype']);
                $entry->setUserOwner($entryData['userOwner']);
                $entry->setUserModification($entryData['userModification']);
                $entry->setCreationDate($entryData['creationDate']);
                $entry->setModificationDate($entryData['modificationDate']);
                $entry->setPublished($entryData['published'] === 0 ? false : true);
                $entries[]=$entry;
            }
        }
        $this->model->setEntries($entries);

        return $entries;
    }

    /**
     * @return string
     */
    public function getTotalCount()
    {
        $amount = $this->db->fetchOne('SELECT COUNT(*) as amount FROM search_backend_data' . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());

        return $amount;
    }

    /**
     * @return int|string
     */
    public function getCount()
    {
        if (count($this->model->getEntries()) > 0) {
            return count($this->model->getEntries());
        }

        $amount = $this->db->fetchOne('SELECT COUNT(*) as amount FROM search_backend_data '  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $amount;
    }

    /**
     * @return string
     */
    protected function getCondition()
    {
        if ($cond = $this->model->getCondition()) {
            return ' WHERE ' . $cond . ' ';
        }

        return '';
    }
}
