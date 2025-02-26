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
use Pimcore;
use Pimcore\Event\Model\ModelEvent;
use Pimcore\Event\NoteEvents;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\Note\Dao getDao()
 * @method void delete()
 */
final class Note extends Model\AbstractModel
{
    /**
     * @internal
     */
    protected ?int $id = null;

    /**
     * @internal
     */
    protected string $type;

    /**
     * @internal
     */
    protected int $cid;

    /**
     * @internal
     */
    protected string $ctype;

    /**
     * @internal
     */
    protected int $date;

    /**
     * @internal
     */
    protected ?int $user = null;

    /**
     * @internal
     */
    protected string $title = '';

    /**
     * @internal
     */
    protected string $description = '';

    /**
     * @internal
     */
    protected array $data = [];

    /**
     * If the note is locked, it can't be deleted in the admin interface
     *
     * @internal
     */
    protected bool $locked = true;

    public static function getById(int $id): ?Note
    {
        try {
            $note = new self();
            $note->getDao()->getById($id);

            return $note;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public function addData(string $name, string $type, mixed $data): static
    {
        $this->data[$name] = [
            'type' => $type,
            'data' => $data,
        ];

        return $this;
    }

    public function setElement(ElementInterface $element): static
    {
        $this->setCid($element->getId());
        $this->setCtype(Service::getElementType($element));

        return $this;
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        // check if there's a valid user
        if (!$this->getUser()) {
            // try to use the logged in user
            if (Pimcore::inAdmin()) {
                if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                    $this->setUser($user->getId());
                }
            }
        }

        $isUpdate = $this->getId() ? true : false;
        $this->getDao()->save();

        if (!$isUpdate) {
            Pimcore::getEventDispatcher()->dispatch(new ModelEvent($this), NoteEvents::POST_ADD);
        }
    }

    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setUser(int $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?int
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }
}
