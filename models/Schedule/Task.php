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

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Schedule\Task\Dao getDao()
 * @method void save()
 */
class Task extends Model\AbstractModel
{
    protected ?int $id = null;

    protected ?int $cid = null;

    protected ?string $ctype = null;

    protected ?int $date = null;

    protected ?string $action = null;

    protected ?int $version = null;

    protected bool $active = false;

    protected ?int $userId = null;

    public static function getById(int $id): ?Task
    {
        $cacheKey = 'scheduled_task_' . $id;

        try {
            $task = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$task) {
                throw new Exception('Scheduled Task in Registry is not valid');
            }
        } catch (Exception $e) {
            try {
                $task = new self();
                $task->getDao()->getById($id);
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $task);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $task;
    }

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCid(?int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCtype(?string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDate(?int $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return $this
     */
    public function setVersion(?int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

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
