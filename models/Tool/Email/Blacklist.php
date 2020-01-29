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

namespace Pimcore\Model\Tool\Email;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Email\Blacklist\Dao getDao()
 * @method \Pimcore\Model\Tool\Email\Blacklist\Dao delete()
 * @method \Pimcore\Model\Tool\Email\Blacklist\Dao save()
 */
class Blacklist extends Model\AbstractModel
{
    /**
     * @var string
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
     * @param string $addr
     *
     * @return null|Blacklist
     */
    public static function getByAddress($addr)
    {
        try {
            $address = new self();
            $address->getDao()->getByAddress($addr);

            return $address;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
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
        $this->creationDate = (int) $creationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        if (!$this->creationDate) {
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
        if (!$this->modificationDate) {
            $this->modificationDate = time();
        }

        return $this->modificationDate;
    }
}
