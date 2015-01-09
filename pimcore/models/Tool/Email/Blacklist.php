<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Email;

use Pimcore\Model;

class Blacklist extends Model\AbstractModel {

    /**
     * @var int
     */
    public $address;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @param $addr
     * @return null|Blacklist
     */
    public static function getByAddress ($addr) {

        try {
            $address = new self();
            $address->getResource()->getByAddress($addr);

            return $address;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param int $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate =  (int) $creationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        if(!$this->creationDate) {
            $this->creationDate = time();
        }

        return $this->creationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        if(!$this->modificationDate) {
            $this->modificationDate = time();
        }
        return $this->modificationDate;
    }
}