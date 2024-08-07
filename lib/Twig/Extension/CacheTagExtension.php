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

namespace Pimcore\Twig\Extension;

use Pimcore\Cache;
use Pimcore\Tool;
use Pimcore\Twig\TokenParser\CacheParser;
use Twig\Extension\AbstractExtension;
use function is_null;

/**
 * @internal
 */
class CacheTagExtension extends AbstractExtension
{
    private const CACHE_KEY_PREFIX = 'pimcore_twigcache_';

    public function getTokenParsers(): array
    {
        return [
            new CacheParser(),
        ];
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
