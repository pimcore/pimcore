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

namespace Pimcore\Model\Translation\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\Translation\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    public function getDatabaseTableName(): string
    {
        return Model\Translation\Dao::TABLE_PREFIX . $this->model->getDomain();
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');
        $queryBuilder->resetOrderBy();
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', (string) $queryBuilder);
        $amount = (int) $this->db->fetchOne($query, $this->model->getConditionVariables());

        return $amount;
    }

    public function getCount(): int
    {
        if (count($this->model->load()) > 0) {
            return count($this->model->load());
        }

        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', (string) $queryBuilder);
        $amount = (int) $this->db->fetchOne($query, $this->model->getConditionVariables());

        return $amount;
    }

    public function getAllTranslations(): array
    {
        $queryBuilder = $this->getQueryBuilder('*');
        $cacheKey = $this->getDatabaseTableName().'_data_' . md5((string)$queryBuilder);
        if (!empty($this->model->getConditionParams()) || !$translations = Cache::load($cacheKey)) {
            $translations = [];
            $queryBuilder->setMaxResults(null); //retrieve all results
            $translationsData = $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

            foreach ($translationsData as $t) {
                if (!isset($translations[$t['key']])) {
                    $translations[$t['key']] = new Model\Translation();
                    $translations[$t['key']]->setDomain($this->model->getDomain());
                    $translations[$t['key']]->setKey($t['key']);
                    $translations[$t['key']]->setType($t['type']);
                }

                $translations[$t['key']]->addTranslation($t['language'], $t['text']);

                //for legacy support
                if ($translations[$t['key']]->getModificationDate() < $t['creationDate']) {
                    $translations[$t['key']]->setDate($t['creationDate']);
                }

                $translations[$t['key']]->setCreationDate($t['creationDate']);
                $translations[$t['key']]->setModificationDate($t['modificationDate']);
            }

            if (empty($this->model->getConditionParams())) {
                Cache::save($translations, $cacheKey, ['translator', 'translate'], null, 999);
            }
        }

        return $translations;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function loadRaw(): array
    {
        $queryBuilder = $this->getQueryBuilder('*');
        $translationsData = $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return $translationsData;
    }

    public function load(): array
    {
        $this->model->setGroupBy($this->getDatabaseTableName() . '.key', false);

        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');
        $cacheKey = $this->getDatabaseTableName().'_data_' . md5((string)$queryBuilder);

        if (!empty($this->model->getConditionParams()) || !$translations = Cache::load($cacheKey)) {
            $translations = [];
            $translationsData = $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
            foreach ($translationsData as $t) {
                $transObj = Model\Translation::getByKey(id: $t['key'], domain: $this->model->getDomain(), languages: $this->model->getLanguages());

                if ($transObj) {
                    $translations[] = $transObj;
                }
            }

            if (empty($this->model->getConditionParams())) {
                Cache::save($translations, $cacheKey, ['translator', 'translate'], null, 999);
            }
        }

        $this->model->setTranslations($translations);

        return $translations;
    }

    public function isCacheable(): bool
    {
        $count = $this->db->fetchOne('SELECT COUNT(*) FROM ' . $this->getDatabaseTableName());
        $cacheLimit = Model\Translation\Listing::getCacheLimit();
        if ($count > $cacheLimit) {
            return false;
        }

        return true;
    }

    public function cleanup(): void
    {
        $keysToDelete = $this->db->fetchFirstColumn('SELECT `key` FROM ' . $this->getDatabaseTableName() . ' as tbl1 WHERE
               (SELECT count(*) FROM ' . $this->getDatabaseTableName() . " WHERE `key` = tbl1.`key` AND (`text` IS NULL OR `text` = ''))
               = (SELECT count(*) FROM " . $this->getDatabaseTableName() . ' WHERE `key` = tbl1.`key`) GROUP BY `key`;');

        if ($keysToDelete) {
            $preparedKeys = [];
            foreach ($keysToDelete as $value) {
                $preparedKeys[] = $this->db->quote($value);
            }

            $this->db->executeStatement('DELETE FROM ' . $this->getDatabaseTableName() . ' WHERE ' . '`key` IN (' . implode(',', $preparedKeys) . ')');
        }
    }

    /**
     * @param string|string[]|null $columns
     *
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getDatabaseTableName());

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }
}
