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

namespace Pimcore\Model\Element\Tag\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Tag\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of tags for the specified parameters, returns an array of Element\Tag elements
     *
     */
    public function load(): array
    {
        $tagsData = $this->db->fetchFirstColumn('SELECT id FROM tags' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $tags = [];
        foreach ($tagsData as $tagData) {
            if ($tag = Model\Element\Tag::getById($tagData)) {
                $tags[] = $tag;
            }
        }

        $this->model->setTags($tags);

        return $tags;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $tagsIds = $this->db->fetchFirstColumn('SELECT id FROM tags' . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return array_map('intval', $tagsIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM tags ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
