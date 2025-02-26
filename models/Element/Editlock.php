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

namespace Pimcore\Model\Element;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Editlock\Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class Editlock extends Model\AbstractModel
{
    protected ?int $id = null;

    protected int $cid;

    protected string $ctype;

    protected int $userId;

    protected string $sessionId;

    protected int $date;

    protected string $cpath;

    public static function isLocked(int $cid, string $ctype, string $sessionId): bool
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            if ((time() - $lock->getDate()) > 3600 || $lock->getSessionId() === $sessionId) {
                // lock is out of date unlock it
                self::unlock($cid, $ctype);

                return false;
            }

            return true;
        }

        return false;
    }

    public static function getByElement(int $cid, string $ctype): ?Editlock
    {
        try {
            $lock = new self();
            $lock->getDao()->getByElement($cid, $ctype);

            return $lock;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function clearSession(string $sessionId): ?bool
    {
        try {
            $lock = new self();
            $lock->getDao()->clearSession($sessionId);

            return true;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function lock(int $cid, string $ctype, string $sessionId): Editlock|bool
    {
        // try to get user
        if (!$user = \Pimcore\Tool\Admin::getCurrentUser()) {
            return false;
        }

        $lock = new self();
        $lock->setCid($cid);
        $lock->setCtype($ctype);
        $lock->setDate(time());
        $lock->setUserId($user->getId());
        $lock->setSessionId($sessionId);
        $lock->save();

        return $lock;
    }

    public static function unlock(int $cid, string $ctype): bool
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            $lock->delete();
        }

        return true;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getUser(): ?Model\User
    {
        if ($user = Model\User::getById($this->getUserId())) {
            return $user;
        }

        return null;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function setCpath(string $cpath): static
    {
        $this->cpath = $cpath;

        return $this;
    }

    public function getCpath(): string
    {
        return $this->cpath;
    }
}
