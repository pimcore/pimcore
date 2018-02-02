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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemInterface;
use Pimcore\Cache\Pool\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class CacheItem implements PimcoreCacheItemInterface
{
    protected $key;
    protected $value;
    protected $isHit;
    protected $expiry;
    protected $defaultLifetime;
    protected $previousTags = [];
    protected $tags = [];

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $isHit
     * @param int|null $defaultLifetime
     * @param array $previousTags
     */
    public function __construct($key, $value, $isHit = false, array $previousTags = [], $defaultLifetime = null)
    {
        $this->key             = $key;
        $this->value           = $value;
        $this->isHit           = $isHit;
        $this->defaultLifetime = $defaultLifetime;
        $this->previousTags    = $previousTags;
        $this->tags            = $previousTags;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->value = $value;

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
     * @return int|null
     */
    public function getDefaultLifetime()
    {
        return $this->defaultLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        if (null === $expiration) {
            $this->expiry = $this->defaultLifetime > 0 ? time() + $this->defaultLifetime : null;
        } elseif ($expiration instanceof \DateTimeInterface) {
            $this->expiry = (int)$expiration->format('U');
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must implement DateTimeInterface or be null, "%s" given', is_object($expiration) ? get_class($expiration) : gettype($expiration)));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        if (null === $time) {
            $this->expiry = $this->defaultLifetime > 0 ? time() + $this->defaultLifetime : null;
        } elseif ($time instanceof \DateInterval) {
            $this->expiry = (int)\DateTime::createFromFormat('U', time())->add($time)->format('U');
        } elseif (is_int($time)) {
            $this->expiry = $time + time();
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given', is_object($time) ? get_class($time) : gettype($time)));
        }

        return $this;
    }

    /**
     * Get all existing tags. These are the tags the item has when the item is
     * returned from the pool.
     *
     * @return array
     */
    public function getPreviousTags()
    {
        return $this->previousTags;
    }

    /**
     * Overwrite all tags with a new set of tags.
     *
     * @param string[] $tags An array of tags
     *
     * @throws \Psr\Cache\InvalidArgumentException When a tag is not valid.
     *
     * @return TaggableCacheItemInterface
     */
    public function setTags(array $tags)
    {
        $this->tags = [];

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidArgumentException(sprintf('Cache tag must be string, "%s" given', is_object($tag) ? get_class($tag) : gettype($tag)));
            }

            if (isset($this->tags[$tag])) {
                continue;
            }

            if (!isset($tag[0])) {
                throw new InvalidArgumentException('Cache tag length must be greater than zero');
            }

            if (isset($tag[strcspn($tag, '{}()/\@:')])) {
                throw new InvalidArgumentException(sprintf('Cache tag "%s" contains reserved characters {}()/\@:', $tag));
            }

            $this->tags[$tag] = $tag;
        }

        return $this;
    }

    /**
     * Merge tags into currently set tags
     *
     * @param array $tags
     *
     * @return TaggableCacheItemInterface
     */
    public function mergeTags(array $tags)
    {
        $tags = array_merge($this->tags, $tags);

        return $this->setTags($tags);
    }

    /**
     * Get currently set tags
     *
     * @return array
     */
    public function getTags()
    {
        $tags = array_values($this->tags);
        $tags = array_unique($tags);

        return $tags;
    }

    /**
     * Validates a cache key according to PSR-6.
     *
     * @param string $key The key to validate
     *
     * @throws InvalidArgumentException When $key is not valid.
     */
    public static function validateKey($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given', is_object($key) ? get_class($key) : gettype($key)));
        }
        if (!isset($key[0])) {
            throw new InvalidArgumentException('Cache key length must be greater than zero');
        }
        if (isset($key[strcspn($key, '{}()/\@:')])) {
            throw new InvalidArgumentException(sprintf('Cache key "%s" contains reserved characters {}()/\@:', $key));
        }
    }

    /**
     * Internal logging helper.
     *
     * @internal
     *
     * @param LoggerInterface $logger
     * @param $message
     * @param array $context
     */
    public static function log(LoggerInterface $logger = null, $message, $context = [])
    {
        if ($logger) {
            $logger->warning($message, $context);
        } else {
            $replace = [];
            foreach ($context as $k => $v) {
                if (is_scalar($v)) {
                    $replace['{' . $k . '}'] = $v;
                }
            }
            @trigger_error(strtr($message, $replace), E_USER_WARNING);
        }
    }
}
