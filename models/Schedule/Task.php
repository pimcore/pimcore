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

namespace Pimcore\Model\Schedule;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Schedule\Task\Dao getDao()
 * @method void save()
 */
class Task extends Model\AbstractModel
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
    protected int $date;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var int|null
     */
    protected ?int $version;

    protected bool $active = false;

    /**
     * @var int|null
     */
    protected ?int $userId;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById(int $id): ?Task
    {
        $cacheKey = 'scheduled_task_' . $id;

        try {
            $task = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$task) {
                throw new \Exception('Scheduled Task in Registry is not valid');
            }
        } catch (\Exception $e) {
            try {
                $task = new self();
                $task->getDao()->getById((int)$id);
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $task);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $task;
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function create(array $data): Task
    {
        $task = new self();
        self::checkCreateData($data);
        $task->setValues($data);

        return $task;
    }

    public function __construct(array $data = [])
    {
        $this->setValues($data);
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
    public function getCid(): int
    {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return int
     */
    public function getDate(): int
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return int|null
     */
    public function getVersion(): ?int
    {
        return $this->version;
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
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
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
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param int|null $version
     *
     * @return $this
     */
    public function setVersion(?int $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive(bool $active): static
    {
        if (empty($active)) {
            $active = false;
        }
        $this->active = (bool) $active;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }
}
