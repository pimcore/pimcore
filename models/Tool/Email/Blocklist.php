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
 * @method \Pimcore\Model\Tool\Email\Blocklist\Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Blocklist extends Model\AbstractModel
{
    protected ?string $address = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getByAddress(string $addr): ?Blocklist
    {
        try {
            $address = new self();
            $address->getDao()->getByAddress($addr);

            return $address;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getCreationDate(): int
    {
        if (!$this->creationDate) {
            $this->creationDate = time();
        }

        return $this->creationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getModificationDate(): int
    {
        if (!$this->modificationDate) {
            $this->modificationDate = time();
        }

        return $this->modificationDate;
    }
}
