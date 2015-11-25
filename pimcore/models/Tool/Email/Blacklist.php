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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
            $address->getDao()->getByAddress($addr);

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