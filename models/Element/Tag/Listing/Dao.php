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

namespace Pimcore\Model\Element\Tag\Listing;

use Exception;
use Pimcore;
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
        $tagsData = $this->db->fetchAllAssociative(
            'SELECT * FROM tags' .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes(),
        );

        $tags = [];
        $modelFactory = Pimcore::getContainer()->get('pimcore.model.factory');
        foreach ($tagsData as $tagData) {
            /** @var Model\Element\Tag $tag */
            $tag = $modelFactory->build(Model\Element\Tag::class);
            $tag->getDao()->assignVariablesToModel($tagData);

            $tags[] = $tag;
        }

        $this->model->setTags($tags);

        return $tags;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $tagsIds = $this->db->fetchFirstColumn('SELECT id FROM tags' . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $tagsIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM tags ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
