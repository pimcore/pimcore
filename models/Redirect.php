<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Event\Model\RedirectEvent;
use Pimcore\Event\RedirectEvents;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pimcore\Model\Redirect\Dao getDao()
 */
final class Redirect extends AbstractModel
{
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
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var int|null
     */
    protected $sourceSite;

    /**
     * @var bool
     */
    protected $passThroughParameters = false;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var int|null
     */
    protected $targetSite;

    /**
     * @var int
     */
    protected $statusCode = 301;

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * @var bool|null
     */
    protected $regex;

    /**
     * @var bool
     */
    protected $active = true;

    /**
     * @var int
     */
    protected $expiry;

    /**
     * @var int
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $modificationDate;

    /**
     * ID of the owner user
     *
     * @var int|null
     */
    protected ?int $userOwner = null;

    /**
     * ID of the user who make the latest changes
     *
     * @var int
     */
    protected $userModification;

    /**
     * StatusCodes
     */
    protected static $statusCodes = [
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '307' => 'Temporary Redirect',
    ];

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $redirect = new self();
            $redirect->getDao()->getById($id);

            return $redirect;
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return Redirect
     */
    public static function create()
    {
        $redirect = new self();
        $redirect->save();

        return $redirect;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
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
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        if ($priority) {
            $this->priority = $priority;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        if ($statusCode) {
            $this->statusCode = $statusCode;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getHttpStatus()
    {
        $statusCode = $this->getStatusCode();
        if (empty($statusCode)) {
            $statusCode = '301';
        }

        return 'HTTP/1.1 ' . $statusCode . ' ' . self::$statusCodes[$statusCode];
    }

    public function clearDependentCache()
    {

        // this is mostly called in Redirect\Dao not here
        try {
            \Pimcore\Cache::clearTag('redirect');
        } catch (\Exception $e) {
            Logger::crit($e);
        }
    }

    /**
     * @param int|string $expiry
     *
     * @return $this
     */
    public function setExpiry($expiry)
    {
        if (is_string($expiry) && !is_numeric($expiry)) {
            $expiry = strtotime($expiry);
        }
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @return bool
     */
    public function getRegex()
    {
        return $this->regex;
    }

    public function isRegex(): bool
    {
        return (bool)$this->regex;
    }

    /**
     * @param bool $regex
     *
     * @return $this
     */
    public function setRegex($regex)
    {
        if ($regex) {
            $this->regex = (bool) $regex;
        } else {
            $this->regex = null;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * @param int $sourceSite
     *
     * @return $this
     */
    public function setSourceSite($sourceSite)
    {
        if ($sourceSite) {
            $this->sourceSite = (int) $sourceSite;
        } else {
            $this->sourceSite = null;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceSite()
    {
        return $this->sourceSite;
    }

    /**
     * @param int $targetSite
     *
     * @return $this
     */
    public function setTargetSite($targetSite)
    {
        if ($targetSite) {
            $this->targetSite = (int) $targetSite;
        } else {
            $this->targetSite = null;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTargetSite()
    {
        return $this->targetSite;
    }

    /**
     * @param bool $passThroughParameters
     *
     * @return Redirect
     */
    public function setPassThroughParameters($passThroughParameters)
    {
        $this->passThroughParameters = (bool) $passThroughParameters;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPassThroughParameters()
    {
        return $this->passThroughParameters;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int|null
     */
    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    /**
     * @param int|null $userOwner
     */
    public function setUserOwner(?int $userOwner)
    {
        $this->userOwner = $userOwner;
    }

    /**
     * @return int
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param int $userModification
     */
    public function setUserModification($userModification)
    {
        $this->userModification = $userModification;
    }

    public function save()
    {
        \Pimcore::getEventDispatcher()->dispatch(new RedirectEvent($this), RedirectEvents::PRE_SAVE);
        $this->getDao()->save();
        \Pimcore::getEventDispatcher()->dispatch(new RedirectEvent($this), RedirectEvents::POST_SAVE);
        $this->clearDependentCache();
    }

    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new RedirectEvent($this), RedirectEvents::PRE_DELETE);
        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(new RedirectEvent($this), RedirectEvents::POST_DELETE);
        $this->clearDependentCache();
    }

    /**
     * @return string[]
     */
    public static function getStatusCodes(): array
    {
        return self::$statusCodes;
    }
}
