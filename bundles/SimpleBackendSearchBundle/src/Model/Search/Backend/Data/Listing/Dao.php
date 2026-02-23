<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data\Listing;

use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Logger;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Listing\Dao\AbstractDao;

/**
 * @internal
 *
 * @property Data\Listing $model
 */
class Dao extends AbstractDao
{
    /**
     * Loads a list of entries for the specicifies parameters, returns an array of Search\Backend\Data
     *
     */
    public function load(): array
    {
        $entries = [];
        $data = $this->db->fetchAllAssociative('SELECT * FROM search_backend_data' .  $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($data as $entryData) {
            if (!in_array($entryData['maintype'], ['document', 'asset', 'object'], true)) {
                Logger::err('unknown maintype');
            }

            $element = Service::getElementById($entryData['maintype'], $entryData['id']);

            if ($element) {
                $entry = new Search\Backend\Data();
                $entry->setId(new Search\Backend\Data\Id($element));
                $entry->setKey($entryData['key']);
                $entry->setIndex((int)$entryData['index']);
                $entry->setFullPath($entryData['fullpath']);
                $entry->setType($entryData['type']);
                $entry->setSubtype($entryData['subtype']);
                $entry->setUserOwner($entryData['userOwner']);
                $entry->setUserModification($entryData['userModification']);
                $entry->setCreationDate($entryData['creationDate']);
                $entry->setModificationDate($entryData['modificationDate']);
                $entry->setPublished($entryData['published'] !== 0);
                $entries[] = $entry;
            }
        }
        $this->model->setEntries($entries);

        return $entries;
    }

    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne('SELECT COUNT(*) FROM search_backend_data' . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getCount(): int|string
    {
        if (count($this->model->getEntries()) > 0) {
            return count($this->model->getEntries());
        }

        return $this->db->fetchOne('SELECT COUNT(*) as amount FROM search_backend_data '  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    protected function getCondition(): string
    {
        if ($cond = $this->model->getCondition()) {
            return ' WHERE ' . $cond . ' ';
        }

        return '';
    }
}
