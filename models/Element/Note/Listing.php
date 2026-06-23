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

namespace Pimcore\Model\Element\Note;

use Pimcore\Model;

/**
 * @method Model\Element\Note\Listing\Dao getDao()
 * @method Model\Element\Note[] load()
 * @method Model\Element\Note|false current()
 * @method int[] loadIdList()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Element\Note[]|null $notes
     *
     * @return $this
     */
    public function setNotes(?array $notes): static
    {
        return $this->setData($notes);
    }

    /**
     * @return Model\Element\Note[]
     */
    public function getNotes(): array
    {
        return $this->getData();
    }
}
