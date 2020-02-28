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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Note;

use Pimcore\Model;

/**
 * @method Model\Element\Note\Listing\Dao getDao()
 * @method Model\Element\Note[] load()
 * @method Model\Element\Note current()
 * @method int[] loadIdList()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\Element\Note[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $notes = null;

    public function __construct()
    {
        $this->notes = & $this->data;
    }

    /**
     * @param Model\Element\Note[]|null $notes
     *
     * @return static
     */
    public function setNotes($notes)
    {
        return $this->setData($notes);
    }

    /**
     * @return Model\Element\Note[]
     */
    public function getNotes()
    {
        return $this->getData();
    }
}
