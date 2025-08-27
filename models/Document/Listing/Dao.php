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

namespace Pimcore\Model\Document\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return Document[]
     */
    public function load(): array
    {
        $documents = [];
        $select = $this->getQueryBuilder('documents.id', 'documents.type');

        $documentsData = $this->db->fetchAllAssociative($select->getSQL(), $select->getParameters(), $select->getParameterTypes());

        foreach ($documentsData as $documentData) {
            if ($documentData['type']) {
                if ($doc = Document::getById($documentData['id'])) {
                    $documents[] = $doc;
                }
            }
        }

        $this->model->setDocuments($documents);

        return $documents;
    }

    /**
     * @param string|string[]|null $columns
     *
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from('documents');

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder('documents.id');
        $documentIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map('intval', $documentIds);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function loadIdPathList(): array
    {
        $queryBuilder = $this->getQueryBuilder('documents.id', 'CONCAT(documents.path, documents.key) as `path`');
        $documentIds = $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return $documentIds;
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getDocuments());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    public function getTotalCount(): int
    {
        $identifierColumn = 'documents.id';
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder, $identifierColumn);

        if (
            $this->isQueryBuilderPartInUse($queryBuilder, 'groupBy') ||
            $this->isQueryBuilderPartInUse($queryBuilder, 'having')
        ) {
            return (int)$this->db->fetchOne('SELECT COUNT(*)  FROM (' . $queryBuilder->getSQL() . ') as XYZ');
        }

        return (int)$this->db->fetchOne(
            $queryBuilder->getSql(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );
    }
}
