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

namespace Pimcore\Model\Translation\AbstractTranslation\Listing;

use Pimcore\Cache;
use Pimcore\Model;

/**
 * @property \Pimcore\Model\Translation\AbstractTranslation\Listing $model
 *
 * @deprecated
 */
abstract class Dao extends Model\Translation\Listing\Dao implements Dao\DaoInterface
{
    /**
     * @return array
     */
    public function getAllTranslations()
    {
        $cacheKey = static::getTableName().'_data';
        if (!$translations = Cache::load($cacheKey)) {
            $itemClass = static::getItemClass();
            $translations = [];

            $select = $this->db->select();

            // create base
            $select->from(
                [ static::getTableName()]
            );

            if ($this->onCreateQueryCallback) {
                $closure = $this->onCreateQueryCallback;
                $closure($select);
            }

            $translationsData = $this->db->fetchAll((string)$select);

            foreach ($translationsData as $t) {
                if (!isset($translations[$t['key']])) {
                    $translations[$t['key']] = new $itemClass();
                    $translations[$t['key']]->setKey($t['key']);
                }

                $translations[$t['key']]->addTranslation($t['language'], $t['text']);

                //for legacy support
                if ($translations[$t['key']]->getModificationDate() < $t['creationDate']) {
                    $translations[$t['key']]->setDate($t['creationDate']);
                }

                $translations[$t['key']]->setCreationDate($t['creationDate']);
                $translations[$t['key']]->setModificationDate($t['modificationDate']);
            }

            Cache::save($translations, $cacheKey, ['translator', 'translate'], 999);
        }

        return $translations;
    }
}
