<?php
declare(strict_types=1);

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

namespace Pimcore\Model\Tool\Email;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Blacklist\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Blacklist extends Model\AbstractModel
{
    /**
     * @var string|null
     */
    protected ?string $address;

    /**
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @var int|null
     */
    protected ?int $modificationDate;

    /**
     * @param string $addr
     *
     * @return null|Blacklist
     */
    public static function getByAddress(string $addr): ?Blacklist
    {
        try {
            $address = new self();
            $address->getDao()->getByAddress($addr);

            return $address;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public function setAddress(string $address)
    {
        $this->address = $address;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCreationDate(int $creationDate)
    {
        $this->creationDate = (int) $creationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate(): int
    {
        if (!$this->creationDate) {
            $this->creationDate = time();
        }

        return $this->creationDate;
    }

    public function setModificationDate(int $modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate(): int
    {
        if (!$this->modificationDate) {
            $this->modificationDate = time();
        }

        return $this->modificationDate;
    }
}
