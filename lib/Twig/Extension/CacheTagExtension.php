<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Cache;
use Pimcore\Tool;
use Pimcore\Twig\TokenParser\CacheParser;
use Pimcore\ValueObject\Collection\ArrayOfStrings;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use ValueError;
use function is_array;
use function is_int;
use function is_null;
use function is_scalar;

/**
 * @internal
 */
class CacheTagExtension extends AbstractExtension
{
    private const CACHE_KEY_PREFIX = 'pimcore_twigcache_';

    private const HASHED_KEY_PREFIX = 'hashed_';

    // characters the cache (PSR-6) does not allow in keys and tags
    private const RESERVED_CHARACTERS = '{}()/\\@:';

    public function getTokenParsers(): array
    {
        return [
            new CacheParser(),
        ];
    }

    public function normalizeKey(mixed $key): string
    {
        // objects are rejected: casting them would run __toString() without the Twig sandbox check
        if (!is_scalar($key)) {
            throw new RuntimeError('The cache key of "pimcorecache" must be a string.');
        }

        $key = (string) $key;
        if ($key === '') {
            throw new RuntimeError('The cache key of "pimcorecache" must not be empty.');
        }

        // a key built from request data (e.g. a path) may contain characters the cache rejects
        // keys starting with the generated prefix are hashed too, so they cannot collide with a hashed key
        if (strpbrk($key, self::RESERVED_CHARACTERS) !== false || str_starts_with($key, self::HASHED_KEY_PREFIX)) {
            return self::HASHED_KEY_PREFIX . sha1($key);
        }

        return $key;
    }

    /**
     * @return string[]
     */
    public function normalizeTags(mixed $tags): array
    {
        try {
            $tags = (new ArrayOfStrings(is_array($tags) ? array_values($tags) : [$tags]))->getValue();
        } catch (ValueError) {
            throw new RuntimeError('The "tags" modifier of "pimcorecache" requires a string or an array of strings.');
        }

        foreach ($tags as $tag) {
            if ($tag === '' || strpbrk($tag, self::RESERVED_CHARACTERS) !== false) {
                throw new RuntimeError(sprintf(
                    'The tag "%s" of "pimcorecache" is empty or contains one of the characters "%s".',
                    $tag,
                    self::RESERVED_CHARACTERS
                ));
            }
        }

        return $tags;
    }

    public function normalizeTtl(mixed $ttl): ?int
    {
        if ($ttl !== null && (!is_int($ttl) || $ttl < 0)) {
            throw new RuntimeError('The "ttl" modifier of "pimcorecache" requires a non-negative integer or null.');
        }

        return $ttl;
    }

    public function normalizeForce(mixed $force): bool
    {
        if (!is_bool($force)) {
            throw new RuntimeError('The "force" modifier of "pimcorecache" requires a boolean.');
        }

        return $force;
    }

    public function getContentFromCache(string $key, bool $force): string|bool
    {

        if ($this->isCacheEnabled($force)) {
            return Cache::load(self::CACHE_KEY_PREFIX . $key);
        }

        return false;
    }

    public function startBuffering(): void
    {
        ob_start();
    }

    public function endBuffering(string $key, array $tags, ?int $ttl, bool $force): string
    {
        $content = ob_get_contents();
        ob_end_clean();

        if ($this->isCacheEnabled($force)) {
            $tags[] = 'in_template';
            if (is_null($ttl)) {
                $tags[] = 'output';
            }
            $tags = array_unique($tags);
            Cache::save($content, self::CACHE_KEY_PREFIX . $key, $tags, $ttl, 996, true);
        }

        return $content;
    }

    private function isCacheEnabled(bool $force): bool
    {
        return !Tool::isFrontendRequestByAdmin() || $force;
    }
}
