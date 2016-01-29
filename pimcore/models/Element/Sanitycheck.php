<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Sanitycheck extends Model\AbstractModel
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  int $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    /**
     * saves sanity check to db
     *
     * @return void
     */
    public function save()
    {
        $this->getDao()->save();
    }

    /**
     * deletes sanity check from db
     *
     * @return void
     */
    public function delete()
    {
        $this->getDao()->delete();
    }


    /**
     * @static
     * @return Sanitycheck
     */
    public static function getNext()
    {
        $sanityCheck = new Sanitycheck();
        $sanityCheck->getDao()->getNext();
        if ($sanityCheck->getId() and $sanityCheck->getType()) {
            return $sanityCheck;
        } else {
            return null;
        }
    }
}
