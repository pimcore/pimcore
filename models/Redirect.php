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

namespace Pimcore\Model;

use Pimcore\Config;
use Pimcore\Event\Model\RedirectEvent;
use Pimcore\Event\RedirectEvents;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Model\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pimcore\Model\Redirect\Dao getDao()
 */
final class Redirect extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    const TYPE_ENTIRE_URI = 'entire_uri';

    const TYPE_PATH_QUERY = 'path_query';

    const TYPE_PATH = 'path';

    const TYPE_AUTO_CREATE = 'auto_create';

    const TYPES = [
        self::TYPE_ENTIRE_URI,
        self::TYPE_PATH_QUERY,
        self::TYPE_PATH,
        self::TYPE_AUTO_CREATE,
    ];

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var string
     */
    protected string $source;

    /**
     * @var int|null
     */
    protected ?int $sourceSite;

    /**
     * @var bool
     */
    protected bool $passThroughParameters = false;

    /**
     * @var string
     */
    protected string $target;

    /**
     * @var int|null
     */
    protected ?int $targetSite;

    /**
     * @var int
     */
    protected int $statusCode = 301;

    /**
     * @var int
     */
    protected int $priority = 1;

    /**
     * @var bool|null
     */
    protected ?bool $regex;

    /**
     * @var bool
     */
    protected bool $active = true;

    /**
     * @var int|null
     */
    protected ?int $expiry;

    /**
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @var int|null
     */
    protected ?int $modificationDate;

    /**
     * ID of the owner user
     */
    protected ?int $userOwner = null;

    /**
     * ID of the user who make the latest changes
     *
     * @var int|null
     */
    protected ?int $userModification;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById(int $id): ?Redirect
    {
        try {
            $redirect = new self();
            $redirect->getDao()->getById($id);

            return $redirect;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @internal
     *
     * @param Request $request
     * @param Site|null $site
     * @param bool $override
     *
     * @return self|null
     */
    public static function getByExactMatch(Request $request, ?Site $site = null, bool $override = false): ?self
    {
        try {
            $redirect = new self();
            $redirect->getDao()->getByExactMatch($request, $site, $override);

            return $redirect;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Redirect
     */
    public static function create(): Redirect
    {
        $redirect = new self();
        $redirect->save();

        return $redirect;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
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
     * enum('entire_uri','path_query','path','auto_create')
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * enum('entire_uri','path_query','path','auto_create')
     *
     * @param string $type
     */
    public function setType(string $type)
    {
        if (!empty($type) && !in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s"', $type));
        }

        $this->type = $type;
    }

    /**
     * @param string $source
     *
     * @return $this
     */
    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setTarget(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority(int $priority): static
    {
        if ($priority) {
            $this->priority = $priority;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode(int $statusCode): static
    {
        if ($statusCode) {
            $this->statusCode = $statusCode;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getHttpStatus(): string
    {
        $statusCode = $this->getStatusCode();
        if (empty($statusCode)) {
            $statusCode = '301';
        }

        return 'HTTP/1.1 ' . $statusCode . ' ' . $this->getStatusCodes()[$statusCode];
    }

    public function clearDependentCache()
    {
        // this is mostly called in Redirect\Dao not here
        try {
            \Pimcore\Cache::clearTag('redirect');
        } catch (\Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * @param int|string|null $expiry
     *
     * @return $this
     */
    public function setExpiry(int|string|null $expiry): static
    {
        if (is_string($expiry) && !is_numeric($expiry)) {
            $expiry = strtotime($expiry);
        }
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getExpiry(): ?int
    {
        return $this->expiry;
    }

    /**
     * @return bool|null
     */
    public function getRegex(): ?bool
    {
        return $this->regex;
    }

    public function isRegex(): bool
    {
        return (bool)$this->regex;
    }

    /**
     * @param bool|null $regex
     *
     * @return $this
     */
    public function setRegex(?bool $regex): static
    {
        $this->regex = $regex ? (bool) $regex : null;

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
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * @param int $sourceSite
     *
     * @return $this
     */
    public function setSourceSite(int $sourceSite): static
    {
        if ($sourceSite) {
            $this->sourceSite = (int) $sourceSite;
        } else {
            $this->sourceSite = null;
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSourceSite(): ?int
    {
        return $this->sourceSite;
    }

    /**
     * @param int $targetSite
     *
     * @return $this
     */
    public function setTargetSite(int $targetSite): static
    {
        if ($targetSite) {
            $this->targetSite = (int) $targetSite;
        } else {
            $this->targetSite = null;
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTargetSite(): ?int
    {
        return $this->targetSite;
    }

    /**
     * @param bool $passThroughParameters
     *
     * @return Redirect
     */
    public function setPassThroughParameters(bool $passThroughParameters): static
    {
        $this->passThroughParameters = (bool) $passThroughParameters;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPassThroughParameters(): bool
    {
        return $this->passThroughParameters;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function setUserOwner(?int $userOwner)
    {
        $this->userOwner = $userOwner;
    }

    /**
     * @return int|null
     */
    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(int $userModification)
    {
        $this->userModification = $userModification;
    }

    public function save()
    {
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::PRE_SAVE);
        $this->getDao()->save();
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::POST_SAVE);
        $this->clearDependentCache();
    }

    public function delete()
    {
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::PRE_DELETE);
        $this->getDao()->delete();
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::POST_DELETE);
        $this->clearDependentCache();
    }

    /**
     * @return string[]
     */
    public static function getStatusCodes(): array
    {
        return Config::getSystemConfiguration('redirects')['status_codes'];
    }
}
