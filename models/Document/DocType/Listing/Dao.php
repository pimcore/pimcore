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

namespace Pimcore\Model\Document\DocType\Listing;

use Pimcore\Model;
use Pimcore\Model\Document\DocType;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\DocType\Listing $model
 */
class Dao extends Model\Document\DocType\Dao
{
    public function loadList(): array
    {
        $docTypes = [];
        foreach ($this->loadIdList() as $id) {
            $docTypes[] = Model\Document\DocType::getById($id);
        }
        if ($this->model->getFilter()) {
            $docTypes = array_filter($docTypes, $this->model->getFilter());
        }
        if ($this->model->getOrder()) {
            usort($docTypes, $this->model->getOrder());
        } else {
            // Default sort if nothing else has been set
            usort($docTypes, self::sortByPriority(...));
        }

        $this->model->setDocTypes($docTypes);

        return $docTypes;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }

    /**
     * Sorts DocTypes by priority and falls back to group and name in case they are equal
     *
     *
     */
    public static function sortByPriority(DocType $a, DocType $b): int
    {
        if ($a->getPriority() === $b->getPriority()) {
            return strcasecmp($a->getGroup() . $a->getName(), $b->getGroup() . $b->getName());
        }

        return $a->getPriority() <=> $b->getPriority();
    }
}
