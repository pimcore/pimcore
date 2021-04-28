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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Sanitycheck\Dao getDao()
 */
final class Sanitycheck extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  int $id
     *
     * @return $this
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
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * saves sanity check to db
     */
    public function save()
    {
        $this->getDao()->save();
    }

    /**
     * deletes sanity check from db
     */
    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @static
     *
     * @return Sanitycheck|null
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
