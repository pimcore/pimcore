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
 * @package    Notification
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

declare(strict_types=1);

namespace Pimcore\Model;

use Pimcore\Cache;
use Pimcore\Event\Model\NotificationEvent;
use Pimcore\Event\NotificationEvents;

/**
 * @method Notification\Dao getDao()
 */
class Notification extends AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $creationDate;

    /**
     * @var string
     */
    protected $modificationDate;

    /**
     * @var User
     */
    protected $sender;

    /**
     * @var User
     */
    protected $recipient;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var Element\ElementInterface|null
     */
    protected $linkedElement;

    /**
     * @var string
     */
    protected $linkedElementType;

    /**
     * @var bool
     */
    protected $read = false;

    /**
     * @param int $id
     *
     * @return null|Notification
     */
    public static function getById(int $id): ?Notification
    {
        $cacheKey = sprintf('notification_%d', $id);

        try {
            $notification = Cache\Runtime::get($cacheKey);
        } catch (\Exception $ex) {
            try {
                $notification = new self();
                $notification->getDao()->getById($id);
                Cache\Runtime::set($cacheKey, $notification);
            } catch (\Exception $e) {
                $notification = null;
            }
        }

        return $notification;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Notification
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    /**
     * @param string $creationDate
     *
     * @return Notification
     */
    public function setCreationDate(string $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModificationDate(): ?string
    {
        return $this->modificationDate;
    }

    /**
     * @param string $modificationDate
     *
     * @return Notification
     */
    public function setModificationDate(string $modificationDate): self
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return null|User
     */
    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * @param null|User $sender
     *
     * @return Notification
     */
    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return null|User
     */
    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    /**
     * @param null|User $recipient
     *
     * @return Notification
     */
    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     *
     * @return Notification
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     *
     * @return Notification
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param null|string $message
     *
     * @return Notification
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return null|Element\ElementInterface
     */
    public function getLinkedElement(): ?Element\ElementInterface
    {
        return $this->linkedElement;
    }

    /**
     * @param null|Element\ElementInterface $linkedElement
     *
     * @return Notification
     */
    public function setLinkedElement(?Element\ElementInterface $linkedElement): self
    {
        $this->linkedElement = $linkedElement;
        $this->linkedElementType = Element\Service::getElementType($linkedElement);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLinkedElementType(): ?string
    {
        return $this->linkedElementType;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @param bool $read
     *
     * @return Notification
     */
    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    /**
     * Save notification
     */
    public function save(): void
    {
        \Pimcore::getEventDispatcher()->dispatch(NotificationEvents::PRE_SAVE, new NotificationEvent($this));
        $this->getDao()->save();
        \Pimcore::getEventDispatcher()->dispatch(NotificationEvents::POST_SAVE, new NotificationEvent($this));
    }

    /**
     * Delete notification
     */
    public function delete(): void
    {
        \Pimcore::getEventDispatcher()->dispatch(NotificationEvents::PRE_DELETE, new NotificationEvent($this));
        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(NotificationEvents::POST_DELETE, new NotificationEvent($this));
    }
}
