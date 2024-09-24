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

namespace Pimcore\Bundle\SeoBundle\Model;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\SeoBundle\Event\Model\RedirectEvent;
use Pimcore\Bundle\SeoBundle\Event\RedirectEvents;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Document;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pimcore\Bundle\SeoBundle\Model\Redirect\Dao getDao()
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

    protected ?int $id = null;

    protected string $type;

    protected ?string $source = null;

    protected ?int $sourceSite = null;

    protected bool $passThroughParameters = false;

    protected ?string $target = null;

    protected ?int $targetSite = null;

    protected int $statusCode = 301;

    protected int $priority = 1;

    protected ?bool $regex = null;

    protected bool $active = true;

    protected int|string|null $expiry = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * ID of the owner user
     */
    protected ?int $userOwner = null;

    /**
     * ID of the user who make the latest changes
     *
     */
    protected ?int $userModification = null;

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
     *
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

    public static function create(): Redirect
    {
        $redirect = new self();
        $redirect->save();

        return $redirect;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Target as string, can be target path or document id
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * resolved target path with handling for document ids as `target`
     *   - tries to resolve the target as document by id and take its full path
     *   - if no document can be found the target is used as target path
     *   - ensures a slash at the beginning of the target string
     */
    public function getTargetPath(): string
    {
        $redirectTarget = $this->target;
        $targetDocumentPath = Document::getById($this->target)?->getFullPath();

        $resolvedPath = ($targetDocumentPath ?? $redirectTarget) ?? '';

        if (!str_starts_with($resolvedPath, '/')) {
            return '/'.$resolvedPath;
        }

        return $resolvedPath;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * enum('entire_uri','path_query','path','auto_create')
     *
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * enum('entire_uri','path_query','path','auto_create')
     *
     */
    public function setType(string $type): void
    {
        if (!empty($type) && !in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(sprintf('Invalid type "%s"', $type));
        }

        $this->type = $type;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function setTarget(?string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function setPriority(int $priority): static
    {
        if ($priority) {
            $this->priority = $priority;
        }

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setStatusCode(int $statusCode): static
    {
        if ($statusCode) {
            $this->statusCode = $statusCode;
        }

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHttpStatus(): string
    {
        $statusCode = $this->getStatusCode();
        if (empty($statusCode)) {
            $statusCode = '301';
        }

        return 'HTTP/1.1 ' . $statusCode . ' ' . $this->getStatusCodes()[$statusCode];
    }

    public function clearDependentCache(): void
    {
        // this is mostly called in Redirect\Dao not here
        try {
            \Pimcore\Cache::clearTag('redirect');
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    public function setExpiry(int|string|null $expiry): static
    {
        if (is_string($expiry) && !is_numeric($expiry)) {
            $expiry = strtotime($expiry);
        }
        $this->expiry = $expiry;

        return $this;
    }

    public function getExpiry(): ?int
    {
        return $this->expiry;
    }

    public function getRegex(): ?bool
    {
        return $this->regex;
    }

    public function isRegex(): bool
    {
        return (bool)$this->regex;
    }

    public function setRegex(?bool $regex): static
    {
        $this->regex = $regex;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function setSourceSite(?int $sourceSite): static
    {
        $this->sourceSite = $sourceSite;

        return $this;
    }

    public function getSourceSite(): ?int
    {
        return $this->sourceSite;
    }

    public function setTargetSite(?int $targetSite): static
    {
        $this->targetSite = $targetSite;

        return $this;
    }

    public function getTargetSite(): ?int
    {
        return $this->targetSite;
    }

    public function setPassThroughParameters(bool $passThroughParameters): static
    {
        $this->passThroughParameters = $passThroughParameters;

        return $this;
    }

    public function getPassThroughParameters(): bool
    {
        return $this->passThroughParameters;
    }

    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function setUserOwner(?int $userOwner): void
    {
        $this->userOwner = $userOwner;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(int $userModification): void
    {
        $this->userModification = $userModification;
    }

    public function save(): void
    {
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::PRE_SAVE);
        $this->getDao()->save();
        $this->dispatchEvent(new RedirectEvent($this), RedirectEvents::POST_SAVE);
        $this->clearDependentCache();
    }

    public function delete(): void
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
        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');

        return $pimcore_seo_redirects['status_codes'];
    }
}
