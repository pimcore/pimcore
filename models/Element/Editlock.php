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

use Pimcore\Model;
use Pimcore\Tool\Session;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Editlock\Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class Editlock extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected int $id;

    /**
     * @var int
     */
    protected int $cid;

    /**
     * @var string
     */
    protected string $ctype;

    /**
     * @var int
     */
    protected int $userId;

    /**
     * @var string
     */
    protected string $sessionId;

    /**
     * @var int
     */
    protected int $date;

    /**
     * @var string
     */
    protected string $cpath;

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool
     */
    public static function isLocked(int $cid, string $ctype): bool
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            if ((time() - $lock->getDate()) > 3600 || $lock->getSessionId() === Session::getSessionId()) {
                // lock is out of date unlock it
                self::unlock($cid, $ctype);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return null|Editlock
     */
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

    /**
     * @param string $sessionId
     *
     * @return bool|null
     */
    public static function clearSession(string $sessionId): ?bool
    {
        try {
            $lock = new self();
            $lock->getDao()->clearSession($sessionId);

            return true;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool|Editlock
     */
    public static function lock(int $cid, string $ctype): Editlock|bool
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
        $lock->setSessionId(Session::getSessionId());
        $lock->save();

        return $lock;
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool
     */
    public static function unlock(int $cid, string $ctype): bool
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            $lock->delete();
        }

        return true;
    }

    /**
     * @return int
     */
    public function getCid(): int
    {
        return $this->cid;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $cid
     *
     * @return $this
     */
    public function setCid(int $cid): static
    {
        $this->cid = (int) $cid;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setUserId(int $userId): static
    {
        $this->userId = (int) $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = (string) $ctype;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = (string) $sessionId;

        return $this;
    }

    /**
     * @return Model\User|null
     */
    public function getUser(): ?Model\User
    {
        if ($user = Model\User::getById($this->getUserId())) {
            return $user;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDate(): int
    {
        return $this->date;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate(int $date): static
    {
        $this->date = (int) $date;

        return $this;
    }

    /**
     * @param string $cpath
     *
     * @return $this
     */
    public function setCpath(string $cpath): static
    {
        $this->cpath = $cpath;

        return $this;
    }

    /**
     * @return string
     */
    public function getCpath(): string
    {
        return $this->cpath;
    }
}
